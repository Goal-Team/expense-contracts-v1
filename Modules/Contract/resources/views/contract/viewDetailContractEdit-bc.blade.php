<div class="row my-4">
   <div class="col">
      <form class="row" id="createcontract" action="store/contract" method="POST" enctype="multipart/form-data">
          <div class="col-md mb-4 mb-md-2">
            <div class="accordion mt-3" id="accordionWithIcon">
                <div class="card accordion-item active">
                   <div class="card-header">
                     <label class="form-check-label">Contract</label>
                     <div class="col mt-2">
                         
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                           <input type="radio" class="contractmode form-check-input" name="contractMode" value="new" {{ $contract->contract_mode == 'new' ? 'checked' : '' }}
>
                           New</label>
                        </div>
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                           <input type="radio" class="contractmode form-check-input" name="contractMode" value="old" {{ $contract->contract_mode == 'old' ? 'checked' : '' }}
> 
                           Legacy Contracts </label>
                        </div>
                     </div>
                  </div>
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                     Basic Contract Information
                     </button>
                  </h2>
                  <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">
                            
                           <div class="col-md-6">
                              <label class="form-label" for="contracttype">Contract Type <span
                                 class="text-danger">*</span></label>
                              <select class="form-select select2 contracttype"
                                 name="BasicContract[contractType]" id="contracttype">
                                 @foreach ($contractTypes as $contractType)
                                                    
                                    @if($contract->contract_type == $contractType->contract_type)
                                    <option value="{{ $contractType->contract_type_id }}" seleted>
                                        {{ $contractType->contract_type }}</option>
                                    @else
                                    <option value="{{ $contractType->contract_type_id }}">
                                        {{ $contractType->contract_type }}</option>
                                    @endif
                                    @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <!--<label class="form-label" for="ecommerce-product-barcode">Contract Type</label>-->
                              <label class="form-label" for="signatory">Signatory <span class="text-danger">*</span></label>
                              <!--<select id="signatory" name="BasicContract[signatory]" class="form-select select2" data-allow-clear="true">-->
                              <!--<option value="">Select Signatory</option>-->
                              <!--<option value="Test">Test</option>-->
                              <!--<option value="Demo">Demo</option>-->
                              <!--</select>    -->
                              <select class="form-select select2 " name="BasicContract[signatory]"
                                 id="signatory">
                                 <option value="">-Select Signatory-</option>
                                 @foreach ($users as $user)
                                 <option value="{{ $user->id }}">
                                    {{ $user->FirstName }}
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="catgoeryType">Department <span class="text-danger">*</span></label>
                              <select id="catgoeryType" name="BasicContract[DepartmentType]"
                                 class="form-select select2" data-allow-clear="true">
                                 <option value="">Select Department</option>
                                    <option value="2">Admin</option>
                                    <option value="3">Finance</option>
                                    <option value="4">Secretarial</option>
                                    <option value="5">Quality</option>
                                    <option value="6">Legal</option>
                                    <option value="7">Marketing</option>
                                    <option value="8">Technology</option>
                                    <option value="9">Human Resources</option>
                                    <option value="10">Engineering</option>
                                    <option value="11">Maintenance</option>
                                    <option value="12" selected>Food &amp; Beverages</option>
                                    <option value="13">Pharmacy</option>
                                    <option value="14">Blood Bank</option>
                                    <option value="15">Radiology</option>
                                    <option value="16">Oncology - NM</option>
                                    <option value="17">Oncology - Therapy</option>
                                    <option value="18">Ultrasound</option>
                                    <option value="19">Gynecology</option>
                                    <option value="20">Transplant</option>
                                    <option value="21">Biomedical Engineering</option>
                                    <option value="22">Medical Records</option>
                                    <option value="23">House Keeping</option>
                                    <option value="24">Transport</option>
                                    <option value="25">Production</option>
                                    <option value="26">Operations</option>
                                    <option value="27">General Stores</option>
                                    <option value="28">FEMA</option>
                                    <option value="29">Fire</option>
                                    <option value="30">Laboratory</option>
                                    <option value="31">EHS</option>
                                    <option value="32">EXIM</option>
                                    <option value="33">Procurement</option>
                                    <option value="34">Electrical Maintenance</option>
                                    <option value="35">Mechanical</option>
                                    <option value="36">Purchase</option>
                                    <option value="37">Environmental Cell</option>
                                    <option value="38">Estate</option>
                                    <option value="39">Sales</option>
                                    <option value="40">Corporate HR</option>
                                    <option value="41">Energy Efficiency</option>
                                    <option value="42">BOE</option>
                                    <option value="43">Dump</option>
                                    <option value="44">Materials</option>
                                 
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="DepartmentType">Category <span class="text-danger">*</span></label>
                              <select id="DepartmentType" name="BasicContract[catgoeryType]"
                                 class="form-select select2 DepartmentType" data-allow-clear="true">
                                 <option value="">Select Category</option>
                                    <option value="1">C1</option>
                                    <option value="2">C2</option>
                                    <option value="3">C3</option>
                                    <option value="4">C4</option>
                                    <option value="5">C5</option>
                                    <option value="6">C6</option>
                                    <option value="7">C7</option>
                                    <option value="8">C8</option>
                                    <option value="9">C9</option>
                                    <option value="10">C10</option>
                              </select>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label" for="contractDescription">Contract
                               Description</label>
                               <textarea class="form-control" id="contractDescription"
                                  name="BasicContract[contractDescription]" rows="5">{{ $contract->contract_description }}</textarea>
                            </div>
                            
                            <!--<div class="col-12">-->
                            <!--    <h6 class="mt-4">Custom Fields</h6>-->
                            <!--    <hr class="mt-0" />-->
                            <!--</div>-->
                            <!--<div class="row mb-3">-->
                            <!--    @include('contract::contract.viewCustomField', ['categoryId' => 1]) -->
                            <!--</div>-->

                        </div>
                     </div>
                  </div>
                  
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                     Party Details
                     </button>
                  </h2>
                  <div id="accordionWithIcon-2" class="accordion-collapse collapse">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">
                            
                            <!--<div class="panel-body">-->
                            <!--    <div class="party-group">-->
                            <!--    </div>-->
                                <!--<button class="admo">+Add more parties</button>-->
                            <!--    <button type="submit" class="btn btn-primary me-sm-3 me-1 admo">+Add more-->
                            <!--    parties</button>-->
                            
                            <!-- </div>-->

                        </div>
                        <div class="row add_users" style="margin-top: 30px;">
                           <input type="hidden" id="user_position" value="1" />
                           <div class="col-md-6">
                              <div class="row" style="" id="">
                                 
                              </div>
                           </div>
                           <div class="col-md-6">
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-3" aria-expanded="false">
                     Contract Duration
                     </button>
                  </h2>
                  <div id="accordionWithIcon-3" class="accordion-collapse collapse">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        
                        <div class="form-group signing_date">
                            <h5>Signing Date</h5>
                            <!--<label>Date</label>-->
                            <div class="clearfix row">
                                <div class="col-sm-4"><input type="date" name="Duration[signingDate]" class="form-control flatpickr" value="{{ $contract->signing_date }}"/></div>
                            </div>
                            <div class="clearfix">
                                <small class="form-text text-muted">The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract.
                                </small>
                            </div>
                        </div>
                        <div class="">
                            <div class="col-sm-12">
                                <div class="form-group mt-3">
                                    <h5>Contract Commencement</h5>
                                    <hr class="mt-0" />
                                    <label>Effective date:</label>
                                    <div class="clearfix mt-2">
                                        <label class="form-check-inline form-check">
                                            <input type="radio" class="form-check-input commencementDate"  name="Duration[commencementDate]" value="FixedDate" {{ $contract->end_contract_type == 'onetimeContract' ? 'checked' : '' }}>
                                            Fixed Date
                                        </label>
                                        <label class="form-check-inline form-check">
                                            <input class="form-check-input commencementDate" type="radio" name="Duration[commencementDate]" value="Eventbased" {{ $contract->end_contract_type == 'Eventbased' ? 'checked' : '' }}>
                                            Event based commencement
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12" id="FixedDate">
                                <div class="form-group mt-3">
                                    <label>Fixed Date</label>
                                    <div class="clearfix row">
                                        <div class="col-sm-4"><input type="date" name="Duration[fixedDate]" class="form-control flatpickr" value="{{ $contract->fixed_date }}"/></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12" id="Eventbased" style="display: none;">
                                <div class="form-group row mt-3">
                                    <div class="col-sm-12">
                                        <label> Event based commencement</label>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mt-2">
                                            <label>(i) Event Condition</label>
                                            <div class="clearfix">
                                                <select class="form-control" name="Duration[eventCondition]">
                                                    <option value="uponCompletion">Upon Completion of Specify Event</option>
                                                    <option value="uponDelivery">Upon Delivery of Specify Deliverable</option>
                                                    <option value="uponApproval">Upon Approval of Specify Approval Process</option>
                                                    <option value="other">Other with a text field for specifying the event Condition</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mt-2">
                                            <label>(ii) Name of event</label>
                                            <div class="clearfix">
                                                <textarea class="form-control" name="Duration[nameofevent]"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mt-2">
                                            <label>(iii) Event Details</label>
                                            <div class="clearfix">
                                                <textarea class="form-control" name="Duration[eventDetails]"></textarea>
                                                <small class="form-text text-muted">If "Event-Based Commencement" is selected, this field allows the user to provide additional details or specifics about the event triggering the commencement of the contract.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mt-2">
                                            <label>(iv) Event deadline</label>
                                            <div class="clearfix">
                                                <input type="date" class="form-control flatpickr" name="Duration[eventDeadline]" />
                                            </div>
                                        </div>
                                    </div>
                        
                                </div>
                            </div>
                        
                        
                        </div>
                        <div class="">
                        <div class="col-sm-12">
        <div class="form-group mt-4">
            <hr class="mt-0" />
            <h5>End of Contract Term</h5>
            <hr class="mt-0" />
            <label>Effective date:</label>
            <div class="clearfix mt-2">
                <label class="form-check-inline form-check"><input type="radio" class="contractCommencementEffectiveDate form-check-input" name="Duration[effectiveDate]" value="onetimeContract" {{ $contract->end_contract_type == 'onetimeContract' ? 'checked' : '' }}>One time Contract</label>
                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" value="fixedTerm" {{ $contract->end_contract_type == 'fixedTerm' ? 'checked' : '' }}>Fixed Term Contract with Periodic Renewal</label>
                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" value="evergreen" {{ $contract->end_contract_type == 'evergreen' ? 'checked' : '' }}>Evergreen Contracts </label>
                <label class="form-check-inline form-check showinedit"> <input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" value="termination" {{ $contract->end_contract_type == 'termination' ? 'checked' : '' }}>Termination</label>
            </div>
        </div>
    </div>

        <div class="col-sm-4" id="onetimeContract">
            <div class="form-group mt-3">
                <hr class="mt-0" />
                <h5>One time Contract</h5>
                <hr class="mt-0" />
                <div class="form-group">
                    <label>End date of contract</label>
                    <div class="clearfix">
                        <input type="date" name="Duration[onetimeEndDateofContract]" class="form-control flatpickr" value="{{ $contract->onetime_end_date }}"/>
                    </div>
                </div>
            </div>
        </div>


    <div class="col-sm-12 mt-2" id="fixedTerm" style="display: none;">
        <hr class="mt-3" />
        <h5 class="mt-2">Fixed Term Contract with Periodic Renewal</h5>
        
        <hr class="mt-3" />
        
        <div class="form-group row mt-2">
            <div class="row">
                <div class="form-group  col-sm-3 mt-2">
                    <label>End date of contract</label>
                    <div class="clearfix">
                        <input type="date" class="form-control flatpickr" name="Duration[fixedtimeEndDateofContract]" value="{{ $contract->fixedterm_end_date }}"/>
                    </div>

                </div>

                <div class="form-group  col-sm-5 mt-2">
                    <label>Type of Renewal</label>
                    <div class="clearfix">
                        <select class="form-control typerenewal" name="Duration[typeRenewal]">
                            <!--<option value="{{ $contract->renewal_type }}">{{ $contract->renewal_type }}</option>-->
                            <option value="automaticrenewal">Automatic renewal with notice</option>
                            <option value="manualRenewal">Manual Renewal with notice</option>
                            <!--<option value="manualRenewal">Manual Renewal with notice</option>-->
                        </select>
                    </div>
                </div>
                <div class="form-group  col-sm-4 mt-2">
                    <label>Period of auto renewal</label>
                    <div class="clearfix row">
                        <div class="col-sm-5"><input class="form-control" type="text" name="Duration[periodAutoRenewal]"></div>
                        <div class="col-sm-7">
                            <select class="form-control" name="Duration[periodAutoRenewalPeriod]">
                                <option value="years">{{ $contract->period_auto_renewal_unit }}</option>
                                <!--<option value="months">Months</option>-->
                                <!--<option value="days">Days</option>-->
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="form-group  col-sm-3 mt-2">
                    <label class="typerenewallable">Auto renewal Date:</label>
                    <div class="clearfix">
                        <input type="date" class="form-control flatpickr" name="Duration[autoRenewalDate]" value="{{ $contract->auto_renewal_date }}"/>
                    </div>
                </div> 
            </div>

        </div>
    </div>



    <div class="col-sm-6" style="display: none;" id="evergreen">
        <hr class="mt-3" />
        <div class="form-group mt-3">
            <h5>Evergreen Contracts</h5>
            <hr class="mt-3" />
            <div class="form-group">
                <label>Condition for end of contract:</label>
                <div class="clearfix">
                    <select class="form-control conditionEndContract" name="Duration[conditionEndContract]">
                        <option value="mutually">{{ $contract->evergreen_condition }}
                        </option>
                        <!--<option value="termination">When Termination Clause is triggered-->
                        <!--</option>-->
                        <!--<option value="delivered">When good are delivered/ project is completed/ milestone is achieved-->
                        <!--</option>-->
                        <!--<option value="others">others [specify]-->
                        <!--</option>-->
                    </select>
                </div>
                <div class="clearfix">
                     <input type="text" style="display: none;" id="conditionEndContractOthers" class="form-control" name="Duration[conditionEndContractOthers]">
                </div>
            </div>
        </div>
    </div>


    <div class="col-sm-12" id="termination">
        <h4>Termination</h4>

        <div class="clearfix row">
            <div class="form-group col-sm-3">
                <div class="form-group">
                    <label>Date</label>
                    <div class="clearfix">
                        <input type="date" class="form-control" name="Duration[terminationDate]" />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group  col-sm-6">
                    <label>Reason for termination</label>
                    <div class="clearfix">
                        <select class="form-control" name="Duration[reasonTermination]">
                            <option value="mutually">When mutually agreed to end</option>
                            <option value="termination">When Termination Clause is triggered</option>
                            <option value="dispute">Dispute</option>
                            <option value="nonRenewal">Non renewal</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="clearfix mt-4"> 
