<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contract</title>

</head>

<body>
    @php
        $buffer = '<div><p><img src="'.asset('assets/logo/OnTrackLogo.png').'" alt="Image A" height="75" width="170" style="float:right;"/> ';
        $buffer .= ' <br/><br/><b> Dear Sir/Madam,</b>' ;
        $buffer .= ' </div> ';

        $buffer .= 'I hope this message finds you well.We would like to inform you that the following Contract 
        updates for your reference <br/>';
        
        //$buffer .= ' <br/><br/><b>Location:</b>'.  $branchName ;
        $buffer .= ' <br/><br/><b>Contract ID: </b>'.  $details['contractname'] ;

        $buffer .= ' <br/><br/><p>Click <a href="'.$details['contraclink'].'">View</a> See Details</p>';
        $buffer .= ' <br/><br/>Thank You.';
        
        
        $buffer .= ' <br/><br/>For any clarifications please contact us at '.env('support_mail').' <br/><br/>To ensure you do not
        miss out on important emails and alerts from us, we Request you to please ensure that emails from this email-ID are NOT SET as spam.  <br/>
        <br>Best regards,<br>'.env('APP_NAME').'<br></p></div> ';

      @endphp
      {!! $buffer !!}
      
    @if(isset($details['path']) && count($details['path']) > 0)
        <b>Attached Documents</b><br/>
        @foreach($details['path'] as $fiKey => $filePath_)
            <a href="{{ $filePath_ }}">{{ $details['fileName'][$fiKey] }}</a><br/>
        @endforeach
    @endif      
</body>

</html>