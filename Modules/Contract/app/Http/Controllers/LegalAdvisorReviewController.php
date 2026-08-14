<?php

namespace Modules\Contract\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AddUsers;
use App\Models\Contract;
use App\Models\LegalAdvisor;
use App\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LegalAdvisorReviewController extends Controller
{
    public function __construct()
    {
        if (Controller::checkCurrentAuth('Contracts') != 1) {
            abort(404);
        }
    }

    public function show(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);
        
        if (isset($contract->contract_type)) {
            $contract->contract_type_id = $contract->contract_type;
            $contract->contract_type = ContractType::where('contract_type_id', $contract->contract_type)->first()->contract_type;
        }        

        $advisor = LegalAdvisor::where('id', $contract->legal_advisor_id)->first();
        $assignedEmail = strtolower((string) ($contract->legal_advisor_email ?? optional($advisor)->email_id ?? ''));
        $currentUserEmail = strtolower(optional(Helpers::userInfo())->email ?? '');
        if ($currentUserEmail === '' || $assignedEmail === '' || $currentUserEmail !== $assignedEmail) {
            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', 'Only the assigned legal advisor can access this page.');
        }

        $legalRequest = [
            'requested_by_name' => $this->safeDecrypt($contract->legal_requested_by_name, 'legal_requested_by_name'),
            'requested_by_email' => $this->safeDecrypt($contract->legal_requested_by_email, 'legal_requested_by_email'),
            'requested_at' => $contract->legal_requested_at,
            'request_comment' => $this->safeDecrypt($contract->legal_contact_comment, 'legal_contact_comment'),
            'response_comment' => $this->safeDecrypt($contract->legal_response_comment, 'legal_response_comment'),
            'responded_by_name' => $this->safeDecrypt($contract->legal_responded_by_name, 'legal_responded_by_name'),
            'responded_by_email' => $this->safeDecrypt($contract->legal_responded_by_email, 'legal_responded_by_email'),
            'responded_at' => $contract->legal_responded_at,
            'status' => $contract->legal_contact_status ?: 'not_contacted',
        ];

        $attachmentUrl = !empty($contract->contract_attachment)
            ? attachmentDummyUrl($contract->contract_attachment, true, $contract->id)
            : null;

        return view('contract::legal-review.show', compact('contract', 'advisor', 'attachmentUrl', 'legalRequest'));
    }

    public function contactLegal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'legal_advisor_id' => 'required|integer|exists:legal_advisors,id',
            'comment' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $contract = Contract::findOrFail($id);
            if (strtolower((string) $contract->contract_status) !== 'draft') {
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', 'Contact Legal is allowed only for Draft contracts.');
            }

            $advisor = LegalAdvisor::where('id', (int) $request->input('legal_advisor_id'))
                ->where('status', 1)
                ->first();

            if (!$advisor) {
                return redirect()->back()->with('error', 'Selected legal advisor is inactive or invalid.')->withInput();
            }

            $comment = trim((string) $request->input('comment'));
            $userInfo = Helpers::userInfo();
            $requesterEmail = (string) ($userInfo->email ?? '');
            $requesterName = trim((string) (($userInfo->first_name ?? '') . ' ' . ($userInfo->last_name ?? '')));
            if ($requesterName === '') {
                $requesterName = (string) ($userInfo->name ?? $requesterEmail);
            }

            $alreadyContacted = !empty($contract->legal_requested_at);

            $contract->legal_advisor_id = $advisor->id;
            $contract->legal_advisor_email = $advisor->email_id;
            $contract->legal_contact_comment = $this->safeEncrypt($comment, 'legal_contact_comment');
            $contract->legal_requested_by_name = $this->safeEncrypt($requesterName, 'legal_requested_by_name');
            $contract->legal_requested_by_email = $this->safeEncrypt($requesterEmail, 'legal_requested_by_email');
            $contract->legal_requested_at = now();
            $contract->legal_response_comment = null;
            $contract->legal_responded_by_name = null;
            $contract->legal_responded_by_email = null;
            $contract->legal_responded_at = null;
            $contract->legal_contact_status = 'contacted';
            $contract->save();

            $mailer = new ContractNotificationController();
            $legalViewLink = route('contracts.legal.view', ['id' => $contract->id]);
            $description = 'Contract ' . ($contract->contract_unique_id ?: $contract->id)
                . ' has been shared for legal information/advice.'
                . ' <br/>Comment: ' . $comment
                . ' <br/>Requested from: ' . $requesterName . ' (' . $requesterEmail . ')'
                . ' <br/>Review link: ' . $legalViewLink;

            $mailer->sendEmail(
                $contract->id,
                $description,
                'Legal Information Sharing Request',
                $advisor->email_id,
                'Information Request',
                [],
                [],
                'notiMail'
            );

            $message = $alreadyContacted
                ? 'Legal information request updated and shared successfully.'
                : 'Legal information request shared successfully.';

            return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to contact legal advisor: ' . $e->getMessage());
        }
    }

    public function submitLegalAdvice(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'response_comment' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $contract = Contract::findOrFail($id);
            $advisor = LegalAdvisor::where('id', $contract->legal_advisor_id)->first();
            $assignedEmail = strtolower((string) ($contract->legal_advisor_email ?? optional($advisor)->email_id ?? ''));
            $currentUserEmail = strtolower((string) (optional(Helpers::userInfo())->email ?? ''));

            if ($assignedEmail === '' || $currentUserEmail === '' || $currentUserEmail !== $assignedEmail) {
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', 'Only the assigned legal advisor can submit legal advice.');
            }

            if (empty($contract->legal_requested_at)) {
                return redirect()->route('viewContract', ['id' => $id, 'tab' => 'timeline'])->with('error', 'Legal information request does not exist for this contract.');
            }

            $responseComment = trim((string) $request->input('response_comment'));
            $advisorName = (string) (optional($advisor)->name ?? (optional(Helpers::userInfo())->name ?? 'Legal Advisor'));
            $advisorEmail = (string) ($assignedEmail ?: optional($advisor)->email_id);

            $contract->legal_response_comment = $this->safeEncrypt($responseComment, 'legal_response_comment');
            $contract->legal_responded_by_name = $this->safeEncrypt($advisorName, 'legal_responded_by_name');
            $contract->legal_responded_by_email = $this->safeEncrypt($advisorEmail, 'legal_responded_by_email');
            $contract->legal_responded_at = now();
            $contract->legal_contact_status = 'responded';
            $contract->save();

            $requesterEmail = $this->safeDecrypt($contract->legal_requested_by_email, 'legal_requested_by_email');
            $requesterName = $this->safeDecrypt($contract->legal_requested_by_name, 'legal_requested_by_name');

            if ($requesterEmail === '') {
                $owner = AddUsers::select('id', decrypt_data('Email', 'AddUsers'))
                    ->where('id', $contract->owner)
                    ->first();
                $requesterEmail = (string) ($owner->Email ?? '');
            }

            if ($requesterEmail !== '') {
                $mailer = new ContractNotificationController();
                $description = 'Legal advice has been shared for contract '
                    . ($contract->contract_unique_id ?: $contract->id)
                    . '.<br/> Advisor: ' . $advisorName . ' (' . $advisorEmail . ')'
                    . '.<br/> Advice: ' . $responseComment;

                $mailer->sendEmail(
                    $contract->id,
                    $description,
                    'Legal Advice Response',
                    $requesterEmail,
                    'Advice Shared',
                    [],
                    [],
                    'notiMail'
                );
            }

            return redirect()->route('contracts.legal.view', ['id' => $id])
                ->with('success', 'Legal advice submitted successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to submit legal advice: ' . $e->getMessage());
        }
    }

    protected function safeDecrypt($value, string $field): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (string) decryptString($value, $field);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    protected function safeEncrypt($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return encryptString((string) $value, $field);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
