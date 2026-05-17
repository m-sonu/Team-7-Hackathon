<?php
use App\Enums\Currency;


if (! function_exists('format_currency')) {
    function format_currency($amount, $currency = 'YEN'): string
    {
        return match (strtoupper($currency)) {
            Currency::YEN->value => '¥ '.number_format($amount),
            Currency::NRP->value => '₨  '.number_format($amount),
            Currency::USD->value => '$ '.number_format($amount),
            default => number_format($amount),
        };
    }
}

if (! function_exists('pagination_response')) {
    function pagination_response($data)
    {
      return [
        'current_page' => $data->currentPage(),
        'last_page' => $data->lastPage(),
        'total' => $data->total(),
         'next' => $data->nextPageUrl(),
        'prev' => $data->previousPageUrl(),
      ];
    }
}
