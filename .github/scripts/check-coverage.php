<?php

/**
 * Reads a Clover coverage report, annotates every uncovered line for GitHub
 * Actions, prints a per-file summary and fails below the required line coverage.
 *
 * Usage: php check-coverage.php <clover.xml> <minimum percentage>
 */

[$script, $file, $minimum] = $argv + [null, 'clover.xml', '100'];

$xml = simplexml_load_file($file);
if ($xml === false) {
    fwrite(STDERR, "Could not read coverage report $file\n");
    exit(1);
}

$root = getcwd() . '/';
$totalStatements = 0;
$totalCovered = 0;

foreach ($xml->xpath('//file') as $fileNode) {
    $path = str_replace($root, '', (string) $fileNode['name']);
    $metrics = $fileNode->metrics;
    $statements = (int) $metrics['statements'];
    $covered = (int) $metrics['coveredstatements'];
    $totalStatements += $statements;
    $totalCovered += $covered;

    printf("%6.2f%%  %3d/%-3d  %s\n", $statements ? 100 * $covered / $statements : 100, $covered, $statements, $path);

    foreach ($fileNode->line as $line) {
        if ((string) $line['type'] === 'stmt' && (int) $line['count'] === 0) {
            printf(
                "::warning file=%s,line=%d,title=Not covered::This line is not covered by any test.\n",
                $path,
                (int) $line['num']
            );
        }
    }
}

$percentage = $totalStatements ? 100 * $totalCovered / $totalStatements : 100;
printf("\nLine coverage: %.2f%% (%d/%d), required: %s%%\n", $percentage, $totalCovered, $totalStatements, $minimum);

if ($percentage + 1e-9 < (float) $minimum) {
    printf(
        "::error title=Coverage::Line coverage is %.2f%%, below the required %s%%.\n",
        $percentage,
        $minimum
    );
    exit(1);
}

printf(
    "::notice title=Coverage::Line coverage is %.2f%% (%d/%d lines).\n",
    $percentage,
    $totalCovered,
    $totalStatements
);
