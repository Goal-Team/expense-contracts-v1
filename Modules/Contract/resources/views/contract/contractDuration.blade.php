@php
$simpleForm = $simpleForm ?? false;

/**
 * V3 drops event-based commencement (contracts always start on a fixed date there) and
 * promotes the reminder block out of the "Show All Fields" set, which V3 does not expose.
 * The other pages including this partial are unaffected.
 */
$v3Form = $v3Form ?? false;
@endphp
<div class="contractDurationSection">
    <div class="col-sm-12">
        <div class="form-group mt-3">
            <h5 class="{{ $simpleForm ? 'd-none': '' }}">Contract Commencement</h5>
            <hr class="mt-0 {{ $simpleForm ? 'd-none': '' }}" />
            <label>Effective date:</label>
            <div class="clearfix mt-2">
                <label class="form-check-inline form-check"><input type="radio" class="form-check-input commencementDate" {{ old('Duration.commencementDate', $defVals['commencementDate']) == 'FixedDate' ? 'checked' : '' }} value="FixedDate" name="Duration[commencementDate]">Fixed Date</label>
                <!-- <label class="form-check-inline form-check {{ $v3Form ? 'd-none': '' }}"><input value="Eventbased" class="form-check-input commencementDate" type="radio" name="Duration[commencementDate]" {{ old('Duration.commencementDate', $defVals['commencementDate']) == 'Eventbased' ? 'checked' : '' }}>Event based commencement</label> -->
            </div>
        </div>
    </div>
    <div class="col-sm-12" id="FixedDate" style="display: {{ old('Duration.commencementDate', $defVals['commencementDate']) == 'FixedDate' ? '' : 'none' }}">
        <div class="form-group mt-3">
            <label>Start Date <span class="required-field-old text-danger" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'inline-block' : 'none'}}">*</span></label>
            <div class="clearfix row">
                <div class="col-sm-{{ $simpleForm ? '12': '4' }}"><input type="date" name="Duration[fixedDate]" class="form-control flatpickr calculateBilling" placeholder="Start Date" value="{{ old('Duration.fixedDate') }}"/></div>
            </div>
        </div>
    </div>
    <!-- <div class="col-sm-12" id="Eventbased" style="display: {{ old('Duration.commencementDate', $defVals['commencementDate']) == 'Eventbased' ? '' : 'none' }}">
        <div class="form-group row mt-3">
            <div class="col-sm-12">
                <label> Event based commencement</label>
            </div>
            <div class="col-sm-{{ $simpleForm ? '12': '6' }}">
                <div class="form-group mt-2">
                    <label>(i) Event Condition</label>
                    <div class="clearfix">
                        <select class="form-control select2" name="Duration[eventCondition]">
                            <option {{ old('Duration.eventCondition') == 'uponCompletion' ? 'selected' : '' }} value="uponCompletion">Upon Completion of Specific Event</option>
                            <option {{ old('Duration.eventCondition') == 'uponDelivery' ? 'selected' : '' }} value="uponDelivery">Upon Delivery of Specific Deliverable</option>
                            <option {{ old('Duration.eventCondition') == 'uponApproval' ? 'selected' : '' }} value="uponApproval">Upon Approval of Specific Approval Process</option>
                            <option {{ old('Duration.eventCondition') == 'other' ? 'selected' : '' }} value="other">Other</option>
                        </select>
                    </div>
                </div>
            </div> 
            
            
             
        <div class="card">
            <div class="card-body">
            <div id="taskgroup">
                @foreach(old('Duration.task', [0]) as $ke => $tasks)
                <div class="row taskgroup">
                    <div class="col-md-4">
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Task Name:</label>  
                            <input type="text" class="form-control" name="Duration[task][$ke][name_of_task]" value="{{ old('Duration.task.'.$ke.'.name_of_task') }}"/>  
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Priority:</label>
                            <select class="select2 form-select" name="Duration[task][$ke][priority]" aria-label="Default select example">
                                <option selected>Choose Priority</option>
                                <option {{ old('Duration.task.'.$ke.'.priority') == 'low' ? 'selected' : '' }} value="low">Low</option>
                                <option {{ old('Duration.task.'.$ke.'.priority') == 'medium' ? 'selected' : '' }} value="medium">Medium</option>
                                <option {{ old('Duration.task.'.$ke.'.priority') == 'high' ? 'selected' : '' }} value="high">High</option>
                            </select>  
                        </div>  
                    </div>
                    <div class="col-md-4" > 
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="start_date">Start Date:</label>  
                            <input type="date" class="form-control flatpickr" name="Duration[task][$ke][start_date]" value="{{ old('Duration.task.'.$ke.'.start_date') }}" placeholder="Start Date"/>  
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Description </label>
                             <textarea class="form-control" name="Duration[task][$ke][description]" rows="5">{{ old('Duration.task.'.$ke.'.description') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Status:</label>
                            <select class="select2 form-select"  name="Duration[task][$ke][status]"  aria-label="Default select example">
                                <option selected>Choose Status</option>
                                <option {{ old('Duration.task.'.$ke.'.status') == 'pending' ? 'selected' : '' }} value="pending">Pending</option>
                                <option {{ old('Duration.task.'.$ke.'.status') == 'inprogress' ? 'selected' : '' }} value="inprogress">Inprogress</option>
                                <option {{ old('Duration.task.'.$ke.'.status') == 'completed' ? 'selected' : '' }} value="completed">Completed</option>
                            </select>  
                        </div>  
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="end_date">End Date:</label>  
                            <input type="date" class="form-control flatpickr" name="Duration[task][$ke][end_date]" placeholder="End Date" value="{{ old('Duration.task.'.$ke.'.end_date') }}"/>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
                
                <div class="float-end" style=" margin-top: 10px;">
                    <a class="btn btn-outline-primary addgroup" >Add Row</a>
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
                <label class="form-check-inline form-check"><input {{ old('Duration.effectiveDate', $defVals['end_contract_type_def']) == 'onetimeContract' ? 'checked' : '' }} type="radio" class="contractCommencementEffectiveDate form-check-input calculateBilling" value="onetimeContract" name="Duration[effectiveDate]" checked>One time Contract</label>
                <label class="form-check-inline form-check"><input {{ old('Duration.effectiveDate', $defVals['end_contract_type_def']) == 'fixedTerm' ? 'checked' : '' }} value="fixedTerm" class="contractCommencementEffectiveDate form-check-input calculateBilling" type="radio" name="Duration[effectiveDate]">Fixed Term Contract with Periodic Renewal</label>
                <label class="form-check-inline form-check"><input {{ old('Duration.effectiveDate', $defVals['end_contract_type_def']) == 'evergreen' ? 'checked' : '' }} value="evergreen" class="contractCommencementEffectiveDate form-check-input calculateBilling" type="radio" name="Duration[effectiveDate]">Evergreen/Perpetual Contracts </label>
                <label class="form-check-inline form-check showinedit"> <input {{ old('Duration.effectiveDate', $defVals['end_contract_type_def']) == 'termination' ? 'checked' : '' }} value="termination" class="contractCommencementEffectiveDate form-check-input calculateBilling" type="radio" name="Duration[effectiveDate]">Termination</label>
            </div>
        </div>
    </div>

    <div class="col-sm-{{ $simpleForm ? '12': '4' }}" id="onetimeContract" style="display: {{ old('Duration.effectiveDate', $defVals['end_contract_type_def']) == 'onetimeContract' ? '' : 'none' }}">
        <div class="form-group mt-3">
            <hr class="mt-0 unRequiredFields {{ $simpleForm ? 'd-none': '' }}" />
            <h5 class="unRequiredFields {{ $simpleForm ? 'd-none': '' }}">One time Contract</h5>
            <hr class="mt-0 unRequiredFields {{ $simpleForm ? 'd-none': '' }}" />
            <div class="form-group">
                <label>End date of contract <span class="required-field-old text-danger" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'inline-block' : 'none'}}">*</span></label>
                <div class="clearfix">
                    <input type="date" name="Duration[onetimeEndDateofContract]" value="{{ old('Duration.onetimeEndDateofContract') }}" class="form-control flatpickr calculateBilling" placeholder="End date of contract"/>
                </div>
            </div>
        </div>
    </div>


    <div class="col-sm-12 mt-2" id="fixedTerm" style="display: {{ old('Duration.effectiveDate', $defVals['end_contract_type_def']) == 'fixedTerm' ? '' : 'none' }}">
        <hr class="mt-3 unRequiredFields {{ $simpleForm ? 'd-none': '' }}" />
        <h5 class="mt-2 unRequiredFields {{ $simpleForm ? 'd-none': '' }}">Fixed Term Contract with Periodic Renewal</h5>
        
        <hr class="mt-3 unRequiredFields {{ $simpleForm ? 'd-none': '' }}" />
        
        <div class="form-group row mt-2">
            <div class="row">
                <div class="form-group  col-sm-{{ $simpleForm ? '12': '4' }} mt-2">
                    <label>End date of contract <span class="required-field-old text-danger" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'inline-block' : 'none'}}">*</span></label>
                    <div class="clearfix">
                        <input type="date" class="form-control flatpickr calculateBilling" name="Duration[fixedtimeEndDateofContract]" value="{{ old('Duration.fixedtimeEndDateofContract') }}" placeholder="End date of contract"/>
                    </div>

                </div>

                <div class="form-group  col-sm-4 mt-2 unRequiredFields {{ $simpleForm ? 'd-none': '' }}">
                    <label>Type of Renewal</label>
                    <div class="clearfix">
                        <select class="form-control select2 typerenewal" name="Duration[typeRenewal]">
                            <option value="automaticrenewal" {{ old('Duration.typeRenewal') == 'automaticrenewal' ? 'selected' : '' }}>Automatic renewal with notice</option>
                            <option value="manualRenewal" {{ old('Duration.typeRenewal') == 'manualRenewal' ? 'selected' : '' }}>Manual Renewal with notice</option>
                        </select>
                    </div>
                </div>
                <div class="form-group  col-sm-4 mt-2 auto-renewal-section unRequiredFields {{ $simpleForm ? 'd-none': '' }}" style="display: {{ old('Duration.typeRenewal') == 'automaticrenewal' ? '' : 'none' }}">
                    <label>Period of auto renewal</label>
                    <div class="clearfix row">
                        <div class="col-sm-5"><input class="form-control" type="text" name="Duration[periodAutoRenewal]" value="{{ old('Duration.periodAutoRenewal')}}"/></div>
                        <div class="col-sm-7"><select class="form-control select2" name="Duration[periodAutoRenewalPeriod]">
                                <option value="years" {{ old('Duration.periodAutoRenewalPeriod') == 'years' ? 'selected' : '' }}>Years</option>
                                <option value="months" {{ old('Duration.periodAutoRenewalPeriod') == 'months' ? 'selected' : '' }}>Months</option>
                                <option value="days" {{ old('Duration.periodAutoRenewalPeriod') == 'days' ? 'selected' : '' }}>Days</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2 unRequiredFields {{ $simpleForm ? 'd-none': '' }}">
                <div class="form-group  col-sm-3 mt-2">
                    <label class="typerenewallable">{{ old('Duration.typeRenewal') == 'automaticrenewal' ? 'Auto' : 'Manual' }} renewal Date:</label>
                    <div class="clearfix">
                        <input type="date" class="form-control flatpickr" name="Duration[autoRenewalDate]" value="{{ old('Duration.autoRenewalDate') }}" placeholder="Auto renewal Date"/>
                    </div>
                </div> 
            </div>

        </div>
    </div>



    <div class="col-sm-{{ $simpleForm ? '12': '6' }}" id="evergreen" style="display: {{ old('Duration.effectiveDate') == 'evergreen' ? '' : 'none' }}">
        <hr class="mt-3 {{ $simpleForm ? 'd-none': '' }}" />
        <div class="form-group mt-3">
            <h5 class="{{ $simpleForm ? 'd-none': '' }}">Evergreen Contracts</h5>
            <hr class="mt-3 {{ $simpleForm ? 'd-none': '' }}" />
            <div class="form-group">
                <label>Condition for end of contract:</label>
                <div class="clearfix">
                    <select class="form-control conditionEndContract" name="Duration[conditionEndContract]">
                        <option {{ old('Duration.conditionEndContract') == 'mutually' ? 'selected' : '' }} value="mutually">When mutually agreed to end
                        </option>
                        <option {{ old('Duration.conditionEndContract') == 'termination' ? 'selected' : '' }} value="termination">When Termination Clause is triggered
                        </option>
                        <option {{ old('Duration.conditionEndContract') == 'delivered' ? 'selected' : '' }} value="delivered">When good are delivered/ project is completed/ milestone is achieved
                        </option>
                        <option {{ old('Duration.conditionEndContract') == 'others' ? 'selected' : '' }} value="others">others [specify]
                        </option>
                    </select>
                </div>
                <div class="clearfix">
                     <input type="text" style="display: {{ old('Duration.conditionEndContract') == 'others' ? '' : 'none' }};" id="conditionEndContractOthers" class="form-control mt-1" name="Duration[conditionEndContractOthers]" value="{{ old('Duration.conditionEndContractOthers') }}">
                </div>
            </div>
        </div>
    </div>


    <div class="col-sm-12" id="termination" style="display: {{ old('Duration.effectiveDate') == 'termination' ? '' : 'none' }}">
        <h4>Termination</h4>

        <div class="clearfix row">
            <div class="form-group col-sm-3">
                <div class="form-group">
                    <label>Date</label>
                    <div class="clearfix">
                        <input type="date"  class="form-control flatpickr"  name="Duration[terminationDate]" value="{{ old('Duration.terminationDate') }}"/>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group  col-sm-6">
                    <label>Reason for termination</label>
                    <div class="clearfix">
                        <select class="form-control" name="Duration[reasonTermination]">
                            <option {{ old('Duration.reasonTermination') == 'mutually' ? 'selected' : '' }} value="mutually">When mutually agreed to end</option>
                            <option {{ old('Duration.reasonTermination') == 'termination' ? 'selected' : '' }} value="termination">When Termination Clause is triggered</option>
                            <option {{ old('Duration.reasonTermination') == 'dispute' ? 'selected' : '' }} value="dispute">Dispute</option>
                            <option {{ old('Duration.reasonTermination') == 'nonRenewal' ? 'selected' : '' }} value="nonRenewal">Non renewal</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="clearfix mt-4 enableReminderSection {{ $v3Form ? '' : 'unRequiredFields' }} {{ $simpleForm ? 'd-none': '' }}">
