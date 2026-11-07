<?php

namespace App\Support;

/**
 * Baseline statistical forecasting.
 *
 * This is the SWAP POINT for a future ML/data-science backend. Today it uses a
 * transparent linear least-squares trend. Later, linear() (and a risk model) can
 * delegate to a Python FastAPI microservice without changing any calling code.
 */
class ForecastService
{
    /**
     * Forecast the next $periods values from a historical series using
     * ordinary least-squares linear regression. Returns rounded, non-negative ints.
     *
     * @param  array<int|float>  $series
     * @return array<int>
     */
    public static function linear(array $series, int $periods = 3): array
    {
        $series = array_values($series);
        $n = count($series);
        if ($n < 2) {
            $last = $n === 1 ? (float) $series[0] : 0.0;
            return array_fill(0, $periods, (int) round(max(0, $last)));
        }

        $sumX = $sumY = $sumXY = $sumXX = 0.0;
        foreach ($series as $i => $y) {
            $sumX += $i;
            $sumY += $y;
            $sumXY += $i * $y;
            $sumXX += $i * $i;
        }

        $denom = ($n * $sumXX) - ($sumX * $sumX);
        $slope = $denom != 0.0 ? (($n * $sumXY) - ($sumX * $sumY)) / $denom : 0.0;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $out = [];
        for ($k = 1; $k <= $periods; $k++) {
            $x = $n - 1 + $k;
            $out[] = (int) round(max(0, $intercept + ($slope * $x)));
        }
        return $out;
    }

    /** Direction of the trend for a short human label. */
    public static function trendLabel(array $series): string
    {
        $series = array_values($series);
        $n = count($series);
        if ($n < 2) return 'stable';
        $first = array_slice($series, 0, (int) ceil($n / 2));
        $second = array_slice($series, (int) floor($n / 2));
        $a = array_sum($first) / max(1, count($first));
        $b = array_sum($second) / max(1, count($second));
        if ($b > $a * 1.1) return 'rising';
        if ($b < $a * 0.9) return 'declining';
        return 'stable';
    }
}
