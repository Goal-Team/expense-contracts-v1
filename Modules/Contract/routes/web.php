<?php

use Illuminate\Support\Facades\Route;
use Modules\Contract\Http\Controllers\ContractController;
use Modules\Contract\Http\Controllers\ContractCustomController;
use Modules\Contract\Http\Controllers\GoogleDriveController;
use Modules\Contract\Http\Controllers\ContractImportController;
use Modules\Contract\Http\Controllers\ContractDashboardController;
use Modules\Contract\Http\Controllers\ContractOptionListController;
use Modules\Contract\Http\Controllers\ContractExportController;
use Modules\Contract\Http\Controllers\ContractReportsController;
use Modules\Contract\Http\Controllers\AdminConfigSettingsController;
use Modules\Contract\Http\Controllers\EmailTemplateController;
use Modules\Contract\Http\Controllers\LegalAdvisorController;
use Modules\Contract\Http\Controllers\LegalAdvisorReviewController;
use Modules\Contract\Http\Controllers\LegalDashboardController;
use Modules\Contract\Http\Controllers\AgreementTemplateController;
use Modules\Contract\Http\Controllers\ApprovalEntriesBackfillController;
use App\Http\Controllers\CronController;

use Modules\Contract\Http\Controllers\EsignApiController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('noaccess', [ContractController::class, 'noAccess']);

Route::view('/upgrade', 'noaccess.upgrade');
Route::view('/storage/error', 'noaccess.storageTokenExpire');
Route::get('invalidfile', [ContractController::class, 'invalidFileAccess']);
Route::get('misconfig', [ContractController::class, 'misConfigAction']);
Route::get('contracts/external/emailDeliveryStatus/{emailRequestToken}', [ContractController::class, 'emailOpenerActions']);

