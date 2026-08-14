<?php

namespace Modules\Contractsetup\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TestMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TestWebController extends Controller
{
    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }

    /**
     * Display listing of tests
     */
    public function index()
    {
        $tests = TestMaster::orderBy('id', 'desc')->paginate(20);
        return view('contract-setup::tests.index', compact('tests'));
    }

    /**
     * Show the form for creating a new test
     */
    public function create()
    {
        return view('contract-setup::tests.create');
    }

    /**
     * Store a newly created test
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        TestMaster::create($data);
        return redirect()->route('contract-setup.tests.index')->with('success', 'Test created successfully.');
    }

    /**
     * Show the form for editing a test
     */
    public function edit($id)
    {
        $test = TestMaster::findOrFail($id);
        return view('contract-setup::tests.edit', compact('test'));
    }

    /**
     * Update the specified test
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $test = TestMaster::findOrFail($id);
        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        $test->update($data);
        return redirect()->route('contract-setup.tests.index')->with('success', 'Test updated successfully.');
    }

    /**
     * Remove the specified test
     */
    public function destroy($id)
    {
        $test = TestMaster::findOrFail($id);
        $test->delete();
        return redirect()->route('contract-setup.tests.index')->with('success', 'Test deleted successfully.');
    }

    /**
     * Bulk delete tests
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            TestMaster::whereIn('id', $ids)->delete();
            return response()->json(['success' => true, 'message' => 'Selected tests deleted.']);
        }
        return response()->json(['success' => false, 'message' => 'No items selected.']);
    }

    /**
     * API: Get list of tests for dropdowns/JS
     */
    public function getList()
    {
        $tests = TestMaster::select('id', 'name', 'price')
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        return response()->json($tests);
    }
}
