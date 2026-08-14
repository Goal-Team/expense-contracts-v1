<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $subject;
    public $attachment;
    public $mailTemplate;


    public function __construct($details, $subject,  $attachment = null, $mailTemplate="notiMail")
    {
        $this->details = $details;
        $this->subject = $subject;
        $this->attachment = $attachment;
        $this->mailTemplate = $mailTemplate;
    }

    public function build()
    {

        $email = $this->subject($this->subject)
            ->view('emails.'.$this->mailTemplate);

        if ($this->attachment) {
            foreach($this->attachment as $attach){
                $email->attach($attach);
            }
        }

        return $email;
    }
}
