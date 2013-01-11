<?php

namespace Fza\FitSmsBundle\FitSms;

class Response
{
    private $isFailure;
    private $isTest;
    private $message;

    public function __construct($responseText)
    {
        $responseText = trim($responseText);

        if(empty($responseText)) {
            $this->isFailure = true;
            $this->message = 'Empty response';

            return;
        }

        try {
            $responseXML = new \SimpleXMLElement($responseText);

            if(strtolower($responseXML->result) == 'error') {
                $this->isFailure = true;
                $this->message = $responseXML->error;
            } else {
                $this->isFailure = false;
                $message = 'Recipients: '.$responseXML->recipients->recipient->count();

                if(!empty($responseXML->deliverDate)) {
                    $dt = new \DateTime($responseXML->deliverDate);
                    $message .= ', delivered: '.$dt->format( 'Y.m.d H:i:s' );
                }

                if(null !== $responseXML->warnings->warning) {
                    $warnings = array();
                    foreach($responseXML->warnings->warning as $warning) {
                        if(substr($warning, 0, 3) == '510') {
                            $this->isTest = true;
                            continue;
                        }

                        $warnings[] = $warning;
                    }

                    if(count( $warnings)) {
                        $message .= ', warnings: '.$responseXML->warnings->warning->count().' ["'.implode($warnings, '", "').'"]';
                    }
                }

                $this->message = $message;
            }
        } catch(\Exception $e) {
            $this->isFailure = true;
            $this->message = 'Unexpected response';
        }
    }

    public function isFailure()
    {
        return $this->isFailure;
    }

    public function isTest()
    {
        return $this->isTest;
    }

    public function getMessage()
    {
        return $this->message;
    }
}