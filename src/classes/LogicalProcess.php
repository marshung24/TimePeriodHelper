<?php

namespace marsapp\helper\timeperiod\classes;

use marsapp\helper\timeperiod\classes\Base;
use marsapp\helper\timeperiod\classes\DataProcess;

/**
 * Logical Process for Time Period Helper
 * 
 * @author Mars Hung <tfaredxj@gmail.com>
 * @see https://github.com/marshung24/TimePeriodHelper
 */
class LogicalProcess extends Base
{

    /**
     * ************************************************
     * ************** Operation Function **************
     * ************************************************
     */

    /**
     * Union one or more time periods
     * 
     * 1. Sort and merge one or more time periods with contacts
     * 2. TimePeriodHelper::union($timePeriods1, $timePeriods2, $timePeriods3, ......);
     * 
     * @param array $timePeriods
     * @return array
     */
    public static function union()
    {
        $opt = [];

        // Combine and sort
        $merge = call_user_func_array('array_merge', func_get_args());
        $merge = DataProcess::sort($merge);

        if (empty($merge)) {
            return $opt;
        }

        $tmp = array_shift($merge);
        foreach ($merge as $k => $tp) {
            if ($tp[0] > $tmp[1]) {
                // Got it, and set next.
                $opt[] = $tmp;
                $tmp = $tp;
            } elseif ($tp[1] > $tmp[1]) {
                // Extend end time
                $tmp[1] = $tp[1];
            }
        }
        $opt[] = $tmp;

        return $opt;
    }

    /**
     * Computes the difference of time periods
     * 
     * 1. Compares $timePeriods1 against $timePeriods2 and returns the values in $timePeriods1 that are not present in $timePeriods2.
     * 2. e.g. TimePeriodHelper::diff($timePeriods1, $timePeriods2);
     * 3. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 
     * @param array $timePeriods1
     * @param array $timePeriods2
     * @param bool|string $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function diff(array $timePeriods1, array $timePeriods2, $sortOut = 'default')
    {
        /*** Arguments prepare ***/
        // Subject or pattern is empty, do nothing
        if (empty($timePeriods1) || empty($timePeriods2)) {
            return $timePeriods1;
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods1, $timePeriods2);

        $opt = [];
        foreach ($timePeriods1 as $k1 => $ori) {
            foreach ($timePeriods2 as $ko => $sub) {
                if ($sub[1] <= $ori[0]) {
                    // No overlap && Passed: --sub0--sub1--ori0--ori1--
                    unset($timePeriods2[$ko]);
                    continue;
                } elseif ($ori[1] <= $sub[0]) {
                    // No overlap: --ori0--ori1--sub0--sub1--
                    continue;
                } elseif ($sub[0] <= $ori[0] && $ori[1] <= $sub[1]) {
                    // Subtract all: --sub0--ori0--ori1--sub1--
                    $ori = [];
                    break;
                } elseif ($ori[0] < $sub[0] && $sub[1] < $ori[1]) {
                    // Delete internal: --ori0--sub0--sub1--ori1--
                    $opt[] = [$ori[0], $sub[0]];
                    $ori = [$sub[1], $ori[1]];
                    //} elseif ($sub[0] <= $ori[0] && $sub[1] <= $ori[1]) { // Complete condition
                } elseif ($sub[0] <= $ori[0]) { // Equivalent condition
                    // Delete overlap: --sub0--ori0--sub1--ori1--
                    $ori = [$sub[1], $ori[1]];
                    //} elseif ($ori[0] <= $sub[0] && $ori[1] <= $sub[1]) { // Complete condition
                    //} elseif ($ori[1] <= $sub[1]) { // Equivalent condition
                } else { // Equivalent condition
                    // Delete overlap: --ori0--sub0--ori1--sub1--
                    $ori = [$ori[0], $sub[0]];
                }
            }

            // All No overlap
            if (!empty($ori)) {
                $opt[] = $ori;
            }
        }

