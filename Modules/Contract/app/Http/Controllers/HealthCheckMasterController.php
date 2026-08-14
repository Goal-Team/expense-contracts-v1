<?php

namespace App\Http\Controllers;

use App\Models\HealthCheckMaster;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class HealthCheckMasterController extends Controller
{
    /**
     * List all health check master records (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $items = HealthCheckMaster::orderByDesc('id')->paginate(25);
        return response()->json($items);
    }

    /**
     * Store a new master health check item.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item = HealthCheckMaster::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
        ]);

        return response()->json($item, 201);
    }

    /**
     * Show one master health check.
     */
    public function show(int $id): JsonResponse
    {
        $item = HealthCheckMaster::find($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($item);
    }

    /**
     * Update master health check.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = HealthCheckMaster::find($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->fill($request->only(['name', 'description', 'price']));
        $item->save();

        return response()->json($item);
    }

    /**
     * Delete master health check.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = HealthCheckMaster::find($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $item->delete();
        return response()->json(['success' => true]);
    }
}