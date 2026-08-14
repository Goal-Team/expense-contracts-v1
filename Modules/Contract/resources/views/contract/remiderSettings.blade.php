@extends('layouts/layoutMaster')
@section('title', 'Contracts - Reminder Settings')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'

])
@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection
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
.status-completed{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    /*background-color: #007bff;*/
    background-color: #a7a9ad;
    
}
.status-active{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #28c76f;
    
}
.status-expired{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #ff264a;
    
}
.status-terminate{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #08cfe6;
    
}

.status-renewed{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #7367f0;
    
}

.status-initialdraft{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #a8aaae;
    
}

.status-negotiation{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #28c76f;
    
}
.substatusText {
  text-transform: capitalize;
}
table.dataTable.table-striped>tbody>tr:nth-of-type(odd)>* {
    box-shadow: none;
   }
   table tr th, table tr td{
    border-right-width: 0 !important;
   }
   table.table-bordered.dataTable thead tr:first-child th, table.table-bordered.dataTable thead tr:first-child td {
    border-top-width: 0 !important;
    }
    table td.dataTables_empty {
        padding: 5rem !important;
    }
 @media(max-width:767px){
     
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
    table td + td {
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
    table.table tbody tr, table.table tbody td {
    margin: 1rem 0;
}
  }
  
#wrapper-scroll,
#wrapper-table {
  overflow-y: hidden;
}
#wrapper-scroll {
  height: 20px;
  overflow-x: auto;
  position: sticky; 
  bottom:0  
}

#wrapper-scroll::-webkit-scrollbar {
    width: 1px;
}

#wrapper-scroll::-webkit-scrollbar-track {
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
}

#wrapper-scroll::-webkit-scrollbar-thumb {
    background-color: #c0c0c0;
}

#wrapper-table::-webkit-scrollbar {
    display: none;
}

#custom-scroll {
  width: 100%;
  height: 1px;
} 

#table-scroll .table-responsive{
    overflow-x: hidden !important;
}
  
</style>
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
])
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')
<!--@vite(['resources/assets/js/tables-datatables-advanced.js'])-->
@endsection
@section('content')

<style>
    .act .card.card-border-shadow-primary:after {
        border-bottom-color: #7367f0;
    }
     .act .card.card-border-shadow-warning:after {
    border-bottom-color: #ff9f43;
}
     .act .card.card-border-shadow-info:after {
    border-bottom-color: #00cfe8;
}
     .act  .card.card-border-shadow-success:after {
    border-bottom-color: #28c76f;
}

  .act .card[class*=card-border-shadow-] {
    box-shadow: 0 0.25rem 1rem rgba(165, 163, 174, 0.4);
  }
  .act .card[class*=card-border-shadow-]:after {
    border-width: 5px;
  }
</style>
<div class="row">

