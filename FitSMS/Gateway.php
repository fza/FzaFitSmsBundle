<?php

namespace Fza\FitSmsBundle\FitSms;

use Fza\FitSmsBundle\Helper\NumberHelper;
use Fza\FitSmsBundle\SMS;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class Gateway
{
    private $options;
    private $failureCount = 0;
    private $successCount = 0;
    private $logger;

    public function __construct(array $options = array(), LoggerInterface $logger = null)
    {
        $this->setOptions($options);
        $this->logger = $logger;
    }

    public function setOptions( array $options )
    {
        $this->options = array(
            'debug'               => false,
            'debug_test'          => true,
            'gateway_uri'         => '',
            'max_sms_part_count'  => null,
            'default_intl_prefix' => '',
            'tracking'            => true,
            'username'            => null,
            'password'            => null,
            'numlock'             => null,
            'iplock'              => null,
            'from'                => null,
        );

        $invalid = array();
        $isInvalid = false;
        foreach($options as $key => $value) {
            if(array_key_exists($key, $this->options)) {
                $this->options[$key] = $this->checkOption($key, $value);
            } else {
                $isInvalid = true;
                $invalid[] = $key;
            }
        }

        if($isInvalid)
        {
            throw new \InvalidArgumentException(sprintf('The FitSMS gateway does not support the following options: "%s".', implode('\', \'', $invalid)));
        }
    }

    public function setOption($key, $value)
    {
        if(!array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The FitSMS gateway does not support the "%s" option.', $key));
        }

        $this->options[$key] = $this->checkOption($key, $value);
    }

    public function getOption($key)
    {
        if(!array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The FitSMS gateway does not support the "%s" option.', $key));
        }

        return $this->options[$key];
    }

    public function sendSMS(SMS $sms, \DateTime $time = null)
    {
        $recipients = array();
        $to = $sms->getRecipient();

        if(is_string($to) && false !== strpos($to, ',')) {
            $to = explode(',', $to);
        }
        else if(!is_array($to)) {
            $to = array($to);
        }

        foreach($to as $number) {
            $recipients[] = NumberHelper::fixPhoneNumber($number, $this->options['default_intl_prefix']);
        }

        $text = $sms->getText();

        if(empty($text)) {
            throw new \InvalidArgumentException('A SMS must contain text.');
        }

        if(null !== $this->options['max_sms_part_count'] && ($partCount = self::getSMSPartCount($sms)) > $this->options['max_sms_part_count']) {
            throw new \LengthException(sprintf('The SMS part count necessary to send the SMS (\d parts) exceeds the defined maximum value of \d parts.', $partCount, $this->options['max_sms_part_count']));
        }

        $dataArray = array(
            'username'  => urlencode($this->options['username']),
            'password'  => urlencode($this->options['password']),
            'type'      => 'text',
            'to'        => implode(',', $recipients),
            'content'   => urlencode($text)
        );

        foreach(array('iplock', 'numlock', 'from') as $key) {
            if(null !== $this->options[$key]) {
                $dataArray[$key] = $this->options[$key];
            }
        }

        if(null !== $time) {
            $dataArray['time'] = $time->format('YmdHis');
        }

        if($this->options['debug'] && $this->options['debug_test']) {
            $dataArray['test'] = '1';
        }

        $messageId = '';
        if($this->options['tracking']) {
            $date = new \DateTime();
            $dataArray['requestid'] = $messageId = $date->format('Ymd-His-').substr(sha1(uniqid(implode('', $dataArray).$date->format('r'), true)), 3, 14);
        }

        $data = '';
        $first = true;
        foreach($dataArray as $key => $value) {
            if(!$first) {
                $data .= '&';
            }

            $first = false;
            $data .= $key.'='.$value;
        }

        $opts = array('http' => array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $data,
        ));

        $response = null;
        $isFailure = false;

        // ToDo: Use curl instead?
        $context = stream_context_create($opts);
        $fp = @fopen($this->options['gateway_uri'], 'rb', false, $context);
        if($fp) {
            $response = new Response(@stream_get_contents($fp));
            @fclose( $fp );
        } else {
            if(null !== $this->logger) {
                $this->logger->warn(sprintf('Failed to connect to the FitSMS gateway server at "\s".', $this->options['gateway_uri']));
            }

            $isFailure = true;
        }

        if(null !== $response) {
            if($response->isFailure()) {
                if(null !== $this->logger) {
                    $this->logger->err(sprintf('Failed to send SMS\s. (\s)', null !== $messageId ? ', message ID: '.$messageId : '', $response->getMessage()));
                }

                $isFailure = true;
            }

            if(null !== $this->logger) {
                $this->logger->info(sprintf('SMS has been sent successfully\s\s. (\s)', $response->isTest() ? ' in test mode' : '', null !== $messageId ? ', message ID: '.$messageId : '', $response->getMessage()));
            }
        }

        if($isFailure) {
            $this->failureCount++;
            return false;
        }

        $this->successCount++;
        return true;
    }

    public function getSMSSuccessCount()
    {
        return $this->successCount;
    }

    public function getSMSFailureCount()
    {
        return $this->failureCount;
    }

    private function checkOption($key, $value)
    {
        switch($key) {
            case 'numlock':
            case 'iplock':
                return !empty($value) ? '1' : '0';

            case 'max_sms_part_count':
                return max(1, min((int) $value, 6));

            case 'from':
                if(preg_match('/[a-zA-Z]/', $value)) {
                    return substr($value, 0, 30);
                }

                return null !== $value ? NumberHelper::fixPhoneNumber($value, $this->options['default_intl_prefix']) : null;
        }

        return $value;
    }

    static public function getSMSPartCount(SMS $sms)
    {
        $text = $sms->getText();
        $len = strlen($text);

        return ceil($len/($len > 160 ? 153 : 160));
    }
}

