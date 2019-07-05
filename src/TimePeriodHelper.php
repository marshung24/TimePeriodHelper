<?php

namespace marsapp\helper\timeperiod;

/**
 * Time Period Helper
 * 
 * Note:
 * 1. Format: $timePeriods = [[$startDatetime1, $endDatetime1], [$startDatetime2, $endDatetime2], ...];
 * - $Datetime = Y-m-d H:i:s ; Y-m-d H:i:00 ; Y-m-d H:00:00 ;
 * 2. If it is hour/minute/second, the end point is usually not included, for example, 8 o'clock to 9 o'clock is 1 hour.
 * - ●=====○
 * 3. If it is a day/month/year, it usually includes an end point, for example, January to March is 3 months.
 * - ●=====●
 * 4. When processing, assume that the $timePeriods format is correct. If necessary, you need to call the verification function to verify the data.
 * 5. Ensure performance by keeping the $timePeriods format correct:
 * - a. When getting the raw $timePeriods, sort out it by format(), filter(), union().
 * - b. Handle $timePeriods using only the functions provided by TimePeriodHelper (Will not break the format, sort)
 * - c. When you achieve the two operations described above, you can turn off auto sort out (TimePeriodHelper::setSortOut(false)) to improve performance.
 * 
 * @version 0.5.3
 * @author Mars Hung <tfaredxj@gmail.com>
 * @see https://github.com/marshung24/TimePeriodHelper
 */
class TimePeriodHelper
{
    /**
     * Option set
     * @var array
     */
    protected static $_options = [
        'unit' => [
            // Time calculate unit - hour, minute, second(default)
            'time' => 'second',
            // Format unit - hour, minute, second(default)
            'format' => 'second',
        ],
        'unitMap' => [
            'hour' => 'hour',
            'hours' => 'hour',
            'h' => 'hour',
            'minute' => 'minute',
            'minutes' => 'minute',
            'i' => 'minute',
            'm' => 'minute',
            'second' => 'second',
            'seconds' => 'second',
            's' => 'second',
        ],
        'filter' => [
            'isDatetime' => true
        ],
        // Default sort out $timePeriods
        'sortOut' => true,
    ];


    /**
     * ************************************************
     * ************** Operation Function **************
     * ************************************************
     */

    /**
     * Sort time periods (Order by ASC)
     * 
     * 1. When sorting, sort the start time first, if the start time is the same, then sort the end time
     * 2. Sort Priority: Start Time => End Time
     * 
     * @param array $timePeriods
     * @return array
     */
    public static function sort(array $timePeriods)
    {
        // Closure in PHP 7.0.X loop maybe die
        usort($timePeriods, function ($a, $b) {
            if ($a[0] == $b[0]) {
                // Start time is equal, compare end time
                $r = $a[1] < $b[1] ? -1 : 1;
            } else {
                // Compare Start time
                $r = $a[0] < $b[0] ? -1 : 1;
            }

            return $r;
        });

        return $timePeriods;
    }

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
        $merge = self::sort($merge);

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
     * @param bool $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
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
     * @param bool $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
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
        // Subject or pattern is empty, do nothing
        if (empty($timePeriods1) || empty($timePeriods2)) {
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
                } elseif ($sub[0] <= $ori[0] && $ori[1] <= $sub[1]) {
                    // Subtract all: --sub0--ori0--ori1--sub1--
                    return true;
                } elseif ($ori[0] < $sub[0] && $sub[1] < $ori[1]) {
                    // Delete internal: --ori0--sub0--sub1--ori1--
                    return true;
                    //} elseif ($sub[0] <= $ori[0] && $sub[1] <= $ori[1]) { // Complete condition
                } elseif ($sub[0] <= $ori[0]) { // Equivalent condition
                    // Delete overlap: --sub0--ori0--sub1--ori1--
                    return true;
                    //} elseif ($ori[0] <= $sub[0] && $ori[1] <= $sub[1]) { // Complete condition
                    //} elseif ($ori[1] <= $sub[1]) { // Equivalent condition
                } else { // Equivalent condition
                    // Delete overlap: --ori0--sub0--ori1--sub1--
                    return true;
                }
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
     * @param string $sortOut
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

        // Get Contact time periods
        $opt = [];
        foreach ($timePeriods as $k => $tp) {
            if ($eTime <= $tp[0]) {
                // No overlap && Passed: --$sTime--$eTime--$tp0--$tp1--
                if ($sTime == $tp[0]) {
                    // But 
                    $opt[] = $tp;
                }
            } elseif ($tp[1] <= $sTime) {
                // No overlap: --$tp0--$tp1--$sTime--$eTime--
            } else {
                // Overlap
                $opt[] = $tp;
            }
        }

