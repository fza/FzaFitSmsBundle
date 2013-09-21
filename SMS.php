<?php

namespace Fza\FitSmsBundle;

class Sms
{
    private $recipient;
    private $text;

    public function __construct($recipient, $text)
    {
        $this->setRecipient($recipient);
        $this->setText($text);
    }

    /**
     * Get the recipient(s)
     *
     * @return mixed
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set the recipient(s)
     *
     * @param $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Get the content
     *
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the content
     *
     * @param $text
     */
    public function setText($text)
    {
        $text = trim($text);

        if (empty($text)) {
            $text = '';

            return;
        }

        // FitSMS expects message text to be ISO-8859-1 encoded
        $this->text = mb_convert_encoding($text, 'ISO-8859-1', mb_detect_encoding($text));
    }
}
