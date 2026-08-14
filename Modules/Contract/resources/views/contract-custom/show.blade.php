@extends('layouts/layoutMaster')
@section('title', ' Edit Contract')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/tagify/tagify.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/jquery-sticky/jquery-sticky.js'
])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
<link href="{{url('/')}}/Modules/Contract/resources/assets/sass/contractrep.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/blob.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/filesaver.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/htmdocx.js"></script>
<script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/scriptparty.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contractRepEdit.js"></script>

@endsection

<style>
  .z-50 { z-index: 1050; }
  .compact-check .form-check { display:inline-flex; align-items:center; margin-right:.4rem; margin-bottom:.2rem; padding-right:.25rem; }
  .compact-check .form-check-label { font-size:.86rem; margin-left:.24rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px; display:inline-block; vertical-align:middle; }
  .compact-check input.form-check-input { width:.95rem; height:.95rem; }
  .form-check-group { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
  .tooltip-helper { font-size:.9rem; color:#6c757d; cursor:pointer; margin-left:.4rem; }
  .components-row { display:flex; gap:1rem; }
  .components-col { flex:1; min-width:0; }
  #response_viewer { margin-top:1rem; }
  #response_extracted { margin-top:1rem; white-space:pre-wrap; background:#f8f9fa; padding:12px; border-radius:6px; }
  .sign-summary { background:#f5f7fb; padding:20px; border-radius:8px; border:1px solid #e9eef8; }
  .consultation-price-wrap { margin-left:6px; }
  .discount-warning { display:none; }
  .collapse-toggle { margin-bottom:.5rem; }
  /* Minimal signatory view box */
  .signatory-summary .row + .row { margin-top:10px; }
  .meta-label { color:#6c757d; font-size:.9rem; }
  .meta-value { font-weight:600; }

  /* Page header layout: prevent wrapping issues and keep actions pinned to the right.
     flex-wrap allows the title to wrap on small screens, header-actions uses margin-left:auto
     to keep action buttons visually separated from the title block. */
  .page-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
    gap:12px;
    flex-wrap:wrap;
  }
  .page-header .header-actions {
    margin-left:auto;
    display:flex;
    gap:8px;
    align-items:center;
  }

  .approvals-section { margin-top:18px; }
  .pkg-totals { font-size:0.95rem; color:#333; }
  .pkg-totals .label { color:#6c757d; font-size:.9rem; }
  .credit-card .form-control-plaintext, .credit-card input, .credit-card textarea { font-size:0.95rem; }

  /* Owner signing card (professional) */
  .owner-signing-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    overflow: hidden;
  }
  .owner-signing-card .card-header {
    background: transparent;
    border-bottom: 1px solid #f0f2f6;
    padding: 0;
  }
  .owner-signing-card .nav-tabs {
    border-bottom: none;
    gap: 0;
  }
  .owner-signing-card .nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    padding: 14px 24px;
    font-weight: 500;
    color: #6b7280;
    font-size: 0.92rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .owner-signing-card .nav-tabs .nav-link:hover {
    color: #4f46e5;
    background: #f9fafb;
  }
  .owner-signing-card .nav-tabs .nav-link.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
    font-weight: 600;
    background: transparent;
  }
  .owner-signing-card .nav-tabs .nav-link .tab-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: all 0.2s ease;
  }
  .owner-signing-card .nav-tabs .nav-link .tab-icon.upload-icon {
    background: #ede9fe;
    color: #7c3aed;
  }
  .owner-signing-card .nav-tabs .nav-link .tab-icon.esign-icon {
    background: #dbeafe;
    color: #2563eb;
  }
  .owner-signing-card .nav-tabs .nav-link.active .tab-icon.upload-icon {
    background: #7c3aed;
    color: #fff;
  }
  .owner-signing-card .nav-tabs .nav-link.active .tab-icon.esign-icon {
    background: #2563eb;
    color: #fff;
  }
  .owner-signing-card .card-body {
    padding: 24px;
  }

  .btn-esign {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 28px;
    font-weight: 600;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 14px rgba(79,70,229,0.3);
  }
  .btn-esign:hover {
    background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(79,70,229,0.4);
  }
  .btn-esign:active {
    transform: translateY(0);
  }
  .btn-upload-sign {
    background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 28px;
    font-weight: 600;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 14px rgba(124,58,237,0.3);
  }
  /* Stronger specificity inside upload zone to avoid inherited color overrides */
  .upload-zone .btn-upload-sign,
  .upload-zone a.btn-upload-sign,
  .upload-zone button.btn-upload-sign {
    color: #fff !important;
  }
  .upload-zone .btn-upload-sign i,
  .upload-zone .btn-upload-sign .ti {
    color: #fff !important;
  }
  .btn-upload-sign:hover {
    background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%);
    color: #fff !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(124,58,237,0.4);
  }
  .btn-upload-sign:focus {
    outline: 3px solid rgba(124,58,237,0.15);
    outline-offset: 2px;
  }
  .btn-upload-sign[disabled], .btn-upload-sign:disabled {
    opacity: 0.6;
  }
  .upload-zone {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    transition: all 0.2s ease;
    cursor: pointer;
    background: #fafbfc;
    color: #0f172a; /* improve contrast for pasted/drag text */
  }
  .upload-zone:hover {
    border-color: #8b5cf6;
    background: #faf5ff;
    color: #0b1220;
  }
  .upload-zone .upload-zone-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #ede9fe;
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin: 0 auto 10px;
    transition: all 0.15s ease;
  }
  .upload-zone:hover .upload-zone-icon {
    background: #7c3aed;
    color: #fff;
  }
  .upload-zone input[type=file] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
  }

  /* Signature method buttons - improve hover readability */
  .signature-method-btn {
    color: #334155;
    border-color: #c7d2fe;
    background: transparent;
    transition: all 0.12s ease;
  }
  .signature-method-btn:hover {
    color: #fff;
    background: linear-gradient(135deg,#4338ca 0%,#4f46e5 100%);
    border-color: #4338ca;
  }
  .signature-method-btn.active {
    color: #fff !important;
    background: linear-gradient(135deg,#4f46e5 0%,#6366f1 100%) !important;
    border-color: #4f46e5 !important;
  }

  /* File preview readability */
  #owner_signed_preview, #sig_upload_preview, #sig_usb_preview {
    color: #0f172a !important;
    font-weight: 600;
  }
  #owner_signed_filename { color: #0f172a; font-weight:600; }

  .owner-summary-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .owner-summary-card .card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
  }
  .owner-summary-card .summary-title {
    font-weight: 700;
    font-size: 1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .owner-summary-card .summary-title .title-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
  }
  .summary-row {
    display: flex;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
  }
  .summary-row:last-child {
    border-bottom: none;
  }
  .summary-label {
    flex: 0 0 180px;
    color: #64748b;
    font-size: 0.88rem;
    font-weight: 500;
  }
  .summary-value {
    flex: 1;
    font-weight: 600;
    color: #1e293b;
    font-size: 0.92rem;
  }


  /* Status header (professional UI) */
  .status-card {
    background: #fff;
    border: 1px solid #eef0f4;
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
  }
  .status-card .status-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #384250;
  }
  .status-card .status-sub {
    color: #6b7280;
    font-size: 0.85rem;
  }
  .status-badge {
    font-weight: 600;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 0.8rem;
  }
  .status-track {
    position: relative;
    height: 8px;
    background: #f0f2f6;
    border-radius: 999px;
    overflow: hidden;
  }
  .status-track .status-fill {
    height: 100%;
    border-radius: 999px;
    transition: width .3s ease;
  }
  .status-steps {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 4px;
    margin-top: 10px;
  }
  .status-step {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #9aa0a6;
    font-size: 0.72rem;
    white-space: nowrap;
  }
  .status-step .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #d1d5db;
    flex: 0 0 auto;
  }
  .status-step.active { color: #1f2937; font-weight: 600; }
  .status-step.active .dot { background: #10b981; }
  .status-step.current .dot { background: #6366f1; }

  /* Change History Timeline Styles */
  .history-timeline {
    position: relative;
    padding-left: 30px;
  }
  .history-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #667eea, #764ba2);
    border-radius: 2px;
  }
  .history-item {
    position: relative;
    padding: 16px 20px;
    margin-bottom: 16px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
  }
  .history-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
  }
  .history-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 24px;
    width: 12px;
    height: 12px;
    background: #fff;
    border: 3px solid #667eea;
    border-radius: 50%;
    z-index: 1;
  }
  .history-item.first-entry::before {
    background: #10b981;
    border-color: #10b981;
  }
  .history-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
  }
  .history-item-meta {
    display: flex;
    flex-direction: column;
  }
  .history-item-date {
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
  }
  .history-item-user {
    font-size: 0.82rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 4px;
  }
  .history-item-status {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .history-changes-list {
    background: #f9fafb;
    border-radius: 8px;
    padding: 12px;
    margin-top: 8px;
  }
  .history-change-row {
    display: flex;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.85rem;
  }
  .history-change-row:last-child {
    border-bottom: none;
  }
  .history-change-field {
    flex: 0 0 140px;
    font-weight: 500;
    color: #4b5563;
  }
  .history-change-arrow {
    flex: 0 0 30px;
    text-align: center;
    color: #9ca3af;
  }
  .history-change-old {
    flex: 1;
    color: #ef4444;
    text-decoration: line-through;
    opacity: 0.75;
  }
  .history-change-new {
    flex: 1;
    color: #10b981;
    font-weight: 500;
  }
  .history-no-changes {
    text-align: center;
    color: #9ca3af;
    font-size: 0.85rem;
    padding: 8px;
  }
  .history-item-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
  }
  .btn-compare {
    font-size: 0.8rem;
    padding: 4px 12px;
  }
  
  /* Related Changes (Locations, Discounts, Health Packages) */
  .related-changes-container {
    border-top: 1px dashed #e5e7eb;
    padding-top: 12px;
    margin-top: 12px;
  }
  .related-change-section {
    margin-bottom: 12px;
    background: #fafafa;
    border-radius: 8px;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
  }
  .related-change-section:last-child {
    margin-bottom: 0;
  }
  .related-change-header {
    font-weight: 600;
    font-size: 0.85rem;
    color: #4b5563;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
  }
  .related-change-items {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .related-change-item {
    font-size: 0.82rem;
    padding: 4px 8px;
    background: #fff;
    border-radius: 4px;
    display: flex;
    align-items: center;
  }
  .related-change-item .badge {
    font-size: 0.7rem;
    padding: 2px 6px;
    text-transform: capitalize;
  }
  
  /* Compare Modal Styling */
  .compare-summary-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  }
  .compare-icon-box {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
  }
  #compareTabsNav .nav-link {
    color: #6b7280;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 12px 16px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
  }
  #compareTabsNav .nav-link:hover {
    color: #667eea;
    background: #f9fafb;
  }
  #compareTabsNav .nav-link.active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: transparent;
    font-weight: 600;
  }
  #compareTabsNav .nav-link .badge {
    font-size: 0.7rem;
    padding: 2px 6px;
  }
  .compare-table {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
  }
  .compare-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  }
  .compare-table thead th {
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    padding: 12px 16px;
    font-size: 0.85rem;
  }
  .compare-table tbody tr {
    transition: background 0.15s ease;
  }
  .compare-table tbody tr:hover {
    background: #f9fafb !important;
  }
  .compare-table tbody td {
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
  }
  .compare-table tbody tr.changed-row {
    background: linear-gradient(90deg, #fef3c7 0%, #fff7ed 100%);
    border-left: 3px solid #f59e0b;
  }
  .compare-table tbody tr.changed-row td:first-child {
    padding-left: 13px;
  }
  .compare-table tbody tr.unchanged-row {
    background: #fff;
  }
  .compare-table .field-label {
    font-weight: 600;
    color: #374151;
  }
  .compare-table .old-value {
    color: #dc2626;
    text-decoration: line-through;
    opacity: 0.85;
  }
  .compare-table .new-value {
    color: #059669;
    font-weight: 600;
  }
  .compare-table .unchanged-value {
    color: #6b7280;
  }
  
  /* Related Items Comparison Cards */
  .compare-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    margin-bottom: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
  }
  .compare-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  .compare-card.added {
    border-left: 4px solid #10b981;
    background: linear-gradient(90deg, #ecfdf5 0%, #fff 30%);
  }
  .compare-card.removed {
    border-left: 4px solid #ef4444;
    background: linear-gradient(90deg, #fef2f2 0%, #fff 30%);
  }
  .compare-card.modified {
    border-left: 4px solid #f59e0b;
    background: linear-gradient(90deg, #fffbeb 0%, #fff 30%);
  }
  .compare-card.unchanged {
    border-left: 4px solid #9ca3af;
    background: #fff;
  }
  .compare-card-header {
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f3f4f6;
  }
  .compare-card-title {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .compare-card-body {
    padding: 12px 16px;
    font-size: 0.85rem;
  }
  .compare-card-row {
    display: flex;
    align-items: center;
    padding: 4px 0;
  }
  .compare-card-label {
    flex: 0 0 120px;
    color: #6b7280;
    font-size: 0.8rem;
  }
  .compare-card-values {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  /* Legend */
  .compare-legend {
    background: #f9fafb;
  }
  .legend-dot {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 6px;
    vertical-align: middle;
  }
  .legend-dot.unchanged { background: #9ca3af; }
  .legend-dot.changed { background: #f59e0b; }
  .legend-dot.added { background: #10b981; }
  .legend-dot.removed { background: #ef4444; }
  
  /* Empty state */
  .compare-empty {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
  }
  .compare-empty i {
    font-size: 3rem;
    margin-bottom: 12px;
    opacity: 0.5;
  }
</style>

@section('content')
@php
  if (! function_exists('parse_json')) {
    function parse_json($val) {
      if ($val === null || $val === '') return [];
      if (is_array($val)) return $val;
      $decoded = @json_decode($val, true);
      return is_array($decoded) ? $decoded : [];
    }
  }

  use Carbon\Carbon;

  // Role flags (should be set by controller). Fallback to previous variables or false.
  $isSignatory = $isSignatory ?? ($is_signatory ?? false);
  $isSecondApprover = $isSecondApprover ?? ($is_second_approver ?? false);
  $isApprover3 = $isApprover3 ?? ($is_approver3 ?? false);
  $isCreditCell = $isCreditCell ?? ($is_credit_cell_user ?? false);

  $party = optional($contract->contractPartyList->get(1));
  $partyEx = $party ? optional($party->partyDetailsEx) : null;
  $partyIn = $party ? optional($party->partyDetailsIn) : null;
  $customerName = $partyEx && ($partyEx->company_name ?? false)
                  ? (function_exists('decryptString') ? @decryptString($partyEx->company_name, 'company_name') : $partyEx->company_name)
                  : ($partyIn && ($partyIn->name ?? false) ? $partyIn->name : '');

  $discounts = $contract->contractDiscounts ?? collect();
  $healthPackages = $contract->contractHealthChecks ?? collect();

  $scopeVals = parse_json($contract->contract_tags ?? null);
  if (empty($scopeVals) && is_string($contract->contract_tags) && trim($contract->contract_tags) !== '') {
    $scopeVals = array_map('trim', explode(',', $contract->contract_tags));
  }

  $startDateValue = $contract->fixed_date ?? $contract->signing_date ?? null;
  $startDateIso = $startDateValue ? Carbon::parse($startDateValue)->format('Y-m-d') : '';
  $startDateDisplay = $startDateValue ? Carbon::parse($startDateValue)->format('d M Y') : '—';
  $endDateDisplay = $contract->contract_end_date ? Carbon::parse($contract->contract_end_date)->format('d M Y') : '—';

  $selectedEntityId = null;
  if (!empty($contract->entity_type_id)) $selectedEntityId = $contract->entity_type_id;
  elseif (!empty($partyEx) && !empty($partyEx->entity_type_id)) $selectedEntityId = $partyEx->entity_type_id;
  elseif (!empty($party) && !empty($party->entity_type_id)) $selectedEntityId = $party->entity_type_id;

  $selectedScope = $partyEx->scope ?? $contract->scope ?? ($party->scope ?? '') ?? '';

  // package summary calculations (server-side initial values)
  $packagesCount = is_countable($healthPackages) ? count($healthPackages) : ($healthPackages->count() ?? 0);
  $totalPackagePrice = 0;
  if (is_iterable($healthPackages)) {
    foreach ($healthPackages as $hp) {
      if (is_array($hp)) {
        $totalPackagePrice += floatval($hp['package_price'] ?? 0);
      } elseif (is_object($hp)) {
        $totalPackagePrice += floatval($hp->package_price ?? 0);
      }
    }
  }
  $proposedPrice = $contract->proposed_price ?? $contract->proposed_amount ?? $contract->proposed_value ?? null;

  // build tests master price map for server-side initial display
  $testsMasterMap = [];
  if (!empty($tests) && is_iterable($tests)) {
    foreach ($tests as $t) {
      $tid = is_object($t) ? ($t->id ?? null) : ($t['id'] ?? null);
      $testsMasterMap[(string)$tid] = is_object($t) ? ($t->price ?? $t->default_price ?? 0) : ($t['price'] ?? $t['default_price'] ?? 0);
    }
  }
@endphp

<div class="container my-4" id="page_root">
@php
    $alerts = [
        'warning' => 'alert-warning',
        'error'   => 'alert-danger',
        'success' => 'alert-success',
    ];
@endphp

@foreach ($alerts as $key => $class)
    @if (session($key))
        <div class="alert {{ $class }}">
            {{ session($key) }}
        </div>
    @php
        session()->forget($key);
    @endphp        
    @endif
@endforeach


@if(session('approvers_changed'))
    @php $diff = session('approvers_changed'); @endphp
    <div class="alert alert-warning">
        <strong>Approver change detected</strong>
        <p class="mb-1 small text-muted">The set of approvers (email addresses) suggested by current contract data differs from the stored approval rules. Please review before proceeding.</p>
        <div class="small">
            @if(!empty($diff['added']))
                <div><strong>Added:</strong></div>
                <ul>
                    @foreach($diff['added'] as $a)
                        <li>{{ $a }}</li>
                    @endforeach
                </ul>
            @endif
            @if(!empty($diff['removed']))
                <div><strong>Removed:</strong></div>
                <ul>
                    @foreach($diff['removed'] as $r)
                        <li>{{ $r }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
  

  <div class="page-header">
          <div>
            <h1 class="mb-0">Agreement</h1>
            <small class="text-muted">Contract #{{ $contract->contract_unique_id ?? $contract->id ?? '—' }}</small>
          </div>
          <div class="header-actions">
            @if(strtolower($contract->contract_status ?? '') !== 'draft')
            <button type="button" id="template_change_request_btn" class="btn btn-outline-primary" title="Request Template Change">
              <i class="ti ti-file-text me-1"></i>Request Template Change
            </button>
            @endif
            @if(strtolower($contract->contract_status ?? '') === 'executed' && in_array(strtolower($contract->substatus ?? ''), ['expired','completed','active']))<button type="button" id="extend_agreement_btn" class="btn btn-warning ms-2">Extend Agreement</button>@endif            
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#contractHistoryModal">
              <i class="ti ti-history me-1"></i>History
            </button>
            <a href="{{ url('/contracts/list/contract-custom') }}" class="btn btn-secondary">Back to list</a>
          </div>
        </div>
        
        @php
          $st = strtolower($contract->contract_status ?? $contract->status ?? '');
          $sub = strtolower($contract->substatus ?? '');
          $progress = 0;
          $label = $contract->contract_status ?? $contract->status ?? 'Unknown';
          $barClass = 'bg-secondary';

          switch($st) {
            case 'draft':
              $progress = 10; $barClass = 'bg-secondary'; $label = 'Draft'; break;
            case 'review':
              $progress = 25; $barClass = 'bg-info'; $label = 'Review'; break;
            case 'negotiation':
              $progress = 40; $barClass = 'bg-warning'; $label = 'Negotiation'; break;
            case 'approval':
              $progress = 55; $barClass = 'bg-warning'; $label = 'Pending Approval'; break;
            case 'approved':
              $progress = 70; $barClass = 'bg-primary'; $label = 'Approved'; break;
            case 'signing':
              $progress = 85; $barClass = 'bg-primary'; $label = 'Signing'; break;
            case 'executed':
              $progress = 100;
              switch($sub) {
                case 'active': $label = 'Active'; $barClass = 'bg-success'; break;
                case 'expired': $label = 'Expired'; $barClass = 'bg-danger'; break;
                case 'pending': $label = 'Pending'; $barClass = 'bg-warning'; break;
                case 'renewed': $label = 'Renewed'; $barClass = 'bg-info'; break;
                case 'terminated': $label = 'Terminated'; $barClass = 'bg-danger'; break;
                case 'completed': $label = 'Completed'; $barClass = 'bg-dark'; break;
                default: $label = 'Executed'; $barClass = 'bg-success'; break;
              }
              break;
            default:
              $progress = 0; $barClass = 'bg-secondary'; $label = $contract->contract_status ?? 'Unknown';
          }
        @endphp

        <div class="mb-3">
          <div class="status-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <div class="status-title">Contract Status</div>
                <div class="status-sub">{{ ucfirst($st) }}@if($sub) / {{ ucfirst($sub) }}@endif</div>
              </div>
              <div>
                <span class="badge status-badge {{ $barClass }}">{{ $label }}</span>
              </div>
            </div>
            <div class="mt-3">
              <div class="status-track">
                <div class="status-fill {{ $barClass }}" style="width: {{ $progress }}%;"></div>
              </div>
              <div class="status-steps">
                <div class="status-step {{ $st === 'draft' ? 'current' : '' }} {{ in_array($st, ['draft','review','negotiation','approval','approved','signing','executed']) ? 'active' : '' }}">
                  <span class="dot"></span> Draft
                </div>
                <div class="status-step {{ $st === 'review' ? 'current' : '' }} {{ in_array($st, ['review','negotiation','approval','approved','signing','executed']) ? 'active' : '' }}">
                  <span class="dot"></span> Review
                </div>
                <div class="status-step {{ $st === 'negotiation' ? 'current' : '' }} {{ in_array($st, ['negotiation','approval','approved','signing','executed']) ? 'active' : '' }}">
                  <span class="dot"></span> Negotiate
                </div>
                <div class="status-step {{ in_array($st, ['approval','approved']) ? 'current' : '' }} {{ in_array($st, ['approval','approved','signing','executed']) ? 'active' : '' }}">
                  <span class="dot"></span> Approval
                </div>
                <div class="status-step {{ $st === 'signing' ? 'current' : '' }} {{ in_array($st, ['signing','executed']) ? 'active' : '' }}">
                  <span class="dot"></span> Signing
                </div>
                <div class="status-step {{ $st === 'executed' ? 'current' : '' }} {{ $st === 'executed' ? 'active' : '' }}">
                  <span class="dot"></span> Executed
                </div>
              </div>
            </div>
          </div>
        </div>
  {{-- Tabs: Agreement (details) + Approvals --}}
  <ul class="nav nav-tabs mb-3" id="contractTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab" aria-controls="tab-details" aria-selected="true">Agreement</button>
    </li>
    @if(!$isCreditUser)
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#tab-approvals" type="button" role="tab" aria-controls="tab-approvals" aria-selected="false">Approvals</button>
    </li>
    @endif
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-details" role="tabpanel" aria-labelledby="details-tab">
        @if((!$isCurrentApproverIsApproverOrVerifier && !$isOwner))
        <div class="signatory-summary">
          <div class="card owner-summary-card mb-3">
            <div class="card-header">
              <div class="summary-title">
                <span class="title-icon"><i class="ti ti-file-check"></i></span>
                Agreement Summary
              </div>
            </div>
            <div class="card-body px-4 py-3">
              @php $userRole = session('contractSessionUserRole') ?? ''; @endphp
              @if(strtolower(trim($userRole)) === 'user1')
                <div class="summary-row">
                  <div class="summary-label">Customer / Vendor</div>
                  <div class="summary-value">{{ $customerName }}</div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">Agreement ID</div>
                  <div class="summary-value">{{ sprintf("%08d", $contract->id) }}</div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">MM Code</div>
                  <div class="summary-value">{{ $contract->mm_code ?? '—' }}</div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">Oracle Code</div>
                  <div class="summary-value">{{ $contract->oracle_code ?? '—' }}</div>
                </div>

                <div class="mt-3 d-flex gap-2">
                  @php
                    $partyViewUrl = url('/parties/contract-parties-org-view/'.$partyEx->id);
                  @endphp
                  <a href="{{ $partyViewUrl }}" target="_blank" class="btn btn-outline-primary"><i class="ti ti-file-list me-1"></i>KYC Customer/Vendor</a>
                  <button type="button" id="mm_edit_btn" class="btn btn-primary"><i class="ti ti-edit me-1"></i>Edit MM/Oracle Codes</button>
                </div>

              @else
                <div class="summary-row">
                  <div class="summary-label">Agreement Name</div>
                  <div class="summary-value">{{ $contract->contract_name_decrypted ?? $contract->contract_name ?? '—' }}</div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">Customer</div>
                  <div class="summary-value">{{ $customerName ?: '—' }}</div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">Start Date</div>
                  <div class="summary-value">{{ $startDateDisplay }}</div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">End Date</div>
                  <div class="summary-value">{{ $endDateDisplay }}</div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">Scope</div>
                  <div class="summary-value">
                    @if(!empty($scopeVals))
                      @foreach($scopeVals as $sv)
                        <span class="badge bg-light text-dark border me-1">{{ $sv }}</span>
                      @endforeach
                    @else
                      {{ $contract->contract_tags ?? '—' }}
                    @endif
                  </div>
                </div>
                <div class="summary-row">
                  <div class="summary-label">Entity Type</div>
                  <div class="summary-value">
                    @foreach($entityTypesList as $et)
                      @if($et['id'] == $contract->catgoery_id) {{ $et['name'] }} @endif
                    @endforeach
                  </div>
                </div>

                @if(!$isCreditUser && $discounts && $discounts->count())
                  <div class="summary-row">
                    <div class="summary-label">Discounts <span class="badge bg-secondary ms-2">{{ $discounts->count() }}</span></div>
                    <div class="summary-value">
                      @foreach($discounts as $d)
                        @php $pct = floatval($d->discount_percent ?? 0); @endphp
                        <span class="badge {{ $pct > 15 ? 'bg-danger' : 'bg-light text-dark border' }} me-1">
                          {{ $d->category ?? '' }} — {{ $d->subcategory ?? '' }}: {{ number_format($pct, 2) }}%
                        </span>
                      @endforeach
                    </div>
                  </div>
                @endif

                  <div class="summary-row">
                    <div class="summary-label">Locations <span class="badge bg-secondary ms-2">{{ ($contract->contractLocations && is_countable($contract->contractLocations)) ? $contract->contractLocations->count() : 0 }}</span></div>
                    <div class="summary-value">
                      @if($contract->contractLocations && $contract->contractLocations->count())
                        @foreach($contract->contractLocations as $loc)
                          <span class="badge bg-light text-dark border me-1">{{ $locations[$loc->location_id] ?? 'Location #'.$loc->location_id }}</span>
                        @endforeach
                      @else
                        <span class="text-muted">None</span>
                      @endif
                    </div>
                  </div>

                  <div class="summary-row">
                    <div class="summary-label">Health Packages <span class="badge bg-secondary ms-2">{{ $packagesCount }}</span></div>
                    <div class="summary-value">
                      @if($packagesCount > 0)
                        <span class="badge bg-light text-dark border me-1">{{ $packagesCount }} package{{ $packagesCount != 1 ? 's' : '' }}</span>
                      @else
                        <span class="text-muted">None</span>
                      @endif
                    </div>
                  </div>

                @if($proposedPrice !== null && $proposedPrice !== '')
                  <div class="summary-row">
                    <div class="summary-label">Proposed Price</div>
                    <div class="summary-value text-primary fw-bold">₹{{ number_format(floatval($proposedPrice), 2) }}</div>
                  </div>
                @endif
              @endif
            </div>
          </div>
        </div>
        @endif

      @if($isOwner && $approvalCycleCompleted)
        @php
            $ownerFirstName = '';
            $ownerLastName = '';
            try {
                $ownerUserRec = \App\Models\AddUsers::select(decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $contract->created_by)->first();
                if ($ownerUserRec) {
                    $ownerFirstName = $ownerUserRec->FirstName ?? '';
                    $ownerFirstName = 'Jeeva';
                    $ownerLastName = $ownerUserRec->LastName ?? '';
                }
            } catch (\Throwable $e) {}
        @endphp

        {{-- ===== Owner Summary (post-approval) ===== --}}
        <div class="card owner-summary-card mb-3">
          <div class="card-header">
            <div class="summary-title">
              <span class="title-icon"><i class="ti ti-file-check"></i></span>
              Agreement Summary
            </div>
          </div>
          <div class="card-body px-4 py-3">
            <div class="summary-row">
              <div class="summary-label">Agreement Name</div>
              <div class="summary-value">{{ $contract->contract_name_decrypted ?? $contract->contract_name ?? '—' }}</div>
            </div>
            <div class="summary-row">
              <div class="summary-label">Agreement ID</div>
              <div class="summary-value">{{ $contract->contract_unique_id ?? $contract->id ?? '—' }}</div>
            </div>
            <div class="summary-row">
              <div class="summary-label">Customer / Vendor</div>
              <div class="summary-value">{{ $customerName ?: '—' }}</div>
            </div>
            <div class="summary-row">
              <div class="summary-label">Start Date</div>
              <div class="summary-value">{{ $startDateDisplay }}</div>
            </div>
            <div class="summary-row">
              <div class="summary-label">End Date</div>
              <div class="summary-value">{{ $endDateDisplay }}</div>
            </div>
            <div class="summary-row">
              <div class="summary-label">Scope</div>
              <div class="summary-value">
                @if(!empty($scopeVals))
                  @foreach($scopeVals as $sv)
                    <span class="badge bg-light text-dark border me-1">{{ $sv }}</span>
                  @endforeach
                @else
                  {{ $contract->contract_tags ?? '—' }}
                @endif
              </div>
            </div>
            <div class="summary-row">
              <div class="summary-label">Entity Type</div>
              <div class="summary-value">
                @foreach($entityTypesList as $et)
                  @if($et['id'] == $contract->catgoery_id) {{ $et['name'] }} @endif
                @endforeach
              </div>
            </div>
            <div class="summary-row">
              <div class="summary-label">Status</div>
              <div class="summary-value">
                <span class="badge rounded-pill bg-success">{{ ucfirst($contract->contract_status ?? '') }} — {{ ucfirst($contract->substatus ?? '') }}</span>
              </div>
            </div>
            @if(!$isCreditUser && $discounts && $discounts->count())
            <div class="summary-row">
              <div class="summary-label">Discounts <span class="badge bg-secondary ms-2">{{ $discounts->count() }}</span></div>
              <div class="summary-value">
                @foreach($discounts as $d)
                  @php $pct = floatval($d->discount_percent ?? 0); @endphp
                  <span class="badge {{ $pct > 15 ? 'bg-danger' : 'bg-light text-dark border' }} me-1">
                    {{ $d->category ?? '' }} — {{ $d->subcategory ?? '' }}: {{ number_format($pct, 2) }}%
                  </span>
                @endforeach
              </div>
            </div>
            @endif
            <div class="summary-row">
              <div class="summary-label">Locations <span class="badge bg-secondary ms-2">{{ ($contract->contractLocations && is_countable($contract->contractLocations)) ? $contract->contractLocations->count() : 0 }}</span></div>
              <div class="summary-value">
                @if($contract->contractLocations && $contract->contractLocations->count())
                  @foreach($contract->contractLocations as $loc)
                    <span class="badge bg-light text-dark border me-1">{{ $locations[$loc->location_id] ?? 'Location #'.$loc->location_id }}</span>
                  @endforeach
                @else
                  <span class="text-muted">None</span>
                @endif
              </div>
            </div>

            <div class="summary-row">
              <div class="summary-label">Health Packages <span class="badge bg-secondary ms-2">{{ $packagesCount }}</span></div>
              <div class="summary-value">
                @if($packagesCount > 0)
                  <span class="badge bg-light text-dark border me-1">{{ $packagesCount }} package{{ $packagesCount != 1 ? 's' : '' }}</span>
                @else
                  <span class="text-muted">None</span>
                @endif
              </div>
            </div>
            @if($proposedPrice !== null && $proposedPrice !== '')
            <div class="summary-row">
              <div class="summary-label">Proposed Price</div>
              <div class="summary-value text-primary fw-bold">₹{{ number_format(floatval($proposedPrice), 2) }}</div>
            </div>
            @endif
          </div>
        </div>

        @if(strtolower($contract->contract_status) === 'signing' && strtolower($contract->substatus) === 'approved')
        {{-- ===== Owner Signing Actions ===== --}}
        <div class="card owner-signing-card mb-3" id="owner-signing-card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="signingMethodTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upload-signed-tab" data-bs-toggle="tab" data-bs-target="#tab-upload-signed" type="button" role="tab" aria-controls="tab-upload-signed" aria-selected="true">
                          <span class="tab-icon upload-icon"><i class="ti ti-cloud-upload"></i></span>
                          Upload Signed Document
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="esign-tab" data-bs-toggle="tab" data-bs-target="#tab-esign" type="button" role="tab" aria-controls="tab-esign" aria-selected="false">
                          <span class="tab-icon esign-icon"><i class="ti ti-shield-lock"></i></span>
                          eSign
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="signingMethodTabsContent">
                    {{-- Upload Signed Document Tab --}}
                    <div class="tab-pane fade show active" id="tab-upload-signed" role="tabpanel" aria-labelledby="upload-signed-tab">
                        <div class="upload-zone position-relative mb-3">
                          <input type="file" id="sig_upload_input" accept="application/pdf,image/*" />
                          <div class="upload-zone-icon"><i class="ti ti-cloud-upload"></i></div>
                          <div class="fw-semibold text-dark mb-1">Drop your signed document here</div>
                          <div class="small text-muted">or click to browse &middot; PDF, JPG, PNG accepted</div>
                        </div>
                        <div id="owner_signed_preview" class="small text-muted mb-3" style="display:none;">
                          <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3">
                            <i class="ti ti-file-check text-success"></i>
                            <span id="owner_signed_filename">No file selected</span>
                          </div>
                        </div>
                        <div>
                            <button type="button" id="complete_sign_btn" class="btn btn-upload-sign">
                              <i class="ti ti-cloud-upload"></i> Upload Signed Agreement
                            </button>
                            <span id="complete_sign_status" class="ms-2 small text-muted"></span>
                        </div>
                    </div>
                    {{-- eSign Tab --}}
                    <div class="tab-pane fade" id="tab-esign" role="tabpanel" aria-labelledby="esign-tab">
                        <div id="esignStatusArea">
                          <p class="mb-2">This will send the contract to current approvers for e-sign using the configured eSign provider. Approvers will receive the sign link by email.</p>
                          <div class="mb-2"><strong>Contract:</strong> {{ $contract->contract_unique_id ?? $contract->id }}</div>
                          <div id="esignResult" style="display:none;"></div>
                        </div>
                        <div class="mt-3">
                            <button type="button" id="sendEsignBtn" class="btn btn-esign" data-contract-id="{{ $contract->id }}">
                              <i class="ti ti-shield-lock"></i> Send eSign
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
      @endif
      
      @if($isSignatory)
        @if($approvalCycleCompleted)
            {{-- Signatory view: minimal summary. Back to list button included. --}}
            @if(strtolower($contract->contract_status) == 'approval' && strtolower($contract->substatus) == 'pending approval') 
              <div class="card mb-3" id="signatory-signature-card">
                <div class="card-header"><strong>Signature</strong></div>
                <div class="card-body">
                  <p class="small text-muted">Choose how you'd like to provide your signature for this approval.</p>
            
                  <div class="mb-3">
                    <div class="btn-group" role="group" aria-label="signature-methods">
                      <button type="button" class="btn btn-outline-primary signature-method-btn active" data-method="upload">Upload</button>
                      <button type="button" class="btn btn-outline-primary signature-method-btn" data-method="sign">Sign on Page</button>
                      <button type="button" class="btn btn-outline-primary signature-method-btn" data-method="usb">USB Drive</button>
                    </div>
                  </div>
            
                  {{-- Upload --}}
                  <div class="signature-method" data-method="upload" style="display:block;">
                    <label class="form-label">Upload signature (image/pdf)</label>
                    <input type="file" id="sig_upload_input" accept="image/*,application/pdf" class="form-control mb-2" />
                    <div id="sig_upload_preview" class="small text-muted">No file selected</div>
                    <input type="hidden" id="sig_uploaded_data" name="sig_uploaded_data" value="">
                  </div>
            
                  {{-- Sign on page (canvas) --}}
                  <div class="signature-method" data-method="sign" style="display:none;">
                    <label class="form-label">Sign on page</label>
                    <div style="border:1px solid #e6e6e6; border-radius:6px; height:200px; position:relative;">
                      <canvas id="sig_canvas" width="800" height="200" style="width:100%; height:200px; display:block;"></canvas>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                      <button type="button" id="sig_clear_btn" class="btn btn-sm btn-outline-secondary">Clear</button>
                      <button type="button" id="sig_save_btn" class="btn btn-sm btn-primary">Save Signature</button>
                    </div>
                    <div id="sig_canvas_preview" class="mt-2 small text-muted">No signature saved</div>
                    <input type="hidden" id="sig_canvas_data" name="sig_canvas_data" value="">
                  </div>
            
                  {{-- USB drive sign --}}
                  <div class="signature-method" data-method="usb" style="display:none;">
                    <label class="form-label">USB Drive / Hardware Token</label>
                    <p class="small text-muted">If you have a signature on a USB drive or hardware token, connect it and upload.</p>
                    <input type="file" id="sig_usb_input" accept="image/*,application/pdf" class="form-control mb-2" />
                    <div id="sig_usb_preview" class="small text-muted">No file selected</div>
                    <input type="hidden" id="sig_usb_data" name="sig_usb_data" value="">
                  </div>
            
                  <div class="mt-3">
                    <small class="text-muted">After saving a signature here, the signature data will be attached to your approval submission.</small>
                  </div>
                  
                  <div class="mt-3">
                    <button type="button" id="complete_sign_btn" class="btn btn-success">Complete Sign & Upload Signed Document</button>
                    <span id="complete_sign_status" class="ms-2 small text-muted"></span>
                  </div>                  
                </div>
              </div>
            @endif 
            <div class="card mb-3">
                <div class="card-header"><strong>Uploaded Contract File</strong></div>
                <div class="card-body">
                    @if(!empty($contract->contract_attachment))
                        @if(isset($contract->contract_attachment_filename))
                            @if(fileStorageType() != 'Local')
                                @php 
                                    $getFinalUrl = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'openfile');
                                    $getFinalUrlNew = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'openfile');
                                @endphp
                                <div class="alert alert-danger mx-2">If below document Not Loaded Please <a href="{{$getFinalUrlNew}}" target="blank">Click Here</a>. Because of some security reasons its not loaded.</div>
                                <iframe src="{{ $getFinalUrl }}" height="500" width="100%"></iframe>
                            @else
                                @include('contract::contract.viewContractDocument')
                            @endif   
                        @endif
                    @else
                        <div class="text-muted">No contract file uploaded.</div>
                    @endif
                </div>
            </div>            
        @else
            <div class="alert alert-warning py-2">
                <strong>Note:</strong> Approval Pending.
            </div>
        @endif
      @else
        {{-- All approvers (except signatory) and default: editable full form --}}
        {{-- Hide full form when signing is approved and approval cycle completed (owner sees summary + signing card above) --}}
        @if($isOwner && strtolower($contract->contract_status ?? '') === 'signing' && strtolower($contract->substatus ?? '') === 'approved' && $approvalCycleCompleted)
          {{-- Form hidden — owner summary and signing actions shown above --}}
        @elseif(!empty($canViewForm))
           
          <div id="main-form-container">
            {{-- Include full form partial for editing/view by owner/approver/verifier --}}
            @include('contract::contract-custom.form_full', compact('contract','tests', 'locations','consultations','discounts','healthPackages','entityTypesList','overviewSummary','isCreditCell', 'readonlyForm','currentEntry','isCurrentApproverIsApprover','isCurrentApproverIsVerifier','isCurrentApproverIsApproverOrVerifier','canViewForm','canEditForm','isOwner','isCurrentApproverActive'))
          </div>

        @else
          {{-- User is not owner nor approver/verifier - show summary only --}}
          @include('contract::contract-custom.overview_contract_show', compact('contract','overviewSummary','discounts','healthPackages','locations','tests','consultations','entityTypesList', 'canViewForm', 'isOwner', 'creditCellData', 'isCreditUser', 'currentEntry', 'isCurrentApproverIsApprover'))
        @endif

      @endif
    </div>
    @if(!$isCreditUser)
    <div class="tab-pane fade" id="tab-approvals" role="tabpanel" aria-labelledby="approvals-tab">
      <div class="approvals-section mt-3">
        @include('contract::contract-custom.approvals_list', compact('contract','approvalEntries', 'canViewForm', 'isOwner'))
      </div>
    </div>
    @endif
  </div>
</div>

{{-- Extend Agreement Modal --}}
<div class="modal fade" id="extendAgreementModal" tabindex="-1" aria-labelledby="extendAgreementLabel" aria-hidden="true">
<div class="modal-dialog modal-sm modal-dialog-centered">
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="extendAgreementLabel">Extend Agreement</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
      <div class="mb-2">
        <label class="form-label">Extend by days</label>
        <input type="number" id="extend_days" min="1" class="form-control" placeholder="Number of days">
        <input type="hidden" id="contractExtendId" name="contractExtendId" value="{{ $contract->id }}" />
      </div>
      <div class="mb-2">
        <label class="form-label">Or set new end date</label>
        <input type="date" id="extend_end_date" class="form-control">
      </div>
      <div class="mb-2">
        <label class="form-label">Comments</label>
        <textarea id="extend_comments" class="form-control" rows="3" placeholder="Enter comments for extension..."></textarea>
      </div>
      <div class="text-danger" id="extend_errors" style="display:block;min-height:1.3em;"></div>
    </div>
    <div class="modal-footer">
      <button type="button" id="extend_save" class="btn btn-primary">Save</button>
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
  </div>
</div>
</div>

<!-- Contract History Modal -->
<div class="modal fade" id="contractHistoryModal" tabindex="-1" aria-labelledby="contractHistoryLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="modal-title text-white" id="contractHistoryLabel">
          <i class="ti ti-history me-2"></i>Contract History & Audit Trail
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <!-- History Tabs -->
        <ul class="nav nav-tabs px-3 pt-3" id="historyTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="change-history-tab" data-bs-toggle="tab" data-bs-target="#change-history-pane" type="button" role="tab" aria-controls="change-history-pane" aria-selected="true">
              <i class="ti ti-git-compare me-1"></i>Change History
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="related-contracts-tab" data-bs-toggle="tab" data-bs-target="#related-contracts-pane" type="button" role="tab" aria-controls="related-contracts-pane" aria-selected="false">
              <i class="ti ti-link me-1"></i>Related Contracts
            </button>
          </li>
        </ul>

        <div class="tab-content p-3" id="historyTabsContent">
          <!-- Change History Tab -->
          <div class="tab-pane fade show active" id="change-history-pane" role="tabpanel" aria-labelledby="change-history-tab">
            <div id="changeHistoryLoading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 text-muted">Loading change history...</p>
            </div>
            <div id="changeHistoryContent" style="display: none;">
              <!-- Timeline will be populated via JavaScript -->
            </div>
            <div id="changeHistoryEmpty" style="display: none;" class="text-center py-5">
              <i class="ti ti-clipboard-list" style="font-size: 3rem; color: #d1d5db;"></i>
              <p class="mt-3 text-muted">No change history records found for this contract.</p>
            </div>
          </div>

          <!-- Related Contracts Tab -->
          <div class="tab-pane fade" id="related-contracts-pane" role="tabpanel" aria-labelledby="related-contracts-tab">
            @if((isset($contractsparentList) && $contractsparentList->count()) || (isset($contractsSubseqList) && $contractsSubseqList->count()))
              @if(isset($contractsparentList) && $contractsparentList->count())
                <div class="mb-4">
                  <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-label-info me-2"><i class="ti ti-arrow-back-up"></i></span>
                    <h6 class="mb-0">Previous Contracts ({{ $contractsparentList->count() }})</h6>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm table-hover border rounded">
                      <thead class="table-light">
                        <tr>
                          <th>Contract Name</th>
                          <th>Signing Date</th>
                          <th>Contract Value</th>
                          <th>Effective Date</th>
                          <th>End Date</th>
                          <th class="text-center">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($contractsparentList as $cp)
                          <tr>
                            <td>
                              <div class="fw-medium">{{ !empty($cp->contract_name) && function_exists('decryptString') ? decryptString($cp->contract_name, 'contract_name') : ($cp->contract_name ?? '—') }}</div>
                              <small class="text-muted">ID: {{ $cp->contract_unique_id ?? $cp->id }}</small>
                            </td>
                            <td>{{ isset($cp->signing_date) ? (function_exists('decryptString') ? decryptString($cp->signing_date,'signing_date') : $cp->signing_date) : '—' }}</td>
                            <td>
                              <span class="fw-medium">{{ function_exists('decryptString') ? decryptString($cp->currency,'currency') : '' }} {{ isset($cp->currency_value) ? (function_exists('decryptString') ? decryptString($cp->currency_value,'currency_value') : $cp->currency_value) : '—' }}</span>
                            </td>
                            <td>{{ isset($cp->fixed_date) ? (function_exists('decryptString') ? date('d M Y', strtotime(decryptString($cp->fixed_date,'fixed_date'))) : $cp->fixed_date) : '—' }}</td>
                            <td>{{ isset($cp->contract_end_date) ? (function_exists('decryptString') ? date('d M Y', strtotime(decryptString($cp->contract_end_date,'contract_end_date'))) : $cp->contract_end_date) : '—' }}</td>
                            <td class="text-center">
                              <a href="{{ url('/contracts/show/contract-custom/' . $cp->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-external-link me-1"></i>View
                              </a>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif

              @if(isset($contractsSubseqList) && $contractsSubseqList->count())
                <div class="mb-2">
                  <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-label-success me-2"><i class="ti ti-arrow-forward-up"></i></span>
                    <h6 class="mb-0">Subsequent Contracts ({{ $contractsSubseqList->count() }})</h6>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm table-hover border rounded">
                      <thead class="table-light">
                        <tr>
                          <th>Contract Name</th>
                          <th>Signing Date</th>
                          <th>Contract Value</th>
                          <th>Effective Date</th>
                          <th>End Date</th>
                          <th class="text-center">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($contractsSubseqList as $cs)
                          <tr>
                            <td>
                              <div class="fw-medium">{{ !empty($cs->contract_name) && function_exists('decryptString') ? decryptString($cs->contract_name, 'contract_name') : ($cs->contract_name ?? '—') }}</div>
                              <small class="text-muted">ID: {{ $cs->contract_unique_id ?? $cs->id }}</small>
                            </td>
                            <td>{{ isset($cs->signing_date) ? (function_exists('decryptString') ? decryptString($cs->signing_date,'signing_date') : $cs->signing_date) : '—' }}</td>
                            <td>
                              <span class="fw-medium">{{ function_exists('decryptString') ? decryptString($cs->currency,'currency') : '' }} {{ isset($cs->currency_value) ? (function_exists('decryptString') ? decryptString($cs->currency_value,'currency_value') : $cs->currency_value) : '—' }}</span>
                            </td>
                            <td>{{ isset($cs->fixed_date) ? (function_exists('decryptString') ? date('d M Y', strtotime(decryptString($cs->fixed_date,'fixed_date'))) : $cs->fixed_date) : '—' }}</td>
                            <td>{{ isset($cs->contract_end_date) ? (function_exists('decryptString') ? date('d M Y', strtotime(decryptString($cs->contract_end_date,'contract_end_date'))) : $cs->contract_end_date) : '—' }}</td>
                            <td class="text-center">
                              <a href="{{ url('/contracts/show/contract-custom/' . $cs->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-external-link me-1"></i>View
                              </a>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif
            @else
              <div class="text-center py-5">
                <i class="ti ti-link-off" style="font-size: 3rem; color: #d1d5db;"></i>
                <p class="mt-3 text-muted">No related contracts found in the contract chain.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Change Comparison Detail Modal -->
<div class="modal fade" id="changeCompareModal" tabindex="-1" aria-labelledby="changeCompareLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
        <h5 class="modal-title" id="changeCompareLabel">
          <i class="ti ti-git-compare me-2"></i>Version Comparison
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="compareLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
          <p class="mt-3 text-muted">Loading comparison data...</p>
        </div>
        <div id="compareContent" style="display: none;">
          <!-- Summary Header -->
          <div class="compare-summary-header p-3 bg-light border-bottom">
            <div class="row align-items-center">
              <div class="col-md-4">
                <div class="d-flex align-items-center">
                  <div class="compare-icon-box me-3">
                    <i class="ti ti-calendar-event"></i>
                  </div>
                  <div>
                    <small class="text-muted d-block">Snapshot Date</small>
                    <strong id="compareDate" class="text-dark"></strong>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="d-flex align-items-center">
                  <div class="compare-icon-box me-3">
                    <i class="ti ti-user-edit"></i>
                  </div>
                  <div>
                    <small class="text-muted d-block">Changed By</small>
                    <strong id="compareUser" class="text-dark"></strong>
                  </div>
                </div>
              </div>
              <div class="col-md-4 text-md-end">
                <span class="badge bg-warning text-dark px-3 py-2" id="changesCountBadge" style="font-size: 0.9rem;">
                  <i class="ti ti-exchange me-1"></i>0 Changes
                </span>
              </div>
            </div>
          </div>

          <!-- Tabs Navigation -->
          <ul class="nav nav-tabs nav-fill px-3 pt-3" id="compareTabsNav" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="compare-main-tab" data-bs-toggle="tab" data-bs-target="#compare-main" type="button" role="tab">
                <i class="ti ti-file-text me-1"></i>Contract Details
                <span class="badge bg-primary ms-1" id="mainChangesCount">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="compare-agreement-tab" data-bs-toggle="tab" data-bs-target="#compare-agreement" type="button" role="tab">
                <i class="ti ti-file-certificate me-1"></i>Agreement Terms
                <span class="badge bg-primary ms-1" id="agreementChangesCount">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="compare-locations-tab" data-bs-toggle="tab" data-bs-target="#compare-locations" type="button" role="tab">
                <i class="ti ti-map-pin me-1"></i>Locations
                <span class="badge bg-primary ms-1" id="locationsChangesCount">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="compare-discounts-tab" data-bs-toggle="tab" data-bs-target="#compare-discounts" type="button" role="tab">
                <i class="ti ti-discount-2 me-1"></i>Discounts
                <span class="badge bg-primary ms-1" id="discountsChangesCount">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="compare-health-tab" data-bs-toggle="tab" data-bs-target="#compare-health" type="button" role="tab">
                <i class="ti ti-heart-rate-monitor me-1"></i>Health Packages
                <span class="badge bg-primary ms-1" id="healthChangesCount">0</span>
              </button>
            </li>
          </ul>

          <!-- Tabs Content -->
          <div class="tab-content p-3" id="compareTabsContent">
            <!-- Main Contract Details Tab -->
            <div class="tab-pane fade show active" id="compare-main" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-hover compare-table mb-0">
                  <thead>
                    <tr>
                      <th style="width: 25%;">Field</th>
                      <th style="width: 37.5%;"><i class="ti ti-history me-1"></i>Snapshot Value</th>
                      <th style="width: 37.5%;"><i class="ti ti-clock me-1"></i>Current Value</th>
                    </tr>
                  </thead>
                  <tbody id="compareTableBody"></tbody>
                </table>
              </div>
            </div>

            <!-- Agreement Terms Tab -->
            <div class="tab-pane fade" id="compare-agreement" role="tabpanel">
              <div class="table-responsive">
                <table class="table table-hover compare-table mb-0">
                  <thead>
                    <tr>
                      <th style="width: 25%;">Field</th>
                      <th style="width: 37.5%;"><i class="ti ti-history me-1"></i>Snapshot Value</th>
                      <th style="width: 37.5%;"><i class="ti ti-clock me-1"></i>Current Value</th>
                    </tr>
                  </thead>
                  <tbody id="compareAgreementBody"></tbody>
                </table>
              </div>
            </div>

            <!-- Locations Tab -->
            <div class="tab-pane fade" id="compare-locations" role="tabpanel">
              <div id="compareLocationsContent"></div>
            </div>

            <!-- Discounts Tab -->
            <div class="tab-pane fade" id="compare-discounts" role="tabpanel">
              <div id="compareDiscountsContent"></div>
            </div>

            <!-- Health Packages Tab -->
            <div class="tab-pane fade" id="compare-health" role="tabpanel">
              <div id="compareHealthContent"></div>
            </div>
          </div>

          <!-- Legend -->
          <div class="compare-legend p-3 bg-light border-top">
            <div class="d-flex gap-4 small justify-content-center">
              <div><span class="legend-dot unchanged"></span> Unchanged</div>
              <div><span class="legend-dot changed"></span> Modified</div>
              <div><span class="legend-dot added"></span> Added</div>
              <div><span class="legend-dot removed"></span> Removed</div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ti ti-x me-1"></i>Close
        </button>
      </div>
    </div>
  </div>
</div>




<!-- MM/Oracle Codes Modal (Show page) -->
<div class="modal fade" id="mmOracleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">MM Code & Oracle Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="mm_contract_id" value="{{ $contract->id }}">
        <div class="mb-3">
          <label class="form-label">MM Code</label>
          <input class="form-control" id="mm_mm_code" />
        </div>
        <div class="mb-3">
          <label class="form-label">Oracle Code</label>
          <input class="form-control" id="mm_oracle_code" />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="mm_save_btn">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Template Change Request Modal -->
<div class="modal fade" id="templateChangeRequestModal" tabindex="-1" aria-labelledby="templateChangeRequestLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="templateChangeRequestLabel"><i class="ti ti-file-text me-2"></i>Request Template Change</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info mb-3">
          <i class="ti ti-info-circle me-2"></i>
          This request will be sent to the Corporate Legal team for review.
        </div>
        <form id="templateChangeRequestForm">
          <input type="hidden" id="tcr_contract_id" value="{{ $contract->id }}">
          <div class="mb-3">
            <label class="form-label fw-bold">Reason for Template Change <span class="text-danger">*</span></label>
            <textarea class="form-control" id="tcr_reason" rows="4" placeholder="Please describe why you need to change the template..." required></textarea>
            <div class="form-text">Provide a clear explanation for requesting the template change.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Requested Changes <small class="text-muted">(Optional)</small></label>
            <textarea class="form-control" id="tcr_requested_changes" rows="4" placeholder="Describe the specific changes you would like to see in the template..."></textarea>
            <div class="form-text">List any specific modifications or additions you're requesting.</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="tcr_submit_btn">
          <i class="ti ti-send me-1"></i>Send Request
        </button>
      </div>
    </div>
  </div>
</div>

{{-- expose server-side master lists and flags to client for immediate use --}}
<script>
  window.__tests = {!! json_encode($tests ?? []) !!};
  window.__consultations = {!! json_encode($consultations ?? []) !!};
  window.__locations = {!! json_encode( collect($contract->contractLocations ?? [])->map(function($l){
      return [
        'id' => ($l->location_id ?? $l['id'] ?? null),
        'name' => (optional($l->location)->location_name ?? ($l['location_name'] ?? '')),
        'region' => (optional($l->location)->region ?? ($l['region'] ?? ''))
      ];
  })->values()->all() ) !!};
  window.__entityTypes = {!! json_encode($entityTypesList ?? []) !!};
  window.__IS_APPROVER2 = {!! json_encode($isSecondApprover ? true : false) !!};
  window.__IS_CREDIT_CELL = {!! json_encode($isCreditCell ? true : false) !!};
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      if (window.bootstrap && window.bootstrap.Tooltip) new bootstrap.Tooltip(tooltipTriggerEl);
    });
    document.querySelector('#back_to_form')?.addEventListener('click', function(){ location.reload(); });
    // If credit inputs exist and user is not credit cell, hide them (defensive)
    if (!window.__IS_CREDIT_CELL) {
      document.querySelectorAll('.credit-card').forEach(el => el.style.display = 'none');
      document.querySelectorAll('#credit_proposed_price,#credit_notes').forEach(el => { if (el) el.disabled = true; });
    }

    // If the URL references the approvals tab (e.g. #approvals), open that tab on load.
    try {
      var hash = (window.location.hash || '').replace('#','').toLowerCase();
      if (hash === 'approvals') {
        var approvalsToggle = document.querySelector('#approvals-tab');
        if (approvalsToggle && window.bootstrap && window.bootstrap.Tab) {
          var tabInstance = new bootstrap.Tab(approvalsToggle);
          tabInstance.show();
        } else {
          // fallback: activate via class
          document.querySelectorAll('#contractTabs .nav-link').forEach(n => n.classList.remove('active'));
          approvalsToggle?.classList.add('active');
          document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show','active'));
          document.querySelector('#tab-approvals')?.classList.add('show','active');
        }
      }
    } catch (e) { /* no-op */ }
  });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
      // method switcher
      document.querySelectorAll('.signature-method-btn').forEach(btn => {
        btn.addEventListener('click', function () {
          document.querySelectorAll('.signature-method-btn').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          const method = this.getAttribute('data-method');
          document.querySelectorAll('.signature-method').forEach(s => {
            s.style.display = (s.getAttribute('data-method') === method) ? 'block' : 'none';
          });
        });
      });

      // Upload preview handler
      const uploadInput = document.getElementById('sig_upload_input');
      const uploadPreview = document.getElementById('sig_upload_preview') || document.getElementById('owner_signed_preview');
      const uploadHidden = document.getElementById('sig_uploaded_data');
      if (uploadInput) {
        uploadInput.addEventListener('change', function (e) {
          const file = this.files && this.files[0];
          const ownerPreview = document.getElementById('owner_signed_preview');
          const ownerFilename = document.getElementById('owner_signed_filename');
          if (!file) {
            if (uploadPreview) uploadPreview.textContent = 'No file selected';
            if (ownerPreview) ownerPreview.style.display = 'none';
            if (uploadHidden) uploadHidden.value = '';
            return;
          }
          var label = file.name + ' (' + Math.round(file.size/1024) + ' KB)';
          if (uploadPreview && uploadPreview !== ownerPreview) uploadPreview.textContent = label;
          if (ownerPreview) { ownerPreview.style.display = 'block'; }
          if (ownerFilename) { ownerFilename.textContent = label; }
          const reader = new FileReader();
          reader.onload = function(evt) {
            if (uploadHidden) uploadHidden.value = evt.target.result;
          };
          reader.readAsDataURL(file);
        });
      }

      // USB input handler (same as upload)
      const usbInput = document.getElementById('sig_usb_input');
      const usbPreview = document.getElementById('sig_usb_preview');
      const usbHidden = document.getElementById('sig_usb_data');
      if (usbInput) {
        usbInput.addEventListener('change', function () {
          const file = this.files && this.files[0];
          if (!file) {
            usbPreview.textContent = 'No file selected';
            usbHidden.value = '';
            return;
          }
          usbPreview.textContent = file.name + ' (' + Math.round(file.size/1024) + ' KB)';
          const reader = new FileReader();
          reader.onload = function(evt) {
            usbHidden.value = evt.target.result;
          };
          reader.readAsDataURL(file);
        });
      }

      // Canvas signing
      const canvas = document.getElementById('sig_canvas');
      const ctx = canvas && canvas.getContext ? canvas.getContext('2d') : null;
      let drawing = false, lastX = 0, lastY = 0;
      if (ctx) {
        // set white background
        ctx.fillStyle = "#fff";
        ctx.fillRect(0,0,canvas.width,canvas.height);
        ctx.strokeStyle = "#000";
        ctx.lineWidth = 2;
        ctx.lineJoin = ctx.lineCap = 'round';

        function getPointerPos(e) {
          const rect = canvas.getBoundingClientRect();
          const clientX = (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
          const clientY = (e.touches && e.touches[0]) ? e.touches[0].clientY : e.clientY;
          const scaleX = canvas.width / rect.width;
          const scaleY = canvas.height / rect.height;
          return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY };
        }

        canvas.addEventListener('mousedown', (e) => { drawing = true; const p = getPointerPos(e); lastX = p.x; lastY = p.y; });
        canvas.addEventListener('touchstart', (e) => { e.preventDefault(); drawing = true; const p = getPointerPos(e); lastX = p.x; lastY = p.y; });
        window.addEventListener('mouseup', () => drawing = false);
        canvas.addEventListener('mousemove', (e) => {
          if (!drawing) return;
          const p = getPointerPos(e);
          ctx.beginPath(); ctx.moveTo(lastX,lastY); ctx.lineTo(p.x,p.y); ctx.stroke();
          lastX = p.x; lastY = p.y;
        });
        canvas.addEventListener('touchmove', (e) => {
          if (!drawing) return;
          const p = getPointerPos(e);
          ctx.beginPath(); ctx.moveTo(lastX,lastY); ctx.lineTo(p.x,p.y); ctx.stroke();
          lastX = p.x; lastY = p.y;
        });

        document.getElementById('sig_clear_btn')?.addEventListener('click', function(){
          ctx.clearRect(0,0,canvas.width,canvas.height);
          ctx.fillStyle = "#fff"; ctx.fillRect(0,0,canvas.width,canvas.height);
          document.getElementById('sig_canvas_preview').textContent = 'No signature saved';
          document.getElementById('sig_canvas_data').value = '';
        });

        document.getElementById('sig_save_btn')?.addEventListener('click', function(){
          const dataUrl = canvas.toDataURL('image/png');
          document.getElementById('sig_canvas_data').value = dataUrl;
          const preview = document.getElementById('sig_canvas_preview');
          preview.innerHTML = '<img src="'+dataUrl+'" alt="signature" style="max-height:80px; border:1px solid #ddd; border-radius:4px;">';
        });
      }

      // eSign send handler
      document.addEventListener('click', function(e){
        var btn = e.target.closest('#sendEsignBtn');
        if(!btn) return;
        var contractId = btn.getAttribute('data-contract-id');
        if(!contractId) return;
        var statusArea = document.getElementById('esignResult');
        btn.disabled = true;
        var originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

        fetch(APP_URL + '/esign/send/' + contractId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
                'Accept': 'application/json'
            }
        }).then(function(res){ return res.json().then(function(json){ return {ok: res.ok, status: res.status, json: json}; }); })
        .then(function(r){
            if(r.ok){
                var sent = r.json.sent || [];
                var html = '<div class="alert alert-success">eSign sent to ' + sent.length + ' recipient(s).</div>';
                if(sent.length > 0){
                    html += '<ul class="small">';
                    sent.forEach(function(s){ html += '<li><strong>' + s.email + '</strong>: <a href="' + s.link + '" target="_blank">Open link</a></li>'; });
                    html += '</ul>';
                }
                statusArea.style.display = 'block';
                statusArea.innerHTML = html;
            }else{
                var err = r.json || {};
                statusArea.style.display = 'block';
                statusArea.innerHTML = '<div class="alert alert-danger">Failed: '+(err.error || err.message || 'Unknown error')+'</div>';
            }
        }).catch(function(err){
            statusArea.style.display = 'block';
            statusArea.innerHTML = '<div class="alert alert-danger">Request failed: '+err.message+'</div>';
        }).finally(function(){ btn.disabled = false; btn.innerHTML = originalText; });
      });
      
      // Complete Sign upload handler
      document.getElementById('complete_sign_btn')?.addEventListener('click', async function(){
        this.disabled = true;
        const statusEl = document.getElementById('complete_sign_status');
        statusEl.textContent = 'Uploading...';
        const url = APP_URL + '/contracts/approval/contract-custom/{{$contract->id}}/complete-sign';
        const formData = new FormData();
        formData.append('_token', "{{ csrf_token() }}");

        const fileInput = document.getElementById('sig_upload_input');
        const usbInput = document.getElementById('sig_usb_input');
        if (fileInput && fileInput.files && fileInput.files[0]) {
          formData.append('signed_file', fileInput.files[0]);
        } else if (usbInput && usbInput.files && usbInput.files[0]) {
          formData.append('signed_file', usbInput.files[0]);
        } else if (document.getElementById('sig_canvas_data') && document.getElementById('sig_canvas_data').value) {
          formData.append('signed_file_base64', document.getElementById('sig_canvas_data').value);
        } else if (document.getElementById('sig_uploaded_data') && document.getElementById('sig_uploaded_data').value) {
          formData.append('signed_file_base64', document.getElementById('sig_uploaded_data').value);
        } else {
          statusEl.textContent = 'No file to upload';
          return;
        }

        try {
          const res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With':'XMLHttpRequest'}
          });
          const data = await res.json();
          if (data.success) {
            statusEl.textContent = 'Upload successful';
            setTimeout(function(){ window.location.reload(); }, 800);
          } else {
            statusEl.textContent = data.message || 'Upload failed';
            this.disabled = false;
          }
        } catch (e) {
          statusEl.textContent = 'Upload error';
        }
      });

      // MM/Oracle edit - open modal and fetch current values
      document.getElementById('mm_edit_btn')?.addEventListener('click', async function(){
        const id = document.getElementById('mm_contract_id') ? document.getElementById('mm_contract_id').value : '{{ $contract->id }}';
        try {
          const res = await fetch(APP_URL + '/contracts/approval/contract-custom/' + id + '/codes', { headers: { 'Accept': 'application/json' } });
          const json = await res.json();
          if (!json || !json.success) {
            alert(json.message || 'Failed to fetch codes');
            return;
          }
          document.getElementById('mm_mm_code').value = json.data.mm_code || '';
          document.getElementById('mm_oracle_code').value = json.data.oracle_code || '';
          var modalEl = document.getElementById('mmOracleModal');
          var bsModal = new bootstrap.Modal(modalEl);
          bsModal.show();
        } catch (e) { alert('Failed to fetch codes'); }
      });

      document.getElementById('mm_save_btn')?.addEventListener('click', function() {
        const id = document.getElementById('mm_contract_id').value;
        const mm = document.getElementById('mm_mm_code').value;
        const oracle = document.getElementById('mm_oracle_code').value;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(APP_URL + '/contracts/approval/contract-custom/' + id + '/codes', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
          },
          body: JSON.stringify({ mm_code: mm, oracle_code: oracle })
        }).then(function(res) { return res.json(); })
          .then(function(json) {
            if (json && json.success) {
              var modalEl = document.getElementById('mmOracleModal');
              var bsModal = bootstrap.Modal.getInstance(modalEl);
              if (bsModal) bsModal.hide();
              location.reload();
            } else {
              alert(json.message || 'Failed to save codes');
            }
          }).catch(function() { alert('Failed to save codes'); });
      });

      // Template Change Request - open modal
      document.getElementById('template_change_request_btn')?.addEventListener('click', function() {
        // Reset form
        document.getElementById('tcr_reason').value = '';
        document.getElementById('tcr_requested_changes').value = '';
        var modalEl = document.getElementById('templateChangeRequestModal');
        var bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
      });

      // Template Change Request - submit
      document.getElementById('tcr_submit_btn')?.addEventListener('click', async function() {
        const reason = document.getElementById('tcr_reason').value.trim();
        const requestedChanges = document.getElementById('tcr_requested_changes').value.trim();
        const contractId = document.getElementById('tcr_contract_id').value;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (!reason) {
          alert('Please provide a reason for the template change request.');
          document.getElementById('tcr_reason').focus();
          return;
        }

        const submitBtn = this;
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';

        try {
          const res = await fetch(APP_URL + '/contracts/template-change-request/contract-custom/' + contractId, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
              reason: reason,
              requested_changes: requestedChanges
            })
          });

          const json = await res.json();

          if (json && json.success) {
            // Close modal
            var modalEl = document.getElementById('templateChangeRequestModal');
            var bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();

            // Show success message
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Request Sent!',
                text: 'Your template change request has been sent to Corporate Legal.',
                confirmButtonColor: '#696cff'
              });
            } else {
              alert('Template change request sent successfully!');
            }
          } else {
            const errorMsg = json.message || 'Failed to send template change request';
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg,
                confirmButtonColor: '#696cff'
              });
            } else {
              alert(errorMsg);
            }
          }
        } catch (e) {
          console.error('Template change request error:', e);
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to send template change request. Please try again.',
              confirmButtonColor: '#696cff'
            });
          } else {
            alert('Failed to send template change request. Please try again.');
          }
        } finally {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      });

    });
  </script>