<hr>
        <div class="clearfix mb-4">
            <label for="Reminder"> Enable Reminder</label>
            <input type="checkbox" class="form-check-input " id="Reminder" name="Duration[reminderEnable]" {{ old('Duration.reminderEnable', 'on') == 'on' ? 'checked' : '' }} />
        </div>
    <div class="nav-align-top nav-tabs-shadow mb-4">
     
        <div class="col-sm-12">
      <ul class="nav nav-tabs m-0 m0" role="tablist">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-home" aria-controls="navs-top-home" aria-selected="true">First level</button>
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
                        <select class="select2 form-select valid" name="Duration[Reminder][first][alertMe]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.first.alertMe') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                            <option {{ old('Duration.Reminder.first.alertMe') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class="">Alert Me on</label>
                         <div class="col">
                            <input type="number" class="form-control" value="{{ old('Duration.Reminder.first.alertMeDay', '30') }}" min="1" name="Duration[Reminder][first][alertMeDay]" />
                        </div>
                         <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][first][alertMePrior]">
                                <option {{ old('Duration.Reminder.first.alertMePrior') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.first.alertMePrior') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.first.alertMePrior') == 'years' ? 'selected' : '' }} value="years">Years</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][first][alertMeType]">
                                <option {{ old('Duration.Reminder.first.alertMeType') == 'prior' ? 'selected' : '' }} value="prior">Prior</option>
                                <option {{ old('Duration.Reminder.first.alertMeType') == 'after' ? 'selected' : '' }} value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" name="Duration[Reminder][first][repeats]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.first.repeats') == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.first.repeats') == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.first.repeats') == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.first.repeats') == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.first.repeats') == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.first.repeats') == 'Never' ? 'selected' : '' }} value="Never">Never</option>
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
                        <select class="select2 form-select valid"  name="Duration[Reminder][second][alertMe]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.second.alertMe') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                            <option {{ old('Duration.Reminder.second.alertMe') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                       <div class="col">
                            <input type="number" class="form-control" min="1" value="{{ old('Duration.Reminder.second.alertMeDay', '15') }}" name="Duration[Reminder][second][alertMeDay]" />
                        </div>
                       <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][second][alertMePrior]">
                                <option {{ old('Duration.Reminder.second.alertMePrior') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.second.alertMePrior') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.second.alertMePrior') == 'years' ? 'selected' : '' }} value="years">Years</option>
                            </select>
                        </div>
                         <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][second][alertMeType]">
                            <option {{ old('Duration.Reminder.second.alertMeType') == 'prior' ? 'selected' : '' }} value="prior">Prior</option>
                            <option {{ old('Duration.Reminder.second.alertMeType') == 'after' ? 'selected' : '' }} value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" name="Duration[Reminder][second][repeats]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.second.repeats') == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.second.repeats') == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.second.repeats') == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.second.repeats') == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.second.repeats') == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.second.repeats') == 'Never' ? 'selected' : '' }} value="Never">Never</option>
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
                        <select class="select2 form-select valid" name="Duration[Reminder][escalation][alertMe]" aria-invalid="false">
                        <option {{ old('Duration.Reminder.escalation.alertMe') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                        <option {{ old('Duration.Reminder.escalation.alertMe') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                        <div class="col">
                            <input type="number" class="form-control" value="{{ old('Duration.Reminder.escalation.alertMeDay', '7') }}"  min="7" name="Duration[Reminder][escalation][alertMeDay]" />
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMePrior]">
                            <option {{ old('Duration.Reminder.escalation.alertMePrior') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.escalation.alertMePrior') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.escalation.alertMePrior') == 'years' ? 'selected' : '' }} value="years">Years</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType]">
                            <option {{ old('Duration.Reminder.escalation.alertMeType') == 'prior' ? 'selected' : '' }} value="prior">Prior</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.first.repeats') == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.escalation.repeats') == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.escalation.repeats') == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats') == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats') == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats') == 'Never' ? 'selected' : '' }} value="Never">Never</option>
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
                        <select class="select2 form-select valid" name="Duration[Reminder][escalation][alertMe_after]" aria-invalid="false">
                        <option {{ old('Duration.Reminder.escalation.alertMe_after') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                        <option {{ old('Duration.Reminder.escalation.alertMe_after') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                        <div class="col">
                            <input type="number" class="form-control" value="{{ old('Duration.Reminder.escalation.alertMeDay_after','7') }}"  min="1" name="Duration[Reminder][escalation][alertMeDay_after]" />
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeAfter]">
                            <option {{ old('Duration.Reminder.escalation.alertMeAfter') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.escalation.alertMeAfter') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.escalation.alertMeAfter') == 'years' ? 'selected' : '' }} value="years">Years</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType_after]">
                            <option {{ old('Duration.Reminder.escalation.alertMeType_after') == 'after' ? 'selected' : '' }} value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats_after]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.escalation.repeats_after') == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after') == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after') == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after') == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after') == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after') == 'Never' ? 'selected' : '' }} value="Never">Never</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div> 
</div>