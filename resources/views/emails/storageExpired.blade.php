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
        $buffer .= ' <br/><br/><b> Dear Admin,</b>' ;
        $buffer .= ' </div> ';
        
        $buffer .= ' <br/><br/><b>Client:</b>'.  $details['clientName'] ;
        $buffer .= ' <br/><br/><b>Storage Type: </b>'.  $details['storage'] ;

        $buffer .= ' <br/><br/> Please be advised that the access token <b>'.$details['expireDays'].' </b>Kindly take the necessary action as soon as possible to avoid any disruption';

      @endphp
      {!! $buffer !!}
      
</body>

</html>