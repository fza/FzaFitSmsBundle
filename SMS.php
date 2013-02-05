<?php

namespace Fza\FitSmsBundle;

class SMS
{
    private $recipient;
    private $text;

    public function __construct($recipient, $text)
    {
        $this->setRecipient($recipient);
        $this->setText($text);
    }

    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setText($text)
    {
        $text = trim($text);

        if(empty($text)) {
            $text = '';
            return;
        }

        // FitSMS expects message text to be ISO-8859-1 encoded
        $this->text = mb_convert_encoding($text, 'ISO-8859-1', mb_detect_encoding($text));
    }

    public function getText()
    {
        return $this->text;
    }
}