<?php
/**
 * Experiment: Test what metrics are valid for /stat/v1/data/bytime
 * 
 * Based on Yandex Metrika API documentation research:
 * - ym:s:pageLoadTime is mentioned in older Yandex docs
 * - ym:s:avgPageLoadTime may be the correct name  
 * - The /bytime endpoint still gives 400 for this metric
 * 
 * This script builds URLs to show exactly what's being requested
 * and what alternatives exist.
 */

$baseUrl = 'https://api-metrika.yandex.net/stat/v1/data/bytime';
$counterId = 'YOUR_COUNTER_ID';
$date1 = date('Y-m-d', strtotime('-30 days'));
$date2 = date('Y-m-d');

$candidates = [
    'ym:s:pageLoadTime',
    'ym:s:avgPageLoadTime',
    'ym:s:pageLoad',
    'ym:s:avgLoadTime',
];

echo "Testing candidate metric names for page load time:\n\n";

foreach ($candidates as $metric) {
    $params = [
        'id'       => $counterId,
        'metrics'  => $metric,
        'date1'    => $date1,
        'date2'    => $date2,
        'group'    => 'day',
        'accuracy' => 'full',
        'lang'     => 'ru',
        'pretty'   => 0,
    ];
    $url = $baseUrl . '?' . http_build_query($params, '', '&');
    echo "Metric: $metric\n";
    echo "URL: $url\n\n";
}

echo "=== Key finding from issue #17 ===\n";
echo "The error shows ym:s:pageLoadTime returns HTTP 400 on /bytime\n";
echo "This means the metric name itself may be invalid, OR\n";
echo "the counter doesn't have page timing data enabled.\n\n";

echo "=== Yandex Metrika official metric names (from docs) ===\n";
echo "According to https://yandex.ru/dev/metrika/doc/api2/stat/fields/visits.html:\n";
echo "- ym:s:pageviews — page views\n";
echo "- ym:s:visits — visits\n";
echo "- ym:s:users — unique visitors\n";
echo "- ym:s:pageDepth — pages per visit (avg)\n";
echo "- ym:s:avgVisitDurationSeconds — avg visit duration\n";
echo "- ym:s:bounceRate — bounce rate\n";
echo "- ym:s:pageLoadTime — avg page load time (but may not work with bytime)\n\n";

echo "=== Alternative approach ===\n";
echo "If ym:s:pageLoadTime is not supported on /bytime, we can:\n";
echo "1. Use /stat/v1/data without date dimension (gets total avg, not daily)\n";
echo "2. Skip this metric with a warning\n";
echo "3. Use a different metric that IS supported\n";
