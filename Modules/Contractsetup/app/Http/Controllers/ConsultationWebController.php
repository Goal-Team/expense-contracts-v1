<?php

namespace Modules\Contractsetup\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConsultationMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultationWebController extends Controller
{
    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }

    /**
     * Display listing of consultations
     */
    public function index()
    {
        $consultations = ConsultationMaster::orderBy('id', 'desc')->paginate(15);
        return view('contract-setup::consultations.index', compact('consultations'));
    }

    /**
     * Show the form for creating a new consultation
     */
    public function create()
    {
        return view('contract-setup::consultations.create');
    }

    /**
     * Store a newly created consultation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        ConsultationMaster::create($data);
        return redirect()->route('contract-setup.consultations.index')->with('success', 'Consultation created successfully.');
    }

    /**
     * Show the form for editing a consultation
     */
    public function edit($id)
    {
        $consultation = ConsultationMaster::findOrFail($id);
        return view('contract-setup::consultations.edit', compact('consultation'));
    }

    /**
     * Update the specified consultation
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $consultation = ConsultationMaster::findOrFail($id);
        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        $consultation->update($data);
        return redirect()->route('contract-setup.consultations.index')->with('success', 'Consultation updated successfully.');
    }

    /**
     * Remove the specified consultation
     */
    public function destroy($id)
    {
        $consultation = ConsultationMaster::findOrFail($id);
        $consultation->delete();
        return redirect()->route('contract-setup.consultations.index')->with('success', 'Consultation deleted successfully.');
    }

    /**
     * Bulk delete consultations
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            ConsultationMaster::whereIn('id', $ids)->delete();
            return response()->json(['success' => true, 'message' => 'Selected consultations deleted.']);
        }
        return response()->json(['success' => false, 'message' => 'No items selected.']);
    }

    /**
     * API: Get list of consultations for dropdowns/JS
     */
    public function getList()
    {
        $consultations = ConsultationMaster::select('id', 'name', 'price')
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        return response()->json($consultations);
    }
}
