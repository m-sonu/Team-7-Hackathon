<?php

if (! function_exists('format_currency')) {
    function format_currency($amount, $currency = 'NRP'): string
    {
        return match (strtoupper($currency)) {
            'JPY' => '¥ '.number_format($amount),
            'NRP' => 'रु '.number_format($amount),
            default => number_format($amount),
        };
    }
}
