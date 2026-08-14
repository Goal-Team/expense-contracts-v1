@extends('contract::layouts.admin')

@section('content')
<div class="row">
    <h1>Custom Field</h1>
    <br />
    <form id="createCustom">
        <div class="row">
            <!-- Contract Type Selection -->
            <div class="col-sm-2 mb-3">
                <label class="form-label">Contract Type <span class="text-danger">*</span></label>
                <select class="form-control" name="contracttype" id="contracttype">
                    <option value="">-Select Contract Type-</option>
                    @foreach ($contractTypes as $contractType)
                    <option value="{{ $contractType->contract_type_id }}">{{ $contractType->contract_type }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <br />
        <h2>Add a Custom Field</h2>
        <div class="row">
            <!-- Category Selection -->
            @if (!empty($categorys))
            <div class="col-sm-6 mb-3">
                <label class="form-label">Select Category <span class="text-danger">*</span></label>
                <select class="form-control" name="category" id="category">
                    <option value="">-Select Category-</option>
                    @foreach ($categorys as $category)
                    <option value="{{ $category->category_id }}">{{ $category->category_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        <div class="row">
            <!-- Field Name Input -->
            <div class="col-sm-4 mb-3">
                <label class="form-label">Field Name <span class="text-danger">*</span></label>
                <input type="text" name="label" class="form-control" id="label">
            </div>
            <input type="hidden" name="val" class="form-control" id="val">
            <!-- Field Type Selection -->
            <div class="col-sm-2 mb-3">
                <label class="form-label">Field Type <span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="type">
                    <option selected value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="date">Date</option>
                    <option value="number">Number</option>
                    <option value="select">Select</option>
                    <option value="currency">Currency</option>
                </select>
            </div>
        </div>
        <div class="row">
            <!-- Required Checkbox -->
            <div class="col-sm-2 mb-3">
                <label class="form-label">Required</label>
                <input type="checkbox" name="required" value="1" id="required">
            </div>
            <!-- Generic field: additionally shown for every contract type -->
            <div class="col-sm-4 mb-3">
                <label class="form-label" for="is_generic">Applies to all contract types</label>
                <input type="checkbox" name="is_generic" value="1" id="is_generic">
                <small class="d-block text-muted">Shows this field on every contract type, in addition to the one
                    selected above.</small>
            </div>
        </div>
        <!-- Edit Options Link -->
        <a class="openselctopino" href="javascript:void(0)" style="display:none" data-toggle="modal" data-target="#exampleModal">Edit Options</a>
        <div class="col-sm-6 mb-3 row">
            <button type="submit" class="btn btn-primary mt-3">Submit</button>
        </div>
    </form>

    <!-- Success and Error Messages -->
    <div id="state" class="alert alert-success" style="display:none">Created successfully</div>
    <div id="dstate" class="alert alert-danger" style="display:none">Deleted successfully</div>
    <div id="ustate" class="alert alert-warning" style="display:none">Updated successfully</div>
</div>

<div class="row">
    <h2>Section</h2>
    <button class="btn btn-primary mt-3 formdata">Submit</button>
    <div id="form-list"></div>
</div>

<!-- Modal for Editing Select Options -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-sm-12">
                    <form id="selectoptions">
                        <div class="dropdownoptions row" id="dropdownOptions"></div>
                        <a href="#" class="addoption">Add option</a>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" style="display: none;" class="btn btn-primary saveselct">Save changes</button>
                <button type="button" data-dismiss="modal" style="display: none;" class="btn btn-primary saveselctupdate">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize select2 plugin for dropdowns
    $('#contracttype, #category').select2();

    // Function to reset index values
    function resetIndex() {
        $('.panel-body').find('input.index').each(function(index) {
            $(this).val(index + 1);
        });
        $('.panel-body').find('input.group').val($.trim($(this).closest('.panel-default').find('.panel-title').text()));
    }

    // Open select options modal
    $('.openselctopino').click(function() {
        $('#dropdownOptions').html('');
        listingGroup(['option1', 'option2', 'option3']);
        $('.saveselct').show();
        $('.saveselctupdate').hide();
    });

    // Edit select options
    $("#form-list").delegate(".editselctopino", "click", function(e) {
        e.preventDefault();
        $('#dropdownOptions').html('');
        listingGroup($(this).closest('.title').find('.value').val().split(","));

        $('.saveselct').hide();
        $('.saveselctupdate').show();

        $('.formlabel').removeClass('curt');

        $(this).closest('.formlabel').addClass('curt');

    });


    $('.saveselctupdate').click(function() {
        var optionsArray = $('#selectoptions').serializeObject().select.options;
        var optionsString = optionsArray.join(",");
        console.log(optionsString);
        $('.curt').find('.value').val(optionsString);
    }); 
    $("#selectoptions").delegate(".opton-del", "click", function(e) {
        e.preventDefault();
        if (confirm("Are you sure?")) {
            $(this).closest('.dropdownoption').remove();
        }
    });
    $(".panel-body").delegate(".list-type", "change", function(e) {
        e.preventDefault();
        if ($(this).val() == 'select') {
            $(this).closest('.title').find('.editselctopino').show();
        } else {
            $(this).closest('.title').find('.editselctopino').hide();
        }
    });
    // Delete a custom field
    $("#form-list").delegate(".delete", "click", function(e) {
        e.preventDefault();
        if (confirm("Are you sure?")) {
            $(this).closest('.col-sm-6').find('.status').val(0);
            $(this).closest('.col-sm-6').hide();
            $('#dstate').show();
            setTimeout(() => {
                $('#dstate').hide();
            }, 2000);
        }
    });

    // Add new option in select modal
    $('.addoption').click(function(e) {
        e.preventDefault();
        listingGroup(['option1']);
    });
    // Save select options
    $('.saveselct').click(function() {
        var optionsArray = $('#selectoptions').serializeObject().select.options;
        var optionsString = optionsArray.join(",");
        $('#val').val(optionsString);
    });
    // Form validation
    $('#createCustom').validate({
        ignore: [],
        errorPlacement: function(error, element) {
            if (element.hasClass('select2-hidden-accessible')) {
                error.insertAfter(element.next('.select2'));
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            label: {
                required: true
            },
            contracttype: {
                required: true
            },
            category: {
                required: true
            }
        },
        messages: {
            label: {
                required: "Please enter a Field Name"
            },
            contracttype: {
                required: "Please select a Contract Type"
            },
            category: {
                required: "Please select a Category"
            }
        },
        submitHandler: function(form) {
            $.ajax({
                url: '<?php echo env('APP_URL') ?>/contract/contract',
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#state').show();
                    setTimeout(() => {
                        getData();
                        $('#state').hide();
                        $('#contracttype, #category').val(null).trigger('change');
                        $('#createCustom')[0].reset();
                        $('.openselctopino').hide();
                    }, 500);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
            return false;
        }
    });

    // Trigger validation on input change
    $('select, input').on('change', function() {
        $('#createCustom').valid();
    });

    // Show/Hide select options link based on field type
    $('#type').on('change', function() {
        if ($(this).val() == 'select') {
            $('.openselctopino').show();
        } else {
            $('.openselctopino').hide();
        }
    });
    // Fetch form data
    function getData() {
        var form = $('#createCustom');
        $.ajax({
            url: '<?php echo env('APP_URL') ?>/contract/list',
            type: 'POST',
            data: $(form).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#form-list').html(response);
                resetIndex();
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }
    // Fetch data on contract type change
    $('#contracttype').on('change', function() {
        getData();
    });
    function listingGroup(selectOption, index = -1) {
        selectOption.forEach(function(option, i) {
            // Check if the current iteration index matches the provided index
            if (index === i) {
                // If the index matches, set the input value to the provided option
                var value = option;
            } else {
                // Otherwise, set the input value to the current option in the array
                var value = option;
            }
            var dropdownOption = $('<div class="dropdownoption col-sm-12 formlabel"></div>');

            dropdownOption.append('<div class="cusor"><img src="<?php echo asset('images/drag.webp') ?>" style="width: 0.9rem; margin-top: -0.4rem;"></div><input type="text" value="' +
                value + '" name="select[options][]"><button class="opton-del pull-right">Delete</button>');
            $('#dropdownOptions').append(dropdownOption);
        });
    }
    // Update custom fields
    $('.formdata').click(function(e) {
        $.ajax({
            url: '<?php echo env('APP_URL') ?>/contract/update',
            type: 'POST',
            data: $('#updateCustom').serializeObject(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#ustate').show();
                setTimeout(() => {
                    $('#ustate').hide();
                    getData();
                }, 500);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });
</script>
<style>
    .title {
        display: block;
    }

    .formlabel input {
        border: none;
    }

    .formintype .form-control {
        border: none;
        background: none;
        box-shadow: none;
        margin-top: -0.5rem;
    }

    .formintype {
        float: right;
        color: #8d8d8d;
        text-transform: capitalize;
        font-size: 1.1rem;
    }

    .formlabel {
        border: 1px solid #ccc;
        background: #fff;
        margin: 0 1px;
        padding: 0.5rem 1.2rem;
        margin-bottom: 1.6rem;
    }

    .cursor,
    .cusor {
        color: #fff;
        cursor: move;
        float: left;
        width: 2.7rem;
        background: #dfdfdf;
        padding: 1rem;
        height: 2.7rem;
        line-height: 0.5;
        margin-right: 0.5rem;
        margin-left: -0.7rem;
    }

    .high {
        opacity: 0.5;
    }

    .ui-sortable-helper {
        opacity: 0.6;
    }
</style>
@endsection
@section('footer')
@endsection