<hr>
        <div class="clearfix mb-4">
            <label for="Reminder"> Enable Reminder</label>
            <input type="checkbox" class="form-check-input " id="Reminder" name="Duration[reminderEnable]" />
        </div>
    <div class="nav-align-top nav-tabs-shadow mb-4">
     
        <div class="col-sm-12">
      <ul class="nav nav-tabs m-0 m0" role="tablist">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-home" aria-controls="navs-top-home" aria-selected="true">Fist level</button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-profile" aria-controls="navs-top-profile" aria-selected="false">Second Level</button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-messages" aria-controls="navs-top-messages" aria-selected="false">Escalation</button>
        </li> 
      </ul>
           
      </div>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Alert Me about</label>
                        <select class="select2 form-select valid" id="AlertMe" name="Duration[Reminder][first][alertMe]" aria-invalid="false">
                            <option>{{ $contract->reminder_first_alert }}</option>
                            <!--<option>Internal Due Date</option>-->
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class="">Alert Me on</label>
                         <div class="col">
                            <input type="text" class="form-control" min="1" name="Duration[Reminder][first][alertMeDay]" value="{{ $contract->reminder_first_alertMeOn }}"/>
                        </div>
                         <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][first][alertMePrior]">
                                <option value="days">{{ $contract->reminder_first_alert_repeats }}</option>
                                <!--<option value="months">Months</option>-->
                                <!--<option value="years">Years</option>-->
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][first][alertMeType]">
                                <option value="prior">{{ $contract->reminder_second_alert }}</option>
                                <!--<option value="prior">Prior</option>-->
                                <!--<option value="after">After</option>-->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][first][repeats]" aria-invalid="false">
                            <option>{{ $contract->reminder_first_alert_repeats }}</option>
                            <!--<option>Daily</option>-->
                            <!--<option>Every 3 days</option>-->
                            <!--<option>Weekly</option>-->
                            <!--<option>Fortnightly</option>-->
                            <!--<option>Monthly</option>-->
                            <!--<option>Never</option>-->
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="navs-top-profile" role="tabpanel">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Alert Me about</label>
                        <select class="select2 form-select valid" id="AlertMe" name="Duration[Reminder][second][alertMe]" aria-invalid="false">
                            <option>{{ $contract->reminder_second_alert }}</option>
                            <!--<option>Renewal/Internal Due Date</option>-->
                            <!--<option>Internal Due Date</option>-->
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                       <div class="col">
                            <input type="number" class="form-control" min="1" name="Duration[Reminder][second][alertMeDay]" />
                        </div>
                       <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][second][alertMePrior]">
                                <option value="days">Days</option>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                            </select>
                        </div>
                         <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][second][alertMeType]">
                                <option value="prior">Prior</option>
                                <option value="prior">Prior</option>
                                <option value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][second][repeats]" aria-invalid="false">
                            <option>{{ $contract->reminder_second_alert_repeats }}</option>
                            <!--<option>Daily</option>-->
                            <!--<option>Every 3 days</option>-->
                            <!--<option>Weekly</option>-->
                            <!--<option>Fortnightly</option>-->
                            <!--<option>Monthly</option>-->
                            <!--<option>Never</option>-->
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="navs-top-messages" role="tabpanel">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Alert Me about</label>
                        <select class="select2 form-select valid" id="AlertMe" name="Duration[Reminder][escalation][alertMe]" aria-invalid="false">
                            <option>{{ $contract->reminder_escalation_alert }}</option>
                            <!--<option>Renewal/Internal Due Date</option>-->
                            <!--<option>Internal Due Date</option>-->
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                        <div class="col">
                            <input type="number" class="form-control"  min="1" name="Duration[Reminder][escalation][alertMeDay]" />
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMePrior]">
                                <option value="days">Days</option>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType]">
                                <option value="prior">Prior</option>
                                <option value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats]" aria-invalid="false">
                            <option>{{ $contract->reminder_escalation_alert_repeats }}</option>
                            <!--<option>Daily</option>-->
                            <!--<option>Every 3 days</option>-->
                            <!--<option>Weekly</option>-->
                            <!--<option>Fortnightly</option>-->
                            <!--<option>Monthly</option>-->
                            <!--<option>Never</option>-->
                        </select>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div> 
