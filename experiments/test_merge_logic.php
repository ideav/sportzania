<?php
/**
 * Test: verify the merge logic for fetch_traffic_engagement fix.
 *
 * Simulates the two API responses (count metrics with ym:s:date dimension
 * and calculated metrics with group=day) and verifies that merging by date
 * produces the correct combined CSV rows.
 */

// Simulate ym_dim_value behavior
function ym_dim_value(array $dimValues, int $index): string
{
    $val = $dimValues[$index] ?? [];
    return $val['name'] ?? $val['id'] ?? '';
}

function ym_format_value($value): string
{
    if (is_float($value)) {
        return number_format($value, 4, ',', '');
    }
    return (string) $value;
}

// Simulated response from Request A: count metrics with dimensions=ym:s:date
$countRows = [
    ['dimensions' => [['name' => '2026-03-08']], 'metrics' => [100, 80, 200, 20]],
    ['dimensions' => [['name' => '2026-03-09']], 'metrics' => [120, 90, 240, 25]],
    ['dimensions' => [['name' => '2026-03-10']], 'metrics' => [90,  70, 180, 15]],
];

// Simulated response from Request B: calculated metrics with group=day
// (API returns these with date in dimensions[0] when group=day is used)
$calcRows = [
    ['dimensions' => [['name' => '2026-03-08']], 'metrics' => [3.5, 120.0, 25.5]],
    ['dimensions' => [['name' => '2026-03-09']], 'metrics' => [4.2, 150.0, 22.1]],
    ['dimensions' => [['name' => '2026-03-10']], 'metrics' => [2.8, 90.0,  30.0]],
];

// Index calculated metrics by date
$calcByDate = [];
foreach ($calcRows as $row) {
    $date = $row['dimensions'][0]['name'] ?? $row['dimensions'][0]['id'] ?? '';
    $calcByDate[$date] = $row['metrics'];
}

// Merge
$csvRows = [];
foreach ($countRows as $row) {
    $date = ym_dim_value($row['dimensions'], 0);
    $calc = $calcByDate[$date] ?? [null, null, null];
    $csvRows[] = [
        $date,
        ym_format_value($row['metrics'][0] ?? ''),
        ym_format_value($row['metrics'][1] ?? ''),
        ym_format_value($row['metrics'][2] ?? ''),
        ym_format_value($row['metrics'][3] ?? ''),
        ym_format_value($calc[0] ?? ''),
        ym_format_value($calc[1] ?? ''),
        ym_format_value($calc[2] ?? ''),
    ];
}

// Assertions
$passed = 0;
$failed = 0;

function assert_eq($label, $actual, $expected) {
    global $passed, $failed;
    if ($actual === $expected) {
        echo "  PASS: $label\n";
        $passed++;
    } else {
        echo "  FAIL: $label\n  Expected: " . var_export($expected, true) . "\n  Got:      " . var_export($actual, true) . "\n";
        $failed++;
    }
}

echo "=== Test: merge logic ===\n\n";

assert_eq("3 rows produced",             count($csvRows), 3);
assert_eq("row[0][0] date",              $csvRows[0][0], '2026-03-08');
assert_eq("row[0][1] visits",            $csvRows[0][1], '100');
assert_eq("row[0][2] users",             $csvRows[0][2], '80');
assert_eq("row[0][3] pageviews",         $csvRows[0][3], '200');
assert_eq("row[0][4] newUsers",          $csvRows[0][4], '20');
assert_eq("row[0][5] pageDepth",         $csvRows[0][5], '3,5000');
assert_eq("row[0][6] visitDuration",     $csvRows[0][6], '120,0000');
assert_eq("row[0][7] bounceRate",        $csvRows[0][7], '25,5000');

assert_eq("row[1][0] date",              $csvRows[1][0], '2026-03-09');
assert_eq("row[1][5] pageDepth",         $csvRows[1][5], '4,2000');

assert_eq("row[2][0] date",              $csvRows[2][0], '2026-03-10');
assert_eq("row[2][7] bounceRate",        $csvRows[2][7], '30,0000');

echo "\n--- Results: $passed passed, $failed failed ---\n";
exit($failed > 0 ? 1 : 0);
