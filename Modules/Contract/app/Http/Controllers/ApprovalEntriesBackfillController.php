<?php

namespace Modules\Contract\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AddUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApprovalEntriesBackfillController extends Controller
{
    private $backfillService;

    public function __construct()
    {
        if (Controller::checkCurrentAuth('Contracts') != 1) {
            abort(404);
        }

        $this->backfillService = app('Modules\\Contract\\Services\\ApprovalEntriesBackfillService');
    }

    public function index(Request $request)
    {
        if (! $this->isAdminUser()) {
            return redirect('/noaccess')->with('message', 'Access denied')->with('alert-class', 'alert-danger');
        }

        $locationId = (int) $request->query('location_id', 0);

        $rows = $this->backfillService->getMissingExecutedContracts([
            'location_id' => $locationId,
        ]);
        $locationOptions = $this->backfillService->getMissingExecutedLocationOptions();

        return view('contract::contract.approvalEntriesBackfill', compact('rows', 'locationOptions', 'locationId'));
    }

    public function previewOne(int $contractId)
    {
        if (! $this->isAdminUser()) {
            return response()->json(['status' => false, 'message' => 'Access denied'], 403);
        }

        $actorId = $this->resolveActorId();
        if (! $actorId) {
            return response()->json(['status' => false, 'message' => 'Unable to resolve current user'], 422);
        }

        $preview = $this->backfillService->buildPreviewForContracts([$contractId], $actorId);

        return response()->json([
            'status' => true,
            'message' => 'Backfill preview generated',
            'data' => $preview,
        ]);
    }

