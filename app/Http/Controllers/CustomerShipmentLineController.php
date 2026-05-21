<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerShipmentLineController extends Controller
{
    /**
     * Check if adding qty to a SO line would exceed the ordered qty.
     * Returns null if OK, or an error message string if exceeded.
     */
    private function validateSOLineQty(int $cOrderlineId, float $newQty, ?int $excludeLineId = null): ?string
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');

        $orderLine = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_orderline')
            ->where('c_orderline_id', $cOrderlineId)
            ->first(['qtyordered', 'm_product_id']);

        if (!$orderLine) {
            return null; // SO line not found, skip validation
        }

        $query = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('m_inoutline as iol')
            ->join('m_inout as io', 'io.m_inout_id', '=', 'iol.m_inout_id')
            ->where('iol.c_orderline_id', $cOrderlineId)
            ->whereIn('io.docstatus', $customerShipmentConfig['statuses']['delivery_progress']);

        if ($excludeLineId) {
            $query->where('iol.m_inoutline_id', '!=', $excludeLineId);
        }

        $totalShipped = (float) $query->sum('iol.movementqty');

        $remaining = (float) $orderLine->qtyordered - $totalShipped;

        if ($newQty > $remaining) {
            // Get product name for better message
            $product = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_product')
                ->where('m_product_id', $orderLine->m_product_id)
                ->first(['name']);

            $productName = $product->name ?? 'Unknown';

            return "Qty {$newQty} exceeds remaining SO qty for {$productName}. Ordered: {$orderLine->qtyordered}, Already shipped: {$totalShipped}, Remaining: {$remaining}";
        }

        return null;
    }

    public function create(Request $request)
    {
        $documentId = $request->query('document_id');
        if (!$documentId) {
            return redirect()->route('customer-shipment.index')->with('error', 'Document ID required.');
        }

        try {
            // Decrypt ID
            $shipmentId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);

            // Fetch Header Info for Context
            $shipment = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_inout')
                ->where('m_inout_id', $shipmentId)
                ->first();

            if (!$shipment) {
                return redirect()->route('customer-shipment.index')->with('error', 'Customer Shipment not found.');
            }

        } catch (\Exception $e) {
            return redirect()->route('customer-shipment.index')->with('error', 'Invalid Document ID.');
        }

        // Return View with Encrypted ID
        return view('pages.customer-shipment.line_form', [
            'document_id' => $documentId,
            'shipment' => $shipment,
            'title' => 'Create Customer Shipment Line'
        ]);
    }

    public function storeFromSO(Request $request)
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');
        $baseUrl = rtrim(config('idempiere.api.base_url'), '/');

        $validated = $request->validate([
            'document_id' => 'required',
            'lines' => 'required|array|min:1',
            'lines.*.c_orderline_id' => 'required|integer',
            'lines.*.m_product_id' => 'required|integer',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.description' => 'nullable|string',
        ]);

        $documentId = $validated['document_id'];

        try {
            $shipmentId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);

            $token = \Illuminate\Support\Facades\Session::get('api_token');
            if (!$token) {
                return response()->json(['message' => 'Authentication required'], 401);
            }

            // Get shipment header info
            $shipment = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inout')
                ->where('m_inout_id', $shipmentId)
                ->first(['ad_org_id', 'm_warehouse_id']);

            if (!$shipment) {
                return response()->json(['message' => 'Shipment not found'], 404);
            }

            // Get default locator
            $locatorId = null;
            if ($shipment->m_warehouse_id) {
                $locator = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_locator')
                    ->where('m_warehouse_id', $shipment->m_warehouse_id)
                    ->where('isdefault', 'Y')
                    ->where('isactive', 'Y')
                    ->first(['m_locator_id']);

                if (!$locator) {
                    $locator = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('m_locator')
                        ->where('m_warehouse_id', $shipment->m_warehouse_id)
                        ->where('isactive', 'Y')
                        ->first(['m_locator_id']);
                }

                if ($locator) {
                    $locatorId = $locator->m_locator_id;
                }
            }

            if (!$locatorId) {
                return response()->json(['message' => 'No active locator found for warehouse'], 400);
            }

            // Get current max line number
            $maxLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inoutline')
                ->where('m_inout_id', $shipmentId)
                ->max('line') ?? 0;

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($validated['lines'] as $lineData) {
                // Validate SO line qty
                $soError = $this->validateSOLineQty(
                    (int) $lineData['c_orderline_id'],
                    (float) $lineData['qty']
                );
                if ($soError) {
                    $errors[] = $soError;
                    $failed++;
                    continue;
                }

                $maxLine += $customerShipmentConfig['limits']['line_increment'];

                $payload = [
                    'M_InOutLine' => [
                        [
                            'AD_Org_ID' => (int) $shipment->ad_org_id,
                            'M_Product_ID' => (int) $lineData['m_product_id'],
                            'MovementQty' => (float) $lineData['qty'],
                            'QtyEntered' => (float) $lineData['qty'],
                            'Description' => $lineData['description'] ?? '',
                            'Line' => $maxLine,
                            'M_Locator_ID' => (int) $locatorId,
                            'C_OrderLine_ID' => (int) $lineData['c_orderline_id'],
                        ]
                    ]
                ];

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($shipmentId, $payload, $baseUrl) {
                    return \Illuminate\Support\Facades\Http::withToken($t)
                        ->put($baseUrl . '/models/m_inout/' . $shipmentId, $payload);
                });

                if ($response->successful()) {
                    $imported++;
                } else {
                    $errorMsg = $response->json()['title'] ?? $response->json()['message'] ?? 'Unknown error';
                    $errors[] = $errorMsg;
                    $failed++;
                }
            }

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
                'message' => $imported > 0 ? "{$imported} line(s) created successfully" . ($failed > 0 ? ", {$failed} failed" : '') : 'No lines created',
            ]);

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['message' => 'Invalid Document ID'], 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create lines from SO: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create lines: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');
        $baseUrl = rtrim(config('idempiere.api.base_url'), '/');

        // Strip thousand-separator commas from numeric fields before validation
        $request->merge([
            'qty' => str_replace(',', '', $request->input('qty', '')),
        ]);

        // Validation
        $validated = $request->validate([
            'document_id' => 'required',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $documentId = $request->input('document_id');

        try {
            $shipmentId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);

            // Get session data for API call
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            // Get organization and warehouse ID from m_inout using database query
            $shipment = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inout')
                ->where('m_inout_id', $shipmentId)
                ->first(['ad_org_id', 'm_warehouse_id']);

            $orgId = null;
            $warehouseId = null;
            if ($shipment) {
                $orgId = $shipment->ad_org_id;
                $warehouseId = $shipment->m_warehouse_id;
            }

            // Get max line number from existing lines
            $linesResponse = \App\Services\IdempiereService::withAutoRetry(function($t) use ($shipmentId, $baseUrl) {
                return \Illuminate\Support\Facades\Http::withToken($t)
                    ->get($baseUrl . '/models/m_inout/' . $shipmentId);
            });

            $maxLine = 0;

            if ($linesResponse->successful()) {
                $shipmentData = $linesResponse->json();

                // Get max line number
                if (isset($shipmentData['M_InOutLine']) && is_array($shipmentData['M_InOutLine'])) {
                    foreach ($shipmentData['M_InOutLine'] as $line) {
                        if (isset($line['Line']) && $line['Line'] > $maxLine) {
                            $maxLine = $line['Line'];
                        }
                    }
                }
            }

            if (!$orgId) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Organization ID not found in shipment'], 400);
                }
                return back()->with('error', 'Organization ID not found in shipment');
            }

            // Get default locator for warehouse
            $locatorId = null;
            if ($warehouseId) {
                $locator = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_locator')
                    ->where('m_warehouse_id', $warehouseId)
                    ->where('isdefault', 'Y')
                    ->where('isactive', 'Y')
                    ->first(['m_locator_id']);

                if ($locator) {
                    $locatorId = $locator->m_locator_id;
                } else {
                    // Get any active locator if no default found
                    $locator = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('m_locator')
                        ->where('m_warehouse_id', $warehouseId)
                        ->where('isactive', 'Y')
                        ->first(['m_locator_id']);

                    if ($locator) {
                        $locatorId = $locator->m_locator_id;
                    }
                }
            }

            if (!$locatorId) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'No active locator found for warehouse'], 400);
                }
                return back()->with('error', 'No active locator found for warehouse');
            }

            // Validate SO line qty if c_orderline_id is provided
            $cOrderlineId = $request->input('c_orderline_id');
            if ($cOrderlineId) {
                $soError = $this->validateSOLineQty((int) $cOrderlineId, (float) $validated['qty']);
                if ($soError) {
                    if ($request->wantsJson()) {
                        return response()->json(['message' => $soError], 422);
                    }
                    return back()->with('error', $soError);
                }
            }

            $nextLine = $maxLine + $customerShipmentConfig['limits']['line_increment'];

            // Prepare payload for iDempiere API
            $linePayload = [
                'AD_Org_ID' => (int) $orgId,
                'M_Product_ID' => (int) $validated['m_product_id'],
                'MovementQty' => (float) $validated['qty'],
                'QtyEntered' => (float) $validated['qty'],
                'Description' => $validated['description'] ?? '',
                'Line' => $nextLine,
                'M_Locator_ID' => (int) $locatorId,
            ];

            if ($cOrderlineId) {
                $linePayload['C_OrderLine_ID'] = (int) $cOrderlineId;
            }

            $payload = [
                'M_InOutLine' => [$linePayload]
            ];

            \Illuminate\Support\Facades\Log::info('Creating shipment line', [
                'm_inout_id' => $shipmentId,
                'payload' => $payload
            ]);

            $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($shipmentId, $payload, $baseUrl) {
                return \Illuminate\Support\Facades\Http::withToken($t)
                    ->put($baseUrl . '/models/m_inout/' . $shipmentId, $payload);
            });

            if (!$response->successful()) {
                $responseBody = $response->json();
                $errorMsg = 'Failed to create line';
                if (isset($responseBody['title'])) {
                    $errorMsg = $responseBody['title'];
                } elseif (isset($responseBody['message'])) {
                    $errorMsg = $responseBody['message'];
                } elseif (isset($responseBody['error'])) {
                    $errorMsg = $responseBody['error'];
                }

                \Illuminate\Support\Facades\Log::error('Failed to create shipment line', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => $errorMsg,
                        'details' => $responseBody
                    ], $response->status());
                }
                return back()->with('error', $errorMsg);
            }

            $responseData = $response->json();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Line created successfully.',
                    'data' => $responseData
                ]);
            }

            return redirect()->route('customer-shipment.index', ['document_id' => $documentId, 'tab' => 'lines'])
                ->with('success', 'Line created successfully.');

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid Document ID'], 400);
            }
            return back()->with('error', 'Invalid Document ID');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save shipment line: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to save line: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save line: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $baseUrl = rtrim(config('idempiere.api.base_url'), '/');

        // Strip thousand-separator commas from numeric fields before validation
        $request->merge([
            'qty' => str_replace(',', '', $request->input('qty', '')),
        ]);

        // Validation
        $validated = $request->validate([
            'line_id' => 'required',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        try {
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            // Get shipment line info from database
            $shipmentLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inoutline')
                ->where('m_inoutline_id', $validated['line_id'])
                ->first(['m_inout_id', 'm_locator_id', 'c_orderline_id']);

            if (!$shipmentLine) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Shipment line not found'], 404);
                }
                return back()->with('error', 'Shipment line not found');
            }

            // Validate SO line qty if linked to SO
            if ($shipmentLine->c_orderline_id) {
                $soError = $this->validateSOLineQty(
                    (int) $shipmentLine->c_orderline_id,
                    (float) $validated['qty'],
                    (int) $validated['line_id']
                );
                if ($soError) {
                    if ($request->wantsJson()) {
                        return response()->json(['message' => $soError], 422);
                    }
                    return back()->with('error', $soError);
                }
            }

            $payload = [
                'M_Product_ID' => (int) $validated['m_product_id'],
                'MovementQty' => (float) $validated['qty'],
                'QtyEntered' => (float) $validated['qty'],
                'Description' => $validated['description'] ?? '',
                'M_Locator_ID' => (int) $shipmentLine->m_locator_id,
            ];

            \Illuminate\Support\Facades\Log::info('Updating shipment line', [
                'line_id' => $validated['line_id'],
                'payload' => $payload
            ]);

            $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($validated, $payload, $baseUrl) {
                return \Illuminate\Support\Facades\Http::withToken($t)
                    ->put($baseUrl . '/models/m_inoutline/' . $validated['line_id'], $payload);
            });

            if (!$response->successful()) {
                $responseBody = $response->json();
                $errorMsg = 'Failed to update line';
                if (isset($responseBody['title'])) {
                    $errorMsg = $responseBody['title'];
                } elseif (isset($responseBody['message'])) {
                    $errorMsg = $responseBody['message'];
                } elseif (isset($responseBody['error'])) {
                    $errorMsg = $responseBody['error'];
                }

                if ($request->wantsJson()) {
                    return response()->json(['message' => $errorMsg, 'details' => $responseBody], $response->status());
                }
                return back()->with('error', $errorMsg);
            }

            $responseData = $response->json();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Line updated successfully.',
                    'data' => $responseData
                ]);
            }

            $documentId = $request->input('document_id');
            return redirect()->route('customer-shipment.index', ['document_id' => $documentId, 'tab' => 'lines'])
                ->with('success', 'Line updated successfully.');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to update line: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to update line: ' . $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        $baseUrl = rtrim(config('idempiere.api.base_url'), '/');

        $validated = $request->validate([
            'line_ids' => 'required|array',
            'line_ids.*' => 'required|integer',
        ]);

        try {
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            $lineIds = $validated['line_ids'];
            $deletedCount = 0;
            $errors = [];

            foreach ($lineIds as $lineId) {
                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($lineId, $baseUrl) {
                    return \Illuminate\Support\Facades\Http::withToken($t)
                        ->delete($baseUrl . '/models/m_inoutline/' . $lineId);
                });

                if ($response->successful()) {
                    $deletedCount++;
                } else {
                    $responseBody = $response->json();
                    $errorMsg = 'Failed to delete line';
                    if (isset($responseBody['title'])) {
                        $errorMsg = $responseBody['title'];
                    } elseif (isset($responseBody['message'])) {
                        $errorMsg = $responseBody['message'];
                    }

                    $errors[] = "Line ID {$lineId}: {$errorMsg}";
                }
            }

            if ($deletedCount === count($lineIds)) {
                $message = $deletedCount === 1 ? 'Line deleted successfully' : "{$deletedCount} lines deleted successfully";
                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $message, 'deleted_count' => $deletedCount]);
                }
                return back()->with('success', $message);
            } elseif ($deletedCount > 0) {
                $message = "{$deletedCount} line(s) deleted, " . count($errors) . " failed";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message, 'deleted_count' => $deletedCount, 'errors' => $errors], 207);
                }
                return back()->with('warning', $message);
            } else {
                $message = 'Failed to delete line(s): ' . implode(', ', $errors);
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Failed to delete lines', 'errors' => $errors], 500);
                }
                return back()->with('error', $message);
            }

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to delete line(s): ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to delete line(s): ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        try {
            // Create simple Excel file using PhpSpreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers (NO PRICE COLUMN)
            $headers = ['Product Code', 'Quantity', 'Description'];
            $sheet->fromArray($headers, null, 'A1');

            // Style header row
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(40);

            // Add sample data
            $sampleData = [
                ['PROD-001', '10', 'Sample product description'],
                ['PROD-002', '5', 'Another product'],
            ];
            $sheet->fromArray($sampleData, null, 'A2');

            // Add instructions in a separate sheet
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');
            $instructions = [
                ['Customer Shipment Line Import Template'],
                [''],
                ['Instructions:'],
                ['1. Product Code: Enter the product SKU/code (required)'],
                ['2. Quantity: Enter quantity as a number (required)'],
                ['3. Description: Enter line description (optional)'],
                [''],
                ['Notes:'],
                ['- Do not modify the header row'],
                ['- Product code must exist in the system'],
                ['- Delete sample rows before importing your data'],
                ['- Locator will be automatically assigned from warehouse default'],
            ];
            $instructionSheet->fromArray($instructions, null, 'A1');
            $instructionSheet->getColumnDimension('A')->setWidth(70);

            // Set active sheet back to data sheet
            $spreadsheet->setActiveSheetIndex(0);

            // Generate file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'customer-shipment-line-template-' . date('Y-m-d') . '.xlsx';

            // Output file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Template Download Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate template: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $customerShipmentConfig = config('idempiere.customer-shipment');
        $baseUrl = rtrim(config('idempiere.api.base_url'), '/');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB
            'document_id' => 'required',
        ]);

        try {
            $documentId = $request->input('document_id');
            $shipmentId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);
            $file = $request->file('file');

            // Get token
            $token = \Illuminate\Support\Facades\Session::get('api_token');
            if (!$token) {
                return response()->json(['message' => 'Authentication required'], 401);
            }

            // Get organization and warehouse from shipment
            $shipment = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inout')
                ->where('m_inout_id', $shipmentId)
                ->first(['ad_org_id', 'm_warehouse_id']);

            if (!$shipment) {
                return response()->json(['message' => 'Shipment not found'], 404);
            }

            // Get default locator for warehouse
            $locatorId = null;
            if ($shipment->m_warehouse_id) {
                $locator = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_locator')
                    ->where('m_warehouse_id', $shipment->m_warehouse_id)
                    ->where('isdefault', 'Y')
                    ->where('isactive', 'Y')
                    ->first(['m_locator_id']);

                if ($locator) {
                    $locatorId = $locator->m_locator_id;
                } else {
                    // Get any active locator if no default found
                    $locator = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('m_locator')
                        ->where('m_warehouse_id', $shipment->m_warehouse_id)
                        ->where('isactive', 'Y')
                        ->first(['m_locator_id']);

                    if ($locator) {
                        $locatorId = $locator->m_locator_id;
                    }
                }
            }

            if (!$locatorId) {
                return response()->json(['message' => 'No active locator found for warehouse'], 400);
            }

            // Get max line number
            $maxLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_inoutline')
                ->where('m_inout_id', $shipmentId)
                ->max('line') ?? 0;

            // Read Excel file
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Remove header row
            $headers = array_shift($rows);

            $imported = 0;
            $failed = 0;
            $failedRows = []; // Store failed rows with error messages
            $lineIncrement = $customerShipmentConfig['limits']['line_increment'];
            $totalRows = 0;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because index starts at 0 and we removed header

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $totalRows++;
                $productCode = trim($row[0] ?? '');
                $qty = trim($row[1] ?? '');
                $description = trim($row[2] ?? '');
                $errorMessage = '';

                // Validate required fields
                if (empty($productCode)) {
                    $errorMessage = 'Product code is required';
                    $failedRows[] = array_merge($row, [$errorMessage]);
                    $failed++;
                    continue;
                }

                if (empty($qty) || !is_numeric($qty) || $qty <= 0) {
                    $errorMessage = 'Valid quantity is required';
                    $failedRows[] = array_merge($row, [$errorMessage]);
                    $failed++;
                    continue;
                }

                // Find product by code
                $product = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('m_product')
                    ->where('value', $productCode)
                    ->where('isactive', 'Y')
                    ->first(['m_product_id']);

                if (!$product) {
                    $errorMessage = "Product '{$productCode}' not found";
                    $failedRows[] = array_merge($row, [$errorMessage]);
                    $failed++;
                    continue;
                }

                // Increment line number
                $maxLine += $lineIncrement;

                // Create shipment line via API
                $payload = [
                    'M_InOutLine' => [
                        [
                            'AD_Org_ID' => (int) $shipment->ad_org_id,
                            'M_Product_ID' => (int) $product->m_product_id,
                            'MovementQty' => (float) $qty,
                            'QtyEntered' => (float) $qty,
                            'Description' => $description,
                            'Line' => $maxLine,
                            'M_Locator_ID' => (int) $locatorId,
                        ]
                    ]
                ];

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($shipmentId, $payload, $baseUrl) {
                    return \Illuminate\Support\Facades\Http::withToken($t)
                        ->put($baseUrl . '/models/m_inout/' . $shipmentId, $payload);
                });

                if ($response->successful()) {
                    $imported++;
                } else {
                    $errorMsg = $response->json()['message'] ?? $response->json()['title'] ?? 'Unknown error';
                    $failedRows[] = array_merge($row, [$errorMsg]);
                    $failed++;
                }
            }

            // Generate error Excel file if there are failed rows
            $errorFileUrl = null;
            if (count($failedRows) > 0) {
                $errorSpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $errorSheet = $errorSpreadsheet->getActiveSheet();

                // Set headers with Error Message column
                $errorHeaders = array_merge($headers, ['Error Message']);
                $errorSheet->fromArray($errorHeaders, null, 'A1');

                // Style header row
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EF4444']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ];
                $lastColumn = chr(65 + count($errorHeaders) - 1); // A=65, dynamic last column
                $errorSheet->getStyle("A1:{$lastColumn}1")->applyFromArray($headerStyle);

                // Add failed rows
                $errorSheet->fromArray($failedRows, null, 'A2');

                // Auto-size columns
                foreach (range('A', $lastColumn) as $col) {
                    $errorSheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Save to temporary location
                $errorFileName = 'import-errors-' . date('YmdHis') . '.xlsx';
                $errorFilePath = storage_path('app/temp/' . $errorFileName);

                // Create temp directory if not exists
                if (!file_exists(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($errorSpreadsheet);
                $writer->save($errorFilePath);

                $errorFileUrl = route('customer-shipment.line.download-errors', ['filename' => $errorFileName]);
            }

            return response()->json([
                'success' => true,
                'total_rows' => $totalRows,
                'imported' => $imported,
                'failed' => $failed,
                'error_file_url' => $errorFileUrl
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Import Error: ' . $e->getMessage());
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    public function downloadErrors($filename)
    {
        try {
            $filePath = storage_path('app/temp/' . $filename);

            if (!file_exists($filePath)) {
                abort(404, 'Error file not found');
            }

            // Delete file after download
            return response()->download($filePath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Download Error File Error: ' . $e->getMessage());
            abort(500, 'Failed to download error file');
        }
    }
}
