<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class LocationMasterController extends Controller
{
    /**
     * Display a listing of locations
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');
            
            $query = LocationMaster::query();
            
            if (! is_null($status)) {
                $query->where('status', $status);
            }
            
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('location_name', 'like', "%{$search}%")
                      ->orWhere('region', 'like', "%{$search}%")
                      ->orWhere('location_code', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%");
                });
            }
            
            if ($request->has('city')) {
                $query->where('city', $request->input('city'));
            }
            
            if ($request->has('state')) {
                $query->where('state', $request->input('state'));
            }
            
            $locations = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $locations
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store a newly created location
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_name' => 'required|string|max:255',
            'region' => 'nullable|string|max:100|unique:locations_master,region',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $location = LocationMaster::create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Location created successfully',
                'data' => $location
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create location',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display the specified location
     */
    public function show($id)
    {
        try {
            $location = LocationMaster::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $location
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Update the specified location
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'location_name' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:100|unique:locations_master,region,' . $id,
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $location = LocationMaster::findOrFail($id);
            $data = $request->all();
            $location->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => $location
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified location
     */
    public function destroy($id)
    {
        try {
            $location = LocationMaster::findOrFail($id);
            $location->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully'
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete location',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk delete locations
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:locations_master,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            LocationMaster::whereIn('id', $request->input('ids'))->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Locations deleted successfully'
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete locations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}