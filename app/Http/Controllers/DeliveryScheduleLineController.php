<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeliveryScheduleLineController extends Controller
{
    public function create(Request $request)
    {
        $documentId = $request->query('document_id');
        if (!$documentId) {
            return redirect()->route('delivery-schedule.index')->with('error', 'Document ID required.');
        }

        try {
            // Decrypt ID
            $orderId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);

            // Fetch Header Info for Context
            $deliverySchedule = \Illuminate\Support\Facades\DB::connection('idempiere')->table('c_order')
                ->where('c_order_id', $orderId)
                ->first();

            if (!$deliverySchedule) {
                return redirect()->route('delivery-schedule.index')->with('error', 'Delivery Schedule not found.');
            }

        } catch (\Exception $e) {
            return redirect()->route('delivery-schedule.index')->with('error', 'Invalid Document ID.');
        }

        // Return View with Encrypted ID
        return view('pages.delivery-schedule.line_form', [
            'document_id' => $documentId,
            'deliverySchedule' => $deliverySchedule,
            'title' => 'Create Delivery Schedule Line'
        ]);
    }

    public function store(Request $request)
    {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

        // Strip thousand-separator commas from numeric fields before validation
        $request->merge([
            'qty' => str_replace(',', '', $request->input('qty', '')),
            'price' => str_replace(',', '', $request->input('price', '')),
        ]);

        // Validation
        $validated = $request->validate([
            'document_id' => 'required',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|min:0.01',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'date_promised' => 'nullable|date',
        ]);

        // dd(!empty($validated['price']) ? (float) $validated['price'] : 0);

        $documentId = $request->input('document_id');

        try {
            $orderId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);

            // Get session data for API call
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            // Get organization and tax ID from c_order using database query
            $order = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_order')
                ->where('c_order_id', $orderId)
                ->first(['ad_org_id', 'c_tax_id']);

            $orgId = null;
            $taxId = null;
            if ($order) {
                $orgId = $order->ad_org_id;
                $taxId = $order->c_tax_id;
            }

            // Get max line number from existing lines
                $linesResponse = \App\Services\IdempiereService::withAutoRetry(function($t) use ($orderId, $baseUrl) {
                return \Illuminate\Support\Facades\Http::withToken($t)
                    ->get($baseUrl . '/models/c_order/' . $orderId);
            });

            $maxLine = 0;

            if ($linesResponse->successful()) {
                $orderData = $linesResponse->json();

                // Get max line number
                if (isset($orderData['C_OrderLine']) && is_array($orderData['C_OrderLine'])) {
                    foreach ($orderData['C_OrderLine'] as $line) {
                        if (isset($line['Line']) && $line['Line'] > $maxLine) {
                            $maxLine = $line['Line'];
                        }
                    }
                }
            }

            if (!$orgId) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Organization ID not found in order'], 400);
                }
                return back()->with('error', 'Organization ID not found in order');
            }

            $nextLine = $maxLine + $deliveryScheduleConfig['limits']['line_increment'];

            // Prepare payload for iDempiere API
            $payload = [
                'C_OrderLine' => [
                    [
                        'AD_Org_ID' => (int) $orgId,
                        'M_Product_ID' => (int) $validated['m_product_id'],
                        'QtyEntered' => (float) $validated['qty'],
                        'Description' => $validated['description'] ?? '',
                        'Line' => $nextLine,
                        'PriceEntered' => !empty($validated['price']) ? (float) $validated['price'] : 0.00,
                        'QtyOrdered' => (float) $validated['qty'],
                        'PriceActual' => !empty($validated['price']) ? (float) $validated['price'] : 0.00,
                        'PriceList' => !empty($validated['price']) ? (float) $validated['price'] : 0.00,
                        'QtyScheduled' => (float) $validated['qty'],
                        'C_Tax_ID' => $taxId ? (int) $taxId : null,
                        'DatePromised' => !empty($validated['date_promised']) ? $validated['date_promised'] : null,
                    ]
                ]
            ];

            \Illuminate\Support\Facades\Log::info('Creating order line', [
                'c_order_id' => $orderId,
                'payload' => $payload
            ]);

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($orderId, $payload, $baseUrl) {
                return \Illuminate\Support\Facades\Http::withToken($t)
                    ->put($baseUrl . '/models/c_order/' . $orderId, $payload);
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

                \Illuminate\Support\Facades\Log::error('Failed to create order line', [
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

            return redirect()->route('delivery-schedule.index', ['document_id' => $documentId, 'tab' => 'lines'])
                ->with('success', 'Line created successfully.');

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid Document ID'], 400);
            }
            return back()->with('error', 'Invalid Document ID');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save order line: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to save line: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save line: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

        // Strip thousand-separator commas from numeric fields before validation
        $request->merge([
            'qty' => str_replace(',', '', $request->input('qty', '')),
            'price' => str_replace(',', '', $request->input('price', '')),
        ]);

        // Validation
        $validated = $request->validate([
            'line_id' => 'required',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|min:0.01',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'date_promised' => 'nullable|date',
        ]);
        // dd(!empty($validated['price']) ? (float) $validated['price'] : 0);
        try {
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            // Get c_tax_id and qtydelivered from database
            $orderLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_orderline')
                ->where('c_orderline_id', $validated['line_id'])
                ->first(['c_order_id', 'qtydelivered', 'qtyscheduled', 'qtyentered', 'ref_orderline_id']);

            // Validate: qty must not be less than qtydelivered
            if ($orderLine && $orderLine->qtydelivered > 0) {
                if ((float) $validated['qty'] < (float) $orderLine->qtydelivered) {
                    $errorMsg = "Quantity cannot be less than delivered quantity ({$orderLine->qtydelivered})";
                    
                    \Illuminate\Support\Facades\Log::warning('Update rejected: qty < qtydelivered', [
                        'line_id' => $validated['line_id'],
                        'new_qty' => $validated['qty'],
                        'qtydelivered' => $orderLine->qtydelivered
                    ]);

                    if ($request->wantsJson()) {
                        return response()->json(['message' => $errorMsg], 400);
                    }
                    return back()->with('error', $errorMsg);
                }
            }

            // Validate: check against source orderline to ensure qtyscheduled doesn't exceed qtyordered
            if ($orderLine && !empty($orderLine->ref_orderline_id)) {
                $sourceLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_orderline')
                    ->where('c_orderline_id', $orderLine->ref_orderline_id)
                    ->first(['qtyordered', 'qtyscheduled']);

                if ($sourceLine) {
                    $oldQty = (float) ($orderLine->qtyscheduled ?? $orderLine->qtyentered ?? 0);
                    $newQty = (float) $validated['qty'];
                    $qtyDiff = $newQty - $oldQty;

                    $newScheduledQty = (float) $sourceLine->qtyscheduled + $qtyDiff;
                    
                    if ($newScheduledQty > (float) $sourceLine->qtyordered) {
                        $maxAllowedDiff = (float) $sourceLine->qtyordered - (float) $sourceLine->qtyscheduled;
                        $maxAllowedQty = $oldQty + $maxAllowedDiff;
                        
                        $errorMsg = "Quantity exceeds ordered quantity on the Sales Order. Maximum allowed is " . $maxAllowedQty;
                        
                        if ($request->wantsJson()) {
                            return response()->json(['message' => $errorMsg], 400);
                        }
                        return back()->with('error', $errorMsg);
                    }
                }
            }

            $taxId = null;
            if ($orderLine) {
                $order = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_order')
                    ->where('c_order_id', $orderLine->c_order_id)
                    ->first(['c_tax_id']);
                
                if ($order && $order->c_tax_id) {
                    $taxId = $order->c_tax_id;
                }
            }

            $payload = [
                'M_Product_ID' => (int) $validated['m_product_id'],
                'PriceEntered' => !empty($validated['price']) ? (float) $validated['price'] : 0,
                'QtyEntered' => (float) $validated['qty'],
                'Description' => $validated['description'] ?? '',
                'QtyOrdered' => (float) $validated['qty'],
                'PriceActual' => !empty($validated['price']) ? (float) $validated['price'] : 0,
                'PriceList' => !empty($validated['price']) ? (float) $validated['price'] : 0,
                'QtyScheduled' => (float) $validated['qty'],
                'C_Tax_ID' => $taxId ? (int) $taxId : null,
                'DatePromised' => !empty($validated['date_promised']) ? $validated['date_promised'] : null,
            ];

            \Illuminate\Support\Facades\Log::info('Updating order line', [
                'line_id' => $validated['line_id'],
                'payload' => $payload
            ]);

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($validated, $payload, $baseUrl) {
                return \Illuminate\Support\Facades\Http::withToken($t)
                    ->put($baseUrl . '/models/c_orderline/' . $validated['line_id'], $payload);
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

            // UPDATE QtyScheduled on source SO Line
            if ($orderLine && !empty($orderLine->ref_orderline_id)) {
                $oldQty = (float) ($orderLine->qtyscheduled ?? $orderLine->qtyentered ?? 0);
                $newQty = (float) $validated['qty'];
                $qtyDiff = $newQty - $oldQty;

                if ($qtyDiff != 0) {
                    $sourceLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('c_orderline')
                        ->where('c_orderline_id', $orderLine->ref_orderline_id)
                        ->first(['qtyscheduled']);
                        
                    if ($sourceLine) {
                        \Illuminate\Support\Facades\DB::connection('idempiere')
                            ->table('c_orderline')
                            ->where('c_orderline_id', $orderLine->ref_orderline_id)
                            ->update([
                                'qtyscheduled' => max(0, (float) $sourceLine->qtyscheduled + $qtyDiff)
                            ]);
                    }
                }
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Line updated successfully.',
                    'data' => $responseData
                ]);
            }

            $documentId = $request->input('document_id');
            return redirect()->route('delivery-schedule.index', ['document_id' => $documentId, 'tab' => 'lines'])
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
        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

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
                // Ambil data line sebelum dihapus untuk mengetahui Qty dan ref_orderline_id
                $lineToDelete = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_orderline')
                    ->where('c_orderline_id', $lineId)
                    ->first(['qtyscheduled', 'qtyentered', 'ref_orderline_id']);

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($lineId, $baseUrl) {
                    return \Illuminate\Support\Facades\Http::withToken($t)
                    ->delete($baseUrl . '/models/c_orderline/' . $lineId);
                });

                if ($response->successful()) {
                    $deletedCount++;
                    
                    // Kembalikan/Kurangi qtyscheduled pada line Sales Order asalnya
                    if ($lineToDelete && !empty($lineToDelete->ref_orderline_id)) {
                        $sourceLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                            ->table('c_orderline')
                            ->where('c_orderline_id', $lineToDelete->ref_orderline_id)
                            ->first(['qtyscheduled']);
                            
                        if ($sourceLine) {
                            $qtyToDeduct = (float) ($lineToDelete->qtyscheduled ?? $lineToDelete->qtyentered ?? 0);
                            
                            \Illuminate\Support\Facades\DB::connection('idempiere')
                                ->table('c_orderline')
                                ->where('c_orderline_id', $lineToDelete->ref_orderline_id)
                                ->update([
                                    'qtyscheduled' => max(0, (float) $sourceLine->qtyscheduled - $qtyToDeduct)
                                ]);
                        }
                    }
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

            // Set headers
            $headers = ['Product Code', 'Quantity', 'Price', 'Description'];
            $sheet->fromArray($headers, null, 'A1');

            // Style header row
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(40);

            // Add sample data
            $sampleData = [
                ['PROD-001', '10', '100.00', 'Sample product description'],
                ['PROD-002', '5', '250.50', 'Another product'],
            ];
            $sheet->fromArray($sampleData, null, 'A2');

            // Add instructions in a separate sheet
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');
            $instructions = [
                ['Delivery Schedule Line Import Template'],
                [''],
                ['Instructions:'],
                ['1. Product Code: Enter the product SKU/code (required)'],
                ['2. Quantity: Enter quantity as a number (required)'],
                ['3. Price: Enter price as a number (optional - will use pricelist if empty)'],
                ['4. Description: Enter line description (optional)'],
                [''],
                ['Notes:'],
                ['- Do not modify the header row'],
                ['- Product code must exist in the system'],
                ['- Delete sample rows before importing your data'],
            ];
            $instructionSheet->fromArray($instructions, null, 'A1');
            $instructionSheet->getColumnDimension('A')->setWidth(70);

            // Set active sheet back to data sheet
            $spreadsheet->setActiveSheetIndex(0);

            // Generate file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'delivery-schedule-line-template-' . date('Y-m-d') . '.xlsx';

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
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB
            'document_id' => 'required',
        ]);

        try {
            $documentId = $request->input('document_id');
            $orderId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);
            $file = $request->file('file');

            // Get token
            $token = \Illuminate\Support\Facades\Session::get('api_token');
            if (!$token) {
                return response()->json(['message' => 'Authentication required'], 401);
            }

            // Get organization and tax ID from order
            $order = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_order')
                ->where('c_order_id', $orderId)
                ->first(['ad_org_id', 'c_tax_id', 'm_pricelist_id']);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // Get max line number
            $maxLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_orderline')
                ->where('c_order_id', $orderId)
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
            $lineIncrement = $deliveryScheduleConfig['limits']['line_increment'];
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
                $price = trim($row[2] ?? '');
                $description = trim($row[3] ?? '');
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

                // Get product price from pricelist if price is empty, 0, or not provided
                $finalPrice = 0;
                if (is_numeric($price) && (float) $price > 0) {
                    $finalPrice = (float) $price;
                } else {
                    $productPrice = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('m_productprice')
                        ->where('m_product_id', $product->m_product_id)
                        ->where('m_pricelist_version_id', function ($query) use ($order) {
                            $query->select('m_pricelist_version_id')
                                ->from('m_pricelist_version')
                                ->where('m_pricelist_id', $order->m_pricelist_id)
                                ->where('isactive', 'Y')
                                ->orderBy('validfrom', 'desc')
                                ->limit(1);
                        })
                        ->where('isactive', 'Y')
                        ->first(['pricestd']);

                    $finalPrice = $productPrice ? (float) $productPrice->pricestd : 0;
                }

                // Increment line number
                $maxLine += $lineIncrement;

                // Create order line via API
                $payload = [
                    'C_OrderLine' => [
                        [
                            'AD_Org_ID' => (int) $order->ad_org_id,
                            'M_Product_ID' => (int) $product->m_product_id,
                            'QtyEntered' => (float) $qty,
                            'QtyOrdered' => (float) $qty,
                            'PriceEntered' => $finalPrice,
                            'PriceActual' => $finalPrice,
                            'PriceList' => $finalPrice,
                            'QtyScheduled' => (float) $qty,
                            'Description' => $description,
                            'Line' => $maxLine,
                            'C_Tax_ID' => $order->c_tax_id ? (int) $order->c_tax_id : null,
                        ]
                    ]
                ];

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($orderId, $payload, $baseUrl) {
                    return \Illuminate\Support\Facades\Http::withToken($t)
                    ->put($baseUrl . '/models/c_order/' . $orderId, $payload);
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

                $errorFileUrl = route('delivery-schedule.line.download-errors', ['filename' => $errorFileName]);
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

    public function storeFromSO(Request $request)
    {
        $deliveryScheduleConfig = config('idempiere.delivery-schedule');
        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

        \Illuminate\Support\Facades\Log::info('StoreFromSO payload: ', $request->all());
        $validated = $request->validate([
            'document_id' => 'required',
            'lines' => 'required|array|min:1',
            'lines.*.ref_c_orderline_id' => 'nullable|integer',
            'lines.*.ref_c_order_id' => 'nullable|integer',
            'lines.*.m_product_id' => 'required|integer',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.priceactual' => 'nullable|numeric',
            'lines.*.c_tax_id' => 'nullable|integer',
        ]);  

        $documentId = $validated['document_id']; 
        try {
            $orderId = \Illuminate\Support\Facades\Crypt::decryptString($documentId); 
            $token = \Illuminate\Support\Facades\Session::get('api_token');
            if (!$token) {
                return response()->json(['message' => 'Authentication required'], 401);
            }  
            // Get order header info (including tax from the Schedule DN header)
            $order = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_order')->where('c_order_id', $orderId)
                ->first(['ad_org_id', 'c_tax_id', 'datepromised']); 
            if (!$order) {
                return response()->json(['message' => 'Schedule not found'], 404);
            }  
            $taxId      = $order->c_tax_id ?? null;
            $orgId      = $order->ad_org_id ?? null;
            // Format exactly as received by store() method
            $datePromised = $order->datepromised
                ? \Carbon\Carbon::parse($order->datepromised)->format('Y-m-d')
                : null;

            // Get max line
            $maxLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_orderline')
                ->where('c_order_id', $orderId)
                ->max('line') ?? 0;

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($validated['lines'] as $lineData) {
                // Validation: Check if requested qty exceeds the remaining available qty
                if (!empty($lineData['ref_c_orderline_id'])) {
                    $sourceLineValidation = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('c_orderline')
                        ->where('c_orderline_id', (int) $lineData['ref_c_orderline_id'])
                        ->first(['qtyordered', 'qtyscheduled']);

                    if ($sourceLineValidation) {
                        $requestedQty = (float) $lineData['qty'];
                        $newScheduledQty = (float) $sourceLineValidation->qtyscheduled + $requestedQty;
                        
                        if ($newScheduledQty > (float) $sourceLineValidation->qtyordered) {
                            $maxAllowedQty = (float) $sourceLineValidation->qtyordered - (float) $sourceLineValidation->qtyscheduled;
                            $errors[] = "Quantity exceeds available for a line. Maximum allowed is " . $maxAllowedQty;
                            $failed++;
                            continue; // Skip this line
                        }
                    }
                }

                $maxLine += $deliveryScheduleConfig['limits']['line_increment'];
                $price = $lineData['priceactual'] ?? 0;

                // Step 1: Create the line via API with minimal fields
                // Pricing fields are sent accurately based on the source line
                $linePayload = [
                    'AD_Org_ID'    => (int) $orgId,
                    'M_Product_ID' => (int) $lineData['m_product_id'],
                    'QtyEntered'   => (float) $lineData['qty'],
                    'Line'         => $maxLine, // Ensure uniqueness
                    'PriceEntered' => (float) $price,
                    'QtyOrdered'   => (float) $lineData['qty'],
                    'PriceActual'  => (float) $price,
                    'PriceList'    => (float) $price,
                    'QtyScheduled' => (float) $lineData['qty'],
                ];
                
                if (!empty($lineData['description'])) {
                    $linePayload['Description'] = $lineData['description'];
                }
                
                if ($taxId) {
                    $linePayload['C_Tax_ID'] = (int) $taxId;
                }
                // Removed DatePromised from the API payload completely to allow iDempiere to use generic server contexts
                
                $payload = ['C_OrderLine' => [$linePayload]];

                \Illuminate\Support\Facades\Log::info('Creating order line', [
                    'c_order_id' => $orderId,
                    'payload' => $payload
                ]);

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($orderId, $payload, $baseUrl) {
                    return \Illuminate\Support\Facades\Http::withToken($t)
                    ->put($baseUrl . '/models/c_order/' . $orderId, $payload);
                }); 

                if ($response->successful()) {
                    $imported++; 
                    // Step 2: Find the newly created line by c_order_id + line number
                    $LineSource = \Illuminate\Support\Facades\DB::connection('idempiere')
                        ->table('c_orderline')
                        ->where('c_order_id', (int) $lineData['ref_c_order_id'])
                        ->where('c_orderline_id', (int) $lineData['ref_c_orderline_id']) 
                        ->first(['c_orderline_id', 'qtyscheduled']); 
                    $newLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                    ->table('c_orderline')
                    ->where('c_order_id', $orderId)
                    ->where('line', $maxLine) 
                    ->first(['c_orderline_id']);

                    if ($LineSource && $newLine) {
                        // Update qtyscheduled on the source SO line
                        \Illuminate\Support\Facades\DB::connection('idempiere')
                            ->table('c_orderline')
                            ->where('c_orderline_id', $LineSource->c_orderline_id)
                            ->update([
                                'qtyscheduled' => (float) $LineSource->qtyscheduled + (float) $lineData['qty'],
                            ]);

                        // Write custom ref fields directly to the new DN line
                        $updateData = [
                            'qtyscheduled' => (float) $lineData['qty'],
                        ];
                        if (!empty($lineData['ref_c_orderline_id'])) {
                            $updateData['ref_orderline_id'] = (int) $lineData['ref_c_orderline_id']; 
                        }
                        if (!empty($lineData['ref_c_order_id'])) {
                            $updateData['ref_order_id'] = (int) $lineData['ref_c_order_id'];
                        }

                        \Illuminate\Support\Facades\DB::connection('idempiere')
                            ->table('c_orderline')
                            ->where('c_orderline_id', $newLine->c_orderline_id)
                            ->update($updateData);
                    }
                } else {
                    $responseBody = $response->json();
                    $errorMsg = $responseBody['title'] ?? $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';

                    \Illuminate\Support\Facades\Log::error('StoreFromSO: line creation failed', [
                        'payload'  => $payload,
                        'status'   => $response->status(),
                        'response' => $responseBody,
                    ]);

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
}
