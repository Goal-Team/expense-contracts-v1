<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
}   

.mainDiv{
  display: flex;
  flex-direction: row;
}
.leftDiv{
    align-self: left;
    text-align: center;
}

.rightDiv {
    align-self: right;
    text-align: center;
 
}
.report-img{
    max-width: 100%;
}
</style>
<div class="mainDiv">
    @if($imagesData[0] ?? false)
    <div class="leftDiv"><img class="report-img" src="data:image/png;base64, {{$imagesData[0] ?? ''}}" alt="Report ONTRACK" /></div>
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
           <th>Contract</th>
           <th>Prev Contract</th>
           <th>Exception Details</th>
        </tr>
     </thead>
     <tbody>
         @php

            $columnKeys = ['curconid["contract_unique_id"]','oldconid["contract_unique_id"]','exceptdetails'];
            $tableHtml = '';
            $sno = 0;

            foreach($tableData as $data){
                $sno++;
                
                $tableHtml .= '<tr>';
                $tableHtml .='<td>'.$sno.'</td>';
                $tableHtml .='<td>'.$data["curconid"]["contract_unique_id"].'</td>';
                $tableHtml .='<td>'.($data["oldconid"]["id"] ? $data["oldconid"]["contract_unique_id"] : "NA").'</td>';
                $tableHtml .='<td>'.$data["exceptdetails"].'</td>';
                $tableHtml .='</tr>';
            }
            echo $tableHtml;
         @endphp
     </tbody>
</table>
@endif