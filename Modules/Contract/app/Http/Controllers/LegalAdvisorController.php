<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LegalAdvisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LegalAdvisorController extends Controller
{
    public function __construct()
    {
        if (Controller::checkCurrentAuth('Contracts') != 1) {
            abort(404);
        }
    }

    public function index()
    {
        $legalAdvisors = LegalAdvisor::orderByDesc('id')->paginate(20);
        return view('contract::legal-advisors.index', compact('legalAdvisors'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email_id' => 'required|email|max:255|unique:legal_advisors,email_id',
            'legal_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        LegalAdvisor::create([
            'name' => $request->input('name'),
            'email_id' => strtolower(trim((string) $request->input('email_id'))),
            'legal_name' => $request->input('legal_name'),
            'designation' => $request->input('designation'),
            'contact' => $request->input('contact'),
            'status' => 1,
        ]);

        return redirect()->route('legal-advisors.index')->with('message', 'Legal advisor created successfully')->with('alert-class', 'alert-success');
    }

    public function update(Request $request, $id)
    {
        $advisor = LegalAdvisor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email_id' => 'required|email|max:255|unique:legal_advisors,email_id,' . $advisor->id,
            'legal_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $advisor->update([
            'name' => $request->input('name'),
            'email_id' => strtolower(trim((string) $request->input('email_id'))),
            'legal_name' => $request->input('legal_name'),
            'designation' => $request->input('designation'),
            'contact' => $request->input('contact'),
        ]);

        return redirect()->route('legal-advisors.index')->with('message', 'Legal advisor updated successfully')->with('alert-class', 'alert-success');
    }

    public function updateStatus(Request $request, $id)
    {
        $advisor = LegalAdvisor::findOrFail($id);
        $advisor->status = (int) !((int) $advisor->status);
        $advisor->save();

        return redirect()->route('legal-advisors.index')->with('message', 'Legal advisor status updated')->with('alert-class', 'alert-success');
    }
}
