@if($renewContract)
    <div class="p-3 bg-warning border rounded">
        <label class="form-check-label text-white">Renew / Addendum</label>
        <div class="col mt-2">
            @if(admin_setting('enable_new_contracts'))
            <div class="form-check form-check-inline">
                <label class="form-check-label text-white">
                    <input type="radio" class="contractmode form-check-input" name="contractRenew" value="renew" {{ old('contractRenew', 'renew') == 'renew' ? 'checked' : '' }}>
                    Renew</label>
            </div>
            @endif
            <div class="form-check form-check-inline">
                <label class="form-check-label text-white">
                    <input type="radio" class="contractmode form-check-input" name="contractRenew" value="addendum" {{ old('contractRenew') == 'addendum' ? 'checked' : '' }}>
                    Addendum </label>
            </div>
        </div>
    </div>            
@endif
<div class="accordion mt-3" id="accordionWithIcon">
    <div class="card accordion-item active">
        <div class="card-header">
            <label class="form-check-label">Contract</label>
            
            <div class="col mt-2">
                @if(admin_setting('enable_new_contracts'))
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input type="radio" class="contractmode form-check-input" name="contractMode" value="new" {{ old('contractMode', decryptString($contract->contract_mode, 'contract_mode')) == 'new' ? 'checked' : '' }}>
                        New</label>
                </div>
                @endif
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input type="radio" class="contractmode form-check-input" name="contractMode" value="old" {{ old('contractMode', decryptString($contract->contract_mode, 'contract_mode')) == 'old' ? 'checked' : '' }}>
                        Legacy/Executed Contracts </label>
                </div>
                @if(!$renewContract)
                <div class="btn-group float-end {{ $contract->contract_status == 'executed' ? '' : 'd-none' }}">
                    <button type="button" class="btn btn-warning waves-effect waves-light">More Actions</button>
                    <button type="button" class="btn btn-warning dropdown-toggle dropdown-toggle-split waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm' && $contract->contract_status == 'executed')
                        <li>
                            <a href="{{ url('contracts/renew/'.$contract->id) }}" class="dropdown-item waves-effect">
                                <span class="ti-xs ti ti-receipt-refund me-2 text-warning"></span>Initiate Renewal/Addendum
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        @endif
                        @if($contract->contract_status == 'executed')
                        <li>
                            <a href="javascript:;" class="dropdown-item waves-effect">
                                <span class="ti-xs ti ti-square-rounded-x me-2 text-info"></span>Terminate
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>                                
                @endif
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

                            <option value="{{ $contractType->contract_type_id }}" {{ old('BasicContract.contractType', $contract->contract_type_id) == $contractType->contract_type_id ? 'selected' : '' }}>
                                {{ decryptString($contractType->contract_type,'contract_type') }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="DepartmentType">Department <span class="text-danger">*</span></label>
                        <select id="DepartmentType" name="BasicContract[DepartmentType]"
                            class="form-select select2" data-allow-clear="true">
                            <option value="">Select Department</option>
                            @foreach($ent as $en)
                                <option value="{{$en->id}}" {{ old('BasicContract.DepartmentType', $contract->department_identity) == $en->id ? 'selected' : ''}}>{{$en->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- <div class="col-md-6">
                        <label class="form-label" for="catgoeryType">Category <span class="text-danger">*</span></label>
                        <select id="catgoeryType" name="BasicContract[catgoeryType]"
                            class="form-select select2" data-allow-clear="true">
                            <option value="">Select Category</option>
                            @foreach($catego as $en)
                                <option value="{{$en->id}}" {{ old('BasicContract.catgoeryType', $contract->catgoery_identity) == $en->id ? 'selected' : ''}}>{{$en->name}}</option>
                            @endforeach
                        </select>
                    </div> -->

                    <div class="col-md-6">
                        <label class="form-label" for="Exclusivity">Exclusivity</label>
                        <select id="Exclusivity" name="BasicContract[Exclusivity]"
                            class="form-select select2" data-allow-clear="true">
                            <option value="Exclusivity to Company" {{old('BasicContract.Exclusivity',decryptString($contract->exclusivity, 'exclusivity' )) == 'Exclusivity to Company' ? 'selected' : '' }}>Exclusive to Company</option>
                            <option value="Exclusive to Contracting Party" {{old('BasicContract.Exclusivity',decryptString($contract->exclusivity, 'exclusivity' )) == 'Exclusive to Contracting Party' ? 'selected' : '' }}>Exclusive to Contracting Party</option>
                            <option value="Mutually Exclusive" {{old('BasicContract.Exclusivity',decryptString($contract->exclusivity, 'exclusivity' )) == 'Mutually Exclusive' ? 'selected' : '' }}>Mutually Exclusive</option>
                            <option value="Non Exclusive" {{old('BasicContract.Exclusivity',decryptString($contract->exclusivity, 'exclusivity' )) == 'Non Exclusive' ? 'selected' : '' }}>Non Exclusive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contractDescription">Contract
                            Description</label>
                        <textarea class="form-control" id="contractDescription"
                            name="BasicContract[contractDescription]" rows="5">{{
                    decryptString($contract->contract_description,'contract_description' )}}</textarea>
                    </div>
                   <div class="col-md-6">
                      <label class="form-label" for="contracttypetags">Other Scopes<span class="text-danger">*</span></label>
                      <select class="form-select select2 contracttypetags" name="BasicContract[contractTypeTags][]" id="contracttypetags" multiple>
                        <option value="">--Select Tags--</option>
                         @foreach ($contractTypes as $contractType)
                         
                         <option data-catid="{{ $contractType->categoryId }}" {{ in_array($contractType->contract_type_id, old('BasicContract.contractTypeTags', json_decode($contract->contract_tags) ?? [])) ? 'selected' : '' }} value="{{ $contractType->contract_type_id }}">
                            {{ $contractType->contract_type }}
                         </option>
                         @endforeach
                      </select>
                   </div>
                   @if(env('enable_contract_priority'))
                        <div class="col-md-6">      
                            <label class="form-label" for="priority">Priority:</label>
                            <select class="select2 form-select" id ="priority" name = "priority">
                                <option selected>Choose Priority</option>
                                <option value="low" {{ (old('priority', $contract->contract_priority) == 'low' ? 'selected' : '' ) }}>Low</option>
                                <option value="medium" {{ (old('priority', $contract->contract_priority) == 'medium' ? 'selected' : '' ) }}>Medium</option>
                                <option value="high" {{ (old('priority', $contract->contract_priority) == 'high' ? 'selected' : '' ) }}>High</option>
                            </select>  
                        </div>
                    @else
                        <input type="hidden" name = "priority" value="{{ old('priority', $contract->contract_priority ?? 'medium') }}"/>
                    @endif                                    
                    <div class="row mb-3">
                        @include('contract::contract.editCustomField', ['categoryId' => 1])
                    </div>
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
                    @include('contract::contract.partyDetailsEdit', ['paryda', $contractPartys])
                    <div class="panel-body">
                        <div class="party-group">
                        </div>
                        <button type="submit" class="btn btn-primary me-sm-3 me-1 admo">+Add more
                            parties</button>
                    </div>
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
                
              
                <div class="">
                    <div class="col-sm-12">
                        <div class="form-group mt-3">
                            <h5>Contract Commencement</h5>
                            <hr class="mt-0" />
                            <label>Effective date:</label>
                            <div class="clearfix mt-2">
                                <label class="form-check-inline form-check">
                                    <input type="radio" class="form-check-input commencementDate" name="Duration[commencementDate]" value="FixedDate" {{ decryptString($contract->commencement_type, 'commencement_type') == 'FixedDate' ? 'checked' : '' }}>
                                    Fixed Date
                                </label>
                                <!-- <label class="form-check-inline form-check">
                                    <input class="form-check-input commencementDate" type="radio" name="Duration[commencementDate]" value="Eventbased" {{ decryptString($contract->commencement_type, 'commencement_type') == 'Eventbased' ? 'checked' : '' }}>
                                    Event based commencement
                                </label> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12" id="FixedDate" style="display: {{ decryptString($contract->commencement_type, 'commencement_type') == 'Eventbased' ? 'none' : '' }}">
                        <div class="form-group mt-3">
                            <label>Fixed Date</label>
                            <div class="clearfix row">
                                <div class="col-sm-4"><input type="date" name="Duration[fixedDate]" class="form-control flatpickr calculateBilling" value="{{ $contract->fixed_date }}" /></div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-sm-12" id="Eventbased" style="display: {{ decryptString($contract->commencement_type, 'commencement_type') == 'Eventbased' ? '' : 'none' }}">
                        <div class="form-group row mt-3">
                            <div class="col-sm-12">
                                <label> Event based commencement</label>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mt-2">
                                    <label>(i) Event Condition</label>
                                    <div class="clearfix">
                                        <select class="form-control" name="Duration[eventCondition]">
                                            <option value="uponCompletion">Upon Completion of Specific Event</option>
                                            <option value="uponDelivery">Upon Delivery of Specific Deliverable</option>
                                            <option value="uponApproval">Upon Approval of Specific Approval Process</option>
                                            <option value="other">Other</option>
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
                    </div> -->
                </div>
                <div class="">
                    <div class="col-sm-12">
                        <div class="form-group mt-4">
                            <hr class="mt-0" />
                            <h5>End of Contract Term</h5>
                            <hr class="mt-0" />
                            <label>Effective date:</label>
                            <div class="clearfix mt-2">
                                <label class="form-check-inline form-check"><input type="radio" class="contractCommencementEffectiveDate form-check-input calculateBilling" name="Duration[effectiveDate]" value="onetimeContract" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'onetimeContract' ? 'checked' : '' }}>One time Contract</label>
                                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input calculateBilling" type="radio" name="Duration[effectiveDate]" value="fixedTerm" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm' ? 'checked' : '' }}>Fixed Term Contract with Periodic Renewal</label>
                                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input calculateBilling" type="radio" name="Duration[effectiveDate]" value="evergreen" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'evergreen' ? 'checked' : '' }}>Evergreen/Perpetual Contracts </label>
                                <label class="form-check-inline form-check showinedit"> <input class="contractCommencementEffectiveDate form-check-input calculateBilling" type="radio" name="Duration[effectiveDate]" value="termination" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'termination' ? 'checked' : '' }}>Termination</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4" id="onetimeContract" style="display: {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'onetimeContract' ? '' : 'none' }}">
                        <div class="form-group mt-3">
                            <hr class="mt-0" />
                            <h5>One time Contract</h5>
                            <hr class="mt-0" />
                            <div class="form-group">
                                <label>End date of contract</label>
                                <div class="clearfix">
                                    <input type="date" name="Duration[onetimeEndDateofContract]" class="form-control flatpickr calculateBilling" value="{{ $contract->contract_end_date }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 mt-2" id="fixedTerm" style="display: {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm' ? '' : 'none' }};">
                        <hr class="mt-3" />
                        <h5 class="mt-2">Fixed Term Contract with Periodic Renewal</h5>
                        <hr class="mt-3" />
                        <div class="form-group row mt-2">
                            <div class="row">
                                <div class="form-group  col-sm-3 mt-2">
                                    <label>End date of contract</label>
                                    <div class="clearfix">
                                        <input type="date" class="form-control flatpickr calculateBilling" name="Duration[fixedtimeEndDateofContract]" value="{{ $contract->contract_end_date }}" />
                                    </div>
                                </div>
                                <div class="form-group  col-sm-5 mt-2">
                                    <label>Type of Renewal</label>
                                    <div class="clearfix">
                                        <select class="form-control typerenewal select2" name="Duration[typeRenewal]">
                                            <!--<option value="{{ $contract->renewal_type }}">{{ $contract->renewal_type }}</option>-->
                                            <option value="automaticrenewal" {{ decryptString($contract->renewal_type , 'renewal_type') == 'automaticrenewal' ? 'selected' : ''}}>Automatic renewal with notice</option>
                                            <option value="manualRenewal" {{ decryptString($contract->renewal_type , 'renewal_type') == 'manualRenewal' ? 'selected' : ''}}>Manual Renewal with notice</option>
                                            <!--<option value="manualRenewal">Manual Renewal with notice</option>-->
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group  col-sm-4 mt-2 auto-renewal-section" style="display: {{ decryptString($contract->renewal_type , 'renewal_type') == 'automaticrenewal' ? '' : 'none'}}">
                                    <label>Period of auto renewal</label>
                                    <div class="clearfix row">
                                        <div class="col-sm-5"><input class="form-control" type="text" name="Duration[periodAutoRenewal]" value="{{$contract->period_auto_renewal}}"></div>
                                        <div class="col-sm-7">
                                            <select class="form-control" name="Duration[periodAutoRenewalPeriod]">
                                                <option value="years">{{ decryptString($contract->period_auto_renewal_unit, 'period_auto_renewal_unit') }}</option>

                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="form-group  col-sm-3 mt-2">
                                    <label class="typerenewallable">{{ decryptString($contract->renewal_type , 'renewal_type') == 'automaticrenewal' ? 'Auto' : 'Manual' }} renewal Date:</label>
                                    <div class="clearfix">
                                        <input type="date" class="form-control flatpickr" name="Duration[autoRenewalDate]" value="{{ $contract->auto_renewal_date }}" placeholder="Auto renewal date" />
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
                                        <option value="mutually">{{ decryptString($contract->evergreen_condition, 'evergreen_condition') }}
                                        </option>

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
                                        <input type="date" class="form-control flatpickr"  name="Duration[terminationDate]" />
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
                        <input type="checkbox" class="form-check-input" id="Reminder" name="Duration[reminderEnable]" {{ decryptString($contract->reminder_enable, 'reminder_enable') == 'on' ? 'checked' : '' }}/>
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
                                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-messages" aria-controls="navs-top-messages" aria-selected="false">Escalation Prior</button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-escalation-after" aria-controls="navs-escalation-after" aria-selected="false">Escalation After</button>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label> Alert Me about</label>
                                            <select class="select2 form-select" name="Duration[Reminder][first][alertMe]" aria-invalid="false">
                                                <option value="Contract End Date" {{decryptString($contract->reminder_first_alert, 'reminder_first_alert') == 'Contract End Date' ? 'selected' : '' }}>Contract End Date</option>
                                                <option value="Renewal Date" {{decryptString($contract->reminder_first_alert, 'reminder_first_alert') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group row">
                                            <label class="">Alert Me on</label>
                                            <?php [$alertDay, $alertUnit, $alertDirection] = reminder_alert_parts($contract->reminder_first_alertMeOn, 'reminder_first_alertMeOn'); 
                                            

                                            ?>
                                            <div class="col">
                                                <input type="text" class="form-control" min="1" name="Duration[Reminder][first][alertMeDay]" value="{{ $alertDay}}" />
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][first][alertMePrior]">
                                                    <option value="days" {{$alertUnit == 'days' ? 'selected' : '' }}>Days</option>
                                                    <option value="months" {{$alertUnit == 'months' ? 'selected' : '' }}>Months</option>
                                                    <option value="years" {{$alertUnit == 'years' ? 'selected' : '' }}>Years</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select  col-sm-6" name="Duration[Reminder][first][alertMeType]">
                                                    <option value="prior" {{$alertDirection == 'prior' ? 'selected' : '' }}>Prior</option>
                                                    <option value="after" {{$alertDirection == 'after' ? 'selected' : '' }}>After</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label> Repeats</label>
                                            <select class="select2 form-select" id="Repeats" name="Duration[Reminder][first][repeats]" aria-invalid="false">
                                                <option value="Daily" {{ decryptString($contract->reminder_first_alert_repeats,'reminder_first_alert_repeats') == 'Daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="Every 3 days" {{ decryptString($contract->reminder_first_alert_repeats,'reminder_first_alert_repeats') == 'Every 3 days' ? 'selected' : '' }}>Every 3 days</option>
                                                <option value="Weekly" {{ decryptString($contract->reminder_first_alert_repeats,'reminder_first_alert_repeats') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="Fortnightly" {{ decryptString($contract->reminder_first_alert_repeats,'reminder_first_alert_repeats') == 'Fortnightly' ? 'selected' : '' }}>Fortnightly</option>
                                                <option value="Monthly" {{ decryptString($contract->reminder_first_alert_repeats,'reminder_first_alert_repeats') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="Never" {{ decryptString($contract->reminder_first_alert_repeats,'reminder_first_alert_repeats') == 'Never' ? 'selected' : '' }}>Never</option>
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
                                            <select class="select2 form-select" id="AlertMe" name="Duration[Reminder][second][alertMe]" aria-invalid="false">
                                                <option {{decryptString($contract->reminder_second_alert, 'reminder_second_alert') == 'Contract End Date' ? 'selected' : '' }}>Contract End Date</option>
                                                <option {{decryptString($contract->reminder_second_alert, 'reminder_second_alert') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group row">
                                            <?php [$alertDay, $alertUnit, $alertDirection] = reminder_alert_parts($contract->reminder_second_alertMeOn, 'reminder_second_alertMeOn'); 
                                            ?>
                                            <label class=" ">Alert Me on</label>
                                            <div class="col">
                                                <input type="number" class="form-control" min="1" value="{{ $alertDay}}" name="Duration[Reminder][second][alertMeDay]" />
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][second][alertMePrior]">
                                                    <option value="days" {{$alertUnit == 'days' ? 'selected' : '' }}>Days</option>
                                                    <option value="months" {{$alertUnit == 'months' ? 'selected' : '' }}>Months</option>
                                                    <option value="years" {{$alertUnit == 'years' ? 'selected' : '' }}>Years</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select  col-sm-6" name="Duration[Reminder][second][alertMeType]">
                                                    <option value="prior" {{$alertDirection == 'prior' ? 'selected' : '' }}>Prior</option>
                                                    <option value="after" {{$alertDirection == 'after' ? 'selected' : '' }}>After</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label> Repeats</label>
                                            <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][second][repeats]" aria-invalid="false">
                                                <option value="Daily" {{ decryptString($contract->reminder_second_alert_repeats,'reminder_second_alert_repeats') == 'Daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="Every 3 days" {{ decryptString($contract->reminder_second_alert_repeats,'reminder_second_alert_repeats') == 'Every 3 days' ? 'selected' : '' }}>Every 3 days</option>
                                                <option value="Weekly" {{ decryptString($contract->reminder_second_alert_repeats,'reminder_second_alert_repeats') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="Fortnightly" {{ decryptString($contract->reminder_second_alert_repeats,'reminder_second_alert_repeats') == 'Fortnightly' ? 'selected' : '' }}>Fortnightly</option>
                                                <option value="Monthly" {{ decryptString($contract->reminder_second_alert_repeats,'reminder_second_alert_repeats') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="Never" {{ decryptString($contract->reminder_second_alert_repeats,'reminder_second_alert_repeats') == 'Never' ? 'selected' : '' }}>Never</option>
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
                                                <option {{ decryptString($contract->reminder_escalation_alert, 'reminder_escalation_alert') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                                                <option {{ decryptString($contract->reminder_escalation_alert, 'reminder_escalation_alert') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group row">
                                            <?php [$alertDay, $alertUnit, $alertDirection] = reminder_alert_parts($contract->reminder_escalation_alertMeOn, 'reminder_escalation_alertMeOn'); 
                                            ?>                                                            
                                            <label class=" ">Alert Me on</label>
                                            <div class="col">
                                                <input type="number" class="form-control" min="1" value="{{ $alertDay}}" name="Duration[Reminder][escalation][alertMeDay]" />
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMePrior]">
                                                    <option value="days" {{$alertUnit == 'days' ? 'selected' : '' }}>Days</option>
                                                    <option value="months" {{$alertUnit == 'months' ? 'selected' : '' }}>Months</option>
                                                    <option value="years" {{$alertUnit == 'years' ? 'selected' : '' }}>Years</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType]">
                                                    <option value="prior" {{$alertDirection == 'prior' ? 'selected' : '' }}>Prior</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label> Repeats</label>
                                            <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats]" aria-invalid="false">
                                                <option value="Daily" {{ decryptString($contract->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats') == 'Daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="Every 3 days" {{ decryptString($contract->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats') == 'Every 3 days' ? 'selected' : '' }}>Every 3 days</option>
                                                <option value="Weekly" {{ decryptString($contract->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="Fortnightly" {{ decryptString($contract->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats') == 'Fortnightly' ? 'selected' : '' }}>Fortnightly</option>
                                                <option value="Monthly" {{ decryptString($contract->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="Never" {{ decryptString($contract->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats') == 'Never' ? 'selected' : '' }}>Never</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="navs-escalation-after" role="tabpanel">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label> Alert Me about</label>
                                            <select class="select2 form-select valid" id="AlertMe" name="Duration[Reminder][escalation][alertMe_after]" aria-invalid="false">
                                                <option {{ decryptString($contract->reminder_escalation_alert_after, 'reminder_escalation_alert_after') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                                                <option {{ decryptString($contract->reminder_escalation_alert_after, 'reminder_escalation_alert_after') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group row">
                                            <?php [$alertDay, $alertUnit, $alertDirection] = reminder_alert_parts($contract->reminder_escalation_alertMeOn_after, 'reminder_escalation_alertMeOn_after'); 
                                            ?>                                                            
                                            <label class=" ">Alert Me on</label>
                                            <div class="col">
                                                <input type="number" class="form-control" min="1" value="{{ $alertDay}}" name="Duration[Reminder][escalation][alertMeDay_after]" />
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeAfter]">
                                                    <option value="days" {{$alertUnit == 'days' ? 'selected' : '' }}>Days</option>
                                                    <option value="months" {{$alertUnit == 'months' ? 'selected' : '' }}>Months</option>
                                                    <option value="years" {{$alertUnit == 'years' ? 'selected' : '' }}>Years</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType_after]">
                                                    <option value="after" {{$alertDirection == 'after' ? 'selected' : '' }}>After</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label> Repeats</label>
                                            <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats_after]" aria-invalid="false">
                                                <option value="Daily" {{ decryptString($contract->reminder_escalation_alert_repeats,'reminder_escalation_alert_repeats_after') == 'Daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="Every 3 days" {{ decryptString($contract->reminder_escalation_alert_repeats_after,'reminder_escalation_alert_repeats_after') == 'Every 3 days' ? 'selected' : '' }}>Every 3 days</option>
                                                <option value="Weekly" {{ decryptString($contract->reminder_escalation_alert_repeats_after,'reminder_escalation_alert_repeats_after') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="Fortnightly" {{ decryptString($contract->reminder_escalation_alert_repeats_after,'reminder_escalation_alert_repeats_after') == 'Fortnightly' ? 'selected' : '' }}>Fortnightly</option>
                                                <option value="Monthly" {{ decryptString($contract->reminder_escalation_alert_repeats_after,'reminder_escalation_alert_repeats_after') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="Never" {{ decryptString($contract->reminder_escalation_alert_repeats_after,'reminder_escalation_alert_repeats_after') == 'Never' ? 'selected' : '' }}>Never</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                @include('contract::contract.editCustomField', ['categoryId' => 2])
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
                    <div class="card-body mt-2">
                      <div class="row mb-3">
                            <div class="col-md-2">
                                <label class="form-label" for="ContractValue">Contract Value <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="The total monetary value of the contract."></i></label>
                                <select id="ContractValue" name="ContractValue[currency]" class="form-select select2"
                                    data-allow-clear="true">
                                   @foreach (currency() as $currency)
                                   <option value="{{ $currency }}" {{ old('ContractValue.currency', decryptString($contract->currency, 'currency')) == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                                   @endforeach
                                </select>
                            </div>
                         <div class="col-md-4">
                            <label class="form-label" for="formValidationSelect2">Billing Frequency <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Frequency at which invoices are issued (e.g., monthly, quarterly, annually)."></i></label>
                            <select id="BillingFrequency" name="ContractValue[billingFrequency]" class="form-select select2 calculateBilling" data-allow-clear="true">
                               <option {{ old('ContractValue.billingFrequency', decryptString($contract->billing_frequency, 'billing_frequency')) == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                               <option {{ old('ContractValue.billingFrequency', decryptString($contract->billing_frequency, 'billing_frequency')) == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                               <option {{ old('ContractValue.billingFrequency', decryptString($contract->billing_frequency, 'billing_frequency')) == 'Quarterly' ? 'selected' : '' }} value="Quarterly">Quarterly</option>
                               <option {{ old('ContractValue.billingFrequency', decryptString($contract->billing_frequency, 'billing_frequency')) == 'Half Yearly' ? 'selected' : '' }} value="Half Yearly">Half Yearly</option>
                               <option {{ old('ContractValue.billingFrequency', decryptString($contract->billing_frequency, 'billing_frequency')) == 'Annually' ? 'selected' : '' }} value="Annually">Annually</option>
                               <option {{ old('ContractValue.billingFrequency', decryptString($contract->billing_frequency, 'billing_frequency')) == 'Onetime' ? 'selected' : '' }} value="Onetime">One Time</option>
                            </select>
                         </div>
                         <div class="col-md-4">
                             <label class="form-label" for="ContractBillingValue">Billing Value</label>
                            <input type="number" class="form-control calculateBilling" placeholder="" name="ContractValue[billingvalue]" id="ContractBillingValue" value="{{ old('ContractValue.billingvalue', decryptString($contract->billing_value, 'billing_value')) }}">
                         </div>                                 
                      </div>
                      <div class="row mb-3">
                         <div class="col-md-6 annualValueDiv {{ old('ContractValue.value', decryptString($contract->currency_value, 'currency_value')) ? '' : 'd-none' }}"><label class="form-label" for="ContractValueAnnual">Annual Contract Value</label>
                            <label class="btn btn-label-warning btn-sm mt-xl-6 waves-effect"><span class="align-middle" id="ContractValAnnText">{{ old('ContractValue.value', decryptString($contract->currency_value, 'currency_value') ?? 0) }}</span></label>                                
                            <input type="hidden" readonly class="form-control" placeholder="" name="ContractValue[value]" id="ContractValueAnnual" value="{{ old('ContractValue.value', decryptString($contract->currency_value, 'currency_value')) }}">
                         </div>
                         <div class="col-md-6 totalValueDiv {{ (old('ContractValue.totalvalue', decryptString($contract->total_value, 'total_value')) && old('Duration.effectiveDate', decryptString($contract->end_contract_type, 'end_contract_type')) != 'evergreen' ) ? '' : 'd-none' }}"><label class="form-label" for="totalContractValue">Total Contract Value</label>
                            <label class="btn btn-label-warning btn-sm mt-xl-6 waves-effect"><span class="align-middle" id="totContValText">{{ old('ContractValue.totalvalue', decryptString($contract->total_value, 'total_value')) }}</span></label>                                 
                            <input type="hidden" readonly class="form-control" placeholder="" name="ContractValue[totalvalue]" id="totalContractValue" value="{{ old('ContractValue.totalvalue', decryptString($contract->total_value, 'total_value')) }}">
                         </div>
                      </div> 
                       
                        <hr class="mt-3" />
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="PaymentSchedule">Payment Schedule <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of payment milestones, amounts, and due dates."></i></label>
                                <input type="text" class="form-control" placeholder="" id="PaymentSchedule"
                                    name="ContractValue[paymentSchedule]" value="{{ decryptString($contract->payment_schedule, 'payment_schedule') }}">
                                
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="PaymentTerms">Payment Terms <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms and conditions governing payments, including payment methods any late payment
                                    penalties."></i></label>
                                <textarea class="form-control" id="PaymentTerms" name="ContractValue[paymentTerms]"
                                    rows="3">{{ decryptString($contract->payment_terms, 'payment_terms') }}</textarea>
                            </div>
                        </div>
                        <hr class="mt-3" />
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="Currencycontract">Taxes and Fees <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any applicable taxes, fees, or surcharges associated with the contract."></i></label>
                                <input type="text" class="form-control" placeholder="" id="Taxes" name="ContractValue[taxes]" value="{{ decryptString($contract->taxes, 'taxes') }}">
                            </div>
                        </div>
                        <hr class="mt-3" />
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="Currencycontract">Escalation Clauses <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Provisions for adjusting contract prices over time based on predetermined factors such as
                                    inflation or market fluctuations."></i></label>
                                <input type="text" class="form-control" placeholder="" id="EscalationClauses"
                                    name="ContractValue[escalationClauses]" value="{{ decryptString($contract->escalation_clauses,'escalation_clauses') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="Currencycontract">Discounts or Rebates <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any discounts or rebates applied to the contract."></i></label>
                                <input type="text" class="form-control" placeholder="" id="Discounts"
                                    name="ContractValue[discounts]" value="{{ decryptString($contract->escalation_clauses, 'discounts') }}">
                            </div>
                        </div>
                        <hr class="mt-3" />
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="Currencycontract">Retention or Holdbacks <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Amounts withheld from payments as retention or holdbacks pending completion of certain
                                    milestones or obligations."></i></label>
                                <input type="text" class="form-control" placeholder="" id="Retention"
                                    name="ContractValue[retention]" value="{{ decryptString($contract->retention, 'retention' )}}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="Currencycontract">Payment Escrow <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of any funds held in escrow for payment security or dispute resolution purposes."></i></label>
                                <input type="text" class="form-control" placeholder="" id="Payment" name="ContractValue[payment_escrow]" value="{{ decryptString($contract->payment_escrow, 'payment_escrow' )}}">
                            </div>
                        </div>
                        <hr class="mt-3" />
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="Currencycontract">Financial Guarantees or Bonds <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Information about any financial guarantees or bonds required under the contract."></i></label>
                                <input type="text" class="form-control" placeholder="" id="Financial Guarantees"
                                    name="ContractValue[financialGuarantees]" value="{{ decryptString($contract->financial_guarantees, 'financial_guarantees' )}}">
                            </div>
                            <div class="col-md-4 d-none">
                                <label class="form-label" for="Currencycontract">Currency Conversion <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms for currency conversion if the contract involves transactions in multiple currencies."></i></label>
                                <input type="text" class="form-control" placeholder="" id="CurrencyConversion"
                                    name="ContractValue[currencyConversion]" value="{{ decryptString($contract->currency_conversion, 'currency_conversion') }}">
                            </div>
                            <div class="row mb-3">
                                @include('contract::contract.editCustomField', ['categoryId' => 3])
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
                Contract Custom Fields / Miscelleneous
            </button>
        </h2>
        <div id="accordionWithIcon-5" class="accordion-collapse collapse">
            <div class="accordion-body">
                <hr class="mt-0" />
                <div class="row g-3">
                    <div class="card-body mt-2">
                        <div class="panel panel-default">
                            <div class="panel-collapse">
                                <div class="panel-body">
                                    <div class="col-sm-12">
                                        @include('contract::contract.editCustomField', ['categoryId' => 4])
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
            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-6" aria-expanded="false">
                Contract Attachments
            </button>
        </h2>
        <div id="accordionWithIcon-6" class="accordion-collapse collapse">
            <div class="accordion-body">
                <div class="accordion-body">
                    @if(isset($contract->contract_attachment))
                    <a target="_blank" href="{{ attachmentDummyUrl($contract->contract_attachment, true) }}">View Attachment</a>
                    @endif
                    
                    @php
                    $showDocumentUpload = true;
                    if(!$renewContract){
                        if(strtolower($contract->contract_status) != 'draft' || isset($contract->contract_attachment)){
                            $showDocumentUpload = false;
                        }
                    }
                    @endphp
                    
                    @if($showDocumentUpload)
                        <div class="col mt-2">
                           <div class="form-check form-check-inline">
                              <label class="form-check-label">
                                 <input type="radio" class="attachmentstype form-check-input" name="attachments_type" value="Upload" data-div="upload" {{ old('attachments_type', 'Upload') == 'Upload' ? 'checked' : ''}} />
                                 Upload file </label>
                           </div>
                           @if(env('enable_contract_template'))
                               <div class="form-check form-check-inline">
                                  <label class="form-check-label">
                                     <input type="radio" class="{{ env('enable_contract_template') ? 'attachmentstype' : '' }} form-check-input" name="attachments_type" value="template" data-div="template" {{ old('attachments_type', 'Upload') == 'template' ? 'checked' : ''}}/>
                                     Take from template</label>
                               </div>
                           @endif
                        </div>
                        <div class="col-12 col-lg-8 attachmentsdiv" id="attachments_type_upload" style="display: {{ old('attachments_type', 'Upload') == 'template' ? 'none' : ''}}">
                           <div class="col-12">
                              <div class="card mb-4">
                                 <div class="card-body">
                                    <div class="form-group files color">
                                       <input type="file" name="file" class="form-control">
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                        <div class="col-12 col-lg-8 attachmentsdiv" id="attachments_type_template" style="display: {{ old('attachments_type', 'Upload') == 'Upload' ? 'none' : ''}}">
                            <div class="mt-2">
                                <div id="template-editor">
                                    -
                                </div>
                                <textarea id="template_text" name="template_text" hidden>{{ old('template_text') }}</textarea>
                                <input type="file" hidden id="docxFile" name="docxFile" />
                            </div>
                        </div>
                    @endif
                </div>
                <hr class="mt-0" />
            </div>
        </div>
    </div>
    <div class="accordion-item card mt-4">
        <h2 class="accordion-header d-flex align-items-center">
            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse"
                data-bs-target="#accordionWithIcon-7" aria-expanded="false">
                Ownership
            </button>
        </h2>
        <div id="accordionWithIcon-7" class="accordion-collapse collapse">
            <div class="accordion-body">
                <hr class="mt-1" />
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="owner">Owner <span class="text-danger">*</span></label>
                        <select class="form-select select2 " name="owner" id="ownership">
                            <option value="">-Owner-</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('BasicContract.owner', $contract->owner) == $user->id ? 'selected' : '' }}>
                               {{ $user->Salutation }}
                                {{ $user->FirstName }}
                                {{ $user->LastName }}
                                ({{ $user->Email }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="Ownership Signatory">Signatory <span
                                class="text-danger">*</span></label>
                        <select class="form-select select2 " name="signatory" id="ownership-signatory" {{ !$renewContract ? 'disabled' : '' }}>
                            <option value="">-Select Signatory-</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('signatory', $contract->signatory) == $user->id ? 'selected' : '' }}>
                               {{ $user->Salutation }}
                                {{ $user->FirstName }}
                                {{ $user->LastName }}
                                ({{ $user->Email }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                   <div class="col-md-6">
                      <label class="form-label" for="users-notify">Users To Notify</label>
                      <select class="form-select" name="userNotify[]" id="users-notify" multiple>
                         @foreach ($usersSel as $user)
                         <option value="{{ $user->id }}" {{ in_array($user->id, old('userNotify', [])) == $user->id ? 'selected' : '' }}>
                           {{ $user->Salutation }}
                            {{ $user->FirstName }}
                            {{ $user->LastName }}
                            ({{ $user->Email }})
                         </option>
                         @endforeach
                      </select>
                   </div>
                    <div class="col-md-6">
                        <div class="form-group ">
                             <label class="form-label">Signing Date</label>
                           <input type="date" name="Duration[signingDate]" {{ !$renewContract ? 'disabled' : '' }} class="form-control flatpickr" value="{{ $contract->signing_date }}" />
                            <div class="clearfix">
                                <small class="form-text text-muted">The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract.
                                </small>
                            </div>
                        </div>
                    </div>                                   
                </div>
            </div>
        </div>
    </div>                    
</div>