Route::middleware(['xssprotect','contractauth'])->group(function () {

// Agreement Templates
Route::get('agreement-templates', [AgreementTemplateController::class, 'index'])->name('agreement-templates.index');
Route::get('agreement-templates/create', [AgreementTemplateController::class, 'create'])->name('agreement-templates.create');
Route::post('agreement-templates', [AgreementTemplateController::class, 'store'])->name('agreement-templates.store');
Route::get('agreement-templates/{id}/edit', [AgreementTemplateController::class, 'edit'])->name('agreement-templates.edit');
Route::put('agreement-templates/{id}', [AgreementTemplateController::class, 'update'])->name('agreement-templates.update');
Route::delete('agreement-templates/{id}', [AgreementTemplateController::class, 'destroy'])->name('agreement-templates.destroy');
Route::get('agreement-templates/{id}/download', [AgreementTemplateController::class, 'download'])->name('agreement-templates.download');
Route::post('agreement-templates/{id}/sync-placeholders', [AgreementTemplateController::class, 'syncPlaceholders'])->name('agreement-templates.sync-placeholders');
Route::post('agreement-templates/{id}/variables', [AgreementTemplateController::class, 'updateVariables'])->name('agreement-templates.variables.update');
Route::post('agreement-templates/{id}/preview', [AgreementTemplateController::class, 'preview'])->name('agreement-templates.preview');
Route::post('agreement-templates/{id}/publish', [AgreementTemplateController::class, 'publish'])->name('agreement-templates.publish');
Route::post('agreement-templates/resolve-for-contract', [AgreementTemplateController::class, 'resolveForContract'])->name('agreement-templates.resolve-for-contract');
Route::get('agreement-templates/{id}/contract-preview', [AgreementTemplateController::class, 'contractPreview'])->name('agreement-templates.contract-preview');
Route::get('agreement-templates/{id}/contract-preview-download', [AgreementTemplateController::class, 'contractPreviewDownload'])->name('agreement-templates.contract-preview-download');

Route::get('contracts/builk-export', [ContractExportController::class, 'contractBuilkImport'])->name('contractBuilkImport');

Route::get('contracts/contract-request', [ContractController::class, 'contractRequest'])->name('contractRequest');

Route::post('contracts/builk-export-download', [ContractExportController::class, 'bulkDownload'])->name('bulkDownload');

Route::post('/contracts/builk-import/upload', [ContractImportController::class, 'uploadFile'])->name('upload');

Route::post('/contracts/builk-import/checkduplicates', [ContractImportController::class, 'checkImportDuplicates'])->name('checkImportDuplicates');

Route::post('/contracts/updateflow', [ContractController::class, 'updateflow'])->name('updateflow');

Route::get('/contracts/dashboard', [ContractDashboardController::class, 'index']);

Route::get('/contracts/legal/dashboard', [LegalDashboardController::class, 'index'])->name('legal.dashboard');
Route::post('/contracts/legal/dashboard/filter', [LegalDashboardController::class, 'getFilteredContracts'])->name('legal.dashboard.filter');
Route::get('/contracts/legal/{contractId}/view', [LegalDashboardController::class, 'viewContract'])->name('legal.contract.view');

Route::get('/contracts/reports', [ContractReportsController::class, 'statusReports'])->name('contractReports');

Route::get('/contracts/reports/imported', [ContractReportsController::class, 'uploadedReports'])->name('uploadedReports');

Route::get('/contracts/reports/{executed}', [ContractReportsController::class, 'statusReports'])->name('contractReportsExecuted');

Route::get('/contracts/reports-expired', [ContractReportsController::class, 'statusReportsExpired'])->name('contractReportsExpired');

Route::post('/contracts/reports-status-data', [ContractReportsController::class, 'statusReportsData']);

Route::post('/contracts/reports-expired-data', [ContractReportsController::class, 'statusReportsExpired']);

Route::get('/contracts/reports-contract-types', [ContractReportsController::class, 'contractTypeReports'])->name('contractReportsType');

// Export location-wise contract type counts (Excel/CSV)
Route::post('/contracts/reports/export-location-type', [ContractReportsController::class, 'exportLocationTypeCounts'])->name('contractReports.exportLocationType');

// Export contract type vs substatus counts (Excel)
Route::post('/contracts/reports/export-type-substatus', [ContractReportsController::class, 'exportContractTypeSubstatusCounts'])->name('contractReports.exportTypeSubstatus');

Route::get('/contracts/reports-contract-tags', [ContractReportsController::class, 'contractTagsReports'])->name('contractReportsTags');

Route::get('/contracts/reports-contract-depts', [ContractReportsController::class, 'contractDeptReports'])->name('contractReportsDept');

Route::get('/contracts/reports-contract-value', [ContractReportsController::class, 'contractValueReports'])->name('contractReportsValue');

Route::get('/contracts/reports-contract-value-tree', [ContractReportsController::class, 'contractValueReportsTree'])->name('contractReportsValueTree');

Route::get('/contracts/reports-exceptions', [ContractReportsController::class, 'listContractException'])->name('contractReportsExcep');

Route::get('/contracts/reports-party-types', [ContractReportsController::class, 'contractPartyTypeReports'])->name('contractReportsPartyType');

Route::post('/contracts/reports-location-data', [ContractReportsController::class, 'contractLocationReportData']);

Route::post('/contracts/reports-location-tags-data', [ContractReportsController::class, 'contractLocationReportTagsData']);

Route::post('/contracts/reports-location-value-data', [ContractReportsController::class, 'contractLocationReportValueData']);

Route::post('/contracts/reports-exceptions-data', [ContractReportsController::class, 'listContractException']);

Route::post('/contracts/reports-contract-depts-data', [ContractReportsController::class, 'contractDeptReports']);

Route::post('/contracts/reports-contract-detail-data', [ContractReportsController::class, 'contractDetailReports']);

Route::get('/contracts/reports-contract-clauses', [ContractReportsController::class, 'contractClauseReports'])->name('contractClauseReports');

Route::post('/contracts/reports-clauses-data', [ContractReportsController::class, 'contractClauseData']);

Route::post('contracts/update/contract',[ContractController::class,'updateContract'])->name('updateContract');

Route::get('contracts/builk-import', [ContractImportController::class, 'contractBuilkImport'])->name('contractBuilkImport');

Route::post('contracts/template-download', [ContractImportController::class, 'templateDownload'])->name('templateDownload');

Route::get('contracts/create', [ContractController::class, 'contractCreate'])->name('contractCreate');

// Optimised rebuild of the AI create page. Runs in parallel with contracts/create
// so users stay on the existing page until this one is signed off.
Route::get('contracts/create-v2', [ContractController::class, 'contractCreateV2'])->name('contractCreateV2');

// Contract Create V3: adds tenure, price revision terms, per-party vendor/address/contact
// and annexure uploads. Runs alongside contracts/create, which is unchanged.
Route::get('contracts/create-v3', [ContractController::class, 'contractCreateV3'])->name('contractCreateV3');
Route::post('contracts/store/contract-v3', [ContractController::class, 'storeContractV3'])->name('storeContractV3');
Route::post('contracts/create/parties-v3', [ContractController::class, 'contractCreatepartiesV3'])->name('contractCreatepartiesV3');

Route::get('contracts/ai/{aiparam}', [ContractController::class, 'contractCreate'])->name('contractCreate');

Route::post('contracts/aidata', [ContractController::class, 'aiDocumentDataInterpreter'])->name('ontrackAiReader');

Route::post('contracts/aidata/riskanalysis', [ContractController::class, 'aiDocumentAnalyser'])->name('ontrackAIDocAnalyzer');

Route::post('contracts/aidata/chatbot', [ContractController::class, 'aiDocumentChatBott'])->name('ontrackAIChatBot');

Route::get('legal-advisors', [LegalAdvisorController::class, 'index'])->name('legal-advisors.index');
Route::post('legal-advisors', [LegalAdvisorController::class, 'store'])->name('legal-advisors.store');
Route::put('legal-advisors/{id}', [LegalAdvisorController::class, 'update'])->name('legal-advisors.update');
Route::patch('legal-advisors/{id}/status', [LegalAdvisorController::class, 'updateStatus'])->name('legal-advisors.status');

Route::get('contracts/{id}/legal-review', [LegalAdvisorReviewController::class, 'show'])->name('contracts.legal.review');
Route::get('contracts/{id}/legal/view', [LegalAdvisorReviewController::class, 'show'])->name('contracts.legal.view');
Route::post('contracts/{id}/legal/contact', [LegalAdvisorReviewController::class, 'contactLegal'])->name('contracts.legal.contact');
Route::post('contracts/{id}/legal/respond', [LegalAdvisorReviewController::class, 'submitLegalAdvice'])->name('contracts.legal.respond');
Route::post('contracts/{id}/legal-review/action', [LegalAdvisorReviewController::class, 'submitLegalAdvice'])->name('contracts.legal.review.action');

Route::get('contracts/list', [ContractController::class, 'listContract'])->name('listContract');

Route::post('getSignatory', [ContractController::class, 'getSignatoryApprovalRules'])->name('getSignatoryApprovalRules');

// The dashboard. dashboardSummary() took over these two URLs on 2026-08-21, spec.md section 10
// step 11: dashDetails() is deleted, and the temporary 'dashboard-summary' pair is gone with it.
// The URLs are the original ones, so bookmarks, the menu_configs "url": "" entry and its
// "slug": "contractDashboard" all keep working unchanged.
//
// The POST now has its own name. The two routes shared the name 'contractDashboard' before, so
// route('contractDashboard') resolved to whichever Laravel registered last - a latent bug noted in
// names.md section 1, fixed here because the rename had to happen anyway.
Route::get('', [ContractDashboardController::class, 'dashboardSummary'])->name('contractDashboard');
Route::post('filterDash', [ContractDashboardController::class, 'dashboardSummary'])->name('contractDashboard.filter');

// Shared dropdown option lists, consumed by the dashboard now and the contract list later
// (spec.md section 8, names.md section 5). GET because it only reads and is cacheable.
Route::get('contracts/option-lists', [ContractOptionListController::class, 'optionLists'])->name('contractOptionLists');

// Location-wise contract status report (Active / Expired / Going-to-Expire)
Route::get('/contracts/dashboard/location-status', [ContractDashboardController::class, 'locationStatusReport'])->name('dashboard.locationStatus');
Route::post('/contracts/dashboard/location-status', [ContractDashboardController::class, 'locationStatusReport'])->name('dashboard.locationStatus.filter');
Route::post('/contracts/dashboard/location-status/export', [ContractDashboardController::class, 'exportLocationStatusReport'])->name('dashboard.locationStatus.export');

Route::post('contracts/data', [ContractController::class, 'listContractData'])->name('listContractData');

Route::get('/party', [ContractController::class, 'indexParty'])->name('indexParty');
Route::post('/party/list', [ContractController::class, 'indexPartyList'])->name('indexPartyList');

Route::post('contracts/create/parties', [ContractController::class, 'contractCreateparties'])->name('contractCreateparties');

Route::post('contracts/create/partylist', [ContractController::class, 'contractCreatePartyList'])->name('contractCreatePartyList');

// One party's address block, fetched when a party is picked on the create pages. They used to
// render every party's address hidden in the page - 7.6 MB of an 8.9 MB document.
Route::get('contracts/create/party-address', [ContractController::class, 'contractPartyAddress'])->name('contractPartyAddress');

// Cached party lookup used by the V2 create page.
Route::post('contracts/create/partylist-v2', [ContractController::class, 'contractCreatePartyListV2'])->name('contractCreatePartyListV2');

Route::post('contracts/updateApprovals', [ContractController::class, 'contractApprovals'])->name('contractApprovals');

Route::post('contracts/store/contract',[ContractController::class,'storeContract'])->name('storeContract');

Route::post('contracts/sendContractForReview/', [ContractController::class, 'sendContractForReview'])->name('sendContractForReview');

Route::post('contracts/approval/{id}/complete-sign', [ContractController::class, 'completeSignUpload']);

Route::get('/contracts/import', [ContractController::class, 'contractImport'])->name('contractImport');

Route::post('/contracts/import-store', [ContractController::class, 'contractImportStore'])->name('contractImportStore');

Route::get('/contracts/delete/{id}', [ContractController::class, 'deleteContract'])->name('deleteContract');

Route::get('/contracts/export-users', [ContractImportController::class, 'export']);

// New approval flow routes
Route::get('contracts/approval/flow/{id}', [ContractController::class, 'approvalFlow'])->name('contracts.approval.flow');
Route::post('contracts/approval/respond/{id}/{approvalId}', [ContractController::class, 'approvalRespond'])->name('contracts.approval.respond.new');
Route::post('contracts/approval/{id}/advance-next', [ContractController::class, 'advanceNextApprovalGroup'])->name('contracts.approval.advance.next');
Route::post('contracts/approval/{id}/preapprover/add', [ContractController::class, 'addPreapproverGroup'])->name('contracts.approval.preapprover.add');
Route::post('contracts/approval/{id}/group/{groupId}/approver/add', [ContractController::class, 'addDynamicGroupApprover'])->name('contracts.approval.group.approver.add');

// Pre-approval flow routes
Route::post('/contracts/{id}/send-to-preapproval', [ContractController::class, 'sendToPreApproval'])->name('contract.sendToPreApproval');
Route::get('/contracts/{id}/pre-approval', [ContractController::class, 'showPreApprovalPage'])->name('contract.preApproval.show');
Route::post('/contracts/{id}/pre-approval/{approvalId}/respond', [ContractController::class, 'preApprovalRespond'])->name('contract.preApproval.respond');
Route::post('/contracts/{id}/negotiation-email', [ContractController::class, 'negotiationEmail'])->name('contract.negotiationEmail');
Route::post('/contracts/{id}/pre-approval/attachment', [ContractController::class, 'preApprovalUpdateAttachment'])->name('contract.preApproval.updateAttachment');

//Compare History
Route::get('contracts/{id}/history/{historyId}/compare', [ContractController::class, 'compareHistory']);

//Custom Contracts
Route::get('contracts/create/contract-custom', [ContractCustomController::class, 'createCustom'])->name('createCustomContract');
Route::post('contracts/store/contract-custom',[ContractCustomController::class,'store'])->name('storeCustomContract');
Route::post('contracts/update/contract-custom/{id}', [ContractCustomController::class, 'agreementFormUpdate'])->name('updateCustomContract');
Route::get('contracts/list/contract-custom', [ContractCustomController::class, 'index'])->name('listCustomContract');
Route::get('contracts/show/contract-custom/{id}', [ContractCustomController::class, 'show'])->name('editCustomContract');

// Dashboard for custom contracts (summary)
Route::get('contracts/dashboard/contract-custom', [ContractCustomController::class, 'dashboard'])->name('dashboardCustomContract');

// Credit Cell Actions
Route::post('contracts/approval/contract-custom/{id}/approve', [ContractCustomController::class, 'approve'])->name('approve');
Route::post('contracts/approval/contract-custom/{id}/reject', [ContractCustomController::class, 'reject'])->name('reject');

// Approval workflow routes
Route::get('contracts/approval/contract-custom/{contract}/approval/{approval}/view', [ContractCustomController::class, 'approvalView'])->name('contracts.approval.view');
Route::post('contracts/approval/contract-custom/{contract}/approval/{approval}/respond', [ContractCustomController::class, 'approvalRespond'])->name('contracts.approval.respond');
Route::post('contracts/approval/contract-custom/{contract}/approval/notify', [ContractCustomController::class, 'notifyOwner'])->name('contracts.approval.notify');
Route::post('contracts/approval/contract-custom/{contract}/approver-edit', [ContractCustomController::class, 'approverUpdate'])->name('contracts.approver.update');

// MM / Oracle codes for contracts (Active-only)
Route::get('contracts/approval/contract-custom/{id}/codes', [ContractCustomController::class, 'getContractCodes']);
Route::post('contracts/approval/contract-custom/{id}/codes', [ContractCustomController::class, 'saveContractCodes']);

// Complete sign and replace contract attachment (Signatory action)
Route::post('contracts/approval/contract-custom/{id}/complete-sign', [ContractCustomController::class, 'completeSignUpload']);

// Extend Contract
Route::post('contracts/extend/contract-custom/{id}/create', [ContractCustomController::class, 'extendContract']);

// Preview Template
Route::post('contracts/approval/contract-custom/{id}/preview-template', [ContractCustomController::class, 'previewTemplate']);

// Template Change Request Email
Route::post('contracts/template-change-request/contract-custom/{id}', [EmailTemplateController::class, 'sendTemplateChangeRequestEmail'])->name('contracts.templateChangeRequest');

Route::get('contracts/api/locations/contract-custom', [ContractCustomController::class, 'apiLocations'])->name('contracts.api.locations');

// Contract Change History API
Route::get('contracts/contract-custom/{id}/change-history', [ContractCustomController::class, 'getChangeHistory'])->name('contracts.changeHistory');
Route::get('contracts/contract-custom/{id}/history/{historyId}/compare', [ContractCustomController::class, 'compareHistoryDetail'])->name('contracts.compareHistoryDetail');


Route::get('google/list', [GoogleDriveController::class, 'listFilesInDriveFolder'])->name('listFilesInDriveFolder');
Route::get('google/create-folder', [GoogleDriveController::class, 'createDriveFolder'])->name('createDriveFolder');
Route::get('google/uploadfile', [GoogleDriveController::class,  'uploadFileToDriveFolder'])->name('uploadFileToDriveFolder');
Route::get('google/readfile', [GoogleDriveController::class,  'readFileFromDrive'])->name('readFileFromDrive');
Route::get('google/editfile', [GoogleDriveController::class,  'editFileInDrive'])->name('editFileInDrive');

Route::post('/contracts/builk-import/storefile', [ContractImportController::class, 'storeFile'])->name('storeFile');

Route::get('/contracts/eventgroup', [ContractController::class, 'eventgroup'])->name('eventgroup');

Route::get('contracts/{id}', [ContractController::class, 'viewContract'])->name('viewContract');
Route::get('contractsnew/{id}', [ContractController::class, 'viewContractNew'])->name('viewContractNew');

Route::post('contract/sign/{id}', [ContractController::class, 'signContract'])->name('InternalSignContract');

//Terminate Contract
Route::post('contracts/terminateContract', [ContractController::class, 'terminateContract'])->name('terminateContract');

//Renewal Contract
Route::get('contracts/renew/{id}', [ContractController::class, 'renewContract'])->name('renewContract');
Route::post('contracts/renew/contract', [ContractController::class, 'renewCreateContract'])->name('renewCreateContract');
Route::get('contracts/terminate/{id}', [ContractController::class, 'terminateContractList'])->name('terminateContractList');

//OTP Actions
Route::post('contracts/sentOtpApprovals', [ContractController::class, 'OtpSigningActions'])->name('OtpSigningActions');
Route::post('contracts/checkOtpApprovals', [ContractController::class, 'OtpApprovalActions'])->name('OtpApprovalActions');
Route::post('setUpSigning', [ContractController::class, 'setUpSigningActions'])->name('setUpSigningActions');
Route::post('resend/sendExternalMail', [ContractController::class, 'resendExternalApprovalMail'])->name('resendExternalApprovalMail');


//Link/Unlink Contract
Route::post('contracts/link', [ContractController::class, 'linkUnlinkContract'])->name('linkUnlinkContract');
Route::get('showDocument/{conid}', [ContractController::class, 'documentViewer']);
Route::get('fileversion', [ContractController::class, 'fileVersion']);
Route::get('contractdocs/{con}/{loc}/{contype}/{conid}/{constat}/{filename}', [ContractController::class, 'getFile'])->where('filename', '^[^/]+$');
Route::get('contractfiles/{con}', [ContractController::class, 'getCloudFile']);
Route::get('reminderEmails', [ContractController::class, 'getRemiderEmails'])->name('reminderEmails');
Route::get('reminderSettings', [ContractController::class, 'reminderSettingsActions'])->name('reminderSettings');
Route::post('reminderSettingsStore', [ContractController::class, 'reminderSettingsSave']);

Route::get('contracts/approval-entries/backfill', [ApprovalEntriesBackfillController::class, 'index'])->name('contracts.approval.backfill.index');
Route::post('contracts/approval-entries/backfill/preview/{contractId}', [ApprovalEntriesBackfillController::class, 'previewOne'])->name('contracts.approval.backfill.preview-one');
Route::post('contracts/approval-entries/backfill/preview-selected', [ApprovalEntriesBackfillController::class, 'previewSelected'])->name('contracts.approval.backfill.preview-selected');
Route::post('contracts/approval-entries/backfill/preview-all', [ApprovalEntriesBackfillController::class, 'previewAll'])->name('contracts.approval.backfill.preview-all');
Route::post('contracts/approval-entries/backfill/insert/{contractId}', [ApprovalEntriesBackfillController::class, 'insertOne'])->name('contracts.approval.backfill.insert-one');
Route::post('contracts/approval-entries/backfill/insert-selected', [ApprovalEntriesBackfillController::class, 'insertSelected'])->name('contracts.approval.backfill.insert-selected');
Route::post('contracts/approval-entries/backfill/insert-all', [ApprovalEntriesBackfillController::class, 'insertAll'])->name('contracts.approval.backfill.insert-all');

Route::resource('admin-settings', AdminConfigSettingsController::class)->names('contracts.admin-settings');

// Authbridge / eSign API endpoints (only used when admin_setting('enable_authbridge_feature'))
Route::prefix('esign')->group(function () {
    Route::get('token', [EsignApiController::class, 'getToken']);
    Route::post('compose', [EsignApiController::class, 'composeEpak']);
    Route::get('{epakId}/links', [EsignApiController::class, 'getEasySignLinks']);
    Route::get('download', [EsignApiController::class, 'downloadDocument']);
    // Orchestrator: compose epak and notify approvers for a contract
    Route::post('send/{contractId}', [EsignApiController::class, 'sendEpakAndNotify'])->name('esign.send');    
    // TruthScreen eSign API - returns HTML response for modal display
    Route::post('truthscreen/{contractId}', [EsignApiController::class, 'sendTruthScreenEsign'])->name('esign.truthscreen');
});

});

