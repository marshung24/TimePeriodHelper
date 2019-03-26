# TimePeriodHelper
The time period processing library provides functions such as sorting, union, difference, intersection, and calculation time.

> Continuation library marshung/helper, only keep and maintain TimePeriodHelper

[![Latest Stable Version](https://poser.pugx.org/marsapp/timeperiodhelper/v/stable)](https://packagist.org/packages/marsapp/timeperiodhelper) [![Total Downloads](https://poser.pugx.org/marsapp/timeperiodhelper/downloads)](https://packagist.org/packages/marsapp/timeperiodhelper) [![Latest Unstable Version](https://poser.pugx.org/marsapp/timeperiodhelper/v/unstable)](https://packagist.org/packages/marsapp/timeperiodhelper) [![License](https://poser.pugx.org/marsapp/timeperiodhelper/license)](https://packagist.org/packages/marsapp/timeperiodhelper)

# Outline
- [Installation](#Installation)
- [Usage](#Usage)
  - [TimePeriodHelper](#TimePeriodHelper)


# [Installation](#Outline)
## Composer Install
```
# composer require marsapp/timeperiodhelper
```

## Include
Include composer autoloader before use.
```php
require __PATH__ . "vendor/autoload.php";
```

# [Usage](#Outline)

## [TimePeriodHelper](#Outline)
> 1. Format: $timePeriods = [[$startDatetime1, $endDatetime1], [$startDatetime2, $endDatetime2], ...];
>   - $Datetime = Y-m-d H:i:s ; Y-m-d H:i:00 ; Y-m-d H:00:00 ;
> 2. If it is hour/minute/second, the end point is usually not included, for example, 8 o'clock to 9 o'clock is 1 hour.
> 3. If it is a day/month/year, it usually includes an end point, for example, January to March is 3 months.
> 4. When processing, assume that the data format is correct. If necessary, you need to call the verification function to verify the data.

### Usage:
```php
// Namespace use
use marsapp\helper\timeperiod\TimePeriodHelper;

// Get time periods
$timeperiods = [.....];

// Filter time periods, ensure that the target data is correct
$timeperiods = TimePeriodHelper::filter($timeperiods);

// Maybe you want change time format
$timeperiods = TimePeriodHelper::setUnit('minute')->format($templete);

// Now you can execute the function you want to execute. Like gap()
$result = TimePeriodHelper::gap($timeperiods);
```

### sort()
Sort time periods (Order by ASC)
> When sorting, sort the start time first, if the start time is the same, then sort the end time  
> Sort Priority: Start Time => End Time

```php
sort(Array $timePeriods) : array
```

Example :
```php
$templete = [
    ['2019-01-04 12:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 08:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 17:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 19:00:00'],
    ['2019-01-04 08:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 09:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 07:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 10:00:00','2019-01-04 16:00:00'],
    ['2019-01-04 11:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 10:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 11:00:00','2019-01-04 15:00:00']
];
$result = TimePeriodHelper::sort($templete);
```

Sort $result:
```php
$result = [
    ['2019-01-04 07:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 08:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 08:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 09:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 10:00:00','2019-01-04 16:00:00'],
    ['2019-01-04 10:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 11:00:00','2019-01-04 15:00:00'],
    ['2019-01-04 11:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 17:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 19:00:00']
];
```


### union()
Union one or more time periods
> Sort and merge one or more time periods with contacts

```php
TimePeriodHelper::union(Array $timePeriods1, [Array $timePeriods2, [Array $timePeriods3, ......]]) : array
```

Example :
```php

$templete1 = [
    ['2019-01-04 13:00:00','2019-01-04 15:00:00'],
    ['2019-01-04 10:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 19:00:00','2019-01-04 22:00:00'],
    ['2019-01-04 15:00:00','2019-01-04 18:00:00']
];

$templete2 = [
    ['2019-01-04 08:00:00','2019-01-04 09:00:00'],
    ['2019-01-04 14:00:00','2019-01-04 16:00:00'],
    ['2019-01-04 21:00:00','2019-01-04 23:00:00']
];
// Sort and merge one timeperiods
$result1 = TimePeriodHelper::union($templete1);

// Sort and merge two timeperiods
$result2 = TimePeriodHelper::union($templete1, $templete2);
```

$result:
```php
$result1 = [
    ['2019-01-04 10:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 13:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 19:00:00','2019-01-04 22:00:00']
];

$result2 = [
    ['2019-01-04 08:00:00','2019-01-04 09:00:00'],
    ['2019-01-04 10:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 13:00:00','2019-01-04 18:00:00'],
    ['2019-01-04 19:00:00','2019-01-04 23:00:00']
];
```


### diff()
Computes the difference of time periods
> Compares $timePeriods1 against $timePeriods2 and returns the values in $timePeriods1 that are not present in $timePeriods2.

```php
diff(Array $timePeriods1, Array $timePeriods2, $sortOut = true) : array
```
> If you can be sure that the input value is already collated(Executed union()), you can turn off $sortOut to make execution faster.

Example :
```php
$templete1 = [
    ['2019-01-04 07:00:00','2019-01-04 08:00:00']
];
$templete2 = [
    ['2019-01-04 07:30:00','2019-01-04 07:40:00'],
];

$result = TimePeriodHelper::diff($templete1, $templete2);
```

$result:
```php
$result = [
    ['2019-01-04 07:00:00','2019-01-04 07:30:00'],
    ['2019-01-04 07:40:00','2019-01-04 08:00:00'],
];
```


### intersect()
Computes the intersection of time periods
```php
intersect(Array $timePeriods1, Array $timePeriods2, $sortOut = true) : array
```
> If you can be sure that the input value is already collated(Executed union()), you can turn off $sortOut to make execution faster.

Example :
```php
$templete1 = [
    ['2019-01-04 07:00:00','2019-01-04 08:00:00']
];
$templete2 = [
    ['2019-01-04 07:30:00','2019-01-04 07:40:00'],
];

$result = TimePeriodHelper::intersect($templete1, $templete2);
```

$result:
```php
$result = [
    ['2019-01-04 07:30:00','2019-01-04 07:40:00'],
];
```


### isOverlap()
Time period is overlap
> Determine if there is overlap between the two time periods

```php
isOverlap(Array $timePeriods1, Array $timePeriods2) : bool
```

Example :
```php
$templete1 = [
    ['2019-01-04 07:00:00','2019-01-04 08:00:00']
];
$templete2 = [
    ['2019-01-04 07:30:00','2019-01-04 07:40:00'],
];
$result = TimePeriodHelper::isOverlap($templete1, $templete2);
```

$result:
```php
$result = true;
```


### fill()
Fill time periods
> Leaving only the first start time and the last end time

```php
fill(Array $timePeriods) : array
```

Example :
```php
$templete = [
    ['2019-01-04 08:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 10:00:00','2019-01-04 19:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 18:00:00']
];

$result = TimePeriodHelper::fill($templete);
```

$result:
```php
$result = [
    ['2019-01-04 08:00:00','2019-01-04 19:00:00'],
];
```


### gap()
Get gap time periods of multiple sets of time periods

```php
gap(Array $timePeriods, $sortOut = true) : array
```
> If you can be sure that the input value is already collated(Executed union()), you can turn off $sortOut to make execution faster.

Example :
```php
$templete = [
    ['2019-01-04 08:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 04:00:00','2019-01-04 05:00:00'],
    ['2019-01-04 07:00:00','2019-01-04 09:00:00'],
    ['2019-01-04 13:00:00','2019-01-04 18:00:00']
];

$result = TimePeriodHelper::gap($templete, false);
```

$result:
```php
$result = [
    ['2019-01-04 05:00:00','2019-01-04 07:00:00'],
    ['2019-01-04 12:00:00','2019-01-04 13:00:00'],
];
```


### time()
Calculation period total time
> You can specify the smallest unit (from setUnit())

```php
time(Array $timePeriods, $sortOut = true) : array
```
> If you can be sure that the input value is already collated(Executed union()), you can turn off $sortOut to make execution faster.

Example :
```php
$templete = [
    ['2019-01-04 08:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 04:00:00','2019-01-04 05:00:00'],
    ['2019-01-04 07:00:00','2019-01-04 09:00:00'],
    ['2019-01-04 13:00:00','2019-01-04 18:00:00']
];

TimePeriodHelper::setUnit('hour');
$resultH = TimePeriodHelper::time($templete);

$resultM = TimePeriodHelper::setUnit('minutes')->time($templete);

TimePeriodHelper::setUnit('s');
$resultS = TimePeriodHelper::time($templete);
```
> Unit:  
> - hour, hours, h  
> - minute, minutes, m  
> - second, seconds, s

$result:
```php
$resultH = 11;
$resultM = 660;
$resultS = 39600;
```


### format()
Transform format
```php
format(Array $timePeriods, $unit = 'default') : array
```
> $unit: Time unit, if default,use class options setting

Example :
```php
$templete = [
    ['2019-01-04 08:11:11','2019-01-04 12:22:22'],
    ['2019-01-04 04:33:33','2019-01-04 05:44:44'],
];

TimePeriodHelper::setUnit('minute');
$result = TimePeriodHelper::format($templete);
```

$result:
```php
$result = [
    ['2019-01-04 08:11:00','2019-01-04 12:22:00'],
    ['2019-01-04 04:33:00','2019-01-04 05:44:00'],
];
```


### validate()
Validate time period
> Verify format, size, start/end time.  
> Format: Y-m-d H:i:s

```php
validate(Array $timePeriods) : Exception | true
```

Example :
```php
$templete = [
    ['2019-01-04 02:00:00','2019-01-04 03:00:00'],
    ['2019-01-04 08:00:00','2019-01-04 12:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 04:00:00'],
    ['2019-01-04 04:00','2019-01-04 05:00:00'],
    'string',
    ['2019-01-04 08:00:00','2019-01-04 05:00:00'],
    ['2019-01-04 19:00:00','2019-01-04 19:00:00'],
];

try {
    $result = TimePeriodHelper::validate($templete);
} catch (\Exception $e) {
    $result = false;
}
```

$result:
```php
$result = false;
```


### filter()
Remove invalid time period
> Verify format, size, start/end time, and remove invalid.

```php
filter(Array $timePeriods, $exception = false) : array
```
> $exception: Whether an exception is returned when an error occurs.(default false)
> @see setFilterDatetime();

Example :
```php
$templete = [
    ['2019-01-04 02:00:00','2019-01-04 03:00:00'],
    ['2019-01-04 08:00:00','2019-01-04 12:00:00','2019-01-04 12:00:00'],
    ['2019-01-04 04:00:00'],
    ['2019-01-04 04:00','2019-01-04 05:00:00'],
    'string',
    ['2019-01-04 08:00:00','2019-01-04 05:00:00'],
    ['2019-01-04 19:00:00','2019-01-04 19:00:00'],
];

//TimePeriodHelper::setFilterDatetime(false);
$result = TimePeriodHelper::filter($templete);
```
> If you do not want to filter the datetime format, set it to setFilterDatetime(false).  
> Maybe the time format is not Y-m-d H:i:s (such as Y-m-d H:i), you need to close it.

$result:
```php
$result = [
    ['2019-01-04 02:00:00','2019-01-04 03:00:00'],
];
```


### setUnit()
Specify the minimum unit of calculation
> hour,minute,second

```php
setUnit(string $unit, string $target = 'all') : self
```
> $target: Specify function,or all functions

Example :
```php
// Set unit hour for all
TimePeriodHelper::setUnit('hour');
// Set unit hour for format
TimePeriodHelper::setUnit('minute', 'format');

// Get unit
$result1 = TimePeriodHelper::getUnit('time');
$result2 = TimePeriodHelper::getUnit('format');
```

$result:
```php
$result1 = 'hour';
$result2 = 'minute';
```

### getUnit()
Get the unit used by the specified function
```php
getUnit(string $target) : string
```

Example :
```php
// Set unit hour for all
TimePeriodHelper::setUnit('hour');
// Set unit hour for format
TimePeriodHelper::setUnit('minute', 'format');

// Get unit
$result1 = TimePeriodHelper::getUnit('time');
$result2 = TimePeriodHelper::getUnit('format');
```

$result:
```php
$result1 = 'hour';
$result2 = 'minute';
```

### setFilterDatetime()
If neet filter datetime : Set option
> If you do not want to filter the datetime format, set it to false.  
> Maybe the time format is not Y-m-d H:i:s (such as Y-m-d H:i), you need to close it.

```php
setFilterDatetime(Bool $bool) : self
```

Example :
```php
TimePeriodHelper::setFilterDatetime(false);
$result1 = TimePeriodHelper::getFilterDatetime();

TimePeriodHelper::setFilterDatetime(true);
$result2 = TimePeriodHelper::getFilterDatetime();
```

$result:
```php
$result1 = false;

$result1 = true;
```

### getFilterDatetime()
If neet filter datetime : Get option
```php
getFilterDatetime() : bool
```

Example :
```php
TimePeriodHelper::setFilterDatetime(false);
$result1 = TimePeriodHelper::getFilterDatetime();

TimePeriodHelper::setFilterDatetime(true);
$result2 = TimePeriodHelper::getFilterDatetime();
```

$result:
```php
$result1 = false;

$result1 = true;
```

