<?php

if (!function_exists('number_format_indian')) {
    function number_format_indian($number, $decimals = 0)
    {
        $number = number_format($number, $decimals, '.', ''); // Normalize to string with fixed decimals

        [$integer, $decimal] = explode('.', $number . '.'); // Ensure there's always a decimal part

        $last3 = substr($integer, -3);
        $restUnits = substr($integer, 0, -3);

        if ($restUnits != '') {
            $restUnits = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $restUnits);
            $formatted = $restUnits . "," . $last3;
        } else {
            $formatted = $last3;
        }

        if ($decimals > 0) {
            $formatted .= "." . substr($decimal . str_repeat('0', $decimals), 0, $decimals);
        }

        return $formatted;
    }
}
