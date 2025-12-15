<?php

namespace App\Console\Traits;

use Cron\CronExpression;

trait ParsesCronFrequency
{
    /**
     * Parse a human-readable frequency into a cron expression.
     *
     * @param  string  $frequency
     * @return string
     */
    protected function parseFrequency(string $frequency): string
    {
        if (CronExpression::isValidExpression($frequency)) {
            return $frequency;
        }

        $frequency = strtolower($frequency);

        if (preg_match('/^(\d+)(m|h|d)$/', $frequency, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];

            if ($value === 0) {
                return '* * * * *'; // every minute
            }

            switch ($unit) {
                case 'm':
                    return "*/{$value} * * * *";
                case 'h':
                    return "0 */{$value} * * *";
                case 'd':
                    return "0 0 */{$value} * *";
            }
        }

        return '* * * * *'; // every minute
    }
}