<script>
  // Contract Change History Module
  (function() {
    const CONTRACT_ID = {{ $contract->id }};
    let historyLoaded = false;
    let historyData = [];

    // Load change history when modal opens
    document.getElementById('contractHistoryModal')?.addEventListener('show.bs.modal', function() {
      if (!historyLoaded) {
        loadChangeHistory();
      }
    });

    // Also load when Change History tab is clicked
    document.getElementById('change-history-tab')?.addEventListener('shown.bs.tab', function() {
      if (!historyLoaded) {
        loadChangeHistory();
      }
    });

    async function loadChangeHistory() {
      const loadingEl = document.getElementById('changeHistoryLoading');
      const contentEl = document.getElementById('changeHistoryContent');
      const emptyEl = document.getElementById('changeHistoryEmpty');

      loadingEl.style.display = 'block';
      contentEl.style.display = 'none';
      emptyEl.style.display = 'none';

      try {
        const response = await fetch(APP_URL + '/contracts/contract-custom/' + CONTRACT_ID + '/change-history', {
          headers: { 'Accept': 'application/json' }
        });

        const json = await response.json();

        if (json.success && json.data && json.data.length > 0) {
          historyData = json.data;
          renderChangeHistory(json.data);
          loadingEl.style.display = 'none';
          contentEl.style.display = 'block';
          historyLoaded = true;
        } else {
          loadingEl.style.display = 'none';
          emptyEl.style.display = 'block';
          historyLoaded = true;
        }
      } catch (error) {
        console.error('Failed to load change history:', error);
        loadingEl.style.display = 'none';
        emptyEl.innerHTML = '<i class="ti ti-alert-circle" style="font-size: 3rem; color: #ef4444;"></i><p class="mt-3 text-danger">Failed to load change history. Please try again.</p>';
        emptyEl.style.display = 'block';
      }
    }

    function renderChangeHistory(data) {
      const container = document.getElementById('changeHistoryContent');
      if (!data || data.length === 0) {
        container.innerHTML = '<div class="text-center py-4 text-muted">No change history available.</div>';
        return;
      }

      let html = '<div class="history-timeline">';

      data.forEach((item, index) => {
        const isFirst = item.is_first || index === data.length - 1;
        const hasChanges = item.changes && item.changes.length > 0;
        const statusBadgeClass = getStatusBadgeClass(item.status);

        html += `
          <div class="history-item ${isFirst ? 'first-entry' : ''}">
            <div class="history-item-header">
              <div class="history-item-meta">
                <span class="history-item-date">
                  <i class="ti ti-calendar me-1"></i>${item.created_at || 'Unknown date'}
                </span>
                <span class="history-item-user">
                  <i class="ti ti-user me-1"></i>
                  ${item.updated_by ? (item.updated_by.name || item.updated_by.email || 'User') : (item.created_by ? (item.created_by.name || 'System') : 'System')}
                </span>
              </div>
              <div class="history-item-status">
                <span class="badge ${statusBadgeClass}">${item.status || 'Unknown'}${item.substatus ? ' / ' + item.substatus : ''}</span>
              </div>
            </div>
        `;

        if (isFirst) {
          html += `
            <div class="history-no-changes">
              <i class="ti ti-flag me-1"></i>Contract Created
            </div>
          `;
        } else if (hasChanges) {
          html += `
            <div class="history-changes-list">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small"><i class="ti ti-edit me-1"></i>${item.changes.length} field(s) changed</span>
              </div>
          `;

          item.changes.forEach(change => {
            html += `
              <div class="history-change-row">
                <span class="history-change-field">${change.label}</span>
                <span class="history-change-old">${change.old_value || '—'}</span>
                <span class="history-change-arrow"><i class="ti ti-arrow-right"></i></span>
                <span class="history-change-new">${change.new_value || '—'}</span>
              </div>
            `;
          });

          html += `</div>`;

          // Render related table changes (locations, discounts, health_checks)
          if (item.related_changes) {
            html += renderRelatedChanges(item.related_changes);
          }
        } else {
          // Check if there are related table changes even when no field changes
          const hasRelatedChanges = item.related_changes && (
            (item.related_changes.locations && item.related_changes.locations.length > 0) ||
            (item.related_changes.discounts && item.related_changes.discounts.length > 0) ||
            (item.related_changes.health_checks && item.related_changes.health_checks.length > 0)
          );
          
          if (hasRelatedChanges) {
            html += renderRelatedChanges(item.related_changes);
          } else {
            html += `
              <div class="history-no-changes">
                <i class="ti ti-check me-1"></i>No field changes detected (status update only)
              </div>
            `;
          }
        }

        // Add compare button for non-first entries
        if (!isFirst) {
          html += `
            <div class="history-item-actions">
              <button type="button" class="btn btn-sm btn-outline-primary btn-compare" onclick="openCompareModal(${item.history_id})">
                <i class="ti ti-git-compare me-1"></i>Compare with Current
              </button>
            </div>
          `;
        }

        html += `</div>`;
      });

      html += '</div>';
      container.innerHTML = html;
    }

    /**
     * Render related table changes (locations, discounts, health_checks)
     */
    function renderRelatedChanges(relatedChanges) {
      if (!relatedChanges) return '';
      
      let html = '';
      const hasLocChanges = relatedChanges.locations && relatedChanges.locations.length > 0;
      const hasDiscChanges = relatedChanges.discounts && relatedChanges.discounts.length > 0;
      const hasHcChanges = relatedChanges.health_checks && relatedChanges.health_checks.length > 0;
      
      if (!hasLocChanges && !hasDiscChanges && !hasHcChanges) return '';
      
      html += `<div class="related-changes-container mt-3">`;
      
      // Locations
      if (hasLocChanges) {
        html += `
          <div class="related-change-section">
            <div class="related-change-header">
              <i class="ti ti-map-pin me-1"></i>Locations
              <span class="badge bg-secondary ms-2">${relatedChanges.locations.length}</span>
            </div>
            <div class="related-change-items">
        `;
        relatedChanges.locations.forEach(loc => {
          const actionClass = loc.action === 'added' ? 'text-success' : (loc.action === 'removed' ? 'text-danger' : 'text-muted');
          const actionIcon = loc.action === 'added' ? 'ti-plus' : (loc.action === 'removed' ? 'ti-minus' : 'ti-minus');
          html += `
            <div class="related-change-item ${actionClass}">
              <i class="ti ${actionIcon} me-1"></i>
              <span class="badge bg-${loc.action === 'added' ? 'success' : (loc.action === 'removed' ? 'danger' : 'secondary')} me-2">${loc.action}</span>
              ${loc.location_name || 'Location #' + loc.location_id}
            </div>
          `;
        });
        html += `</div></div>`;
      }
      
      // Discounts
      if (hasDiscChanges) {
        html += `
          <div class="related-change-section">
            <div class="related-change-header">
              <i class="ti ti-discount-2 me-1"></i>Discounts
              <span class="badge bg-secondary ms-2">${relatedChanges.discounts.length}</span>
            </div>
            <div class="related-change-items">
        `;
        relatedChanges.discounts.forEach(disc => {
          const actionClass = disc.action === 'added' ? 'text-success' : (disc.action === 'removed' ? 'text-danger' : 'text-warning');
          const actionIcon = disc.action === 'added' ? 'ti-plus' : (disc.action === 'removed' ? 'ti-minus' : 'ti-edit');
          let detail = `${disc.category || ''} - ${disc.subcategory || ''}`;
          if (disc.action === 'modified') {
            detail += ` (${disc.old_value}% → ${disc.new_value}%)`;
          } else {
            detail += ` (${disc.discount_percent}%)`;
          }
          html += `
            <div class="related-change-item ${actionClass}">
              <i class="ti ${actionIcon} me-1"></i>
              <span class="badge bg-${disc.action === 'added' ? 'success' : (disc.action === 'removed' ? 'danger' : 'warning')} me-2">${disc.action}</span>
              ${detail}
            </div>
          `;
        });
        html += `</div></div>`;
      }
      
      // Health Checks / Packages
      if (hasHcChanges) {
        html += `
          <div class="related-change-section">
            <div class="related-change-header">
              <i class="ti ti-heart-rate-monitor me-1"></i>Health Packages
              <span class="badge bg-secondary ms-2">${relatedChanges.health_checks.length}</span>
            </div>
            <div class="related-change-items">
        `;
        relatedChanges.health_checks.forEach(hc => {
          const actionClass = hc.action === 'added' ? 'text-success' : (hc.action === 'removed' ? 'text-danger' : 'text-warning');
          const actionIcon = hc.action === 'added' ? 'ti-plus' : (hc.action === 'removed' ? 'ti-minus' : 'ti-edit');
          let detail = hc.row_name || 'Package';
          if (hc.action === 'modified' && hc.changes) {
            const changeDetails = [];
            if (hc.changes.package_price) changeDetails.push(`Price: ${hc.changes.package_price.old} → ${hc.changes.package_price.new}`);
            if (hc.changes.approved_cost) changeDetails.push(`Cost: ${hc.changes.approved_cost.old} → ${hc.changes.approved_cost.new}`);
            if (hc.changes.overhead_allocation) changeDetails.push(`Overhead: ${hc.changes.overhead_allocation.old} → ${hc.changes.overhead_allocation.new}`);
            if (changeDetails.length > 0) detail += ` (${changeDetails.join(', ')})`;
          } else if (hc.package_price || hc.approved_cost) {
            detail += ` (Price: ${hc.package_price || '—'}, Cost: ${hc.approved_cost || '—'})`;
          }
          html += `
            <div class="related-change-item ${actionClass}">
              <i class="ti ${actionIcon} me-1"></i>
              <span class="badge bg-${hc.action === 'added' ? 'success' : (hc.action === 'removed' ? 'danger' : 'warning')} me-2">${hc.action}</span>
              ${detail}
            </div>
          `;
        });
        html += `</div></div>`;
      }
      
      html += `</div>`;
      return html;
    }

    function getStatusBadgeClass(status) {
      if (!status) return 'bg-secondary';
      const s = status.toLowerCase();
      switch (s) {
        case 'draft': return 'bg-secondary';
        case 'review': return 'bg-info';
        case 'negotiation': return 'bg-warning';
        case 'approval': return 'bg-warning';
        case 'approved': return 'bg-primary';
        case 'signing': return 'bg-primary';
        case 'executed': return 'bg-success';
        default: return 'bg-secondary';
      }
    }

    // Global function for opening compare modal
    window.openCompareModal = async function(historyId) {
      const compareModal = new bootstrap.Modal(document.getElementById('changeCompareModal'));
      const loadingEl = document.getElementById('compareLoading');
      const contentEl = document.getElementById('compareContent');

      loadingEl.style.display = 'block';
      contentEl.style.display = 'none';
      compareModal.show();

      try {
        const response = await fetch(APP_URL + '/contracts/contract-custom/' + CONTRACT_ID + '/history/' + historyId + '/compare', {
          headers: { 'Accept': 'application/json' }
        });

        const json = await response.json();

        if (json.success) {
          renderComparison(json);
          loadingEl.style.display = 'none';
          contentEl.style.display = 'block';
        } else {
          loadingEl.innerHTML = '<div class="alert alert-danger">Failed to load comparison data.</div>';
        }
      } catch (error) {
        console.error('Failed to load comparison:', error);
        loadingEl.innerHTML = '<div class="alert alert-danger">Failed to load comparison. Please try again.</div>';
      }
    };

    function renderComparison(data) {
      // Update header info
      document.getElementById('compareDate').textContent = data.history_date || 'Unknown';
      document.getElementById('compareUser').textContent = data.updated_by ? (data.updated_by.name || data.updated_by.email || 'User') : 'System';
      document.getElementById('changesCountBadge').innerHTML = '<i class="ti ti-exchange me-1"></i>' + data.changes_count + ' Change' + (data.changes_count !== 1 ? 's' : '');

      // Count changes per section
      let mainChanges = 0, agreementChanges = 0, locChanges = 0, discChanges = 0, healthChanges = 0;

      // Render Main Contract Details
      const mainTbody = document.getElementById('compareTableBody');
      let mainHtml = '';
      if (data.comparison && data.comparison.length > 0) {
        data.comparison.forEach(item => {
          if (item.is_changed) mainChanges++;
          mainHtml += renderCompareRow(item);
        });
      } else {
        mainHtml = '<tr><td colspan="3" class="compare-empty"><i class="ti ti-file-x"></i><p>No contract details to compare</p></td></tr>';
      }
      mainTbody.innerHTML = mainHtml;
      document.getElementById('mainChangesCount').textContent = mainChanges;

      // Render Agreement Terms
      const agreementTbody = document.getElementById('compareAgreementBody');
      let agreementHtml = '';
      if (data.confidentiality_agreement && data.confidentiality_agreement.length > 0) {
        data.confidentiality_agreement.forEach(item => {
          if (item.is_changed) agreementChanges++;
          agreementHtml += renderCompareRow(item);
        });
      } else {
        agreementHtml = '<tr><td colspan="3" class="compare-empty"><i class="ti ti-file-certificate"></i><p>No agreement terms to compare</p></td></tr>';
      }
      agreementTbody.innerHTML = agreementHtml;
      document.getElementById('agreementChangesCount').textContent = agreementChanges;

      // Render Locations
      const locContent = document.getElementById('compareLocationsContent');
      if (data.locations && data.locations.length > 0) {
        locChanges = data.locations.filter(l => l.action !== 'unchanged').length;
        locContent.innerHTML = renderLocationCards(data.locations);
      } else {
        locContent.innerHTML = '<div class="compare-empty"><i class="ti ti-map-pin-off"></i><p>No locations to compare</p></div>';
      }
      document.getElementById('locationsChangesCount').textContent = locChanges;

      // Render Discounts
      const discContent = document.getElementById('compareDiscountsContent');
      if (data.discounts && data.discounts.length > 0) {
        discChanges = data.discounts.filter(d => d.action !== 'unchanged').length;
        discContent.innerHTML = renderDiscountCards(data.discounts);
      } else {
        discContent.innerHTML = '<div class="compare-empty"><i class="ti ti-discount-2-off"></i><p>No discounts to compare</p></div>';
      }
      document.getElementById('discountsChangesCount').textContent = discChanges;

      // Render Health Packages
      const healthContent = document.getElementById('compareHealthContent');
      if (data.health_checks && data.health_checks.length > 0) {
        healthChanges = data.health_checks.filter(h => h.action !== 'unchanged').length;
        healthContent.innerHTML = renderHealthCheckCards(data.health_checks);
      } else {
        healthContent.innerHTML = '<div class="compare-empty"><i class="ti ti-stethoscope-off"></i><p>No health packages to compare</p></div>';
      }
      document.getElementById('healthChangesCount').textContent = healthChanges;
    }

    function renderCompareRow(item) {
      const rowClass = item.is_changed ? 'changed-row' : 'unchanged-row';
      const historyVal = item.history_value ?? '—';
      const currentVal = item.current_value ?? '—';

      return `
        <tr class="${rowClass}">
          <td><span class="field-label">${escapeHtml(item.label)}</span></td>
          <td class="${item.is_changed ? 'old-value' : 'unchanged-value'}">${escapeHtml(historyVal)}</td>
          <td class="${item.is_changed ? 'new-value' : 'unchanged-value'}">${escapeHtml(currentVal)}</td>
        </tr>
      `;
    }

    function renderLocationCards(locations) {
      let html = '<div class="row g-3">';
      locations.forEach(loc => {
        const actionClass = loc.action || 'unchanged';
        const actionBadge = getActionBadge(loc.action);
        html += `
          <div class="col-md-6">
            <div class="compare-card ${actionClass}">
              <div class="compare-card-header">
                <span class="compare-card-title">
                  <i class="ti ti-map-pin"></i>
                  ${escapeHtml(loc.location_name || loc.current_name || 'Location')}
                </span>
                ${actionBadge}
              </div>
              <div class="compare-card-body">
                <div class="compare-card-row">
                  <span class="compare-card-label">Location ID:</span>
                  <span>${loc.location_id || '—'}</span>
                </div>
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      return html;
    }

    function renderDiscountCards(discounts) {
      let html = '<div class="row g-3">';
      discounts.forEach(disc => {
        const actionClass = disc.action || 'unchanged';
        const actionBadge = getActionBadge(disc.action);
        const category = disc.category || '—';
        const subcategory = disc.subcategory || '—';
        
        let valueDisplay = '';
        if (disc.action === 'modified' && disc.history_percent !== undefined) {
          valueDisplay = `
            <div class="compare-card-row">
              <span class="compare-card-label">Discount:</span>
              <div class="compare-card-values">
                <span class="old-value">${disc.history_percent}%</span>
                <i class="ti ti-arrow-right text-muted"></i>
                <span class="new-value">${disc.current_percent}%</span>
              </div>
            </div>
          `;
        } else {
          valueDisplay = `
            <div class="compare-card-row">
              <span class="compare-card-label">Discount:</span>
              <span>${disc.discount_percent || disc.current_percent || disc.history_percent || '—'}%</span>
            </div>
          `;
        }

        html += `
          <div class="col-md-6">
            <div class="compare-card ${actionClass}">
              <div class="compare-card-header">
                <span class="compare-card-title">
                  <i class="ti ti-discount-2"></i>
                  ${escapeHtml(category)} - ${escapeHtml(subcategory)}
                </span>
                ${actionBadge}
              </div>
              <div class="compare-card-body">
                ${valueDisplay}
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      return html;
    }

    function renderHealthCheckCards(healthChecks) {
      let html = '<div class="row g-3">';
      healthChecks.forEach(hc => {
        const actionClass = hc.action || 'unchanged';
        const actionBadge = getActionBadge(hc.action);
        const rowName = hc.row_name || 'Health Package';
        
        let changesHtml = '';
        if (hc.action === 'modified' && hc.changes) {
          for (const [key, val] of Object.entries(hc.changes)) {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            changesHtml += `
              <div class="compare-card-row">
                <span class="compare-card-label">${label}:</span>
                <div class="compare-card-values">
                  <span class="old-value">${val.old ?? '—'}</span>
                  <i class="ti ti-arrow-right text-muted"></i>
                  <span class="new-value">${val.new ?? '—'}</span>
                </div>
              </div>
            `;
          }
        } else {
          changesHtml = `
            <div class="compare-card-row">
              <span class="compare-card-label">Package Price:</span>
              <span>${hc.package_price || hc.current_price || hc.history_price || '—'}</span>
            </div>
            <div class="compare-card-row">
              <span class="compare-card-label">Approved Cost:</span>
              <span>${hc.approved_cost || hc.current_cost || hc.history_cost || '—'}</span>
            </div>
          `;
        }

        html += `
          <div class="col-md-6">
            <div class="compare-card ${actionClass}">
              <div class="compare-card-header">
                <span class="compare-card-title">
                  <i class="ti ti-heart-rate-monitor"></i>
                  ${escapeHtml(rowName)}
                </span>
                ${actionBadge}
              </div>
              <div class="compare-card-body">
                ${changesHtml}
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      return html;
    }

    function getActionBadge(action) {
      switch (action) {
        case 'added':
          return '<span class="badge bg-success"><i class="ti ti-plus me-1"></i>Added</span>';
        case 'removed':
          return '<span class="badge bg-danger"><i class="ti ti-minus me-1"></i>Removed</span>';
        case 'modified':
          return '<span class="badge bg-warning text-dark"><i class="ti ti-edit me-1"></i>Modified</span>';
        default:
          return '<span class="badge bg-secondary"><i class="ti ti-check me-1"></i>Unchanged</span>';
      }
    }

    function escapeHtml(text) {
      if (text === null || text === undefined) return '';
      const div = document.createElement('div');
      div.textContent = String(text);
      return div.innerHTML;
    }
  })();
</script>
@endsection