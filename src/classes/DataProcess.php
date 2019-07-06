<?php

namespace marsapp\helper\timeperiod\classes;

use marsapp\helper\timeperiod\classes\Base;
use marsapp\helper\timeperiod\classes\LogicalProcess;

/**
 * Data Processing for Time Period Helper
 * 
 * @author Mars Hung <tfaredxj@gmail.com>
 * @see https://github.com/marshung24/TimePeriodHelper
 */
class DataProcess extends Base
{

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
     * @param bool|string $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function gap(array $timePeriods, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        LogicalProcess::dataSortOut($sortOut, $timePeriods);

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
     * @param bool|string $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return number
     */
    public static function time(array $timePeriods, $precision = 0, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return 0;
        }

        // Data sorting out
        LogicalProcess::dataSortOut($sortOut, $timePeriods);

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
     * @param bool|string $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function cut(array $timePeriods, $time, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        LogicalProcess::dataSortOut($sortOut, $timePeriods);
        // Convert time by unit
        $time = self::time2Second($time);

        $opt = [];
        $timeLen = 0;
        foreach ($timePeriods as $k => $tp) {
            // Calculation time
            $tlen = strtotime($tp[1]) - strtotime($tp[0]);

            // Judging the length of time
            if ($timeLen + $tlen <= $time) {
                // Within limits, get data && continue
                $opt[] = $tp;
                $timeLen = $timeLen + $tlen;
                // next loop
                continue;
            } elseif ($timeLen < $time) {
                // Partially exceeded limit
                $tpe = self::extendTime($tp[0], $time - $timeLen);
                $tp[0] != $tpe && $opt[] = [$tp[0], $tpe];
            }

            // Exceed the limit, exit loop
            break;
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
     * @param bool|string $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function extend(array $timePeriods, $time, $interval = 0, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        LogicalProcess::dataSortOut($sortOut, $timePeriods);

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
     * @param bool|string $sortOut Whether the input needs to be rearranged. Value: true, false, 'default'. If it is 'default', see getSortOut()
     * @return array
     */
    public static function shorten(array $timePeriods, $time, $crossperiod = true, $sortOut = 'default')
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }

        // Data sorting out
        LogicalProcess::dataSortOut($sortOut, $timePeriods);

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
        // If not array, throw exception.
        !is_array($timePeriods) && self::throwException('Time periods format error !', 400);

        array_walk($timePeriods, function ($tp) {
            // filter format
            !is_array($tp) && self::throwException('Time periods format error !', 400);

            // filter number
            sizeof($tp) != 2 && self::throwException('Time periods format error !', 400);

            // filter time period
            $tp[0] >= $tp[1] && self::throwException('Time periods format error !', 400);

            // filter time format
            self::getFilterDatetime() && (!self::isDatetime($tp[0]) || !self::isDatetime($tp[1])) && self::throwException('Time periods format error !', 400);
        });

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

        // filter format, number
        $timePeriods = array_filter($timePeriods, function ($tp) {
            return !is_array($tp) || sizeof($tp) != 2 ? false : true;
        });

        // filter time period
        $timePeriods = array_filter($timePeriods, function ($tp) {
            return $tp[0] >= $tp[1] ? false : true;
        });

        // filter time format
        $timePeriods = array_filter($timePeriods, function ($tp) {
            return self::getFilterDatetime() && (!self::isDatetime($tp[0]) || !self::isDatetime($tp[1])) ? false : true;
        });

        // Time carry: ex: 2019-06-01 24:10:22 => 2019-06-02 00:10:22
        $timePeriods = array_map(function ($tp) {
            $tp[0] = substr($tp[0], 11, 2) == '24' ? self::extendTime($tp[0], 0) : $tp[0];
            $tp[1] = substr($tp[1], 11, 2) == '24' ? self::extendTime($tp[1], 0) : $tp[1];
            return $tp;
        }, $timePeriods);

        return $timePeriods;
    }
}
