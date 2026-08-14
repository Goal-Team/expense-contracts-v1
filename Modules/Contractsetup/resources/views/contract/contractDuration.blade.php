<div class="form-group">
    <h4>Signing Date</h4>
    <label>Date</label>
    <div class="clearfix row">
        <div class="col-sm-4"><input type="date" name="Duration[signingDate]" class="form-control" /></div>
    </div>
    <div class="clearfix">
        <small class="form-text text-muted">The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract.
        </small>
    </div>
</div>


<div class="card clearfix">
    <div class="col-sm-12">
        <div class="form-group">
            <h4>Contract Commencement</h4>
            <label>Effective date:</label>
            <div class="clearfix">
                <label class="radio-inline"><input type="radio" class="commencementDate" checked value="FixedDate" name="Duration[commencementDate]">Fixed Date</label>
                <label class="radio-inline"><input value="Eventbased" class="commencementDate" type="radio" name="Duration[commencementDate]">Event based commencement</label>
            </div>
        </div>
    </div>
    <div class="col-sm-12" id="FixedDate">
        <div class="form-group">
            <label>Fixed Date</label>
            <div class="clearfix row">
                <div class="col-sm-4"><input type="date" name="Duration[fixedDate]" class="form-control" /></div>
            </div>
        </div>
    </div>
    <div class="col-sm-12" id="Eventbased" style="display: none;">
        <div class="form-group row">
            <div class="col-sm-12">
                <label> Event based commencement</label>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>(i) Event Condition</label>
                    <div class="clearfix">
                        <select class="form-control" name="Duration[eventCondition]">
                            <option value="uponCompletion">Upon Completion of [Specify Event]</option>
                            <option value="uponDelivery">Upon Delivery of [Specify Deliverable]</option>
                            <option value="uponApproval">Upon Approval of [Specify Approval Process]</option>
                            <option value="other">Other (with a text field for specifying the event Condition)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>(ii) Name of event</label>
                    <div class="clearfix">
                        <textarea class="form-control" name="Duration[nameofevent]"></textarea>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>(iii) Event Details</label>
                    <div class="clearfix">
                        <textarea class="form-control" name="Duration[eventDetails]"></textarea>
                        <small class="form-text text-muted">If "Event-Based Commencement" is selected, this field allows the user to provide additional details or specifics about the event triggering the commencement of the contract.</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>(iv) Event deadline</label>
                    <div class="clearfix">
                        <input type="date" class="form-control" name="Duration[eventDeadline]" />
                    </div>
                </div>
            </div>

        </div>
    </div>


</div>


