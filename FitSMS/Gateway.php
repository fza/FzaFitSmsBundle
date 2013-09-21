<?php

namespace Fza\FitSmsBundle\FitSms;

use Fza\FitSmsBundle\Helper\NumberHelper;
use Fza\FitSmsBundle\Sms;
use Psr\Log\LoggerInterface;

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

    /**
     * Set the gateway options
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    public function setOptions(array $options)
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

        $invalid   = array();
        $isInvalid = false;
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $this->checkOption($key, $value);
            } else {
                $isInvalid = true;
                $invalid[] = $key;
            }
        }

        if ($isInvalid) {
            throw new \InvalidArgumentException(sprintf(
                'The FitSMS gateway does not support the following options: "%s".',
                implode('\', \'', $invalid)
            ));
        }
    }

    /**
     * Some options needs special validation/sanitization
     *
     * @param $key
     * @param $value
     *
     * @return mixed|null|string
     */
    private function checkOption($key, $value)
    {
        switch ($key) {
            case 'numlock':
            case 'iplock':
                // Send these as plain integers
                return !empty($value) ? '1' : '0';

            case 'max_sms_part_count':
                // There is an enforced maximum of 6 parts per SMS
                return max(1, min((int) $value, 6));

            case 'from':
                // Is it alphanumeric?
                if (preg_match('/[a-zA-Z]/', $value)) {
                    return substr($value, 0, 30);
                }

                // Otherwise treat it as a telephone number
                return null !== $value ? NumberHelper::fixPhoneNumber(
                    $value,
                    $this->options['default_intl_prefix']
                ) : null;
        }

        return $value;
    }

    /**
     * Set one gateway option
     *
     * @param $key
     * @param $value
     *
     * @throws \InvalidArgumentException
     */
    public function setOption($key, $value)
    {
        if (!array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The FitSMS gateway does not support the "%s" option.', $key));
        }

        $this->options[$key] = $this->checkOption($key, $value);
    }

    /**
     * Get one gateway option
     *
     * @param $key
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getOption($key)
    {
        if (!array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The FitSMS gateway does not support the "%s" option.', $key));
        }

        return $this->options[$key];
    }

    /**
     * Main sending routine
     *
     * @param SMS       $sms
     * @param null      $from
     * @param \DateTime $time
     *
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \LengthException
     */
    public function sendSms(Sms $sms, $from = null, \DateTime $time = null)
    {
        $recipients = array();
        $to         = $sms->getRecipient();

        if (is_string($to) && false !== strpos($to, ',')) {
            $to = explode(',', $to);
        } else if (!is_array($to)) {
            $to = array($to);
        }

        foreach ($to as $number) {
            $recipients[] = NumberHelper::fixPhoneNumber($number, $this->options['default_intl_prefix']);
        }

        $text = $sms->getText();

        if (empty($text)) {
            throw new \InvalidArgumentException('A SMS must contain text.');
        }

        if (null !== $this->options['max_sms_part_count'] && ($partCount = self::getSmsPartCount(
                $sms
            )) > $this->options['max_sms_part_count']
        ) {
            throw new \LengthException(sprintf(
                'The SMS part count necessary to send the SMS (\d parts) exceeds the defined maximum value of \d parts.',
                $partCount,
                $this->options['max_sms_part_count']
            ));
        }

        $query = array(
            'username' => urlencode($this->options['username']),
            'password' => urlencode($this->options['password']),
            'type'     => 'text',
            'to'       => implode(',', $recipients),
            'content'  => urlencode($text)
        );

        foreach (array('iplock', 'numlock', 'from') as $key) {
            if (null !== $this->options[$key]) {
                $query[$key] = $this->options[$key];
            }
        }

        if (null !== $from) {
            $query['from'] = $this->checkOption('from', $from);
        }

        if (null !== $time) {
            $query['time'] = $time->format('YmdHis');
        }

        if ($this->options['debug'] && $this->options['debug_test']) {
            $query['test'] = '1';
        }

        $messageId = null;
        if ($this->options['tracking']) {
            $date = new \DateTime();

            $query['requestid'] = $messageId = $date->format('Ymd-His-') . substr(
                    sha1(uniqid(implode('', $query) . $date->format('r'), true)),
                    3,
                    14
                );
        }

        $opts = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($query),
            )
        );

        $response  = null;
        $isFailure = false;

        $context = stream_context_create($opts);
        $fp      = fopen($this->options['gateway_uri'], 'rb', false, $context);
        if ($fp) {
            $response = new Response(@stream_get_contents($fp));
            fclose($fp);
        } else {
            if (null !== $this->logger) {
                $this->logger->warning(
                    sprintf('Failed to connect to the FitSMS gateway server at "%s".', $this->options['gateway_uri'])
                );
            }

            $isFailure = true;
        }

        if (null !== $response) {
            if ($response->isFailure()) {
                if (null !== $this->logger) {
                    $this->logger->error(
                        sprintf(
                            'Failed to send SMS\s. %s)',
                            null !== $messageId ? ', message ID: ' . $messageId : '',
                            $response->getMessage()
                        )
                    );
                }

                $isFailure = true;
            }

            if (null !== $this->logger) {
                $this->logger->info(
                    sprintf(
                        'SMS has been sent successfully%s%s. (%s)',
                        $response->isTest() ? ' in test mode' : '',
                        null !== $messageId ? ', message ID: ' . $messageId : '',
                        $response->getMessage()
                    )
                );
            }
        }

        if ($isFailure) {
            $this->failureCount++;

            return false;
        }

        $this->successCount++;

        return true;
    }

    /**
     * Determine in how many parts a sms needs to be split
     *
     * @param SMS $sms
     *
     * @return float
     */
    static public function getSmsPartCount(Sms $sms)
    {
        $text = $sms->getText();
        $len  = strlen($text);

        return ceil($len / ($len > 160 ? 153 : 160));
    }

    /**
     * How many SMS were send successfully?
     *
     * @return int
     */
    public function getSmsSuccessCount()
    {
        return $this->successCount;
    }

    /**
     * How many SMS were not sent because of an error?
     *
     * @return int
     */
    public function getSmsFailureCount()
    {
        return $this->failureCount;
    }
}
