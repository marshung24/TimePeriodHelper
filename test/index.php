<?php
include_once '../vendor/autoload.php';

use marsapp\helper\timeperiod\TimePeriodHelper;
use marsapp\dev\tools\DevTools;
use marsapp\helper\test\timeperiod\Test;

echo '<pre>';

// Test Sort
Test::testSort();

// Test Union
Test::testUnion();

// Test Diff
Test::testDiff();

// Test Intersect
Test::testIntersect();

// Test IsOverlap
Test::testIsOverlap();

// Test Contact
Test::testContact();

// Test GreaterThan
Test::testGreaterThan();

// Test LessThan
Test::testLessThan();

// Test Fill
Test::testFill();

// Test Gap
Test::testGap();

// Test Time
Test::testTime();

// Test Cut
Test::testCut();

// Test Extend
Test::testExtend();

// Test Shorten
Test::testShorten();

// Test Format
Test::testFormat();

// Test Validate
Test::testValidate();

// Test Filter
Test::testFilter();







