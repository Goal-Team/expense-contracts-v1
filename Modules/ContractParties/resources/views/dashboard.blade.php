@extends('layouts/layoutMaster')
@section('title', ' Contracts')
<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss','resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])

<link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>

<script type="module">
  if (typeof flatpickr !== 'undefined') {


    $(".flatpickr").flatpickr({
      altInput: true,
      altFormat: "F j, Y",
      //   defaultDate: new Date(),
      dateFormat: "Y-m-d",
      prevArrow: "<i class='fa fa-chevron-left'></i>",
      nextArrow: "<i class='fa fa-chevron-right'></i>"
    });


  }
</script>

@endsection
@section('content')

<div class="col-lg-12">



  <div class="card h-100">
    <div class="card-header d-flex justify-content-between">
      <h5 class="card-title mb-0">Parties Type</h5>
    </div>
    <div class="card-body pt-2">
      <div class="row">
        <div class="col-md-6">
          <div class="row gy-3">
            <div class="col-md-4 col-6 clickableDashItems cursor-pointer"
              data-cushref="https://test.legalityevaluate.in/contractsdemo/contracts/list?status=executed_active">
              <div class="d-flex align-items-center">
                <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-file-like ti-xl"></i></div>
                <div class="card-info">
                  <h5 class="mb-0">{{$internal}}</h5>
                  <small>Internal</small>
                </div>
              </div>
            </div>
            <div class="col-md-4 col-6 clickableDashItems cursor-pointer"
              data-cushref="https://test.legalityevaluate.in/contractsdemo/contracts/list?status=executed_completed">
              <div class="d-flex align-items-center">
                <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-file-like ti-xl"></i></div>
                <div class="card-info">
                  <h5 class="mb-0">{{$external}}</h5>
                  <small>External</small>
                </div>
              </div>
            </div>
            <div class="col-md-4 col-6 clickableDashItems cursor-pointer"
              data-cushref="https://test.legalityevaluate.in/contractsdemo/contracts/list?status=executed_terminated">
              <div class="d-flex align-items-center">
                <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-file-like ti-xl"></i></div>
                <div class="card-info">
                  <h5 class="mb-0">{{$related_party}}</h5>
                  <small>Related party</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="row gy-3">
            <div class="col-md-4 col-6 clickableDashItems cursor-pointer"
              data-cushref="https://test.legalityevaluate.in/contractsdemo/contracts/list?status=executed_renewed">
              <div class="d-flex align-items-center">
                <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-files ti-xl"></i></div>
                <div class="card-info">
                  <h5 class="mb-0">{{$vendors}}</h5>
                  <small>Vendors</small>
                </div>
              </div>
            </div>
            <div class="col-md-4 col-6 clickableDashItems cursor-pointer"
              data-cushref="https://test.legalityevaluate.in/contractsdemo/contracts/list?status=executed_renewed">
              <div class="d-flex align-items-center">
                <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-files ti-xl"></i></div>
                <div class="card-info">
                  <h5 class="mb-0">{{$customer}}</h5>
                  <small>Customer</small>
                </div>
              </div>
            </div>
            <div class="col-md-4 col-6 clickableDashItems cursor-pointer"
              data-cushref="https://test.legalityevaluate.in/contractsdemo/contracts/list?status=executed_renewed">
              <div class="d-flex align-items-center">
                <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-files ti-xl"></i></div>
                <div class="card-info">
                  <h5 class="mb-0">{{$supplier}}</h5>
                  <small>Supplier</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>



<style>
  .col-lg-2.col-sm-2.mb-4 {
    width: 20%;
  }

  .headStyle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-right: 15px;
  }

  .table th {
    text-align: left !important;
  }

  table.dataTable.table-striped>tbody>tr:nth-of-type(odd)>* {
    box-shadow: none;
  }

  table tr th,
  table tr td {
    border-right-width: 0 !important;
  }

  table.table-bordered.dataTable thead tr:first-child th,
  table.table-bordered.dataTable thead tr:first-child td {
    border-top-width: 0 !important;
  }

  table td.dataTables_empty {
    padding: 5rem !important;
  }

  @media(max-width:767px) {

    .col-lg-2.col-sm-2.mb-4 {
      width: 100%;
    }

    table.table td {
      padding-left: 5%;
    }

    table thead {
      display: none;
    }

    table td {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      border-bottom: 1px solid #eee;
      font-size: 15px;
      line-height: 1.35em;
    }

    table td:before {
      content: attr(data-label);
      font-size: 0.9em;
      text-align: left;
      font-weight: bold;
      text-transform: capitalize;
      max-width: 45%;
      color: #545454;
    }

    table td+td {
      margin-top: 0.8em;
      text-align: left;
    }

    table td:last-child {
      border-bottom: 0;
    }

    .project-list-table {
      border-collapse: separate;
      border-spacing: 0 12px
    }

    .project-list-table tr {
      background-color: #fff
    }

    .table-nowrap td,
    .table-nowrap th {
      white-space: nowrap;
    }

    .table-borderless>:not(caption)>*>* {
      border-bottom-width: 0;
    }

    .table>:not(caption)>*>* {
      padding: 0.75rem 0.75rem;
      background-color: var(--bs-table-bg);
      border-bottom-width: 1px;
      box-shadow: inset 0 0 0 9999px var(--bs-table-accent-bg);
    }

    table.table tbody tr:nth-of-type(odd) {
      background-color: rgba(204, 209, 216, 0.5);
    }

    table.table tbody tr,
    table.table tbody td {
      margin: 1rem 0;
    }
  }
</style>
@endsection