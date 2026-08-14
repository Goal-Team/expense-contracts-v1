<?php

namespace Modules\Contractsetup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LocationMaster;
use Illuminate\Support\Facades\Validator;

class LocationWebController extends Controller
{
    public function index(Request $request)
    {
        $locations = LocationMaster::orderBy('id', 'desc')->paginate(15);
        return view('contract-setup::locations.index', compact('locations'));
    }

    public function create()
    {
        return view('contract-setup::locations.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_name' => 'required|string|max:255',
            'location_code' => 'nullable|string|max:100|unique:locations_master,location_code',
            'region' => 'nullable|string|max:100|locations_master,region',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            // Regional Approvers validation
            'regional_verifier_name' => 'nullable|string|max:255',
            'regional_verifier_email' => 'nullable|email|max:255',
            'regional_approver_name' => 'nullable|string|max:255',
            'regional_approver_email' => 'nullable|email|max:255',
            'regional_signatory_name' => 'nullable|string|max:255',
            'regional_signatory_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        LocationMaster::create($request->all());
        return redirect()->route('contract-setup.locations.index')->with('success', 'Location created successfully');
    }

    public function edit($id)
    {
        $location = LocationMaster::findOrFail($id);
        return view('contract-setup::locations.edit', compact('location'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'location_name' => 'required|string|max:255',
            'location_code' => 'nullable|string|max:100|unique:locations_master,location_code,' . $id,
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            // Regional Approvers validation
            'regional_verifier_name' => 'nullable|string|max:255',
            'regional_verifier_email' => 'nullable|email|max:255',
            'regional_approver_name' => 'nullable|string|max:255',
            'regional_approver_email' => 'nullable|email|max:255',
            'regional_signatory_name' => 'nullable|string|max:255',
            'regional_signatory_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $location = LocationMaster::findOrFail($id);
        $location->update($request->all());
        return redirect()->route('contract-setup.locations.index')->with('success', 'Location updated successfully');
    }

    public function destroy($id)
    {
        $location = LocationMaster::findOrFail($id);
        $location->delete();
        return redirect()->route('contract-setup.locations.index')->with('success', 'Location deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return redirect()->back()->with('error', 'No locations selected');
        }
        LocationMaster::whereIn('id', $ids)->delete();
        return redirect()->route('contract-setup.locations.index')->with('success', 'Locations deleted successfully');
    }
}
