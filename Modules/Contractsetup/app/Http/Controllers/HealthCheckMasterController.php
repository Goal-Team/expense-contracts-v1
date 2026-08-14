<?php

namespace Modules\Contractsetup\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HealthCheckMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class HealthCheckMasterController extends Controller
{
    /**
     * Display a listing of health checks
     */
    public function index(Request $request)
    {

            $perPage = $request->input('per_page', 15);
            $query = HealthCheckMaster::query();
            
            // Search filter
            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('test_name', 'like', "%{$search}%")
                      ->orWhere('test_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }
            
            // Category filter
            if ($request->has('category') && ! empty($request->category)) {
                $query->where('category', $request->category);
            }
            
            $healthChecks = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            // Get unique categories for filter
            $categories = HealthCheckMaster::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->pluck('category');
            
            return view('contract-setup::health-checks.index', compact('healthChecks', 'categories'));
            

    }
    
    /**
     * Show the form for creating a new health check
     */
    public function create()
    {
        return view('contract-setup::health-checks.create');
    }
    
    /**
     * Store a newly created health check
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_name' => 'required|string|max:255',
            'test_code' => 'nullable|string|max:100|unique:health_check_masters,test_code',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'default_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->all();
            $data['status'] = $request->has('status') ? 1 : 0;
            
            HealthCheckMaster::create($data);
            
            return redirect()->route('contract-setup.health-checks.index')
                ->with('success', 'Health check created successfully');
            
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create health check: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Display the specified health check
     */
    public function show($id)
    {
        try {
            $healthCheck = HealthCheckMaster::findOrFail($id);
            return view('contract-setup::health-checks.show', compact('healthCheck'));
            
        } catch (Exception $e) {
            return redirect()->route('contract-setup.health-checks.index')
                ->with('error', 'Health check not found');
        }
    }
    
    /**
     * Show the form for editing the specified health check
     */
    public function edit($id)
    {
        try {
            $healthCheck = HealthCheckMaster::findOrFail($id);
            return view('contract-setup::health-checks.edit', compact('healthCheck'));
            
        } catch (Exception $e) {
            return redirect()->route('contract-setup.health-checks.index')
                ->with('error', 'Health check not found');
        }
    }
    
    /**
     * Update the specified health check
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'test_name' => 'required|string|max:255',
            'test_code' => 'nullable|string|max:100|unique:health_check_masters,test_code,' . $id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'default_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $healthCheck = HealthCheckMaster::findOrFail($id);
            
            $data = $request->all();
            $data['status'] = $request->has('status') ? 1 : 0;
            
            $healthCheck->update($data);
            
            return redirect()->route('contract-setup.health-checks.index')
                ->with('success', 'Health check updated successfully');
            
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update health check: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Remove the specified health check
     */
    public function destroy($id)
    {
        try {
            $healthCheck = HealthCheckMaster::findOrFail($id);
            $healthCheck->delete();
            
            return redirect()->route('contract-setup.health-checks.index')
                ->with('success', 'Health check deleted successfully');
            
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete health check: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk delete health checks
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:health_check_masters,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->with('error', 'Invalid selection for bulk delete');
        }

        try {
            HealthCheckMaster::whereIn('id', $request->input('ids'))->delete();
            
            return redirect()->route('contract-setup.health-checks.index')
                ->with('success', 'Selected health checks deleted successfully');
            
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete health checks: ' . $e->getMessage());
        }
    }
    
    public function getHealthChecks(Request $request){

        try {
            $query = HealthCheckMaster::query();
            
            // Filter by status (default: active only)
            $status = $request->input('status', 1);
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            
            // Filter by category
            if ($request->has('category') && ! empty($request->category)) {
                $query->where('category', $request->category);
            }
            
            // Search by name or code
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('test_name', 'like', "%{$search}%")
                      ->orWhere('test_code', 'like', "%{$search}%");
                });
            }
            
            // Filter by IDs
            if ($request->has('ids') && is_array($request->ids)) {
                $query->whereIn('id', $request->ids);
            }
            
            $healthChecks = $query->orderBy('test_name', 'asc')->get();
            
            // Transform to required format
            $data = $healthChecks->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->test_name,
                    'price' => (float) $item->default_price
                ];
            });
            
            return response()->json($data, 200);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve health checks',
                'message' => $e->getMessage()
            ], 500);
        }        
    }
}