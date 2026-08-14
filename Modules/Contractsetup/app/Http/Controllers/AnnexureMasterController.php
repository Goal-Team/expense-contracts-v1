<?php

namespace Modules\Contractsetup\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AnnexureMaster;
use App\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AnnexureMasterController extends Controller
{
    /**
     * Sample annexure templates are not tied to a contract, so they cannot use the
     * contract-scoped fileStorageTypeController()/get_file_path() abstraction. They live
     * on the default disk instead. Per-contract annexure uploads do use that abstraction.
     */
    private const SAMPLE_DIR = 'annexures/samples';

    /** Annexures are appended to the generated contract PDF, so only Word is accepted. */
    private const ALLOWED_EXTENSIONS = ['doc', 'docx'];

    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }

    /**
     * Display listing of annexures
     */
    public function index(Request $request)
    {
        $contractType = $request->input('contract_type');

        $annexures = AnnexureMaster::with('contractType')
            ->when($contractType, function ($query) use ($contractType) {
                $query->where('contract_type', $contractType);
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        $contractTypes = ContractType::get();

        return view('contract-setup::annexures.index', compact('annexures', 'contractTypes', 'contractType'));
    }

    /**
     * Show the form for creating a new annexure
     */
    public function create()
    {
        $contractTypes = ContractType::get();
        return view('contract-setup::annexures.create', compact('contractTypes'));
    }

    /**
     * Store a newly created annexure
     */
    public function store(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'annexure_name' => $request->input('annexure_name'),
            'title'         => $request->input('title'),
            'contract_type' => $request->input('contract_type'),
            'status'        => $request->has('status') ? 1 : 0,
            'created_by'    => Auth::id(),
            'updated_by'    => Auth::id(),
        ];

        if ($request->hasFile('sample_file')) {
            $stored = $this->storeSample($request->file('sample_file'));
            $data['sample_file']      = $stored['path'];
            $data['sample_file_name'] = $stored['name'];
        }

        AnnexureMaster::create($data);

        return redirect()->route('contract-setup.annexures.index')
            ->with('success', 'Annexure created successfully.');
    }

    /**
     * Show the form for editing an annexure
     */
    public function edit($id)
    {
        $annexure = AnnexureMaster::findOrFail($id);
        $contractTypes = ContractType::get();
        return view('contract-setup::annexures.edit', compact('annexure', 'contractTypes'));
    }

    /**
     * Update the specified annexure. The existing sample file is retained unless a new
     * one is uploaded, in which case the old file is removed.
     */
    public function update(Request $request, $id)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $annexure = AnnexureMaster::findOrFail($id);

        $data = [
            'annexure_name' => $request->input('annexure_name'),
            'title'         => $request->input('title'),
            'contract_type' => $request->input('contract_type'),
            'status'        => $request->has('status') ? 1 : 0,
            'updated_by'    => Auth::id(),
        ];

        if ($request->hasFile('sample_file')) {
            $oldPath = $annexure->sample_file;

            $stored = $this->storeSample($request->file('sample_file'));
            $data['sample_file']      = $stored['path'];
            $data['sample_file_name'] = $stored['name'];

            if ($oldPath && Storage::exists($oldPath)) {
                Storage::delete($oldPath);
            }
        }

        $annexure->update($data);

        return redirect()->route('contract-setup.annexures.index')
            ->with('success', 'Annexure updated successfully.');
    }

    /**
     * Remove the specified annexure along with its sample file
     */
    public function destroy($id)
    {
        $annexure = AnnexureMaster::findOrFail($id);

        if ($annexure->sample_file && Storage::exists($annexure->sample_file)) {
            Storage::delete($annexure->sample_file);
        }

        $annexure->delete();

        return redirect()->route('contract-setup.annexures.index')
            ->with('success', 'Annexure deleted successfully.');
    }

    /**
     * Download the sample template for an annexure
     */
    public function downloadSample($id)
    {
        $annexure = AnnexureMaster::findOrFail($id);

        if (!$annexure->sample_file || !Storage::exists($annexure->sample_file)) {
            return redirect()->route('contract-setup.annexures.index')
                ->with('error', 'No sample file available for this annexure.');
        }

        return Storage::download($annexure->sample_file, $annexure->sample_file_name);
    }

    /**
     * API: active annexures for the contract create page / dropdowns
     */
    public function getList(Request $request)
    {
        $contractType = $request->input('contract_type');

        $annexures = AnnexureMaster::select('id', 'annexure_name', 'title', 'contract_type', 'sample_file')
            ->where('status', 1)
            ->when($contractType, function ($query) use ($contractType) {
                $query->where(function ($inner) use ($contractType) {
                    $inner->where('contract_type', $contractType)
                        ->orWhereNull('contract_type')
                        ->orWhere('contract_type', 0);
                });
            })
            ->orderBy('annexure_name')
            ->get();

        return response()->json($annexures);
    }

    private function validator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'annexure_name' => 'required|string|max:255',
            'title'         => 'nullable|string|max:255',
            'contract_type' => 'nullable|integer|exists:contract_type,contract_type_id',
            'status'        => 'nullable|boolean',
            'sample_file'   => 'nullable|file|mimes:doc,docx|max:10240',
        ]);

        // mimes: can be satisfied by a spoofed MIME type, so the extension is checked too.
        $validator->after(function ($validator) use ($request) {
            if (!$request->hasFile('sample_file')) {
                return;
            }

            $extension = strtolower($request->file('sample_file')->getClientOriginalExtension());

            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $validator->errors()->add('sample_file', 'The sample file must be a Word document (.doc or .docx).');
            }
        });

        return $validator;
    }

    private function storeSample($file): array
    {
        $originalName = $file->getClientOriginalName();
        $extension    = strtolower($file->getClientOriginalExtension());
        $fileName     = Str::uuid() . '.' . $extension;

        $path = $file->storeAs(self::SAMPLE_DIR, $fileName);

        return ['path' => $path, 'name' => $originalName];
    }
}
