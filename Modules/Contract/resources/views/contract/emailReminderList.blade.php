@extends('layouts/layoutMaster')
@section('title', 'Contracts - List')
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
   @if(Session::has('message'))
  <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible mb-2">{!! Session::get('message') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </p>
   @endif
        
       <div class="card-datatable text-responsive" id="table-scroll">

                 @foreach($remaindersFinalEmail as $emailIdd => $byEmails)

                    @php
                                        $buffer ='';
                                        $overdueBuffer ='';
                                        $dueBuffer ='';
                                        $upcomingBuffer ='';
                                        $buffer = '<div> <h4>Emails For '.$emailIdd.'</h4>';
                            
                                    
                                        $overDue = 0;
                                        $due = 0;
                                        $upcoming = 0;
                                        
                                        $overdueBuffer .= '<br><h6>Esclation Level</h6>
                                                        <table border="1" cellpadding="10" cellspacing="10" 
                                                            style="border-collapse:collapse;">
                                                        <thead>
                                                            <th>Contract Number</th>
                                                            <th>Start Date</th>
                                                            <th>End Date</th>
                                                            <th>Actions</th>
                                                        </thead>
                                                        <tbody>';
                                                    
                                        $dueBuffer .= '<br><h6>Second Level</h6>
                                                        <table border="1" cellpadding="10" cellspacing="10" 
                                                            style="border-collapse:collapse;">
                                                        <thead>
                                                            <th>Contract Number</th>
                                                            <th>Start Date</th>
                                                            <th>End Date</th>
                                                            <th>Actions</th>
                                                        </thead>
                                                        <tbody>';
                                                    
                                        $upcomingBuffer .= '<br><h6>First Level</h6>
                                                            <table border="1" cellpadding="10" cellspacing="10" 
                                                                style="border-collapse:collapse;">
                                                            <thead>
                                                                <th>Contract Number</th>
                                                                <th>Start Date</th>
                                                                <th>End Date</th>
                                                                <th>Actions</th>
                                                            </thead>
                                                            <tbody>';
                                                        
                                        foreach($byEmails as $rowTask)
                                        {
                                        
                                            if($rowTask['escalationRemain'] != '0'){
                                                        
                                                $overdueBuffer .= '<tr>
                                                                        <td>'.$rowTask['contract_number'].'</td>
                                                                        <td>'.$rowTask['start_date'].'</td>
                                                                        <td>'.$rowTask['end_date'].'</td>
                                                                        <td><a href="'.$rowTask['actions'].'">View Contract</a></td>
                                                                    </tr>';    
                                                
                                                $overDue++;
                                                
                                            }elseif($rowTask['secondRemain'] != '0' && $rowTask['escalationRemain'] == '0'){
                                                $dueBuffer .= '<tr>
                                                                    <td>'.$rowTask['contract_number'].'</td>
                                                                    <td>'.$rowTask['start_date'].'</td>
                                                                    <td>'.$rowTask['end_date'].'</td>
                                                                    <td><a href="'.$rowTask['actions'].'">View Contract</a></td>
                                                                </tr>';
                                                $due++;
                                            }elseif($rowTask['firstRemain'] != '0' && $rowTask['secondRemain'] == '0' && $rowTask['escalationRemain'] == '0'){
                                                $upcomingBuffer .= '<tr>
                                                                        <td>'.$rowTask['contract_number'].'</td>
                                                                        <td>'.$rowTask['start_date'].'</td>
                                                                        <td>'.$rowTask['end_date'].'</td>
                                                                        <td><a href="'.$rowTask['actions'].'">View Contract</a></td>
                                                                    </tr>'; 
                                                            
                                                $upcoming++;
                                                
                                            }
                            
                                        }
                                        
                                        $overdueBuffer .= '</tbody>
                                                </table>
                                                <br><br>';
                                            
                                        $dueBuffer .= '</tbody>
                                            </table>
                                            <br><br>';    
                                        
                                        $upcomingBuffer .= '</tbody>
                                            </table>
                                            <br><br>';
                                            
                                        
                                        if($due > 0)
                                        {
                                            $buffer .= $dueBuffer;
                                        }
                                        
                                        
                                        if($overDue > 0)
                                        {
                                            $buffer .= $overdueBuffer;
                                        }
                                    
                                        
                                        if($upcoming > 0)
                                        {
                                            $buffer .= $upcomingBuffer;
                                        }
                            
                                     
                                        $buffer .= '</div> ';
                
                      @endphp
                      {!! $buffer !!}
                
                 @endforeach
        <div id="wrapper-scroll">
          <div id="custom-scroll"></div>
        </div>           
       </div>
</div>
      </div>
@endsection