        return $opt;
    }

    /**
     * Computes the intersection of time periods
     * 
     * 1. e.g. TimePeriodHelper::intersect($timePeriods1, $timePeriods2);
     * 2. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 
     * @param array $timePeriods1
     * @param array $timePeriods2
     * @param bool|string $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function intersect(array $timePeriods1, array $timePeriods2, $sortOut = 'default')
    {
        // Subject or pattern is empty, do nothing
        if (empty($timePeriods1) || empty($timePeriods2)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods1, $timePeriods2);

        $opt = [];
        foreach ($timePeriods1 as $k1 => $ori) {
            foreach ($timePeriods2 as $ko => $sub) {
                if ($sub[1] <= $ori[0]) {
                    // No overlap && Passed: --sub0--sub1--ori0--ori1--
                    unset($timePeriods2[$ko]);
                    continue;
                } elseif ($ori[1] <= $sub[0]) {
                    // No overlap: --ori0--ori1--sub0--sub1--
                    continue;
                } elseif ($sub[0] <= $ori[0] && $ori[1] <= $sub[1]) {
                    // Subtract all: --sub0--ori0--ori1--sub1--
                    $opt[] = [$ori[0], $ori[1]];
                    break;
                } elseif ($ori[0] < $sub[0] && $sub[1] < $ori[1]) {
                    // Delete internal: --ori0--sub0--sub1--ori1--
                    $opt[] = [$sub[0], $sub[1]];
                    $ori = [$sub[1], $ori[1]];
                    //} elseif ($sub[0] <= $ori[0] && $sub[1] <= $ori[1]) { // Complete condition
                } elseif ($sub[0] <= $ori[0]) { // Equivalent condition
                    // Delete overlap: --sub0--ori0--sub1--ori1--
                    $opt[] = [$ori[0], $sub[1]];
                    $ori = [$sub[1], $ori[1]];
                    //} elseif ($ori[0] <= $sub[0] && $ori[1] <= $sub[1]) { // Complete condition
                    //} elseif ($ori[1] <= $sub[1]) { // Equivalent condition
                } else { // Equivalent condition
                    // Delete overlap: --ori0--sub0--ori1--sub1--
                    $opt[] = [$sub[0], $ori[1]];
                    break;
                }
            }
        }

        return $opt;
    }

    /**
     * Time period is overlap
     * 
     * 1. Determine if there is overlap between the two time periods
     * 2. Only when there is no intersection, no data is needed.
     * 3. Logic is similar to intersect.
     *  
     * @param array $timePeriods1
     * @param array $timePeriods2
     * @return bool
     */
    public static function isOverlap(array $timePeriods1, array $timePeriods2)
    {
        // Subject or pattern is empty, do nothing ($timePeriods1 is first loop, not need check)
        if (empty($timePeriods2)) {
            return false;
        }

        foreach ($timePeriods1 as $k1 => $ori) {
            foreach ($timePeriods2 as $ko => $sub) {
                if ($sub[1] <= $ori[0]) {
                    // No overlap && Passed: --sub0--sub1--ori0--ori1--
                    unset($timePeriods2[$ko]);
                    continue;
                } elseif ($ori[1] <= $sub[0]) {
                    // No overlap: --ori0--ori1--sub0--sub1--
                    continue;
                }
                // --sub0--ori0--ori1--sub1--
                // --ori0--sub0--sub1--ori1--
                // --sub0--ori0--sub1--ori1--
                // --ori0--sub0--ori1--sub1--
                return true;
            }
        }

        return false;
    }

    /**
     * The time period is in contact with the specified time (time period)
     * 
     * @param array $timePeriods
     * @param string $sDateTime
     * @param string $eDateTime
     * @param bool|string $sortOut
     * @return array
     */
    public static function contact(array $timePeriods, $sDateTime, $eDateTime = null, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);
        // Set $eDateTime
        $eDateTime = $eDateTime ?: $sDateTime;
        $sTime = min($sDateTime, $eDateTime);
        $eTime = max($sDateTime, $eDateTime);

        // Strip: No overlap && Passed: --$sTime--$eTime--$tp0--$tp1--
        $timePeriods = array_filter($timePeriods, function ($tp) use ($sTime, $eTime) {
            return $eTime <= $tp[0] && $sTime < $tp[0] ? false : true;
        });

        // Strip: No overlap: --$tp0--$tp1--$sTime--$eTime--
        $timePeriods = array_filter($timePeriods, function ($tp) use ($sTime) {
            return $tp[1] <= $sTime ? false : true;
        });

        return array_values($timePeriods);
    }

    /**
     * Time period greater than the specified time
     * 
     * @param array $timePeriods
     * @param string $refDatetime Specified time to compare against
     * @param bool $fullTimePeriod Get only the full time period
     * @param bool|string $sortOut
     * @return array
     */
    public static function greaterThan(array $timePeriods, $refDatetime, $fullTimePeriod = true, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);

        // Get Contact time periods
        $opt = [];
        foreach ($timePeriods as $k => $tp) {
            if ($fullTimePeriod) {
                // Time period is intact
                if ($tp[0] >= $refDatetime) {
                    $opt[] = $tp;
                }
            } else {
                // Time period not intact
                if ($tp[1] > $refDatetime) {
                    $opt[] = $tp;
                }
            }
        }

        return $opt;
    }

    /**
     * Time period less than the specified time
     * 
     * @param array $timePeriods
     * @param string $refDatetime Specified time to compare against
     * @param bool $fullTimePeriod Get only the full time period
     * @param bool|string $sortOut
     * @return array
     */
    public static function lessThan(array $timePeriods, $refDatetime, $fullTimePeriod = true, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);

        // Get Contact time periods
        $opt = [];
        foreach ($timePeriods as $k => $tp) {
            if ($fullTimePeriod) {
                // Time period is intact
                if ($tp[1] <= $refDatetime) {
                    $opt[] = $tp;
                }
            } else {
                // Time period not intact
                if ($tp[0] < $refDatetime) {
                    $opt[] = $tp;
                }
            }
        }

        return $opt;
    }

    /**
     * ********************************************
     * ************** Tools Function **************
     * ********************************************
     */

    /**
     * Data sorting out
     *
     * @param bool|string $sortOut
     * @param array $timePeriods1
     * @param array|null $timePeriods2
     * @return void
     */
    public static function dataSortOut(&$sortOut, &$timePeriods1, &$timePeriods2 = null)
    {
        // Data sorting out
        $sortOut = $sortOut === 'default' ? self::getSortOut() : !!$sortOut;
        if ($sortOut) {
            $timePeriods1 = self::union($timePeriods1);
            if (!is_null($timePeriods2)) {
                $timePeriods2 = self::union($timePeriods2);
            }
        }
    }
}
