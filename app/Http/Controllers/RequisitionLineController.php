<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequisitionLineController extends Controller
{
    public function create(Request $request)
    {
        $documentId = $request->query('document_id');
        if (!$documentId) {
            return redirect()->route('requisition.index')->with('error', 'Document ID required.');
        }

        try {
            // Decrypt ID
            $requisitionId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);

            // Fetch Header Info for Context (Optional, but good for Breadcrumbs/Validation)
            $requisition = \Illuminate\Support\Facades\DB::connection('idempiere')->table('m_requisition')
                ->where('m_requisition_id', $requisitionId)
                ->first();

            if (!$requisition) {
                return redirect()->route('requisition.index')->with('error', 'Requisition not found.');
            }

        } catch (\Exception $e) {
            return redirect()->route('requisition.index')->with('error', 'Invalid Document ID.');
        }

        // Return View with Encrypted ID
        return view('pages.requisition.line_form', [
            'document_id' => $documentId,
            'requisition' => $requisition,
            'title' => 'Create Requisition Line'
        ]);
    }

    public function store(Request $request)
    {
        $requisitionConfig = config('idempiere.create-pr');

        // Validation
        $validated = $request->validate([
            'document_id' => 'required',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|min:0.01',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $documentId = $request->input('document_id');
        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

        try {
            $requisitionId = \Illuminate\Support\Facades\Crypt::decryptString($documentId);

            // Get session data for API call
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            // Get max line number from existing lines and organization
            $linesResponse = \App\Services\IdempiereService::withAutoRetry(function($t) use ($baseUrl, $requisitionId) {
                return \Illuminate\Support\Facades\Http::withoutVerifying()->withToken($t)
                    ->get("{$baseUrl}/models/m_requisition/{$requisitionId}");
            });

            $maxLine = 0;
            $orgId = null;

            if ($linesResponse->successful()) {
                $requisitionData = $linesResponse->json();

                // Get organization ID from requisition header
                if (isset($requisitionData['AD_Org_ID']['id'])) {
                    $orgId = $requisitionData['AD_Org_ID']['id'];
                }

                // Get max line number
                if (isset($requisitionData['M_Requisitionline']) && is_array($requisitionData['M_Requisitionline'])) {
                    foreach ($requisitionData['M_Requisitionline'] as $line) {
                        if (isset($line['Line']) && $line['Line'] > $maxLine) {
                            $maxLine = $line['Line'];
                        }
                    }
                }
            }

            if (!$orgId) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Organization ID not found in requisition'], 400);
                }
                return back()->with('error', 'Organization ID not found in requisition');
            }

            $nextLine = $maxLine + $requisitionConfig['limits']['line_increment'];

            // Prepare payload for iDempiere API
            // Create line by updating the requisition with new M_Requisitionline array
            $payload = [
                'M_Requisitionline' => [
                    [
                        'AD_Org_ID' => (int) $orgId,
                        'M_Product_ID' => (int) $validated['m_product_id'],
                        'PriceActual' => !empty($validated['price']) ? (float) $validated['price'] : 0,
                        'Qty' => (float) $validated['qty'],
                        'Description' => $validated['description'] ?? '',
                        'Line' => $nextLine
                    ]
                ]
            ];

            // Call iDempiere API to add requisition line
            \Illuminate\Support\Facades\Log::info('Creating requisition line', [
                'requisition_id' => $requisitionId,
                'payload' => $payload
            ]);

            $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($baseUrl, $requisitionId, $payload) {
                return \Illuminate\Support\Facades\Http::withoutVerifying()->withToken($t)
                    ->put("{$baseUrl}/models/m_requisition/{$requisitionId}", $payload);
            });

            \Illuminate\Support\Facades\Log::info('API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                $responseBody = $response->json();

                // Try to extract error message from various possible fields
                $errorMsg = 'Failed to create line';
                if (isset($responseBody['title'])) {
                    $errorMsg = $responseBody['title'];
                } elseif (isset($responseBody['message'])) {
                    $errorMsg = $responseBody['message'];
                } elseif (isset($responseBody['error'])) {
                    $errorMsg = $responseBody['error'];
                } elseif (is_string($responseBody)) {
                    $errorMsg = $responseBody;
                }

                \Illuminate\Support\Facades\Log::error('Failed to create requisition line', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                    'full_response' => $responseBody
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

            \Illuminate\Support\Facades\Log::info('Requisition line created successfully', [
                'response' => $responseData
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Line created successfully.',
                    'data' => $responseData
                ]);
            }

            // Redirect back to Lines Tab
            return redirect()->route('requisition.index', ['document_id' => $documentId, 'tab' => 'lines'])
                ->with('success', 'Line created successfully.');

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid Document ID'], 400);
            }
            return back()->with('error', 'Invalid Document ID');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save requisition line: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to save line: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save line: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'line_id' => 'required',
            'm_product_id' => 'required',
            'qty' => 'required|numeric|min:0.01',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

        try {
            // Get session data for API call
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            $requisitionLine = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('m_requisitionline')
                ->where('m_requisitionline_id', $validated['line_id'])
                ->first(['m_requisitionline_id', 'qty']);

            if (!$requisitionLine) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Requisition line not found'], 404);
                }
                return back()->with('error', 'Requisition line not found');
            }

            $totalOrderedQty = (float) \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_orderline as ol')
                ->join('c_order as o', 'o.c_order_id', '=', 'ol.c_order_id')
                ->where('ol.m_requisitionline_id', $validated['line_id'])
                ->whereNotIn('o.docstatus', ['VO', 'RE'])
                ->sum('ol.qtyordered');

            $requestedQty = (float) $validated['qty'];

            if ($requestedQty < $totalOrderedQty) {
                $message = sprintf(
                    'Qty requisition tidak boleh lebih kecil dari total Qty Ordered pada Purchase Order. Qty Ordered saat ini: %s.',
                    rtrim(rtrim(number_format($totalOrderedQty, 2, '.', ''), '0'), '.')
                );

                if ($request->wantsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                return back()->with('error', $message);
            }

            // Prepare payload for iDempiere API (update existing line)
            $payload = [
                'M_Product_ID' => (int) $validated['m_product_id'],
                'PriceActual' => !empty($validated['price']) ? (float) $validated['price'] : 0,
                'Qty' => (float) $validated['qty'],
                'Description' => $validated['description'] ?? '',
            ];

            // Call iDempiere API to update requisition line
            \Illuminate\Support\Facades\Log::info('Updating requisition line', [
                'line_id' => $validated['line_id'],
                'payload' => $payload
            ]);

            $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($baseUrl, $validated, $payload) {
                return \Illuminate\Support\Facades\Http::withoutVerifying()->withToken($t)
                    ->put("{$baseUrl}/models/m_requisitionline/{$validated['line_id']}", $payload);
            });

            \Illuminate\Support\Facades\Log::info('API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                $responseBody = $response->json();

                // Try to extract error message from various possible fields
                $errorMsg = 'Failed to update line';
                if (isset($responseBody['title'])) {
                    $errorMsg = $responseBody['title'];
                } elseif (isset($responseBody['message'])) {
                    $errorMsg = $responseBody['message'];
                } elseif (isset($responseBody['error'])) {
                    $errorMsg = $responseBody['error'];
                } elseif (is_string($responseBody)) {
                    $errorMsg = $responseBody;
                }

                \Illuminate\Support\Facades\Log::error('Failed to update requisition line', [
                    'status' => $response->status(),
                    'error' => $errorMsg,
                    'full_response' => $responseBody
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

            \Illuminate\Support\Facades\Log::info('Requisition line updated successfully', [
                'response' => $responseData
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Line updated successfully.',
                    'data' => $responseData
                ]);
            }

            // Redirect back to Lines Tab
            $documentId = $request->input('document_id');
            return redirect()->route('requisition.index', ['document_id' => $documentId, 'tab' => 'lines'])
                ->with('success', 'Line updated successfully.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update requisition line: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to update line: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to update line: ' . $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'line_ids' => 'required|array',
            'line_ids.*' => 'required|integer',
        ]);

        $baseUrl = rtrim((string) config('idempiere.api.base_url'), '/');

        try {
            // Get session data for API call
            $token = \Illuminate\Support\Facades\Session::get('api_token');

            if (!$token) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Authentication required'], 401);
                }
                return back()->with('error', 'Authentication required');
            }

            $lineIds = $validated['line_ids'];

            $linkedLineIds = \Illuminate\Support\Facades\DB::connection('idempiere')
                ->table('c_orderline')
                ->whereIn('m_requisitionline_id', $lineIds)
                ->whereNotNull('m_requisitionline_id')
                ->distinct()
                ->pluck('m_requisitionline_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($linkedLineIds)) {
                $message = count($linkedLineIds) === 1
                    ? 'Cannot delete requisition line because it is already linked to a Purchase Order line.'
                    : 'Cannot delete requisition lines because one or more selected lines are already linked to Purchase Order lines.';

                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => $message,
                        'linked_line_ids' => $linkedLineIds,
                    ], 422);
                }

                return back()->with('error', $message);
            }

            $deletedCount = 0;
            $errors = [];

            // Delete each line via API
            foreach ($lineIds as $lineId) {
                \Illuminate\Support\Facades\Log::info('Deleting requisition line', [
                    'line_id' => $lineId
                ]);

                $response = \App\Services\IdempiereService::withAutoRetry(function($t) use ($baseUrl, $lineId) {
                    return \Illuminate\Support\Facades\Http::withoutVerifying()->withToken($t)
                        ->delete("{$baseUrl}/models/m_requisitionline/{$lineId}");
                });

                \Illuminate\Support\Facades\Log::info('API Response', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                if ($response->successful()) {
                    $deletedCount++;
                    \Illuminate\Support\Facades\Log::info('Requisition line deleted successfully', [
                        'line_id' => $lineId
                    ]);
                } else {
                    $responseBody = $response->json();

                    // Try to extract error message
                    $errorMsg = 'Failed to delete line';
                    if (isset($responseBody['title'])) {
                        $errorMsg = $responseBody['title'];
                    } elseif (isset($responseBody['message'])) {
                        $errorMsg = $responseBody['message'];
                    } elseif (isset($responseBody['error'])) {
                        $errorMsg = $responseBody['error'];
                    }

                    $errors[] = "Line ID {$lineId}: {$errorMsg}";

                    \Illuminate\Support\Facades\Log::error('Failed to delete requisition line', [
                        'line_id' => $lineId,
                        'status' => $response->status(),
                        'error' => $errorMsg,
                        'full_response' => $responseBody
                    ]);
                }
            }

            // Prepare response message
            if ($deletedCount === count($lineIds)) {
                $message = $deletedCount === 1
                    ? 'Line deleted successfully'
                    : "{$deletedCount} lines deleted successfully";

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'deleted_count' => $deletedCount
                    ]);
                }
                return back()->with('success', $message);
            } elseif ($deletedCount > 0) {
                $message = "{$deletedCount} line(s) deleted, " . count($errors) . " failed";

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'deleted_count' => $deletedCount,
                        'errors' => $errors
                    ], 207); // Multi-Status
                }
                return back()->with('warning', $message);
            } else {
                $message = 'Failed to delete line(s): ' . implode(', ', $errors);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to delete lines',
                        'errors' => $errors
                    ], 500);
                }
                return back()->with('error', $message);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete requisition line(s): ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to delete line(s): ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to delete line(s): ' . $e->getMessage());
        }
    }
}

