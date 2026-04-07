<?php
/**
 * Experiment: demonstrate the difference between failing and successful API calls.
 *
 * This script simulates the URL building to show that the params differ
 * between the failing and successful fetch functions.
 *
 * The failing functions use calculated metrics (bounceRate, pageDepth, visitDuration,
 * pageLoadTime) combined with dimensions=ym:s:date.
 *
 * The hypothesis: Yandex Metrika Stat API returns HTTP 400 when calculated/ratio
 * metrics are requested together with dimensions. These metrics only work without
 * dimensions (using group=day instead).
 */

function build_url(array $params): string
{
    $queryString = http_build_query($params, '', '&');
    return 'https://api-metrika.yandex.net/stat/v1/data?' . $queryString;
}

// --- Failing: fetch_traffic_engagement ---
$params_failing_engagement = [
    'id'         => '70071025',
    'metrics'    => 'ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:newUsers,ym:s:pageDepth,ym:s:visitDuration,ym:s:bounceRate',
    'date1'      => '2026-03-08',
    'date2'      => '2026-04-07',
    'limit'      => 10000,
    'offset'     => 1,
    'accuracy'   => 'full',
    'lang'       => 'ru',
    'dimensions' => 'ym:s:date',
    'pretty'     => 0,
];

echo "=== FAILING: fetch_traffic_engagement ===\n";
echo "URL: " . build_url($params_failing_engagement) . "\n\n";

// --- Failing: fetch_technical ---
$params_failing_technical = [
    'id'         => '70071025',
    'metrics'    => 'ym:s:pageLoadTime',
    'date1'      => '2026-03-08',
    'date2'      => '2026-04-07',
    'limit'      => 10000,
    'offset'     => 1,
    'accuracy'   => 'full',
    'lang'       => 'ru',
    'dimensions' => 'ym:s:date',
    'pretty'     => 0,
];

echo "=== FAILING: fetch_technical ===\n";
echo "URL: " . build_url($params_failing_technical) . "\n\n";

// --- Successful: fetch_traffic_sources ---
$params_success_sources = [
    'id'         => '70071025',
    'metrics'    => 'ym:s:visits',
    'date1'      => '2026-03-08',
    'date2'      => '2026-04-07',
    'limit'      => 10000,
    'offset'     => 1,
    'accuracy'   => 'full',
    'lang'       => 'ru',
    'dimensions' => 'ym:s:date,ym:s:trafficSource',
    'pretty'     => 0,
];

echo "=== SUCCESS: fetch_traffic_sources ===\n";
echo "URL: " . build_url($params_success_sources) . "\n\n";

echo "=== Key Observation ===\n";
echo "Failing requests: calculated metrics (bounceRate, pageDepth, visitDuration, pageLoadTime)\n";
echo "  + single dimension 'ym:s:date'\n";
echo "Successful requests: simple count metrics (visits, users)\n";
echo "  + multiple dimensions including 'ym:s:date'\n\n";

echo "=== Proposed fix ===\n";
echo "For calculated metrics that don't support dimensions:\n";
echo "  Use group=day WITHOUT dimensions parameter\n";
echo "  This gives daily aggregation without dimensional breakdown\n\n";

// --- Proposed fix URL for fetch_traffic_engagement ---
// Split into two requests:
// 1. Simple counts with date dimension
$params_fix_counts = [
    'id'         => '70071025',
    'metrics'    => 'ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:newUsers',
    'date1'      => '2026-03-08',
    'date2'      => '2026-04-07',
    'limit'      => 10000,
    'offset'     => 1,
    'accuracy'   => 'full',
    'lang'       => 'ru',
    'dimensions' => 'ym:s:date',
    'pretty'     => 0,
];

// 2. Calculated metrics with group=day (no dimensions)
$params_fix_calculated = [
    'id'       => '70071025',
    'metrics'  => 'ym:s:pageDepth,ym:s:visitDuration,ym:s:bounceRate',
    'date1'    => '2026-03-08',
    'date2'    => '2026-04-07',
    'limit'    => 10000,
    'offset'   => 1,
    'accuracy' => 'full',
    'lang'     => 'ru',
    'group'    => 'day',
    'pretty'   => 0,
];

echo "=== FIX: Request 1 (simple counts with ym:s:date dimension) ===\n";
echo "URL: " . build_url($params_fix_counts) . "\n\n";

echo "=== FIX: Request 2 (calculated metrics with group=day, no dimensions) ===\n";
echo "URL: " . build_url($params_fix_calculated) . "\n\n";
