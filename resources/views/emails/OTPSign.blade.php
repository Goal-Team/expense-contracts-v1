<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
        }

        .email-header {
            background-color: #7367f0;
            color: #fff;
            padding: 10px 20px;
            text-align: center;
        }

        .email-body {
            margin: 20px 0;
        }

        .email-footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $buffer = '<div><p><img src="'.asset('assets/logo/OnTrackLogo.png').'" alt="ONTRACK" height="75" width="170" style="float:right;"/> ';
        $buffer .= ' <br/><br/><b> Dear Sir/Madam,</b>' ;
        $buffer .= ' </div> ';

        $buffer .= 'Please Fill The Following OTP for <b>' . $details['appDataStatus'] .'</b> <br/>';
        
        //$buffer .= ' <br/><br/><b>Location:</b>'.  $branchName ;
        $buffer .= ' <br/><br/><b>OTP: </b> '.$details['otp_number'] ;

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