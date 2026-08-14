<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Models\Contract; // adjust to your actual model
use Exception;

class AgreementController extends Controller
{
    /**
     * Store agreement (marketing) — accepts JSON payload and returns JSON response.
     *
     * Endpoint used by front-end: POST APP_URL + '/contracts/store/marketing'
     *
     * Responsibilities:
     *  - validate incoming JSON payload
     *  - persist contract / related records (transactional)
     *  - return well-formed JSON responses:
     *     - 201 created with data.id on success
     *     - 422 with validation errors when validation fails
     *     - 500 with message on unexpected exception
     */
    public function storeMarketing(Request $request): JsonResponse
    {
        // Accept JSON or form-encoded input
        $payload = $request->json()->all() ?: $request->all();

        // Basic validation rules — extend to match your DB schema & business rules
        $rules = [
            'customer' => 'required|integer|exists:customers,id', // adjust table/name as needed
            'agreementName' => 'required|string|max:255',
            'type' => ['nullable', Rule::in(['new','renewal'])],
            'entityScope' => 'nullable|string|max:120',
            'entityType' => 'nullable|string|max:255',
            'tenure.start' => 'required|date',
            'tenure.end' => 'required|date|after_or_equal:tenure.start',
            'scope' => 'nullable|array',
            'scope.*' => 'string',
            'locations' => 'nullable|array',
            'locations.*' => 'integer',
            'notes' => 'nullable|string',
            'healthCheckPackages' => 'nullable|array',
            'healthCheckPackages.*.name' => 'required_with:healthCheckPackages|string',
            'healthCheckPackages.*.priceINR' => 'nullable|numeric|min:0',
            'domesticDiscounts' => 'nullable|array',
            'psuDiscounts' => 'nullable|array',
            'psuRoomCustom' => 'nullable|array',
            'psuRoomCustom.*.name' => 'required_with:psuRoomCustom|string',
            'psuRoomCustom.*.rate' => 'nullable|numeric|min:0',
        ];

        $messages = [
            'customer.required' => 'Customer is required.',
            'tenure.start.required' => 'Start date is required.',
            'tenure.end.required' => 'End date is required.',
            'tenure.end.after_or_equal' => 'End date must be the same or after start date.',
            // Add other custom messages as needed
        ];

        $validator = Validator::make($payload, $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // You can add additional domain validation here (e.g. tenure <= 2 years)
        try {
            $start = isset($payload['tenure']['start']) ? new \DateTime($payload['tenure']['start']) : null;
            $end = isset($payload['tenure']['end']) ? new \DateTime($payload['tenure']['end']) : null;
            if ($start && $end) {
                $maxEnd = clone $start;
                $maxEnd->modify('+2 years');
                if ($end > $maxEnd) {
                    return response()->json([
                        'message' => 'Tenure validation failed.',
                        'errors' => ['tenure.end' => ['Tenure cannot exceed 2 years from start date.']],
                    ], 422);
                }
            }
        } catch (\Exception $e) {
            // continue, validation already ensured date format but be safe
        }

        // Persist payload in a DB transaction
        DB::beginTransaction();
        try {
            // Map payload fields to your Contract model columns.
            // Adjust the mapping per your schema.
            $contractData = [
                'customer_id' => $payload['customer'],
                'name' => $payload['agreementName'],
                'type' => $payload['type'] ?? null,
                'entity_scope' => $payload['entityScope'] ?? null,
                'entity_type' => $payload['entityType'] ?? null,
                'start_date' => $payload['tenure']['start'] ?? null,
                'end_date' => $payload['tenure']['end'] ?? null,
                'notes' => $payload['notes'] ?? null,
                // add other top-level columns as needed
            ];

            // Create contract (ensure Contract model has fillable/guarded set appropriately)
            $contract = Contract::create($contractData);

            // Save related data: packages, discounts, psu discounts, custom rooms, locations
            // This depends on your relationships. Below are example patterns — adapt to your schema.

            // Example: healthCheckPackages
            if (!empty($payload['healthCheckPackages']) && is_array($payload['healthCheckPackages'])) {
                foreach ($payload['healthCheckPackages'] as $pkg) {
                    // Example: $contract->packages()->create([...]);
                    if (isset($pkg['name'])) {
                        $contract->healthPackages()->create([
                            'name' => $pkg['name'],
                            'price_inr' => isset($pkg['priceINR']) ? $pkg['priceINR'] : null,
                            'subtests' => isset($pkg['subtests']) ? json_encode($pkg['subtests']) : null,
                        ]);
                    }
                }
            }

            // Example: domesticDiscounts
            if (!empty($payload['domesticDiscounts']) && is_array($payload['domesticDiscounts'])) {
                foreach ($payload['domesticDiscounts'] as $key => $d) {
                    // $key is a composite key used on client-side; parse or store as needed
                    $contract->domesticDiscounts()->create([
                        'meta_key' => $key,
                        'category' => $d['category'] ?? null,
                        'subcategory_value' => $d['subcategoryValue'] ?? null,
                        'subcategory_label' => $d['subcategoryLabel'] ?? null,
                        'percent' => isset($d['percent']) ? $d['percent'] : null,
                    ]);
                }
            }

            // Example: psuDiscounts
            if (!empty($payload['psuDiscounts']) && is_array($payload['psuDiscounts'])) {
                foreach ($payload['psuDiscounts'] as $key => $d) {
                    $contract->psuDiscounts()->create([
                        'meta_key' => $key,
                        'category' => $d['category'] ?? null,
                        'subcategory_value' => $d['subcategoryValue'] ?? null,
                        'subcategory_label' => $d['subcategoryLabel'] ?? null,
                        'percent' => isset($d['percent']) ? $d['percent'] : null,
                    ]);
                }
            }

            // Example: psuRoomCustom
            if (!empty($payload['psuRoomCustom']) && is_array($payload['psuRoomCustom'])) {
                foreach ($payload['psuRoomCustom'] as $custom) {
                    $contract->psuRoomCustoms()->create([
                        'name' => $custom['name'] ?? null,
                        'rate' => isset($custom['rate']) ? $custom['rate'] : null,
                    ]);
                }
            }

            // Example: locations (attach pivot)
            if (!empty($payload['locations']) && is_array($payload['locations'])) {
                $contract->locations()->sync($payload['locations']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Agreement created successfully.',
                'data' => [
                    'id' => $contract->id,
                ],
            ], 201);

        } catch (Exception $ex) {
            DB::rollBack();
            // Log the exception as needed: Log::error($ex);
            return response()->json([
                'message' => 'Failed to create agreement.',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }
}