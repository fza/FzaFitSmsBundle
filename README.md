# FzaFitSmsBundle

This bundle provides a convenient method to send SMS using the [FitSMS.de](http://fitsms.de) gateway service.

## Features

- Multiple recipients per SMS
- Validation of phone numbers (sender and recipients)
- Expansion of phone numbers to satisfy international standards
- Default country prefix is configurable
- Able to check for a maximum number of SMS parts
- NumLock/IPLock support (see FitSMS gateway documentation)

## Installation

Add the bundle in your composer.json:

```js
{
    "require": {
        "fza/fitsms-bundle": "*"
    }
}
```

Run composer and download the bundle:

``` bash
$ php composer.phar update
```

Enable the bundle in the AppKernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Fza\FitSmsBundle\FzaFitSmsBundle(),
    );
}
```

## Configuration

Add the following lines to your config.yml:

``` yaml
fza_fit_sms:
    default_intl_prefix:  49
    username: "123456"
    password: "password"
    tracking: true

    # Default values are just fine here
    debug_test: ~
    gateway_uri: ~
    max_sms_part_count: ~
    numlock: ~
    iplock: ~
```

Notes:

- `default_intl_prefix`: (int) sets the default country prefix if none was detected for a number
- `username`, `password`: (string) self-explanatory
- `tracking`: (bool) enables transmitting a unique request id alongside the sms and logging it. You may view this id in the FitSMS control panel as well.
- `gateway_uri`: (string) In case the gateway URI ever changes, you can override the default
- `debug_test`: (bool) defaults to the kernel enviroment's debug value and sets the `debug` flag while sending a sms (will transmit it to the gateway, but not actually send it)
- `max_sms_part_count`: (int) defaults to 6
- `numlock`, `iplock`: (bool) Use these to enable the limit of sms to be sent to a recipient / to be sent from your server IP within an hour. These are just boolean values, the numeric limits are to be configured in the FitSMS control panel.

## Usage

``` php
use Fza\FitSmsBundle\SmsMessage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MyController extends Controller {

    public function smsAction()
    {
        // Send to multiple recipients with an array of phone numbers
        // Note that you should set phone numbers as strings to preserve leading zeros
        $recipient = '0049123456789';
        $message = this->renderView('MyBundle::sms.txt.twig');

        $sms = new SmsMessage($recipient, $message);

        $from = '0049123456789';
        $timeToSend = new \DateTime('2012-01-01 14:00:00');

        try {
            // $timeToSend parameter is optional (will send immediately)
            $smsSent = $this->get('fitsms.gateway')->sendMessage($sms, $from, $timeToSend);

            if (!$smsSent) {
                // Handle gateway errors (insufficient credit etc.)
            }
        } catch (\Exception $e) {
            // Catch exceptions (mostly due to invalid arguments)
        }
    }

}
```

