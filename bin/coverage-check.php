<?php
$requiredLineCoverage = 60.0;

chdir(dirname(__FILE__));

require __DIR__ . '/../vendor/autoload.php';

exec('./coverage');
$coverage = require_once('../.tmp/code-coverage_php');
$report = $coverage->getReport();
$percentage = round(($report->getNumExecutedLines() / $report->getNumExecutableLines()) * 100, 2);

print "Line Coverage: " . $percentage;
if ($percentage < $requiredLineCoverage) {
    print "\nTOO LOW: should be >= " . $requiredLineCoverage . "%\n";
    exit(1);
} else {
    print "\nOK\n";
    exit(0);
}