</div>
                        
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-4" aria-expanded="false">
                     Contract Value
                     </button>
                  </h2>
                  <div id="accordionWithIcon-4" class="accordion-collapse collapse">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div class="row g-3">
                            
                          <div class="card-body">
                               <!--<h3 class="mt-3">Contract Value</h3>-->
                               
                               <div class="row mb-3">
                                  <div class="col-md-2">
                                     <label class="form-label" for="ContractValue">Contract Value</label>
                                     <select id="ContractValue" name="ContractValue[currency]" class="form-select select2"
                                        data-allow-clear="true">
                                        <!--@foreach (currency() as $currency)-->
                                        <option value="{{ $currency }}">{{ $contract->currency }}</option>
                                        <!--@endforeach-->
                                     </select>
                                     <p>The total monetary value of the contract.</p>
                                  </div>
                                  <div class="col-md-4"><label class="form-label" for="ContractValue"></label>
                                     <input type="number" class="form-control" placeholder="" name="ContractValue[value]"
                                        id="ContractValue" value="{{ $contract->currency_value }}">
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="PaymentSchedule">Payment Schedule <span class="text-danger">*</span></label>
                                     <input type="text" class="form-control" placeholder="" id="PaymentSchedule"
                                        name="ContractValue[paymentSchedule]" value="{{ $contract->payment_schedule }}">
                                     <p>Details of payment milestones, amounts, and due dates.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-2">
                                     <label class="form-label" for="Currencycontract">Currency of the contract</label>
                                     <select id="Currencycontract" name="ContractValue[currencyContract]" class="form-select select2"
                                        data-allow-clear="true">
                                        <!--@foreach (currency() as $currency)-->
                                        <option value="{{ $currency }}">{{ $contract->currency_contract }}</option>
                                        <!--@endforeach-->
                                     </select>
                                     <p>The total monetary value of the contract.</p>
                                  </div>
                                  <div class="col-md-8">
                                     <label class="form-label" for="PaymentSchedule">Payment Terms</label>
                                     <textarea class="form-control" id="PaymentSchedule" name="ContractValue[paymentSchedule]"
                                        rows="3">{{ $contract->currency_contract }}</textarea>
                                     <p>Terms and conditions governing payments, including payment methods any late payment
                                        penalties.
                                     </p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="formValidationSelect2">Billing Frequency</label>
                                     <select id="BillingFrequency" name="ContractValue[billingFrequency]" class="form-select select2"
                                        data-allow-clear="true">
                                        <option value="monthly">{{ $contract->billing_frequency }}</option>
                                        <!--<option value="quarterly">Quarterly</option>-->
                                        <!--<option value="annually">Annually</option>-->
                                     </select>
                                     <p>Frequency at which invoices are issued (e.g., monthly, quarterly, annually).</p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Taxes and Fees</label>
                                     <input type="text" class="form-control" placeholder="" id="Taxes" name="ContractValue[taxes]" value="{{ $contract->taxes }}">
                                     <p>Any applicable taxes, fees, or surcharges associated with the contract.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Escalation Clauses</label>
                                     <input type="text" class="form-control" placeholder="" id="EscalationClauses"
                                        name="ContractValue[escalationClauses]" value="{{ $contract->escalation_clauses }}">
                                     <p>Provisions for adjusting contract prices over time based on predetermined factors such as
                                        inflation or market fluctuations.
                                     </p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Discounts or Rebates</label>
                                     <input type="text" class="form-control" placeholder="" id="Discounts"
                                        name="ContractValue[discounts]" value="{{ $contract->escalation_clauses }}">
                                     <p>Any discounts or rebates applied to the contract.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Retention or Holdbacks</label>
                                     <input type="text" class="form-control" placeholder="" id="Retention"
                                        name="ContractValue[Retention]" value="{{ $contract->retention }}">
                                     <p>Amounts withheld from payments as retention or holdbacks pending completion of certain
                                        milestones or obligations.
                                     </p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Payment Escrow</label>
                                     <input type="text" class="form-control" placeholder="" id="Payment"
                                        name="ContractValue[payment]" value="{{ $contract->payment_escrow }}">
                                     <p>Details of any funds held in escrow for payment security or dispute resolution purposes.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Financial Guarantees or Bonds</label>
                                     <input type="text" class="form-control" placeholder="" id="Financial Guarantees"
                                        name="ContractValue[financialGuarantees]" value="{{ $contract->payment_escrow }}">
                                     <p>Information about any financial guarantees or bonds required under the contract.</p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Currency Conversion</label>
                                     <input type="text" class="form-control" placeholder="" id="CurrencyConversion"
                                        name="ContractValue[currencyConversion]" value="{{ $contract->currency_conversion }}">
                                     <p>Terms for currency conversion if the contract involves transactions in multiple currencies.
                                     </p>
                                  </div>
                               </div>
                            </div>

                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-5" aria-expanded="false">
                     Contract Custom Fileds / Miscelleneous
                     </button>
                  </h2>
                  <div id="accordionWithIcon-5" class="accordion-collapse collapse">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div class="row g-3">
                            
                            <div class="card-body">

                               <div class="panel panel-default">

                                  <div class="panel-collapse">
                                     <div class="panel-body">
                                        <div class="col-sm-12">
                                           @include('contract::contract.viewCustomField', ['categoryId' => 4])
                                        </div>
                                     </div>
                                     <hr>
                                  </div>
                               </div>
                            </div>
                            
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-6" aria-expanded="false">
                     Contract Attachments
                     </button>
                  </h2>
                  <div id="accordionWithIcon-6" class="accordion-collapse collapse">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div class="col-12 col-lg-8">
                          <!-- Media -->
                          <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                              <h5 class="mb-0 card-title">Media</h5>
                              <a href="javascript:void(0);" class="fw-medium">Add media from URL</a>
                            </div>
                            <div class="card-body">
                              <form action="/upload" class="dropzone needsclick" id="dropzone-basic">
                                <div class="dz-message needsclick">
                                  <p class="fs-4 note needsclick pt-3 mb-1">Drag and drop your image here</p>
                                  <p class="text-muted d-block fw-normal mb-2">or</p>
                                  <span class="note needsclick btn bg-label-primary d-inline" id="btnBrowse">Browse image</span>
                                </div>
                                <div class="fallback">
                                  <input name="file" type="file" />
                                </div>
                           
                            </div>
                          </div>
                          <!-- /Media -->
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <!--<div class="pt-4">-->
         <!--   <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>-->
         <!--   <button type="reset" class="btn btn-label-secondary">Cancel</button>-->
         <!--</div>-->
         <div class="buy-now">
      <!--<a href="https://1.envato.market/vuexy_admin" target="_blank" class="btn btn-primary btn-buy-now waves-effect waves-light">Submit</a>-->
      
       <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
    </div>
      </form>
                            </div>
                        </div>
                    </div>
                 </div>
      
      <!--<h6> Collapsible Section </h6>-->
   </div>
</div>
            </div>
          </div>
      </form>
    </div>
</div>