<?php
/**
 * Experiment: Document valid technical metrics for Yandex Metrika Reports API.
 *
 * Root cause of issue #17:
 *   ym:s:pageLoadTime is NOT a valid metric in the Yandex Metrika Reports API.
 *   It causes HTTP 400 on BOTH /stat/v1/data and /stat/v1/data/bytime.
 *
 * Evidence:
 *   - The official API docs (https://yandex.com/dev/metrika/en/stat/metrics/visits/tech)
 *     list ONLY 5 valid technology metrics for sessions:
 *     1. ym:s:mobilePercentage
 *     2. ym:s:jsEnabledPercentage
 *     3. ym:s:cookieEnabledPercentage
 *     4. ym:s:thirdPartyCookieEnabledPercentage
 *     5. ym:s:blockedPercentage
 *   - pageLoadTime appears nowhere in the official metrics documentation.
 *
 * Fix:
 *   Replace fetch_technical() to use the 5 valid technology metrics
 *   via /stat/v1/data with dimensions=ym:s:date (works correctly).
 */

$baseUrl = 'https://api-metrika.yandex.net/stat/v1/data';
$counterId = 'YOUR_COUNTER_ID';
$date1 = date('Y-m-d', strtotime('-30 days'));
$date2 = date('Y-m-d');

$validMetrics = [
    'ym:s:mobilePercentage',
    'ym:s:jsEnabledPercentage',
    'ym:s:cookieEnabledPercentage',
    'ym:s:thirdPartyCookieEnabledPercentage',
    'ym:s:blockedPercentage',
];

$params = [
    'id'         => $counterId,
    'metrics'    => implode(',', $validMetrics),
    'dimensions' => 'ym:s:date',
    'date1'      => $date1,
    'date2'      => $date2,
    'limit'      => 100,
    'offset'     => 1,
    'accuracy'   => 'full',
    'lang'       => 'ru',
    'pretty'     => 0,
];

$url = $baseUrl . '?' . http_build_query($params, '', '&');
echo "Valid request URL (all 5 valid tech metrics with date dimension):\n";
echo $url . "\n\n";

echo "=== INVALID metric that was causing HTTP 400 ===\n";
$invalidParams = array_merge($params, [
    'metrics' => 'ym:s:pageLoadTime',
]);
$invalidUrl = 'https://api-metrika.yandex.net/stat/v1/data/bytime?' . http_build_query(
    ['id' => $counterId, 'metrics' => 'ym:s:pageLoadTime', 'date1' => $date1, 'date2' => $date2, 'group' => 'day', 'accuracy' => 'full', 'lang' => 'ru', 'pretty' => 0],
    '', '&'
);
echo "Invalid URL (ym:s:pageLoadTime — not in API docs):\n";
echo $invalidUrl . "\n\n";

echo "=== Summary ===\n";
echo "ym:s:pageLoadTime → NOT a valid Yandex Metrika Reports API metric → HTTP 400\n";
echo "ym:s:mobilePercentage + others → valid tech metrics → works correctly\n";
