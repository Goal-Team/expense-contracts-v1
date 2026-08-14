<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Emails</title>
</head>

<body>
    @php
                        $buffer ='';
                        $overdueBuffer ='';
                        $dueBuffer ='';
                        $upcomingBuffer ='';
                        $buffer = '<div><p><img src="'.asset('assets/logo/OnTrackLogo.png').'" alt="Image A" height="75" width="170" style="float:right;"/> ';
                        $buffer .= '<br/><br/><br/> Dear <b> Sir / Madam </b>,<br/><br/>';
                        $buffer .= 'We wish to inform you that the following Contracts for which you are the owner or review are going to complete/Renewal needed. 
                                        Kindly review';
            
                    
                        $overDue = 0;
                        $due = 0;
                        $upcoming = 0;
                        
                        $overdueBuffer .= '<br><h3>Esclation Level</h3>
                                        <table border="1" cellpadding="10" cellspacing="10" 
                                            style="border-collapse:collapse;">
                                        <thead>
                                            <th>Contract Number</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Actions</th>
                                        </thead>
                                        <tbody>';
                                    
                        $dueBuffer .= '<br><h3>Second Level</h3>
                                        <table border="1" cellpadding="10" cellspacing="10" 
                                            style="border-collapse:collapse;">
                                        <thead>
                                            <th>Contract Number</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Actions</th>
                                        </thead>
                                        <tbody>';
                                    
                        $upcomingBuffer .= '<br><h3>First Level</h3>
                                            <table border="1" cellpadding="10" cellspacing="10" 
                                                style="border-collapse:collapse;">
                                            <thead>
                                                <th>Contract Number</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Actions</th>
                                            </thead>
                                            <tbody>';
                                        
                        foreach($details['mailData'] as $rowTask)
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
            
                     
                        $buffer .= ' <br/><br/>For any clarifications please contact us at '.env('support_mail').' <br/><br/>
                                        To ensure you do not miss out on important emails and alerts from us, 
                                        we Request you to please ensure that emails from this email-ID are NOT SET as spam.  
                                        <br/><br>Best regards,<br>Contracts Support Team <br>'.env('APP_NAME').'<br></p></div> ';

      @endphp
      {!! $buffer !!}
      
</body>

</html>