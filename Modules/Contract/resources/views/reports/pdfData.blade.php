<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
}   

.mainDiv{
  display: inline-flex;
  flex-direction: row;
}
.leftDiv{
    align-self: start;
    text-align: center;
}

.rightDiv {
    align-self: end;
    text-align: center;
 
}
.report-img{
    width: 50%;
}
</style>
<div class="mainDiv">
    @if($imagesData[0] ?? false)
    <div class="leftDiv"><img class="report-img" style="{{ (!($imagesData[0] ?? false)) ? 'max-width: 100%;' : '' }}" src="data:image/png;base64, {{$imagesData[0] ?? ''}}" alt="Report ONTRACK" /></div>
    @endif
    @if($imagesData[1] ?? false)
    <div class="rightDiv"><img class="report-img" src="data:image/png;base64, {{$imagesData[1] ?? ''}}" alt="Report ONTRACK" /></div>
    @endif
</div>
<br/>
@if((is_array($tableData) || is_object($tableData)) && count($tableData) > 0)
<table>
     <thead>
        <tr>
           <th>S.No.</th>
           <th>Contract Name</th>
           <th>Location</th>
           <th>Contract Type</th>
           <th>Effective Date</th>
           <th>End Date</th>
           <th>Contract value</th>
        </tr>
     </thead>
     <tbody>
         @php

            $columnKeys = ['contract_name','location_branch','contract_type','fixed_date','contract_end_date','currency_value_converted'];
            $tableHtml = '';
            $sno = 0;

            foreach($tableData as $data){
                $sno++;
                
                $tableHtml .= '<tr>';
                $tableHtml .='<td>'.$sno.'</td>';
                foreach($columnKeys as $ck){
                    $tableHtml .='<td>'.$data->$ck.'</td>';
                }
                $tableHtml .='</tr>';
            }
            echo $tableHtml;
         @endphp
     </tbody>
</table>
@endif