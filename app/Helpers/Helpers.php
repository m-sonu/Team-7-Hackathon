<?php
use App\Enums\Currency;


if (! function_exists('format_currency')) {
    function format_currency($amount, $currency = 'NRP'): string
    {
        return match (strtoupper($currency)) {
            Currency::YEN->value => '¥ '.number_format($amount),
            Currency::NRP->value => '₨  '.number_format($amount),
            Currency::USD->value => '$ '.number_format($amount),
            default => number_format($amount),
        };
    }
}
