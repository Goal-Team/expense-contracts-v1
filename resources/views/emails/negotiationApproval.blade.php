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
        $buffer = '<div><p><img src="'.asset('assets/logo/OnTrackLogo.png').'" alt="Image A" height="75" width="170" style="float:right;"/> ';
        $buffer .= ' <br/><br/><b> Dear Sir/Madam,</b>' ;
        $buffer .= ' </div> ';

        $buffer .= 'We would like to invite you to review and provide your feedback on the following contract for <b>' . $details['appDataStatus'] .'</b> <br/>';

        $buffer .= ' <br/><br/><b>Contract: </b>'.  $details['contractname'] ;
        $buffer .= ' <br/><br/>Please review the attached contract document. You have the option to:';
        $buffer .= ' <br/>• Download and review the contract';
        $buffer .= ' <br/>• Upload a modified version if you have changes to suggest';
        $buffer .= ' <br/>• Accept or Reject the contract';

        $buffer .= ' <br/><br/>Please access the review portal using the link below:';
        $buffer .= ' <br/><br/><p>Click to Review & Respond <a href="'.$details['externalLink'].'" style="background-color: #7367f0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Review Contract</a></p>';
        $buffer .= ' <br/><br/>The access link will expire in <b>7 days</b>. Please ensure your response is submitted before the expiration date.';
        $buffer .= ' <br/><br/>Thank You.';

        $buffer .= ' <br/><br/>For any clarifications please contact us at '.env('support_mail').' <br/><br/>To ensure you do not miss out on important emails and alerts from us, we request you to please ensure that emails from this email-ID are NOT SET as spam.  <br/>
        <br>Best regards,<br>'.env('APP_NAME').'<br></p></div>';
        if(isset($details['emailExTrackLink'])){
            $buffer .= '<img src="'.$details['emailExTrackLink'].'" width="1" height="1" alt="" style="display:none;" />';
        }

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
