<?php

namespace marsapp\helper\timeperiod;

use marsapp\helper\timeperiod\classes\Base;
use marsapp\helper\timeperiod\classes\LogicalProcess;
use marsapp\helper\timeperiod\classes\DataProcess;

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
class TimePeriodHelper extends Base
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
        return DataProcess::sort($timePeriods);
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
        return \call_user_func_array(['\marsapp\helper\timeperiod\classes\LogicalProcess', 'union'], func_get_args());
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
        return LogicalProcess::diff($timePeriods1, $timePeriods2, $sortOut);
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
        return LogicalProcess::intersect($timePeriods1, $timePeriods2, $sortOut);
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
        return LogicalProcess::isOverlap($timePeriods1, $timePeriods2);
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
        return LogicalProcess::contact($timePeriods, $sDateTime, $eDateTime, $sortOut);
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
        return LogicalProcess::greaterThan($timePeriods, $refDatetime, $fullTimePeriod, $sortOut);
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
        return LogicalProcess::lessThan($timePeriods, $refDatetime, $fullTimePeriod, $sortOut);
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
        return DataProcess::fill($timePeriods);
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
        return DataProcess::gap($timePeriods, $sortOut);
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
        return DataProcess::time($timePeriods, $precision, $sortOut);
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
        return DataProcess::cut($timePeriods, $time, $sortOut);
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
        return DataProcess::extend($timePeriods, $time, $interval, $sortOut);
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
        return DataProcess::shorten($timePeriods, $time, $crossperiod, $sortOut);
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
        return DataProcess::format($timePeriods, $unit);
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
        return DataProcess::validate($timePeriods);
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
        return DataProcess::filter($timePeriods);
    }
}