    public function previewSelected(Request $request)
    {
        if (! $this->isAdminUser()) {
            return response()->json(['status' => false, 'message' => 'Access denied'], 403);
        }

        $actorId = $this->resolveActorId();
        if (! $actorId) {
            return response()->json(['status' => false, 'message' => 'Unable to resolve current user'], 422);
        }

        $ids = array_values(array_filter(array_map('intval', (array) $request->input('contract_ids', []))));
        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'Please select at least one contract'], 422);
        }

        $preview = $this->backfillService->buildPreviewForContracts($ids, $actorId);

        return response()->json([
            'status' => true,
            'message' => 'Backfill preview generated',
            'data' => $preview,
        ]);
    }

    public function previewAll()
    {
        if (! $this->isAdminUser()) {
            return response()->json(['status' => false, 'message' => 'Access denied'], 403);
        }

        $actorId = $this->resolveActorId();
        if (! $actorId) {
            return response()->json(['status' => false, 'message' => 'Unable to resolve current user'], 422);
        }

        $ids = $this->backfillService->getMissingExecutedContractIds();
        if (empty($ids)) {
            return response()->json([
                'status' => true,
                'message' => 'No missing contracts found',
                'data' => [
                    'items' => [],
                    'errors' => [],
                    'preview_token' => '',
                    'summary' => ['total' => 0, 'ok' => 0, 'warnings' => 0, 'failed' => 0],
                ],
            ]);
        }

        $preview = $this->backfillService->buildPreviewForContracts($ids, $actorId);

        return response()->json([
            'status' => true,
            'message' => 'Backfill preview generated',
            'data' => $preview,
        ]);
    }

    public function insertOne(Request $request, int $contractId)
    {
        if (! $this->isAdminUser()) {
            return redirect('/noaccess')->with('message', 'Access denied')->with('alert-class', 'alert-danger');
        }

        $actorId = $this->resolveActorId();
        if (! $actorId) {
            return redirect()->back()->with('message', 'Unable to resolve current user')->with('alert-class', 'alert-danger');
        }

        $tokenValidation = $this->backfillService->validatePreviewTokenForContracts(
            (string) $request->input('preview_token', ''),
            [$contractId],
            $actorId
        );
        if (! ($tokenValidation['ok'] ?? false)) {
            return redirect()->back()->with('message', (string) ($tokenValidation['message'] ?? 'Please preview before insert.'))->with('alert-class', 'alert-danger');
        }

        $summary = $this->backfillService->insertForContract($contractId, $actorId);
        Log::info('Approval entries backfill single insert', ['contract_id' => $contractId, 'summary' => $summary, 'actor_id' => $actorId]);

        return redirect()->back()
            ->with('message', $this->buildSummaryMessage($summary))
            ->with('alert-class', ($summary['failed'] ?? 0) > 0 ? 'alert-danger' : 'alert-success')
            ->with('backfill_summary', $summary);
    }

    public function insertSelected(Request $request)
    {
        if (! $this->isAdminUser()) {
            return redirect('/noaccess')->with('message', 'Access denied')->with('alert-class', 'alert-danger');
        }

        $actorId = $this->resolveActorId();
        if (! $actorId) {
            return redirect()->back()->with('message', 'Unable to resolve current user')->with('alert-class', 'alert-danger');
        }

        $ids = array_values(array_filter(array_map('intval', (array) $request->input('contract_ids', []))));
        if (empty($ids)) {
            return redirect()->back()->with('message', 'Please select at least one contract')->with('alert-class', 'alert-warning');
        }

        $tokenValidation = $this->backfillService->validatePreviewTokenForContracts(
            (string) $request->input('preview_token', ''),
            $ids,
            $actorId
        );
        if (! ($tokenValidation['ok'] ?? false)) {
            return redirect()->back()->with('message', (string) ($tokenValidation['message'] ?? 'Please preview before insert.'))->with('alert-class', 'alert-danger');
        }

        $summary = $this->backfillService->insertForContracts($ids, $actorId);
        Log::info('Approval entries backfill selected insert', ['contract_ids' => $ids, 'summary' => $summary, 'actor_id' => $actorId]);

        return redirect()->back()
            ->with('message', $this->buildSummaryMessage($summary))
            ->with('alert-class', ($summary['failed'] ?? 0) > 0 ? 'alert-danger' : 'alert-success')
            ->with('backfill_summary', $summary);
    }

    public function insertAll(Request $request)
    {
        if (! $this->isAdminUser()) {
            return redirect('/noaccess')->with('message', 'Access denied')->with('alert-class', 'alert-danger');
        }

        $actorId = $this->resolveActorId();
        if (! $actorId) {
            return redirect()->back()->with('message', 'Unable to resolve current user')->with('alert-class', 'alert-danger');
        }

        $ids = $this->backfillService->getMissingExecutedContractIds();
        if (empty($ids)) {
            return redirect()->back()->with('message', 'No missing contracts found')->with('alert-class', 'alert-warning');
        }

        $tokenValidation = $this->backfillService->validatePreviewTokenForContracts(
            (string) $request->input('preview_token', ''),
            $ids,
            $actorId
        );
        if (! ($tokenValidation['ok'] ?? false)) {
            return redirect()->back()->with('message', (string) ($tokenValidation['message'] ?? 'Please preview before insert.'))->with('alert-class', 'alert-danger');
        }

        $summary = $this->backfillService->insertForContracts($ids, $actorId);
        Log::info('Approval entries backfill insert all', ['summary' => $summary, 'actor_id' => $actorId]);

        return redirect()->back()
            ->with('message', $this->buildSummaryMessage($summary))
            ->with('alert-class', ($summary['failed'] ?? 0) > 0 ? 'alert-danger' : 'alert-success')
            ->with('backfill_summary', $summary);
    }

    private function isAdminUser(): bool
    {
        $role = strtolower((string) session()->get('contractSessionUserRole', ''));
        $isRoleAdmin = in_array($role, ['admin', 'super admin', 'super-admin'], true);
        $isRootAdmin = strtolower((string) (optional(Helpers::userInfo())->email ?? '')) === 'admin@legalitysimplified.com';

        return $isRoleAdmin || $isRootAdmin;
    }

    private function resolveActorId(): ?int
    {
        $sessionUser = session()->get('contractSessionUser');
        if ($sessionUser) {
            $row = AddUsers::select('id')
                ->where(decrypt_datas('UserName', 'AddUsers'), $sessionUser)
                ->first();
            if ($row) {
                return (int) $row->id;
            }
        }

        $email = optional(Helpers::userInfo())->email;
        if ($email) {
            $row = AddUsers::select('id')
                ->where(decrypt_datas('Email', 'AddUsers'), $email)
                ->first();
            if ($row) {
                return (int) $row->id;
            }
        }

        return null;
    }

    private function buildSummaryMessage(array $summary): string
    {
        $inserted = (int) ($summary['inserted'] ?? 0);
        $skipped = (int) ($summary['skipped'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);
        return "Backfill completed. Inserted: {$inserted}, Skipped: {$skipped}, Failed: {$failed}";
    }
}
