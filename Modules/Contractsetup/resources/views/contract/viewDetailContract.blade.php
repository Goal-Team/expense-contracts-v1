@extends('contract-setup::layouts.admin')

@section('content')

<div class="panel-group" id="accordion">

    <h1>
        View Contract - {{$contract->contract_name}}
    </h1>
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
                            <label for="contractName">Contract Name </label>
                            <p>{{$contract->contract_name}}</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="sel1">Contract Type </label>
                            <p>{{$contract->contract_type}}</p>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="sel1">Catgoery </label>
                            <p>{{$contract->contract_type}}</p>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="sel1">Department</label>
                            <p>{{$contract->contract_type}}</p>
                        </div>
                    </div>
                </div>
                <div class="clearfix">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="comment"> Contract Description </label>
                            <p>{{$contract->contract_description}}</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="clearfix">
                        <h3>
                            Custom Fields
                        </h3>
                        <div class="row">

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <div class="panel panel-default" id="party">
        <div class="panel-heading">
            <h4 class="panel-title">
                Party Details
            </h4>
        </div>
        <div class="panel-collapse collapse in">
            <div class="panel-body">
                <div class="clearfix">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="contractName">Contract Name </label>
                            <p>{{$contract->contract_name}}</p>
                        </div>
                    </div>
                </div>
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
                <h4>Signing Date</h4>
                <div class="clearfix">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="contractName">Date</label>
                            <p>{{$contract->signing_date}}</p>
                        </div>
                    </div>
                </div>
                <h4>Contract Commencement</h4>
                <div class="clearfix">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="contractName">Effective date</label>
                            @if($contract->commencement_type == 'FixedDate')
                            <p>Fixed Date </p>

                            @if(isset($contract->fixed_date))
                            <p><strong>Fixed Date:</strong>{{$contract->fixed_date}}</p>
                            @endif

                            @else
                            <p>Event based commencement</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="panel panel-default" id="party">
        <div class="panel-heading">
            <h4 class="panel-title">
                Contract Value
            </h4>
        </div>
        <div class="panel-collapse collapse in">
            <div class="panel-body">
                <div class="clearfix">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="contractName">Contract Value</label>
                            <p>{{$contract->currency}} {{$contract->currency_value}}</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="contractName">Payment Schedule</label>
                            <p>{{$contract->payment_schedule}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('footer')


@endsection