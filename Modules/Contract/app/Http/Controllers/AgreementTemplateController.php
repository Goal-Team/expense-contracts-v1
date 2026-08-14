<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\AgreementTemplate;
use App\Models\ContractType;
use App\Models\ContractPartyEntityType;
use App\Services\AgreementTemplateStorageService;
use App\Services\AgreementTemplateVariableService;
use App\Services\AgreementTemplateRenderService;
use App\Services\AgreementTemplateValidationService;
use App\Services\AgreementTemplateSourceResolver;

class AgreementTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = AgreementTemplate::query();

        if ($request->filled('template_name')) {
            $query->where('template_name', 'like', '%' . $request->input('template_name') . '%');
        }
        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->input('contract_type'));
        }
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->input('payment_type'));
        }
        if ($request->filled('entity_type_id')) {
            $query->where('entity_type_id', $request->input('entity_type_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $templates = $query->orderBy('id', 'desc')->paginate(15)->appends($request->query());

        $categories = ContractType::get();
        $entityTypes = ContractPartyEntityType::get();
        $paymentTypes = ['Cash', 'Credit'];

        $filters = $request->only(['template_name', 'contract_type', 'payment_type', 'entity_type_id', 'status']);

        return view('contract::agreement-templates.index', compact(
            'templates', 'categories', 'entityTypes', 'paymentTypes', 'filters'
        ));
    }

    public function create()
    {
        $categories = ContractType::get();

        $variableService = app(AgreementTemplateVariableService::class);
        $availableVariables = $variableService->buildCustomVarMapFromDb();

        return view('contract::agreement-templates.create', compact('categories', 'availableVariables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'template_name' => 'nullable|string|max:255',
            'contract_type' => 'nullable|integer',
            'payment_type' => 'nullable|string|max:50',
            'entity_type_id' => 'nullable|integer',
            'status' => 'nullable|in:draft,published,archived',
            'version_no' => 'nullable|integer|min:1',
            'template_html' => 'nullable|string',
            'source_docx' => 'nullable|file|mimes:doc,docx|max:20480',
        ]);

        $storageService = app(AgreementTemplateStorageService::class);
        $validationService = app(AgreementTemplateValidationService::class);

        $docPath = null;
        $docName = null;
        if ($request->hasFile('source_docx')) {
            $uploaded = $storageService->uploadDocx($request->file('source_docx'));
            $docPath = $uploaded['path'];
            $docName = $uploaded['filename'];
        }

        $status = $data['status'] ?? 'draft';
        if ($status === 'published') {
            $conflict = $validationService->findPublishedConflict($data, null);
            if ($conflict) {
                return redirect()->back()->withErrors([
                    'status' => 'A published template already exists for this scope (ID: ' . $conflict->id . '). Archive it first.',
                ])->withInput();
            }
        }

        $template = AgreementTemplate::create([
            'template_name' => $data['template_name'] ?? null,
            'contract_type' => $data['contract_type'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'entity_type_id' => $data['entity_type_id'] ?? null,
            'status' => $status,
            'version_no' => $data['version_no'] ?? 1,
            'template_html' => $data['template_html'] ?? null,
            'source_docx_path' => $docPath,
            'source_docx_filename' => $docName,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('agreement-templates.edit', $template->id)
            ->with('success', 'Agreement template created successfully.');
    }

    public function edit($id)
    {
        $template = AgreementTemplate::findOrFail($id);

        $categories = ContractType::get();

        $variableService = app(AgreementTemplateVariableService::class);
        $variables = $template->variables()->orderBy('variable_key')->get();
        $availableVariables = $variableService->buildCustomVarMapFromDb();
        $unresolvedTokens = $variableService->getUnresolvedTokens($template);

        return view('contract::agreement-templates.edit', compact(
            'template', 'categories',
            'variables', 'availableVariables', 'unresolvedTokens'
        ));
    }

    public function update(Request $request, $id)
    {
        $template = AgreementTemplate::findOrFail($id);

        $data = $request->validate([
            'template_name' => 'nullable|string|max:255',
            'contract_type' => 'nullable|integer',
            'payment_type' => 'nullable|string|max:50',
            'entity_type_id' => 'nullable|integer',
            'status' => 'nullable|in:draft,published,archived',
            'version_no' => 'nullable|integer|min:1',
            'template_html' => 'nullable|string',
            'source_docx' => 'nullable|file|mimes:doc,docx|max:20480',
        ]);

        $storageService = app(AgreementTemplateStorageService::class);
        $variableService = app(AgreementTemplateVariableService::class);
        $validationService = app(AgreementTemplateValidationService::class);

        $uploadedDocx = false;
        if ($request->hasFile('source_docx')) {
            $uploaded = $storageService->uploadDocx($request->file('source_docx'));
            $template->source_docx_path = $uploaded['path'];
            $template->source_docx_filename = $uploaded['filename'];
            $uploadedDocx = true;
        }

        $template->template_name = $data['template_name'] ?? $template->template_name;
        $template->contract_type = $data['contract_type'] ?? $template->contract_type;
        $template->payment_type = $data['payment_type'] ?? $template->payment_type;
        $template->entity_type_id = $data['entity_type_id'] ?? $template->entity_type_id;
        $template->status = $data['status'] ?? $template->status;
        $template->version_no = $data['version_no'] ?? $template->version_no;
        $template->template_html = $data['template_html'] ?? $template->template_html;
        $template->updated_by = auth()->id();

        if ($template->status === 'published') {
            $template->published_scope_key = $validationService->buildPublishedScopeKey([
                'contract_type' => $template->contract_type,
                'payment_type' => $template->payment_type,
                'entity_type_id' => $template->entity_type_id,
            ]);
        } else {
            $template->published_scope_key = null;
        }

        $template->save();

        if ($uploadedDocx) {
            $variableService->syncPlaceholders($template);
        }

        return redirect()->route('agreement-templates.edit', $template->id)
            ->with('success', 'Agreement template updated successfully.');
    }

    public function syncPlaceholders(Request $request, $id)
    {
        $template = AgreementTemplate::findOrFail($id);
        $variableService = app(AgreementTemplateVariableService::class);
        $result = $variableService->syncPlaceholders($template);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', 'Placeholders synced (' . ($result['token_count'] ?? 0) . ' tokens found).');
    }

    public function updateVariables(Request $request, $id)
    {
        $template = AgreementTemplate::findOrFail($id);
        $variableService = app(AgreementTemplateVariableService::class);
        $variableService->updateVariables(
            $template,
            $request->input('required', []),
            $request->input('default_value', [])
        );

        return redirect()->back()->with('success', 'Variables updated successfully.');
    }

    public function preview(Request $request, $id)
    {
        $template = AgreementTemplate::findOrFail($id);

        $values = [];
        if ($request->filled('values_json')) {
            $decoded = json_decode($request->input('values_json'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->with('error', 'Preview values JSON is invalid.');
            }
            $values = is_array($decoded) ? $decoded : [];
        }

        $renderService = app(AgreementTemplateRenderService::class);
        $renderResult = $renderService->renderPreview($template, $values);
        if (! $renderResult['success']) {
            return redirect()->back()->with('error', $renderResult['message']);
        }

        return Storage::disk('local')->download($renderResult['pdf_path'], $renderResult['pdf_name']);
    }

    public function publish(Request $request, $id)
    {
        $template = AgreementTemplate::findOrFail($id);

        $validationService = app(AgreementTemplateValidationService::class);
        $publishErrors = $validationService->canPublish($template);
        if (! empty($publishErrors)) {
            return redirect()->back()->with('error', implode(' ', $publishErrors));
        }

        // Archive existing published templates for same scope
        AgreementTemplate::where('id', '!=', $template->id)
            ->where('status', 'published')
            ->where('contract_type', $template->contract_type)
            ->where('payment_type', $template->payment_type)
            ->where('entity_type_id', $template->entity_type_id)
            ->update(['status' => 'archived', 'published_scope_key' => null]);

        $template->status = 'published';
        $template->published_scope_key = $validationService->buildPublishedScopeKey([
            'contract_type' => $template->contract_type,
            'payment_type' => $template->payment_type,
            'entity_type_id' => $template->entity_type_id,
        ]);
        $template->updated_by = auth()->id();
        $template->save();

        return redirect()->back()->with('success', 'Template published successfully.');
    }

    public function download($id)
    {
        $template = AgreementTemplate::findOrFail($id);
        $storageService = app(AgreementTemplateStorageService::class);
        $response = $storageService->downloadDocx($template);

        if (! $response) {
            return redirect()->back()->with('error', 'No DOCX file available or file not found.');
        }
        return $response;
    }

    public function destroy($id)
    {
        $template = AgreementTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('agreement-templates.index')
            ->with('success', 'Agreement template deleted successfully.');
    }

    /**
     * Strip comment references from a DOCX file to prevent PhpWord reader errors.
     * DOCX files are ZIP archives containing XML. Comment references in document.xml
     * that point to non-existent comment IDs cause PhpWord to throw type errors.
     *
     * @return string Path to cleaned DOCX (same as input if no cleaning needed)
     */
    private function stripDocxComments(string $docxPath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath, \ZipArchive::RDONLY) !== true) {
            return $docxPath;
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            return $docxPath;
        }

        $hasCommentRefs = preg_match('/w:commentReference|w:commentRangeStart|w:commentRangeEnd/', $documentXml);
        $hasCommentsXml = $zip->getFromName('word/comments.xml') !== false;
        $hasCommentsExtendedXml = $zip->getFromName('word/commentsExtended.xml') !== false;
        $hasCommentsIdsXml = $zip->getFromName('word/commentsIds.xml') !== false;

        if (!$hasCommentRefs && !$hasCommentsXml && !$hasCommentsExtendedXml && !$hasCommentsIdsXml) {
            $zip->close();
            return $docxPath;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'docx_clean_') . '.docx';
        copy($docxPath, $tmpPath);

        $outZip = new \ZipArchive();
        if ($outZip->open($tmpPath, \ZipArchive::CREATE) !== true) {
            return $docxPath;
        }

        $cleanXml = $documentXml;
        $cleanXml = preg_replace('/<w:commentReference[^>]*\/?>/', '', $cleanXml);
        $cleanXml = preg_replace('/<w:commentRangeStart[^>]*\/?>/', '', $cleanXml);
        $cleanXml = preg_replace('/<w:commentRangeEnd[^>]*\/?>/', '', $cleanXml);

        $outZip->addFromString('word/document.xml', $cleanXml);

        if ($outZip->deleteName('word/comments.xml') === false) {
        }
        if ($outZip->deleteName('word/commentsExtended.xml') === false) {
        }
        if ($outZip->deleteName('word/commentsIds.xml') === false) {
        }

        $relsXml = $outZip->getFromName('word/_rels/document.xml.rels');
        if ($relsXml !== false) {
            $cleanRels = preg_replace('/<Relationship[^>]*Type="http:\/\/schemas\.openxmlformats\.org\/officeDocument\/2006\/relationships\/comments"[^>]*\/?>/', '', $relsXml);
            $cleanRels = preg_replace('/<Relationship[^>]*Type="http:\/\/schemas\.microsoft\.com\/office\/2016\/officeDocument\/relationships\/commentsExtended"[^>]*\/?>/', '', $cleanRels);
            $cleanRels = preg_replace('/<Relationship[^>]*Type="http:\/\/schemas\.microsoft\.com\/office\/2016\/officeDocument\/relationships\/commentsIds"[^>]*\/?>/', '', $cleanRels);
            $outZip->addFromString('word/_rels/document.xml.rels', $cleanRels);
        }

        $contentTypesXml = $outZip->getFromName('[Content_Types].xml');
        if ($contentTypesXml !== false) {
            $cleanTypes = preg_replace('/<Override[^>]*PartName="\/word\/comments\.xml"[^>]*\/?>/', '', $contentTypesXml);
            $cleanTypes = preg_replace('/<Override[^>]*PartName="\/word\/commentsExtended\.xml"[^>]*\/?>/', '', $cleanTypes);
            $cleanTypes = preg_replace('/<Override[^>]*PartName="\/word\/commentsIds\.xml"[^>]*\/?>/', '', $cleanTypes);
            $outZip->addFromString('[Content_Types].xml', $cleanTypes);
        }

        $outZip->close();
        return $tmpPath;
    }

    /**
     * API endpoint: Resolve agreement template for contract creation preview.
     * Returns the DOCX file as a PDF preview or download.
     */
    public function resolveForContract(Request $request)
    {
        $contractType = $request->input('contracttype', 0);
        $paymentType = $request->input('payment_type');
        $entityTypeId = $request->input('entity_type_id');

        $resolver = app(AgreementTemplateSourceResolver::class);
        $template = $resolver->resolvePublishedTemplate($contractType, $paymentType, $entityTypeId);

        if (! $template) {
            return response()->json(['found' => false, 'message' => 'No agreement template found for this scope.']);
        }

        // Return template info so JS can show preview
        return response()->json([
            'found' => true,
            'template_id' => $template->id,
            'template_name' => $template->template_name,
            'has_docx' => ! empty($template->source_docx_path),
            'download_url' => route('agreement-templates.download', $template->id),
            'preview_url' => route('agreement-templates.contract-preview', $template->id),
            'preview_download_url' => route('agreement-templates.contract-preview-download', $template->id),
        ]);
    }

    /**
     * Serve the agreement template DOCX converted to PDF for inline preview.
     */
    public function contractPreview($id)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $template = AgreementTemplate::findOrFail($id);

        if (empty($template->source_docx_path)) {
            abort(404, 'No DOCX file available.');
        }

        $docxPath = Storage::disk('local')->path($template->source_docx_path);
        if (! file_exists($docxPath)) {
            abort(404, 'DOCX file not found.');
        }

        try {
            $cleanDocxPath = $this->stripDocxComments($docxPath);

            $phpWord = \PhpOffice\PhpWord\IOFactory::load($cleanDocxPath);
            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

            ob_start();
            $htmlWriter->save('php://output');
            $html = ob_get_clean();

            if ($cleanDocxPath !== $docxPath && file_exists($cleanDocxPath)) {
                @unlink($cleanDocxPath);
            }

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('A4');
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('isHtml5ParserEnabled', true);

            return $pdf->stream($template->source_docx_filename . '.pdf', ['Attachment' => false]);
        } catch (\Throwable $e) {
            \Log::error('Agreement template preview error: ' . $e->getMessage());
            abort(500, 'Failed to generate preview: ' . $e->getMessage());
        }
    }

    /**
     * Download the agreement template DOCX file directly.
     */
    public function contractPreviewDownload($id)
    {
        $template = AgreementTemplate::findOrFail($id);

        if (empty($template->source_docx_path)) {
            abort(404, 'No DOCX file available.');
        }

        $docxPath = Storage::disk('local')->path($template->source_docx_path);
        if (! file_exists($docxPath)) {
            abort(404, 'DOCX file not found.');
        }

        return response()->download($docxPath, $template->source_docx_filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
