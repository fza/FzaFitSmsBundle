<?php

namespace Fza\FitSmsBundle;

class SmsMessage
{
    private $recipient;
    private $text;

    /**
     * @param array|string $recipient
     * @param string       $text
     */
    public function __construct($recipient, $text)
    {
        $this->setRecipient($recipient);
        $this->setText($text);
    }

    /**
     * Get the recipient(s)
     *
     * @return array|string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set the recipient(s)
     *
     * @param array|string $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Get the content
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the content
     *
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = trim($text);
    }
}
