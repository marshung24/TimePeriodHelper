<?php
namespace marsapp\helper\timeperiod;

/**
 * Time Period Helper
 * 
 * Note:
 * 1. Format: $timePeriods = [[$startDatetime1, $endDatetime1], [$startDatetime2, $endDatetime2], ...];
 * - $Datetime = Y-m-d H:i:s ; Y-m-d H:i:00 ; Y-m-d H:00:00 ;
 * 2. If it is hour/minute/second, the end point is usually not included, for example, 8 o'clock to 9 o'clock is 1 hour.
 * 3. If it is a day/month/year, it usually includes an end point, for example, January to March is 3 months.
 * 4. When processing, assume that the $timePeriods format is correct. If necessary, you need to call the verification function to verify the data.
 * 5. Ensure performance by keeping the $timePeriods format correct:
 * - a. When getting the raw $timePeriods, sort out it by filter(), union().
 * - b. Handle $timePeriods using only the functions provided by TimePeriodHelper (Will not break the format, sort)
 * - c. When you achieve the two operations described above, you can turn off auto sort out (TimePeriodHelper::setSortOut(false)) to improve performance.
 * 
 * @version 0.3.0
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
     * *********************************************
     * ************** Public Function **************
     * *********************************************
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
    public static function sort(Array $timePeriods)
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
     * @return array
     */
    public static function diff(Array $timePeriods1, Array $timePeriods2)
    {
        /*** Arguments prepare ***/
        // Subject or pattern is empty, do nothing
        if (empty($timePeriods1) || empty($timePeriods2)) {
            return $timePeriods1;
        }
        
        // Data sorting out
        if (self::getSortOut()) {
            $timePeriods1 = self::union($timePeriods1);
            $timePeriods2 = self::union($timePeriods2);
        }
        
        $opt = [];
        foreach ($timePeriods1 as $k1 => $ori) {
            foreach ($timePeriods2 as $ko => $sub) {
                if ($sub[1] <= $ori[0]) {
                    // No overlap && Passed: --$sub0--$sub1--$ori0--$ori1--
                    unset($timePeriods2[$ko]);
                    continue;
                } elseif ($ori[1] <= $sub[0]) {
                    // No overlap: --$ori0--$ori1--$sub0--$sub1--
                    continue;
                } elseif ($sub[0] <= $ori[0] && $ori[1] <= $sub[1]) {
                    // Subtract all: --sub0--ori0--ori1--sub1--
                    $ori = [];
                    break;
                } elseif ($ori[0] < $sub[0] && $sub[1] < $ori[1]) {
                    // Delete internal: --ori0--sub0--sub1--ori1--
                    $opt[]= [$ori[0], $sub[0]];
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
            if (! empty($ori)) {
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
     * @return array
     */
    public static function intersect(Array $timePeriods1, Array $timePeriods2)
    {
        // Subject or pattern is empty, do nothing
        if (empty($timePeriods1) || empty($timePeriods2)) {
            return [];
        }
        
        // Data sorting out
        if (self::getSortOut()) {
            $timePeriods1 = self::union($timePeriods1);
            $timePeriods2 = self::union($timePeriods2);
        }
        
        $opt = [];
        foreach ($timePeriods1 as $k1 => $ori) {
            foreach ($timePeriods2 as $ko => $sub) {
                if ($sub[1] <= $ori[0]) {
                    // No overlap && Passed: --$sub0--$sub1--$ori0--$ori1--
                    unset($timePeriods2[$ko]);
                    continue;
                } elseif ($ori[1] <= $sub[0]) {
                    // No overlap: --$ori0--$ori1--$sub0--$sub1--
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
    public static function isOverlap(Array $timePeriods1, Array $timePeriods2)
    {
        // Subject or pattern is empty, do nothing
        if (empty($timePeriods1) || empty($timePeriods2)) {
            return false;
        }
        
        foreach ($timePeriods1 as $k1 => $ori) {
            foreach ($timePeriods2 as $ko => $sub) {
                if ($sub[1] <= $ori[0]) {
                    // No overlap && Passed: --$sub0--$sub1--$ori0--$ori1--
                    unset($timePeriods2[$ko]);
                    continue;
                } elseif ($ori[1] <= $sub[0]) {
                    // No overlap: --$ori0--$ori1--$sub0--$sub1--
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
     * Fill time periods
     * 
     * Leaving only the first start time and the last end time
     * 
     * @param array $timePeriods
     * @return array
     */
    public static function fill(Array $timePeriods)
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
            $opt = [[$start,$end]];
        }
        
        return $opt;
    }

    /**
     * Get gap time periods of multiple sets of time periods
     * 
     * 1. Whether $timePeriods is sorted out will affect the correctness of the results. Please refer to Note 5. Ensure performance by keeping the $timePeriods format correct.
     * 
     * @param array $timePeriods
     * @return array
     */
    public static function gap(Array $timePeriods)
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }
        
        // Data sorting out
        if (self::getSortOut()) {
            $timePeriods = self::union($timePeriods);
        }
        
        $opt = [];
        foreach ($timePeriods as $k => $tp) {
            if (isset($timePeriods[$k+1])) {
                $opt[] = [$tp[1], $timePeriods[$k+1][0]];
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
     * @return number
     */
    public static function time(Array $timePeriods, $precision = 0)
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return 0;
        }
        
        // Data sorting out
        if (self::getSortOut()) {
            $timePeriods = self::union($timePeriods);
        }
        
        // Calculate time
        $time = 0;
        foreach ($timePeriods as $k => $tp) {
            $time += strtotime($tp[1]) - strtotime($tp[0]);
        }
        
        // Time unit convert
        switch (self::getUnit('time')) {
            case 'minute':
                if ($precision > 0) {
                    $pow = pow(10, (int)$precision);
                    $time = ((int) ($time / 60 * $pow)) / $pow;
                } else {
                    $time = (int) ($time / 60);
                }
                break;
            case 'hour':
                if ($precision > 0) {
                    $pow = pow(10, (int)$precision);
                    $time = ((int) ($time / 3600 * $pow)) / $pow;
                } else {
                    $time = (int) ($time / 3600);
                }
                break;
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
     * @param string $extension
     *            If the specified time is long, whether to extend the time period.(default:false)
     * @return array
     */
    public static function cut(Array $timePeriods, $time, $extension = false)
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }
        
        // Data sorting out
        if (self::getSortOut()) {
            $timePeriods = self::union($timePeriods);
        }
        
        // Convert time by unit
        $time = self::convTimeByUnit($time);
        
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
                $timeLen = $time;
                break;
            }
        }
        
        // Extend the last time period
        if ($extension && ($timeLen < $time)) {
            $eTime = $time - $timeLen;
            $eIdx = sizeof($opt) - 1;
            
            $tps = $opt[$eIdx][0];
            $tpe = self::extendTime($opt[$eIdx][1], $eTime);
            if ($tps != $tpe) {
                $opt[$eIdx] = [$tps, $tpe];
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
     * @return array
     */
    public static function extend(Array $timePeriods, $time, $interval = 0)
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }
        
        // Data sorting out
        if (self::getSortOut()) {
            $timePeriods = self::union($timePeriods);
        }
        
        // Convert time by unit
        $time = self::convTimeByUnit($time);
        $interval = self::convTimeByUnit($interval);
        
        // last time period index
        $eIdx = sizeof($timePeriods) - 1;
        
        if (! $interval) {
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
     * @return array
     */
    public static function shorten(Array $timePeriods, $time, $crossperiod = true)
    {
        // Subject is empty, do nothing
        if (empty($timePeriods)) {
            return [];
        }
        
        // Data sorting out
        if (self::getSortOut()) {
            $timePeriods = self::union($timePeriods);
        }
        
        // Convert time by unit
        $time = self::convTimeByUnit($time);
        
        // last time period index
        $eIdx = sizeof($timePeriods) - 1;
        
        for ($i = $eIdx; $i>=0; $i--) {
            $tps = $timePeriods[$i][0];
            $tpe = $timePeriods[$i][1];
            $tTime = strtotime($tpe) - strtotime($tps);
            
            if ($tTime <= $time) {
                // Not enough, unset this timeperiod
                unset($timePeriods[$i]);
                $time -= $tTime;
            } else {
                // Enough, shorten end time.
                $timePeriods[$i][1] = self::extendTime($timePeriods[$i][0], $tTime-$time);
                $time = 0;
                break;
            }
            
            // End or No cross-period
            if ($time <= 0 || ! $crossperiod) {
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
    public static function format(Array $timePeriods, $unit = 'default')
    {
        foreach ($timePeriods as $k => & $tp) {
            $tp[0] = self::timeConv($tp[0], $unit);
            $tp[1] = self::timeConv($tp[1], $unit);
        }
        
        return $timePeriods;
    }
    
    /**
     * Validate time period
     * 
     * Verify format, size, start/end time
     * 
     * @param array $timePeriods
     * @throws \Exception
     * @return bool
     */
    public static function validate($timePeriods)
    {
        self::filter($timePeriods, true);
        return true;
    }
    
    /**
     * Remove invalid time period
     * 
     * 1. Verify format, size, start/end time, and remove invalid.
     * 2. time carry problem processing, e.g. 2019-01-01 24:00:00 => 2019-01-02 00:00:00
     * 
     * @param array $timePeriods
     * @param bool $exception Whether an exception is returned when an error occurs.(default false)
     * @throws \Exception
     * @return array
     */
    public static function filter($timePeriods, $exception = false)
    {
        // If not array, return.
        if (! is_array($timePeriods)) {
            if ($exception)
                throw new \Exception('Time periods format error !', 400);
            return [];
        }
        
        foreach ($timePeriods as $k => $tp) {
            // filter format, number
            if (! is_array($tp) || sizeof($tp) != 2) {
                if ($exception)
                    throw new \Exception('Time periods format error !', 400);
                unset($timePeriods[$k]);
                continue;
            }
            // filter time period
            if ($tp[0] >= $tp[1]) {
                if ($exception)
                    throw new \Exception('Time periods format error !', 400);
                unset($timePeriods[$k]);
                continue;
            }
            // filter time format
            if (self::getFilterDatetime() && (! self::isDatetime($tp[0]) || ! self::isDatetime($tp[1]))) {
                if ($exception)
                    throw new \Exception('Time periods format error !', 400);
                unset($timePeriods[$k]);
                continue;
            }
            
            // Time carry
            $timeLen = strlen($tp[0]);
            if ($timeLen >= 13) {
                if (substr($tp[0], 11, 2) == '24') {
                    $timePeriods[$k][0] = self::extendTime($timePeriods[$k][0], 0);
                }
                if (substr($tp[01], 11, 2) == '24') {
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
     * @param string $unit
     * @param string $target Specify function,or all functions
     * @throws \Exception
     * @return $this
     */
    public static function setUnit(string $unit, string $target = 'all')
    {
        /*** Arguments prepare ***/
        if (! isset(self::$_options['unitMap'][$unit])) {
            throw new \Exception('Error Unit: ' . $unit, 400);
        }
        // conv unit
        $unit = self::$_options['unitMap'][$unit];
        
        if ($target != 'all' && ! isset(self::$_options['unit'][$target])) {
            throw new \Exception('Error Target: ' . $target, 400);
        }
        
        /* Setting */
        
        if ($target != 'all') {
            self::$_options['unit'][$target] = $unit;
        } else {
            foreach (self::$_options['unit'] as $tar => & $value) {
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
     * 1. Before the function is processed, union() will be used to organize $timePeriods format.
     * 2. Scope: Global
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
     * **********************************************
     * ************** Private Function **************
     * **********************************************
     */
    
    /**
     * Check datetime fast
     * 
     * Only check format,no check for reasonableness
     * 
     * @param string $datetime
     * @return boolean
     */
    protected static function isDatetime(string $datetime)
    {
        return preg_match('|^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}$|', $datetime);
    }
    
    /**
     * Time format convert
     * 
     * @param string $datetime
     * @param string $unit Time unit, if default,use self::$_options setting
     * @return string
     */
    protected static function timeConv(string $datetime, $unit = 'default')
    {
        $unit = ! isset(self::$_options['unitMap'][$unit]) ? self::$_options['unit']['time'] : self::$_options['unitMap'][$unit];
        
        // fill format
        $strlen = strlen($datetime);
        switch ($strlen) {
            case 10:
                $datetime .= ' ';
            case 11:
                $datetime .= '0';
            case 12:
                $datetime .= '0';
            case 13:
                $datetime .= ':';
            case 14:
                $datetime .= '0';
            case 15:
                $datetime .= '0';
            case 16:
                $datetime .= ':';
            case 17:
                $datetime .= '0';
            case 18:
                $datetime .= '0';
        }
        
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
     * @param number $timeLen 
     * @return string
     */
    protected static function extendTime(String $datetime, $timeLen)
    {
        $tout = date('Y-m-d H:i:s', strtotime($datetime) + $timeLen);
        return substr($tout, 0, strlen($datetime));
    }
    
    /**
     * Convert time by unit
     * 
     * @param number $time
     * @return number
     */
    protected static function convTimeByUnit($time)
    {
        // Git time unit
        $timeUnit = self::getUnit('time');
        // Convert
        switch ($timeUnit) {
            case 'minute':
                $time = $time * 60;
                break;
            case 'hour':
                $time = $time * 3600;
                break;
        }
        
        return $time;
    }
}