<div class="card pt-4">
   <h4 class="card-title">Reminder Settings</h4>
   @if(Session::has('message'))
  <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible mb-2">{!! Session::get('message') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </p>
   @endif
   <form method="POST" action="{{url('reminderSettingsStore')}}">
    @csrf
    <input type="hidden" name="Duration[Reminder][severity]" value="{{old('Duration.Reminder.severity',$remiderSettingsType)}}" />
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
                        <select class="select2 form-select valid" name="Duration[Reminder][first][alertMe]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.first.alertMe', $remiderSettings->reminder_first_alert ?? 'Renewal Date') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                            <option {{ old('Duration.Reminder.first.alertMe', $remiderSettings->reminder_first_alert ?? 'Renewal Date') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class="">Alert Me on</label>
                        @php
                         $fristarl  = explode(" ", $remiderSettings->reminder_first_alertMeOn ?? ''); 
                        @endphp
                         <div class="col">
                            <input type="number" class="form-control" value="{{ old('Duration.Reminder.first.alertMeDay', $fristarl[0] ?? '') }}" min="1" name="Duration[Reminder][first][alertMeDay]" />
                        </div>
                         <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][first][alertMePrior]">
                                <option {{ old('Duration.Reminder.first.alertMePrior', $fristarl[1] ?? '') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.first.alertMePrior', $fristarl[1] ?? '') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.first.alertMePrior', $fristarl[1] ?? '') == 'years' ? 'selected' : '' }} value="years">Years</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][first][alertMeType]">
                                <option {{ old('Duration.Reminder.first.alertMeType', $fristarl[2] ?? '') == 'prior' ? 'selected' : '' }} value="prior">Prior</option>
                                <option {{ old('Duration.Reminder.first.alertMeType', $fristarl[2] ?? '') == 'after' ? 'selected' : '' }} value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        @php
                        $firstRepeats = $remiderSettings['reminder_first_alert_repeats'] ?? 'Daily';
                        @endphp
                        <select class="select2 form-select valid" name="Duration[Reminder][first][repeats]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.first.repeats', $firstRepeats) == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.first.repeats', $firstRepeats) == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.first.repeats', $firstRepeats) == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.first.repeats', $firstRepeats) == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.first.repeats', $firstRepeats) == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.first.repeats', $firstRepeats) == 'Never' ? 'selected' : '' }} value="Never">Never</option>
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
                            <option {{ old('Duration.Reminder.second.alertMe', $remiderSettings['reminder_second_alert'] ?? 'Renewal Date') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                            <option {{ old('Duration.Reminder.second.alertMe', $remiderSettings['reminder_second_alert'] ?? 'Renewal Date') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                        @php
                         $secondalrt  = explode(" ", $remiderSettings['reminder_second_alertMeOn'] ?? ''); 
                        @endphp                        
                       <div class="col">
                            <input type="number" class="form-control" min="1" value="{{ old('Duration.Reminder.second.alertMeDay', $secondalrt[0] ?? '1') }}" name="Duration[Reminder][second][alertMeDay]" />
                        </div>
                       <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][second][alertMePrior]">
                                <option {{ old('Duration.Reminder.second.alertMePrior', $secondalrt[1] ?? '') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.second.alertMePrior', $secondalrt[1] ?? '') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.second.alertMePrior', $secondalrt[1] ?? '') == 'years' ? 'selected' : '' }} value="years">Years</option>
                            </select>
                        </div>
                         <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][second][alertMeType]">
                            <option {{ old('Duration.Reminder.second.alertMeType', $secondalrt[2] ?? '') == 'prior' ? 'selected' : '' }} value="prior">Prior</option>
                            <option {{ old('Duration.Reminder.second.alertMeType', $secondalrt[2] ?? '') == 'after' ? 'selected' : '' }} value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        @php
                        $secondRepeats = $remiderSettings['reminder_second_alert_repeats'] ?? 'Daily';
                        @endphp                        
                        <select class="select2 form-select valid" name="Duration[Reminder][second][repeats]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.second.repeats', $secondRepeats) == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.second.repeats', $secondRepeats) == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.second.repeats', $secondRepeats) == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.second.repeats', $secondRepeats) == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.second.repeats', $secondRepeats) == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.second.repeats', $secondRepeats) == 'Never' ? 'selected' : '' }} value="Never">Never</option>
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
                        <option {{ old('Duration.Reminder.escalation.alertMe',$remiderSettings['reminder_escalation_alert'] ?? 'Renewal Date') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                        <option {{ old('Duration.Reminder.escalation.alertMe',$remiderSettings['reminder_escalation_alert'] ?? 'Renewal Date') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                        @php
                         $thirdalrt  = explode(" ", $remiderSettings['reminder_escalation_alertMeOn'] ?? ''); 
                        @endphp                          
                        <div class="col">
                            <input type="number" class="form-control" value="{{ old('Duration.Reminder.escalation.alertMeDay', $thirdalrt[0] ?? '') }}"  min="1" name="Duration[Reminder][escalation][alertMeDay]" />
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMePrior]">
                                <option {{ old('Duration.Reminder.escalation.alertMePrior',$thirdalrt[1] ?? '') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.escalation.alertMePrior',$thirdalrt[1] ?? '') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.escalation.alertMePrior',$thirdalrt[1] ?? '') == 'years' ? 'selected' : '' }} value="years">Years</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType]">
                            <option {{ old('Duration.Reminder.escalation.alertMeType', $thirdRepeats[2] ?? '') == 'prior' ? 'selected' : '' }} value="prior">Prior</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        @php
                        $thirdRepeats = $remiderSettings['reminder_escalation_alert_repeats'] ?? 'Daily';
                        @endphp                         
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.escalation.repeats',$thirdRepeats) == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.escalation.repeats',$thirdRepeats) == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.escalation.repeats',$thirdRepeats) == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats',$thirdRepeats) == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats',$thirdRepeats) == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats',$thirdRepeats) == 'Never' ? 'selected' : '' }} value="Never">Never</option>
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
                        <option {{ old('Duration.Reminder.escalation.alertMe_after', $remiderSettings['reminder_escalation_alert_after'] ?? 'Renewal Date') == 'Renewal Date' ? 'selected' : '' }}>Renewal Date</option>
                        <option {{ old('Duration.Reminder.escalation.alertMe_after', $remiderSettings['reminder_escalation_alert_after'] ?? 'Renewal Date') == 'Contract End Date' ? 'selected' : '' }} >Contract End Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                        @php
                         $fourthalrt  = explode(" ", $remiderSettings['reminder_escalation_alertMeOn_after'] ?? ''); 
                        @endphp                           
                        <div class="col">
                            <input type="number" class="form-control" value="{{ old('Duration.Reminder.escalation.alertMeDay_after', $fourthalrt[0] ?? '') }}"  min="1" name="Duration[Reminder][escalation][alertMeDay_after]" />
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeAfter]">
                                <option {{ old('Duration.Reminder.escalation.alertMeAfter', $fourthalrt[1] ?? '') == 'days' ? 'selected' : '' }} value="days">Days</option>
                                <option {{ old('Duration.Reminder.escalation.alertMeAfter', $fourthalrt[1] ?? '') == 'months' ? 'selected' : '' }} value="months">Months</option>
                                <option {{ old('Duration.Reminder.escalation.alertMeAfter', $fourthalrt[1] ?? '') == 'years' ? 'selected' : '' }} value="years">Years</option>
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
                        @php
                        $fourthRepeats = $remiderSettings['reminder_escalation_alert_repeats_after'] ?? 'Daily';
                        @endphp                         
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats_after]" aria-invalid="false">
                            <option {{ old('Duration.Reminder.escalation.repeats_after', $fourthRepeats) == 'Daily' ? 'selected' : '' }} value="Daily">Daily</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after', $fourthRepeats) == 'Every 3 days' ? 'selected' : '' }} value="Every 3 days">Every 3 days</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after', $fourthRepeats) == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after', $fourthRepeats) == 'Fortnightly' ? 'selected' : '' }} value="Fortnightly">Fortnightly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after', $fourthRepeats) == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                            <option {{ old('Duration.Reminder.escalation.repeats_after', $fourthRepeats) == 'Never' ? 'selected' : '' }} value="Never">Never</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
    <div class="card-footer">
       <button type="submit" class="btn btn-primary waves-effect waves-light">Save</button> 
    </div>
   </form>
</div>
      </div>
@endsection