<div class="card clearfix">
    <div class="col-sm-12">
        <div class="form-group">
            <h4>End of Contract Term</h4>
            <label>Effective date:</label>
            <div class="clearfix">
                <label class="radio-inline"><input type="radio" class="contractCommencementEffectiveDate" value="onetimeContract" name="Duration[effectiveDate]" checked>One time Contract</label>
                <label class="radio-inline"><input value="fixedTerm" class="contractCommencementEffectiveDate" type="radio" name="Duration[effectiveDate]">Fixed Term Contract with Periodic Renewal</label>
                <label class="radio-inline"><input value="evergreen" class="contractCommencementEffectiveDate" type="radio" name="Duration[effectiveDate]">Evergreen Contracts </label>
                <label class="radio-inline showinedit"> <input value="termination" class="contractCommencementEffectiveDate" type="radio" name="Duration[effectiveDate]">Termination</label>
            </div>
        </div>
    </div>

    <div class="col-sm-4" id="onetimeContract">
        <div class="form-group">
            <h4>One time Contract</h4>
            <div class="form-group">
                <label>End date of contract</label>
                <div class="clearfix">
                    <input type="date" name="Duration[onetimeEndDateofContract]" class="form-control" />
                </div>
            </div>
        </div>
    </div>


    <div class="col-sm-12" id="fixedTerm" style="display: none;">

        <h4>Fixed Term Contract with Periodic Renewal</h4>
        <div class="form-group row">
            <div class="clearfix">
                <div class="form-group  col-sm-3">
                    <label>End date of contract</label>
                    <div class="clearfix">
                        <input type="date" class="form-control" name="Duration[fixedtimeEndDateofContract]" />
                    </div>

                </div>

                <div class="form-group  col-sm-5">
                    <label>Type of Renewal</label>
                    <div class="clearfix">
                        <select class="form-control" name="Duration[typeRenewal]">
                            <option value="automaticrenewal">Automatic renewal with notice</option>
                            <option value="manualRenewal">Manual Renewal with notice</option>
                        </select>
                    </div>
                </div>
                <div class="form-group  col-sm-4">
                    <label>Period of auto renewal</label>
                    <div class="clearfix row">
                        <div class="col-sm-5"><input class="form-control" type="text" name="Duration[periodAutoRenewal]"></div>
                        <div class="col-sm-7"><select class="form-control" name="Duration[periodAutoRenewalPeriod]">
                                <option value="years">Years</option>
                                <option value="months">Months</option>
                                <option value="days">Days</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix">
                <div class="form-group  col-sm-3">
                    <label>Auto renewal Date:</label>
                    <div class="clearfix">
                        <input type="date" class="form-control" name="Duration[autoRenewalDate]" />
                    </div>
                </div>
                <div class="form-group  col-sm-3">
                    <label>When Manual Renewal is selected</label>
                    <div class="clearfix">
                        <input type="date" class="form-control" name="Duration[autoManualRenewalDate]" />
                    </div>
                </div>
            </div>

        </div>
    </div>



    <div class="col-sm-6" style="display: none;" id="evergreen">
        <div class="form-group">
            <h4>Evergreen Contracts</h4>
            <div class="form-group">
                <label>Condition for end of contract:</label>
                <div class="clearfix">
                    <select class="form-control conditionEndContract" name="Duration[conditionEndContract]">
                        <option value="mutually">When mutually agreed to end
                        </option>
                        <option value="termination">When Termination Clause is triggered
                        </option>
                        <option value="delivered">When good are delivered/ project is completed/ milestone is achieved
                        </option>
                        <option value="others">others [specify]
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
<div class="row">
    <div class="panel-heading">
        <h4 class="panel-title">
            Enable Reminder
            <input type="checkbox" name="Duration[reminderEnable]" />
        </h4>
    </div>
    <div class="panel-collapse collapse in">
        <div class="panel-body ">
            <div class="row">
            <div class="col-sm-12">
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#first">Fist level</a></li>
                    <li><a data-toggle="tab" href="#second">Second Level</a></li>
                    <li><a data-toggle="tab" href="#escalation">Escalation</a></li>
                </ul>

                <div class="tab-content">
                    <div id="first" class="tab-pane fade in active">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label> Alert Me about</label>
                                    <select class="form-control valid" id="AlertMe" name="Duration[Reminder][first][alertMe]" aria-invalid="false">
                                        <option>Renewal/Internal Due Date</option>
                                        <option>Internal Due Date</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group row">
                                    <label class="col-xs-12">Alert Me on</label>
                                    <div class="col-xs-4">
                                        <input type="number" class="form-control" name="Duration[Reminder][first][alertMeDay]" />
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control col-sm-6" name="Duration[Reminder][first][alertMePrior]">
                                            <option value="days">Days</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control  col-sm-6" name="Duration[Reminder][first][alertMeType]">
                                            <option value="prior">Prior</option>
                                            <option value="after">After</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label> Repeats</label>
                                    <select class="form-control valid" id="Repeats" name="Duration[Reminder][first][repeats]" aria-invalid="false">
                                        <option>Daily</option>
                                        <option>Every 3 days</option>
                                        <option>Weekly</option>
                                        <option>Fortnightly</option>
                                        <option>Monthly</option>
                                        <option>Never</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="second" class="tab-pane fade">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label> Alert Me about</label>
                                    <select class="form-control valid" id="AlertMe" name="Duration[Reminder][second][alertMe]" aria-invalid="false">
                                        <option>Renewal/Internal Due Date</option>
                                        <option>Internal Due Date</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group row">
                                    <label class="col-xs-12">Alert Me on</label>
                                    <div class="col-xs-4">
                                        <input type="number" class="form-control" name="Duration[Reminder][second][alertMeDay]" />
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control col-sm-6" name="Duration[Reminder][second][alertMePrior]">
                                            <option value="days">Days</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control  col-sm-6" name="Duration[Reminder][second][alertMeType]">
                                            <option value="prior">Prior</option>
                                            <option value="after">After</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label> Repeats</label>
                                    <select class="form-control valid" id="Repeats" name="Duration[Reminder][second][repeats]" aria-invalid="false">
                                        <option>Daily</option>
                                        <option>Every 3 days</option>
                                        <option>Weekly</option>
                                        <option>Fortnightly</option>
                                        <option>Monthly</option>
                                        <option>Never</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="escalation" class="tab-pane fade">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label> Alert Me about</label>
                                    <select class="form-control valid" id="AlertMe" name="Duration[Reminder][escalation][alertMe]" aria-invalid="false">
                                        <option>Renewal/Internal Due Date</option>
                                        <option>Internal Due Date</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group row">
                                    <label class="col-xs-12">Alert Me on</label>
                                    <div class="col-xs-4">
                                        <input type="number" class="form-control" name="Duration[Reminder][escalation][alertMeDay]" />
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control col-sm-6" name="Duration[Reminder][escalation][alertMePrior]">
                                            <option value="days">Days</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                    <div class="col-xs-4">
                                        <select class="form-control  col-sm-6" name="Duration[Reminder][escalation][alertMeType]">
                                            <option value="prior">Prior</option>
                                            <option value="after">After</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label> Repeats</label>
                                    <select class="form-control valid" id="Repeats" name="Duration[Reminder][escalation][repeats]" aria-invalid="false">
                                        <option>Daily</option>
                                        <option>Every 3 days</option>
                                        <option>Weekly</option>
                                        <option>Fortnightly</option>
                                        <option>Monthly</option>
                                        <option>Never</option>
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