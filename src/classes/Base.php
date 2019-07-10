<?php

namespace marsapp\helper\timeperiod\classes;

/**
 * Base class for Time Period Helper
 * 
 * @author Mars Hung <tfaredxj@gmail.com>
 * @see https://github.com/marshung24/TimePeriodHelper
 */
class Base
{
    /**
     * Option set
     * @var array
     */
    private static $_options = [
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
     * @param string $unit Time unit, if default,use self::$_options setting(set by setUnit())
     * @throws \Exception
     * @return string
     */
    public static function getUnit(string $target, $unit = 'default')
    {
        if (isset(self::$_options['unit'][$target])) {
            return !isset(self::$_options['unitMap'][$unit]) ? self::$_options['unit'][$target] : self::$_options['unitMap'][$unit];
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
     * @param string $unit Time unit, if default,use self::$_options setting(set by setUnit())
     * @return string
     */
    public static function timeFormatConv(string $datetime, $unit = 'default')
    {
        // fill format
        $strlen = strlen($datetime);
        $datetime .= substr(' 00:00:00', $strlen - 10);

        // replace
        $unit = self::getUnit('format', $unit);
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
    public static function extendTime(String $datetime, $timeLen)
    {
        $tout = date('Y-m-d H:i:s', strtotime($datetime) + $timeLen);
        return substr($tout, 0, strlen($datetime));
    }

    /**
     * Time Conversion frm unit to second
     * 
     * @param number $time
     * @param string $unit Time unit, if default,use self::$_options setting(set by setUnit())
     * @return int
     */
    public static function time2Second($time, $unit = 'default')
    {
        // Convert
        $unit = self::getUnit('time', $unit);
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
     * Throw Exception by function 
     * 
     * In order to remove the if statement:
     * - if (true/false) { throw new \Exception($msg, $code); }
     * - true/false || throw new \Exception($msg, $code);    <= Error
     * - true/false || self::throwException($msg, $code);    <= Good
     * 
     * @param string $msg
     * @param int $code
     * @return void
     */
    protected static function throwException($msg, $code) {
        throw new \Exception($msg, $code);
    }
}
