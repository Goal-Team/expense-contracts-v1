@extends('layouts/layoutMaster')
@section('title', ' Contracts')
<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss','resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])

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
    <div class="row">
        <div class="col-lg-3 col-sm-3 mb-4">
            <label>Contract Type</label>
            <select class="select2" name="contractType[]" multiple="multiple">
                <option value="">Select All</option>
                <option value="1">
                    Rental Agreement
                </option>

                <option value="2">
                    Disclosure Agreement
                </option>

                <option value="3">
                    Agreement
                </option>

                <option value="4">
                    Test Agreement
                </option>

                <option value="5">
                    test
                </option>

                <option value="6">
                    new type 2
                </option>

                <option value="7">
                    new type
                </option>
            </select>
        </div>
        <div class="col-lg-3 col-sm-3 mb-4">
            <label>Currency</label>
            <select class="select2">
                <option value="">Select All</option>
                <option value="INR">INR</option>
                <option value="USD">USD</option>
            </select>
        </div>
        <div class="col-lg-3 col-sm-3 mb-4">
            <label>Status</label>
            <select class="select2">
                <option>Expired 1 month</option>
                <option>Expired</option>
                <option>Approved</option>
            </select>
        </div>
        <div class="col-lg-3 col-sm-3 mb-4">
            <label>Status</label>
            <select class="select2">
                <option>Expired 1 month</option>
                <option>Expired</option>
                <option>Approved</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-sm-3 mb-4">
            <label>Branch</label>
            <select class="select2" name="branch[]" multiple="multiple">
                <option value="0">-Select-</option>
                <option value="1">
                    Test-Adyar
                </option>
                <option value="2">
                    Test-Egmore
                </option>
                <option value="3">
                    Test-Mylapore
                </option>
                <option value="4">
                    Test-Mannady
                </option>
                <option value="5">
                    Test-Porur
                </option>
                <option value="6">
                    Test-Greems Road
                </option>
                <option value="7">
                    Test-Tambaram
                </option>
            </select>
        </div>
        <div class="col-lg-3 col-sm-3 mb-4">
            <label> Signatory</label>
            <select class="select2" name="signatory[]" multiple="multiple">
                <option value=""> -Select Signatory-</option>
                <option value="1">
                    Test
                </option>
                <option value="2">
                    Gopinath
                </option>
                <option value="3">
                    Karunakaran
                </option>
                <option value="4">
                    Mohamed Kader
                </option>
                <option value="5">
                    Ragul
                </option>
                <option value="6">
                    Sathasivam
                </option>
            </select>
        </div>
        <div class="col-lg-3 col-sm-3 mb-4">
            <div class="form-group required-0">
                <label for="Currencycontract">
                    Start Date
                </label>
                <input type="text" class="form-control flatpickr" name="startDate">
            </div>
        </div>
        <div class="col-lg-3 col-sm-3 mb-4">
            <div class="form-group required-0">
                <label for="Currencycontract">
                    End Date
                </label>
                <input type="text" class="form-control flatpickr" name="endDate">
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-10">
    </div>
    <div class="col-lg-2">
        <button class="btn btn-label-primary">Clear</button>
        <button class="btn btn-primary ">Search</button>
    </div>
</div>

<div class="card mt-5">
    <div class="card-widget-separator-wrapper">
        <div class="card-body card-widget-separator">
            <div class="row gy-4 gy-sm-1">
                <div class="col-sm-6 col-lg-3">
                    <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                        <div>
                            <p class="mb-1">Expired</p>
                            <h4 class="mb-1">43</h4>
                        </div>
                    </div>
                    <hr class="d-none d-sm-block d-lg-none me-6">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                        <div>
                            <p class="mb-1">Expire soon</p>
                            <h4 class="mb-1">5</h4>
                        </div>
                    </div>
                    <hr class="d-none d-sm-block d-lg-none">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                        <div>
                            <p class="mb-1">Approved</p>
                            <h4 class="mb-1">12</h4>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1">Canceled</p>
                            <h4 class="mb-1">23</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="card mt-5">
    <div class="card-widget-separator-wrapper">

        <div class="card-datatable text-responsive">



            <table class="dt-column-search table dataTable no-footer">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contract Name</th>
                        <th>Date</th>
                        <th>Ageing Days</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="odd">

                        <td>1</td>
                        <td>asddasd</td>
                        <td>2024-08-15</td>
                        <td>5</td>
                        <td>
                            <a href="D" class="btn btn-sm btn-icon dropdown-toggle hide-arrow text-body" data-bs-toggle="tooltip" title="Preview"><i class="ti ti-eye mx-2 ti-sm"></i></a>
                        </td>
                    </tr>

                </tbody>
            </table>
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