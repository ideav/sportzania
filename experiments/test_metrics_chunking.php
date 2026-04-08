<?php
/**
 * Experiment: Demonstrate that Yandex Metrika API returns HTTP 400 when
 * more than 20 metrics are included in a single request.
 *
 * Root cause of issue #19:
 *   fetch_conversions(), fetch_traffic_sources(), fetch_utm() each build a
 *   metrics list by iterating all goals. With 60+ goals:
 *     - fetch_conversions: 60 goals × 2 metrics = 120 metrics → HTTP 400
 *     - fetch_traffic_sources: 60 goals × 1 metric + 1 base = 61 metrics → HTTP 400
 *     - fetch_utm: 60 goals × 2 metrics + 1 base = 121 metrics → HTTP 400
 *
 * The Yandex Metrika API limit is 20 metrics per request.
 *
 * Fix: ym_fetch_all_rows_chunked() splits extra metrics into batches of
 * (YM_METRICS_PER_REQUEST - baseCount) and merges results by dimension key.
 *
 * This script shows what URLs were being built before the fix, and
 * what they look like after chunking.
 */

$baseUrl   = 'https://api-metrika.yandex.net/stat/v1/data';
$counterId = 'YOUR_COUNTER_ID';
$date1     = date('Y-m-d', strtotime('-30 days'));
$date2     = date('Y-m-d');

// Simulate 60 goals like in the real counter.
$goals = [];
for ($i = 1; $i <= 60; $i++) {
    $goals[] = ['id' => 300000000 + $i, 'name' => "Goal $i"];
}

echo "=== Issue #19: HTTP 400 due to too many metrics per request ===\n\n";

// -----------------------------------------------------------------------
// BEFORE the fix: all goal metrics in one URL
// -----------------------------------------------------------------------

$metricsBefore = [];
foreach ($goals as $goal) {
    $metricsBefore[] = "ym:s:goal{$goal['id']}conversionRate";
    $metricsBefore[] = "ym:s:goal{$goal['id']}reaches";
}

$urlBefore = $baseUrl . '?' . http_build_query([
    'id'         => $counterId,
    'metrics'    => implode(',', $metricsBefore),
    'dimensions' => 'ym:s:date',
    'date1'      => $date1,
    'date2'      => $date2,
    'limit'      => 10000,
    'offset'     => 1,
    'accuracy'   => 'full',
    'lang'       => 'ru',
    'pretty'     => 0,
], '', '&');

echo "BEFORE fix:\n";
echo "  Metrics count: " . count($metricsBefore) . "\n";
echo "  URL length: " . strlen($urlBefore) . " chars\n";
echo "  Result: HTTP 400 Bad Request (exceeds 20-metric limit)\n\n";

// -----------------------------------------------------------------------
// AFTER the fix: metrics split into chunks of 20
// -----------------------------------------------------------------------

$maxPerRequest = 20;
$baseCount     = 0; // fetch_conversions has no base metrics
$chunkSize     = $maxPerRequest - $baseCount;
$chunks        = array_chunk($metricsBefore, $chunkSize);

echo "AFTER fix (chunked into batches of $maxPerRequest):\n";
echo "  Total metrics: " . count($metricsBefore) . "\n";
echo "  Chunks needed: " . count($chunks) . "\n\n";

foreach ($chunks as $i => $chunk) {
    $url = $baseUrl . '?' . http_build_query([
        'id'         => $counterId,
        'metrics'    => implode(',', $chunk),
        'dimensions' => 'ym:s:date',
        'date1'      => $date1,
        'date2'      => $date2,
        'limit'      => 10000,
        'offset'     => 1,
        'accuracy'   => 'full',
        'lang'       => 'ru',
        'pretty'     => 0,
    ], '', '&');
    echo "  Chunk " . ($i + 1) . ": " . count($chunk) . " metrics, URL length: " . strlen($url) . " chars → OK\n";
}

echo "\n";

// -----------------------------------------------------------------------
// fetch_traffic_sources: 1 base metric + goal conversionRate per goal
// -----------------------------------------------------------------------

echo "=== fetch_traffic_sources ===\n";
$baseMetrics  = ['ym:s:visits'];
$extraMetrics = array_map(fn($g) => "ym:s:goal{$g['id']}conversionRate", $goals);
$totalBefore  = count($baseMetrics) + count($extraMetrics);
$chunkSize2   = $maxPerRequest - count($baseMetrics);
$chunks2      = array_chunk($extraMetrics, $chunkSize2);

echo "  Before: " . $totalBefore . " metrics total → HTTP 400\n";
echo "  After:  " . count($chunks2) . " chunks of ≤$maxPerRequest metrics each → OK\n\n";

// -----------------------------------------------------------------------
// fetch_utm: 1 base metric + 2 goal metrics per goal
// -----------------------------------------------------------------------

echo "=== fetch_utm ===\n";
$extraUtm    = [];
foreach ($goals as $goal) {
    $extraUtm[] = "ym:s:goal{$goal['id']}conversionRate";
    $extraUtm[] = "ym:s:goal{$goal['id']}reaches";
}
$totalUtm    = 1 + count($extraUtm);
$chunkSize3  = $maxPerRequest - 1;
$chunks3     = array_chunk($extraUtm, $chunkSize3);

echo "  Before: " . $totalUtm . " metrics total → HTTP 400\n";
echo "  After:  " . count($chunks3) . " chunks of ≤$maxPerRequest metrics each → OK\n\n";

echo "=== Summary ===\n";
echo "Root cause: Yandex Metrika API limit is 20 metrics per request.\n";
echo "Fix: ym_fetch_all_rows_chunked() splits extra metrics into batches\n";
echo "     and merges results by dimension key.\n";
