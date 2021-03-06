<?php

namespace Fza\FitSmsBundle\Helper;

class NumberHelper
{
    /**
     * Inspect a phone number, mangle it or throw an exception if it has substantial errors
     *
     * @param string $number
     * @param int    $defaultIntlPrefix
     *
     * @return mixed|string
     * @throws \InvalidArgumentException
     */
    static public function fixPhoneNumber($number, $defaultIntlPrefix = 1)
    {
        if (empty($number) || (!is_numeric($number) && (!is_string($number) || !preg_match(
                        '/^\s*\+?[\d\s\-]$/',
                        $number
                    )))
        ) {
            throw new \InvalidArgumentException(sprintf(
                'The phone number must be a numeric string containing numbers, spaces, dashes and optionally a plus sign at the beginning. (\s)',
                $number
            ));
        }

        $number    = preg_replace(array('/(\s|-)/', '/^\+/'), array('', '00'), $number);
        $hasPrefix = (substr($number, 0, 2) == '00' || $number{0} == '+');

        if ($hasPrefix && !preg_match('/^(\+|00)[123456789]{3}/', $number)) {
            throw new \InvalidArgumentException(sprintf(
                'The receiver number does not follow international phone numbering standards. (\s)',
                $number
            ));
        } else if (!$hasPrefix) {
            $number = '00' . $defaultIntlPrefix . ltrim($number, '0');
        }

        return $number;
    }
}