//Obligation
Route::get('obligation/dashboard', [ContractController::class, 'obligationDashboard'])->name('obligationDashboard');
Route::post('contracts/addObligation/', [ContractController::class, 'addObligation'])->name('addObligation');
Route::post('contracts/deleteObligation/', [ContractController::class, 'deleteObligation'])->name('deleteObligation');

Route::post('export-report', [ContractReportsController::class, 'exportData'])->name('exportData');

Route::get('logout', [ContractDashboardController::class, 'logoutContract']);

//Cron Actions
Route::get('sendReminders', [CronController::class, 'cronSendRemiderEmails']);
Route::get('changeStatusDoc', [CronController::class, 'cronStatusChangeActions']);
Route::get('changeStatusDoc', [CronController::class, 'cronStatusChangeActions']);
Route::get('checkTokenExpiry', [CronController::class, 'cronStorageTokenExpiryCheck']);

// eSign doc response callback (no auth)
Route::get('contracts/esign/docresponse', [EsignApiController::class, 'docResponse'])->name('esign.docresponse');

//External Actions
// Negotiation guest access (external, non-logged-in users) — must stay outside the
// contractauth middleware group so unauthenticated reviewers can open/respond.
Route::get('contracts/negotiation/{accessSlug}', [ContractController::class, 'negotiationAccess'])->name('contract.negotiationAccess');
Route::post('contracts/negotiation/{accessSlug}/respond', [ContractController::class, 'negotiationRespond'])->name('contract.negotiationRespond');

Route::post('contracts/external/accessCheck', [ContractController::class, 'accessExContract']);
Route::get('contracts/external/approval/{id}', [ContractController::class, 'viewExContract']);
Route::post('contracts/external/sentOtpApprovals', [ContractController::class, 'OtpExSigningActions']);
Route::post('contracts/external/checkOtpApprovals', [ContractController::class, 'OtpExApprovalActions']);
Route::post('contracts/external/setUpSign', [ContractController::class, 'setUpExSigningActions']);
Route::post('contracts/external/updateApprovals', [ContractController::class, 'contractExApprovals']);
Route::get('contracts/external/showdoc/{conid}', [ContractController::class, 'documentExViewer']);
Route::post('contracts/setasign/{id}', [ContractController::class, 'setupSignaturePdf'])->name('setupSignaturePdf');
Route::post('contracts/external/setasign/{id}', [ContractController::class, 'setupExSignaturePdf'])->name('setupExSignaturePdf');
Route::post('contract/external/sign/{id}', [ContractController::class, 'signExContract'])->name('ExternalSignContract');