        return $opt;
    }

    /**
     * Time period greater than the specified time
     * 
     * @param array $timePeriods
     * @param string $refDatetime
     * @param string $intactTime
     * @param string $sortOut
     * @return array
     */
    public static function greaterThan(array $timePeriods, $refDatetime, $intactTime = true, $sortOut = 'default')
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
            if ($intactTime) {
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
     * @param string $refDatetime
     * @param string $intactTime
     * @param string $sortOut
     * @return array
     */
    public static function lessThan(array $timePeriods, $refDatetime, $intactTime = true, $sortOut = 'default')
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
            if ($intactTime) {
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
     * Fill time periods
     * 
     * Leaving only the first start time and the last end time
     * 
     * @param array $timePeriods
     * @return array
     */
    public static function fill(array $timePeriods)
    {
        $opt = [];

        if (isset($timePeriods[0][0])) {
            $tmp = array_shift($timePeriods);
            $start = $tmp[0];
            $end = $tmp[1];
            foreach ($timePeriods as $k => $tp) {
                $start = min($start, $tp[0]);
                $end = max($end, $tp[1]);
            }
            $opt = [[$start, $end]];
        }

        return $opt;
    }

    /**
     * Get gap time periods of multiple sets of time periods
     * 
     * 1. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 
     * @param array $timePeriods
     * @param bool $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function gap(array $timePeriods, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);

        $opt = [];
        foreach ($timePeriods as $k => $tp) {
            if (isset($timePeriods[$k + 1])) {
                $opt[] = [$tp[1], $timePeriods[$k + 1][0]];
            }
        }

        return $opt;
    }

    /**
     * Calculation period total time
     *
     * 1. You can specify the smallest unit (from setUnit())
     * 2. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 3. approximation: chop off
     *
     * @param array $timePeriods            
     * @param int $precision
     *            Optional decimal places for the decimal point
     * @param bool $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return number
     */
    public static function time(array $timePeriods, $precision = 0, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return 0;
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);

        // Calculate time
        $time = 0;
        foreach ($timePeriods as $k => $tp) {
            $time += strtotime($tp[1]) - strtotime($tp[0]);
        }

        // Time unit convert
        switch (self::getUnit('time')) {
            case 'minute':
                $time = $time / 60;
                break;
            case 'hour':
                $time = $time / 3600;
                break;
        }

        // Precision
        if ($precision > 0) {
            $pow = pow(10, (int) $precision);
            $time = ((int) ($time * $pow)) / $pow;
        } else {
            $time = (int) ($time);
        }

        return $time;
    }

    /**
     * Cut the time period of the specified length of time
     *
     * 1. You can specify the smallest unit (from setUnit())
     * 2. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 
     * @param array $timePeriods            
     * @param number $time
     *            Specified length of time
     * @param bool $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function cut(array $timePeriods, $time, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);
        // Convert time by unit
        $time = self::time2Second($time);

        $opt = [];
        $timeLen = 0;
        foreach ($timePeriods as $k => $tp) {
            // Calculation time
            $tlen = strtotime($tp[1]) - strtotime($tp[0]);

            // Judging the length of time
            if ($timeLen + $tlen <= $time) {
                // Within limits
                $opt[] = $tp;
                $timeLen = $timeLen + $tlen;
            } else {
                // Outside the limits
                $lastTime = $time - $timeLen;
                if ($lastTime > 0) {
                    $tps = $tp[0];
                    $tpe = self::extendTime($tps, $lastTime);
                    if ($tps != $tpe) {
                        $opt[] = [$tps, $tpe];
                    }
                }
                break;
            }
        }

        return $opt;
    }

    /**
     * Increase the time period of the specified length of time after the last time period
     *
     * 1. You can specify the smallest unit (from setUnit())
     * 2. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 
     * @param array $timePeriods            
     * @param number $time
     *            Specified length of time (default uint:second)
     * @param number $interval
     *            Interval with existing time period
     * @param bool $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function extend(array $timePeriods, $time, $interval = 0, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);

        // Convert time by unit
        $time = self::time2Second($time);
        $interval = self::time2Second($interval);

        // last time period index
        $eIdx = sizeof($timePeriods) - 1;

        if (!$interval) {
            // No gap, Directly extend the end time
            $timePeriods[$eIdx][1] = self::extendTime($timePeriods[$eIdx][1], $time);
        } else {
            // Has gap
            $tps = self::extendTime($timePeriods[$eIdx][1], $interval);
            $tpe = self::extendTime($tps, $time);
            if ($tps != $tpe) {
                $timePeriods[] = [$tps, $tpe];
            }
        }

        return $timePeriods;
    }

    /**
     * Shorten the specified length of time from behind
     *
     * 1. You can specify the smallest unit (from setUnit())
     * 2. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 
     * @param array $timePeriods            
     * @param number $time
     *            Specified length of time (default uint:second)
     * @param bool $crossperiod
     *            Whether to shorten across time
     * @param bool $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function shorten(array $timePeriods, $time, $crossperiod = true, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        self::dataSortOut($sortOut, $timePeriods);

        // Convert time by unit
        $time = self::time2Second($time);

        // last time period index
        $eIdx = sizeof($timePeriods) - 1;

        for ($i = $eIdx; $i >= 0; $i--) {
            $tps = $timePeriods[$i][0];
            $tpe = $timePeriods[$i][1];
            $tTime = strtotime($tpe) - strtotime($tps);

            if ($tTime <= $time) {
                // Not enough, unset this timeperiod
                unset($timePeriods[$i]);
                $time -= $tTime;
            } else {
                // Enough, shorten end time.
                $timePeriods[$i][1] = self::extendTime($timePeriods[$i][0], $tTime - $time);
                break;
            }

            // End or No cross-period
            if ($time <= 0 || !$crossperiod) {
                break;
            }
        }

        return $timePeriods;
    }

    /**
     * Transform format
     * 
     * @param array $timePeriods
     * @param string $unit Time unit, if default,use class options setting
     * @return array
     */
    public static function format(array $timePeriods, $unit = 'default')
    {
        foreach ($timePeriods as $k => &$tp) {
            $tp[0] = self::timeFormatConv($tp[0], $unit);
            $tp[1] = self::timeFormatConv($tp[1], $unit);
        }

        return $timePeriods;
    }

    /**
     * Validate time period
     * 
     * Verify format, size, start/end time
     * 
     * @param mixed|array $timePeriods
     * @throws \Exception
     * @return bool
     */
    public static function validate($timePeriods)
    {
        // If not array, return.
        if (!is_array($timePeriods)) {
            throw new \Exception('Time periods format error !', 400);
        }

        foreach ($timePeriods as $k => $tp) {
            // filter format, number
            if (!is_array($tp) || sizeof($tp) != 2) {
                    throw new \Exception('Time periods format error !', 400);
            }
            // filter time period
            if ($tp[0] >= $tp[1]) {
                    throw new \Exception('Time periods format error !', 400);
            }
            // filter time format
            if (self::getFilterDatetime() && (!self::isDatetime($tp[0]) || !self::isDatetime($tp[1]))) {
                    throw new \Exception('Time periods format error !', 400);
            }
        }

        return true;
    }

    /**
     * Remove invalid time period
     * 
     * 1. Verify format, size, start/end time, and remove invalid.
     * 2. time carry problem processing, e.g. 2019-01-01 24:00:00 => 2019-01-02 00:00:00
     * 
     * @param mixed|array $timePeriods
     * @throws \Exception
     * @return array
     */
    public static function filter($timePeriods)
    {
        // If not array, return.
        if (!is_array($timePeriods)) {
            return [];
        }

        foreach ($timePeriods as $k => $tp) {
            // filter format, number
            if (!is_array($tp) || sizeof($tp) != 2) {
                unset($timePeriods[$k]);
                continue;
            }
            // filter time period
            if ($tp[0] >= $tp[1]) {
                unset($timePeriods[$k]);
                continue;
            }
            // filter time format
            if (self::getFilterDatetime() && (!self::isDatetime($tp[0]) || !self::isDatetime($tp[1]))) {
                unset($timePeriods[$k]);
                continue;
            }

            // Time carry
            $timeLen = strlen($tp[0]);
            if ($timeLen >= 13) {
                if (substr($tp[0], 11, 2) == '24') {
                    $timePeriods[$k][0] = self::extendTime($timePeriods[$k][0], 0);
                }
                if (substr($tp[1], 11, 2) == '24') {
                    $timePeriods[$k][1] = self::extendTime($timePeriods[$k][1], 0);
                }
            }
        }

        return $timePeriods;
    }


    /**
     * **********************************************
     * ************** Options Function **************
     * **********************************************
     */


    /**
     * Specify the minimum unit of calculation
     * 
     * 1. Scope: Global
     * 2. hour,minute,second
     * 
     * @param string $unit time unit. e.g. hour, minute, second.
     * @param string $target Specify function,or all functions
     * @throws \Exception
     * @return $this
     */
    public static function setUnit(string $unit, string $target = 'all')
    {
        /*** Arguments prepare ***/
        if (!isset(self::$_options['unitMap'][$unit])) {
            throw new \Exception('Error Unit: ' . $unit, 400);
        }
        // conv unit
        $unit = self::$_options['unitMap'][$unit];

        if ($target != 'all' && !isset(self::$_options['unit'][$target])) {
            throw new \Exception('Error Target: ' . $target, 400);
        }

        /* Setting */
        if ($target != 'all') {
            self::$_options['unit'][$target] = $unit;
        } else {
            foreach (self::$_options['unit'] as $tar => &$value) {
                $value = $unit;
            }
        }

        return new static();
    }

    /**
     * Get the unit used by the specified function
     * 
     * @param string $target Specify function's unit
     * @throws \Exception
     * @return string
     */
    public static function getUnit(string $target)
    {
        if (isset(self::$_options['unit'][$target])) {
            return self::$_options['unit'][$target];
        } else {
            throw new \Exception('Error Target: ' . $target, 400);
        }
    }

    /**
     * If neet filter datetime : Set option
     * 
     * 1. Scope: Global
     * 2. If you do not want to filter the datetime format, set it to false.  
     * 3. Maybe the time format is not Y-m-d H:i:s (such as Y-m-d H:i), you need to close it.
     * 4. Effect function: filter(), validate()
     * 
     * @param bool $bool
     * @return $this
     */
    public static function setFilterDatetime($bool)
    {
        self::$_options['filter']['isDatetime'] = !!$bool;

        return new static();
    }

    /**
     * If neet filter datetime : Get option
     * 
     * @return bool
     */
    public static function getFilterDatetime()
    {
        return self::$_options['filter']['isDatetime'];
    }

    /**
     * Auto sort out $timePeriods : Set option
     *
     * 1. Scope: Global
     * 2. Before the function is processed, union() will be used to organize $timePeriods format.
     * 
     * @param bool $bool default true
     * @return $this
     */
    public static function setSortOut($bool = true)
    {
        self::$_options['sortOut'] = !!$bool;

        return new static();
    }

    /**
     * Auto sort out $timePeriods : Get option
     *
     * @return bool
     */
    public static function getSortOut()
    {
        return self::$_options['sortOut'];
    }

    /**
     * ********************************************
     * ************** Tools Function **************
     * ********************************************
     */

    /**
     * Check datetime fast
     * 
     * Only check format,no check for reasonableness
     * 
     * @param string $datetime
     * @return boolean
     */
    public static function isDatetime(string $datetime)
    {
        return (bool) preg_match('|^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}$|', $datetime);
    }

    /**
     * Time format convert
     * 
     * format:Y-m-d H:i:s
     * When the length is insufficient, it will add the missing
     * 
     * @param string $datetime
     * @param string $unit Time unit, if default,use self::$_options setting
     * @return string
     */
    public static function timeFormatConv(string $datetime, $unit = 'default')
    {
        // fill format
        $strlen = strlen($datetime);
        $datetime .= substr(' 00:00:00', $strlen - 10);

        $unit = !isset(self::$_options['unitMap'][$unit]) ? self::$_options['unit']['format'] : self::$_options['unitMap'][$unit];
        // replace
        if ($unit == 'minute') {
            $datetime = substr_replace($datetime, "00", 17, 2);
        } elseif ($unit == 'hour') {
            $datetime = substr_replace($datetime, "00:00", 14, 5);
        }

        return $datetime;
    }

    /**
     * Extend time
     * 
     * @param string $datetime
     * @param int $timeLen 
     * @return string
     */
    protected static function extendTime(String $datetime, $timeLen)
    {
        $tout = date('Y-m-d H:i:s', strtotime($datetime) + $timeLen);
        return substr($tout, 0, strlen($datetime));
    }

    /**
     * Time Conversion frm unit to second
     * 
     * @param number $time
     * @param string $unit Time unit, if default,use self::$_options setting
     * @return int
     */
    public static function time2Second($time, $unit = 'default')
    {
        // Git time unit
        $unit = !isset(self::$_options['unitMap'][$unit]) ? self::getUnit('time') : self::$_options['unitMap'][$unit];

        // Convert
        switch ($unit) {
            case 'minute':
                $time = $time * 60;
                break;
            case 'hour':
                $time = $time * 3600;
                break;
        }

        return (int) $time;
    }

    /**
     * Data sorting out
     *
     * @param string|bool $sortOut
     * @param array $timePeriods1
     * @param array|null $timePeriods2
     * @return void
     */
    protected static function dataSortOut(&$sortOut, &$timePeriods1, &$timePeriods2 = null)
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
