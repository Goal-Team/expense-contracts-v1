 @extends('contract-setup::layouts.admin')

 @section('content')
 <div class="panel-group" id="accordion">
     <div id="state" class="alert alert-success" style="display:none">Contract created successfully</div>
     <form id="createContract" enctype="multipart/form-data">
         <h1>
             Create Contract
         </h1>

         <input type="submit" />
         <div class="clearfix">
             <div class="col-sm-6">
                 <div class="form-group">
                     <label for="staus">Contract</label>
                     <label class="radio-inline"><input type="radio" class="contractmode" name="contractMode" value="new" checked>New</label>
                     <label class="radio-inline"><input type="radio" class="contractmode" name="contractMode" value="old">Old</label>
                 </div>
             </div>
         </div>
         <div class="panel panel-default" id="basic">
             <div class="panel-heading">
                 <h4 class="panel-title">
                     Basic Contract Information
                 </h4>
             </div>
             <div class="panel-collapse collapse in">
                 <div class="panel-body">
                     <div class="clearfix">
                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="contractName">Contract Name <span class="text-danger">*</span></label>
                                 <input type="text" class="form-control contractName" name="BasicContract[contractName]" id="contractName">
                             </div>
                         </div>
                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="sel1">Contract Type <span class="text-danger">*</span></label>
                                 <select class="form-control contracttype" name="BasicContract[contractType]" id="contracttype">
                                     <option value="">-Select Contract Type-</option>
                                     @foreach ($contractTypes as $contractType)
                                     <option value="{{ $contractType->contract_type_id }}">{{ $contractType->contract_type }}</option>
                                     @endforeach
                                 </select>
                             </div>
                         </div>

                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="sel1">Catgoery <span class="text-danger">*</span></label>
                                 <select class="form-control contracttype" name="BasicContract[contractType]" id="contracttype">
                                    <option value="">-Select Catgoery-</option>
                                    <option value="catgoery1">Catgoery 1</option>
                                    <option value="catgoery2">Catgoery 2</option>                                      
                                 </select>
                             </div>
                         </div>

                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="sel1">Department<span class="text-danger">*</span></label>
                                 <select class="form-control contracttype" name="BasicContract[contractType]" id="contracttype">
                                     <option value="">-Select Department Type-</option>
                                     <option value="department1">Department 1</option>
                                    <option value="department2">Department 2</option>      
                                 </select>
                             </div>
                         </div>
                     </div>
                     <div class="clearfix">
                         <div class="col-sm-12">
                             <div class="form-group">
                                 <label for="comment"> Contract Description <span class="text-danger">*</span></label>
                                 <textarea class="form-control description" name="BasicContract[contractDescription]" rows="5" id="description"></textarea>
                             </div>
                         </div>
                     </div>
                     <div class="col-sm-12">
                         @include('contract.viewCustomField', ['categoryId' => 1])
                     </div>
                 </div>

             </div>
         </div>
         <div class="panel panel-default">
             <div class="panel-heading">
                 <h4 class="panel-title">
                     Party Details
                 </h4>
             </div>
             <div class="panel-collapse collapse in">
                 <div class="panel-body">
                     <div class="party-group">

                     </div>
                     <button class="admo">+Add more parties</button>
                 </div>
             </div>
         </div>
         <div class="panel panel-default">
             <div class="panel-heading">
                 <h4 class="panel-title">
                     Contract Duration
                 </h4>
             </div>
             <div class="panel-collapse collapse in">
                 <div class="panel-body">
                     @include('contract.contractDuration')
                     <div class="col-sm-12">

                         @include('contract.viewCustomField', ['categoryId' => 2])
                     </div>
                 </div>
             </div>
         </div>
         <div class="panel panel-default">
             <div class="panel-heading">
                 <h4 class="panel-title">
                     Contract Value
                 </h4>
             </div>
             <div class="panel-collapse collapse in">
                 <div class="panel-body">
                     <div class="col-sm-6">
                         <div class="form-group row">
                             <div class="col-sm-12">
                                 <label for="ContractValue">Contract Value</label>
                             </div>

                             <div class="col-sm-3 col-xs-4">
                                 <select class="form-control" id="ContractValue" name="ContractValue[currency]">
                                     @foreach (currency() as $currency)
                                     <option value="{{ $currency }}">{{ $currency }}</option>
                                     @endforeach
                                 </select>
                             </div>
                             <div class="col-sm-9 col-xs-8">
                                 <input type="number" class="form-control" name="ContractValue[value]" id="ContractValue">
                             </div>
                             <div class="col-sm-12">
                                 <small class="form-text text-muted">The total monetary value of the contract.</small>
                             </div>
                         </div>
                     </div>
                     <div class="col-sm-6">
                         <div class="form-group">
                             <label for="PaymentSchedule">Payment Schedule</label>
                             <input type="text" class="form-control" name="ContractValue[paymentSchedule]" id="PaymentSchedule">
                             <small class="form-text text-muted">Details of payment milestones, amounts, and due
                                 dates.</small>
                         </div>
                     </div>

                     <div class="col-sm-3">
                         <div class="form-group">
                             <label for="Currencycontract">Currency of the contract</label>
                             <select class="form-control" id="Currencycontract" name="ContractValue[currencyContract]">
                                 @foreach (currency() as $currency)
                                 <option value="{{ $currency }}">{{ $currency }}</option>
                                 @endforeach
                             </select>
                             <small class="form-text text-muted">INR or FC</small>
                         </div>
                     </div>

                     <div class="col-sm-9">
                         <div class="form-group">
                             <label for="Currencycontract">Payment Terms</label>
                             <textarea class="form-control" name="ContractValue[paymentSchedule]" id="PaymentSchedule"></textarea>
                             <small class="form-text text-muted">Terms and conditions governing payments, including payment
                                 methods any late payment penalties.</small>
                         </div>
                     </div>

                     <div class="col-sm-6">
                         <div class="form-group">
                             <label for="Currencycontract">Billing Frequency</label>
                             <select class="form-control" id="BillingFrequency" name="ContractValue[billingFrequency]">
                                 <option value="monthly">Monthly</option>
                                 <option value="quarterly">Quarterly</option>
                                 <option value="annually">Annually</option>
                             </select>
                             <small class="form-text text-muted">Frequency at which invoices are issued (e.g., monthly,
                                 quarterly, annually).
                             </small>
                         </div>
                     </div>

                     <div class="col-sm-6">
                         <div class="form-group">
                             <label for="Currencycontract">Taxes and Fees</label>
                             <input type="text" class="form-control" name="ContractValue[taxes]" id="Taxes">
                             <small class="form-text text-muted">Any applicable taxes, fees, or surcharges associated with
                                 the contract.</small>
                         </div>
                     </div>

                     <div class="clearfix">
                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="Currencycontract">Escalation Clauses</label>
                                 <input type="text" class="form-control" name="ContractValue[escalationClauses]" id="EscalationClauses">
                                 <small class="form-text text-muted"> Provisions for adjusting contract prices over time
                                     based on predetermined factors such as inflation or market fluctuations.</small>
                             </div>
                         </div>

                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="Currencycontract">Discounts or Rebates</label>
                                 <input type="text" class="form-control" name="ContractValue[discounts]" id="Discounts">
                                 <small class="form-text text-muted">Any discounts or rebates applied to the
                                     contract.</small>
                             </div>
                         </div>
                     </div>
                     <div class="clearfix">
                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="Currencycontract">Retention or Holdbacks</label>
                                 <input type="text" class="form-control" name="ContractValue[Retention]" id="Retention">
                                 <small class="form-text text-muted">Amounts withheld from payments as retention or
                                     holdbacks pending completion of certain milestones or obligations.
                                 </small>
                             </div>
                         </div>
                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="Currencycontract">Payment Escrow</label>
                                 <input type="text" class="form-control" name="ContractValue[payment]" id="Payment">
                                 <small class="form-text text-muted">Details of any funds held in escrow for payment
                                     security or dispute resolution purposes.</small>
                             </div>
                         </div>
                     </div>

                     <div class="clearfix">
                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="Currencycontract">Financial Guarantees or Bonds</label>
                                 <input type="text" class="form-control" name="ContractValue[financialGuarantees]" id="Financial Guarantees">
                                 <small class="form-text text-muted">Information about any financial guarantees or bonds
                                     required under the contract.</small>
                             </div>
                         </div>
                         <div class="col-sm-6">
                             <div class="form-group">
                                 <label for="Currencycontract">Currency Conversion</label>
                                 <input type="text" class="form-control" name="ContractValue[currencyConversion]" id="CurrencyConversion">
                                 <small class="form-text text-muted">Terms for currency conversion if the contract involves
                                     transactions in multiple currencies.</small>
                             </div>
                         </div>
                     </div>
                     <div class="col-sm-12">
                         @include('contract.viewCustomField', ['categoryId' => 3])
                     </div>
                 </div>

             </div>
         </div>
         <div class="panel panel-default">
             <div class="panel-heading">
                 <h4 class="panel-title">
                     Contract Custom Fileds / Miscelleneous
                 </h4>
             </div>
             <div class="panel-collapse collapse in">
                 <div class="panel-body">
                     <div class="col-sm-12">

                         @include('contract.viewCustomField', ['categoryId' => 4])
                     </div>
                 </div>
             </div>
         </div>
         <div class="panel panel-default">
             <div class="panel-heading">
                 <h4 class="panel-title">
                     Contract attachment
                 </h4>
             </div>
             <div class="panel-collapse collapse in">
                 <div class="panel-body">
                     <div class="col-sm-6">
                         <label for="staus">Attachment</label>
                         <div class="form-group">
                             <label class="radio-inline"><input type="radio" class="attachment_group" name="AttachmentGroup" value="takefromtemplate" checked="">Take from
                                 template
                             </label>
                             <label class="radio-inline"><input type="radio" class="attachment_group" name="AttachmentGroup" value="pploadfile">Upload file</label>
                         </div>
                     </div>
                     <div class="clearfix">
                         <div class="col-sm-6">
                             <div class="custom-file" style="display: none;">
                                 <input type=file name="attachmentGroupFile" id="file" class="custom-file-input">
                                 <label class="custom-file-label" for="file">Choose File</label>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </form>
 </div>
 <script>
     $(document).ready(function() {
         var localState = 0;

         $('#updateStateTo5').click(function() {
             localState = 5;
             $('#stateText').addClass('hide');
             $('#stateText5').removeClass('hide');
         });

         $('#updateStateTo0').click(function() {
             localState = 0;
             $('#stateText5').addClass('hide');
             $('#stateText').removeClass('hide');
         });



         $('.showinedit, #termination').hide();

         $('.contractmode').on('change', function() {

             if ($(this).val() == 'old') {
                 $('.showinedit').show();
             }
             if ($(this).val() == 'new') {
                 $('.showinedit').hide();
             }
         })

         $('.attachment_group').on('change', function() {
             if ($(this).val() == 'takefromtemplate') {
                 $('.custom-file').hide()
             } else {
                 $('.custom-file').show()
             }
         })

         addMorePartis();
         addMorePartis();

         //  store/contract

         function addMorePartis() {
             $.ajax({
                 url: '<?php echo env('APP_URL') ?>/contract/create/parties',
                 type: 'POST',
                 data: {},
                 headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                 },
                 success: function(response) {
                     $(".party-group").append(response);
                     $('.partyc').each(function(index) {
                         $(this).html(index + 1);
                     })

                     $('.index').each(function(index) {
                         $(this).val(index);
                     })


                     //  $('.partygroupwrap').each(function(index) {
                     //      $(this).find('.partygroup').attr('name', 'partygroup[party][''][mode]');
                     //  })

                     $('.group-ry').each(function(index) {
                         $(this).find('.contractname').select2();
                         $(this).find('.partycontracttype').select2();
                         $(this).find('.partyExternal').select2();


                     })


                     $('.group-ry').each(function(index) {
                         $(this).find('.partygroup').attr('name', 'Partygroup[party][' + index + '][mode]');
                         $(this).find('.contractname').attr('name', 'Partygroup[party][' + index + '][internal_name]');
                         $(this).find('.partycontracttype').attr('name', 'Partygroup[party][' + index + '][location]');
                         $(this).find('.partyExternal').attr('name', 'Partygroup[party][' + index + '][external_name]');
                         $(this).find('.index').attr('name', 'Partygroup[party][' + index + '][index]');



                     })

                     $('.contractname').each(function(index) {
                         $(this).select2();
                     })

                     //  $('.index').each(function(index) {
                     //      $(this).val(index);
                     //  })



                     //


                     //  $('.contractname').each(function(index) {
                     //      $(this).select2();
                     //  });
                     //  $('.partycontracttype').each(function(index) {
                     //      $(this).select2();
                     //  });
                     //  $('.partyExternal').each(function(index) {
                     //      $(this).select2();
                     //  });

                     $('.partycontracttype').on('change', function() {
                         console.log('sdf');
                         if ($(this).val() != null) {
                             $(this).closest('.col-sm-6').find('.address-list li').hide();
                             $(this).closest('.col-sm-6').find('.address-list li#' + $(this).val()).show();
                         }
                     })
                     $('.partyExternal').on('change', function() {
                         console.log('sdf');
                         if ($(this).val() != null) {
                             $(this).closest('.col-sm-6').find('.external-address-list li').hide();
                             $(this).closest('.col-sm-6').find('.external-address-list li#' + $(this).val()).show();
                         }
                     })

                     $('.partygroup').on('change', function() {
                         if ($(this).val() == 'external') {
                             $(this).closest('.group-ry').find('.internal').hide();
                             $(this).closest('.group-ry').find('.external').show();
                         }
                         if ($(this).val() == 'internal') {
                             $(this).closest('.group-ry').find('.internal').show();
                             $(this).closest('.group-ry').find('.external').hide();
                         }
                     })
                 },
                 error: function(xhr, status, error) {
                     console.error(xhr.responseText);
                 }
             });
         }

         $('.admo').click(function(e) {
             e.preventDefault();
             addMorePartis();
         });


         $('.commencementDate').on('change', function() {
             $('#FixedDate, #Eventbased').hide();
             $('#' + $(this).val()).show();
         })

         $('.conditionEndContract').on('change', function() {
             $('#conditionEndContractOthers').hide();
             if ($(this).val() == 'others') {
                 $('#conditionEndContractOthers').show();
             }
         })



         $('.contractCommencementEffectiveDate').on('change', function() {
             $('#termination, #evergreen, #fixedTerm, #onetimeContract').hide();
             $('#' + $(this).val()).show();
         })


         $.validator.addClassRules({
             'contractName': {
                 required: true
             },
             'contracttype': {
                 required: true
             },
             'description': {
                 required: true
             },
             'required1': {
                 required: true
             },
         });

         $('#createContract').validate({
             ignore: [],
             errorPlacement: function(error, element) {
                 if (element.hasClass('select2-hidden-accessible')) {
                     error.insertAfter(element.next('.select2'));
                 } else {
                     error.insertAfter(element);
                 }
             },
             rules: {
                 "Duration[signingDate]": {
                     required: true,
                     date: true // This ensures the input is a valid date
                 },
                 "ContractValue[value]": {
                     required: true,
                 },
                 "ContractValue[paymentSchedule]": {
                     required: true,
                 },
                 "ContractValue[escalationClauses]": {
                     required: true,
                 },
                 "ContractValue[discounts]": {
                     required: true,
                 },
                 "ContractValue[taxes]": {
                     required: true,
                 },
                 "ContractValue[Retention]": {
                     required: true,
                 },
                 "ContractValue[payment]": {
                     required: true,
                 },
                 "ContractValue[currencyConversion]": {
                     required: true,
                 },
                 "ContractValue[financialGuarantees]": {
                     required: true,
                 },


                 description: {
                     required: true
                 },
                 contracttype: {
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
                 console.log('serializeObject', $(form).serializeObject());

                 var formData = new FormData(this);
                 var request = new FormData($("#createContract")[0]);
                 $.ajax({
                     url: '<?php echo env('APP_URL') ?>/store/contract',
                     type: 'POST',
                     data:request,
                     contentType: false,
                     processData: false,

                     headers: {
                         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                     },
                     success: function(response) {
                         $('#state').show();
                         setTimeout(() => {
                             location.reload();
                         }, 1000);
                     },
                     error: function(xhr, status, error) {
                         console.error(xhr.responseText);
                     }
                 });
             }
         });


     });


     // Click event handler for the button
 </script>
 <style>

 </style>
 @endsection
 @section('footer')


 @endsection