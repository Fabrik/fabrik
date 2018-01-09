<?php

/**
 * Use Javascript to display a message as an Alert
 */
function alert($msg) {
    echo "<script type='text/javascript'>alert('$msg');</script>";
}


/**
 *
 *
 */
class EDTF {
    /**
     * Prepare a fully formed EDTF date string
     * -- cut short the process if either the "start" or "end" is "unknown" or "open"
     * -- use buildEDTF function to do the actual work
     *
     * @param $data array of all data associated with this form
     * @param $table e.g., "event", "people", or "..."
     * @param $type "Single Date", "Start/End", "Start/Duration", "Duration/End", or "Duration"
     *
     * @todo day becomes monthDay and add weekDay & yearDay -- dayOfMonth etc. ???
     * @todo "Purify" the code to that it can be moved to class of its own by getting the
     *       year, division, week & day ready before calling the buildEDTF function
	 * @todo translate unicode ellipsis to ".." before save
     */
    function getEDTF($data, $table, $type) {
        $tableName = 'gn_' . $table . '___';
        $typeName = $tableName . 'basics_date_type';
        $tabType  = str_replace("%20", " ", $data[$typeName]);
        $tabChoice = ($tabType == 'Single Date') ? 'start' : $type;
        /* alert('|' . $typeName . '|' . $tabType . '|' . $type . '|' . $tabChoice . '|'); */
        $dateOpen    = "\u{2022}\u{2006}\u{2022}";
        $dateUnknown = "??";
        
        $statusStart = $data[$tableName . 'start_status_raw'];
        $statusStart = ($statusStart == 'unknown') ? $dateUnknown : $statusStart;
        $statusStart = ($statusStart == 'open'   ) ? $dateOpen    : $statusStart;
        
        $statusEnd   = $data[$tableName . 'end_status_raw'];
        $statusEnd   = ($statusEnd   == 'unknown') ? $dateUnknown : $statusEnd;
        $statusEnd   = ($statusEnd   == 'open'   ) ? $dateOpen    : $statusEnd;
        
        switch ($tabChoice) {
            case 'both':
                $edtf_start = ($statusStart != 'known') ? $statusStart : self::buildEDTF($data, $tableName, 'start');
                $edtf_end   = ($statusEnd   != 'known') ? $statusEnd   : self::buildEDTF($data, $tableName, 'end');
                $edtf       = $edtf_start . '/' . $edtf_end;
                break;
                
            case 'start':
                $edtf       = ($statusStart != 'known') ? $statusStart : self::buildEDTF($data, $tableName, 'start');
                break;
                
            case 'end':
                $edtf       = ($statusEnd   != 'known') ? $statusEnd   : self::buildEDTF($data, $tableName, 'end');
                break;
                
            case 'duration':
                break;
        }
        
        return $edtf;
    }
    
    
    /**
     * Prepare an EDTF date string
     *
     */
    function buildEDTF($data, $tableName, $tabType) {
        $tabName = $tableName . $tabType;
        $calType = $data[$tabName . '_calendar_type_raw'];
        $yearTest = $data[$tabName . '_year_value_raw']; // more checking needed
        $leapYear = self::isLeapYear($yearTest);
        
        switch ($calType) {
            case 'iso-edtf':
                $year = self::buildSegment($data, $tabName, $calType, 'year',     $leapYear, 0);
                $div  = self::buildSegment($data, $tabName, $calType, 'division', $leapYear, 2);
                $day  = self::buildSegment($data, $tabName, $calType, 'day',      $leapYear, 2);
                $edtf = $year . '-' . $div . '-' . $day;
                break;
                
            case 'iso-yd':
                $year = self::buildSegment($data, $tabName, $calType, 'year', $leapYear, 0);
                $day  = self::buildSegment($data, $tabName, $calType, 'day',  $leapYear, 3);
                $edtf = $year . '-' .  $day;
                break;
                
            case 'iso-yw':
                $year = self::buildSegment($data, $tabName, $calType, 'year', $leapYear, 0);
                $week = self::buildSegment($data, $tabName, $calType, 'week', $leapYear, 2);
                $edtf = $year . '-W' . $week;
                break;
                
            case 'iso-ywd':
                $year =       self::buildSegment($data, $tabName, $calType, 'year', $leapYear, 0);
                $week = 'W' . self::buildSegment($data, $tabName, $calType, 'week', $leapYear, 2);
                $day  =       self::buildSegment($data, $tabName, $calType, 'day',  $leapYear, 1);
                $edtf = $year . '-' . $week . '-' . $day;
                break;
                
            case 'julian':
                // base on iso-edtf
                break;
        }
        
        $edtf =  rtrim($edtf, '-');
        
        return $edtf;
    }
    
    
    /**
     * Prepare a segment of a date
     * -- year, division, week, day
     * -- "divisions" are: month, quarter, third, half, season
     *
     */
    function buildSegment($data, $tabName, $calType, $segType, $leapYear, $pad) {
        $segName  = $tabName . '_' . $segType;
        $segValueName = $segName . '_value';
        $segValueName = ($segType == 'division') ? $segValueName . '_raw' : $segValueName;
        
        /**
         * Prepare the "naked" segment value:
         * -- year - strip leading zeros from year
         * -- everything else - add leading zeros according to $pad param
         */
        $segment = ltrim((string)$data[$segValueName], '0'); // get rid of all leading 0s
        $segment = preg_match('/^[0-9xX]+$/', $segment) ? $segment : '!' . $segment;
        $segment = $segment == '!' ? '' : $segment;
        
        /**
         * Don't do anything more if $segment has anything other than Xs or digits
         */
        if (substr($segment, 0, 1) != '!') {
            if (is_numeric($segment)) {
                $pattern = '%0' . (string)$pad . 'd'; // %02d or %03d
                $segment = sprintf($pattern, (string)$segment);
            } elseif ($pad > 0) {
                $segment = substr($segment, -$pad);
            }
            
            /**
             * Conditionally adjust a year segment:
             * -- prefix with minus sign if Era is BC (BCE)
             * -- suffix with optional exponent (Ennn) and significant digits (Snnn)
             *
             * @todo figure out implication of "significant digits"
             */
            if ($segType == 'year') {
                $segEra  = $data[$tabName . '_era_value_raw'];
                $segment = ($segEra == 'bce') ? '-' . $segment : $segment;
                
                $segExp  = $data[$segName . '_exponent'];
                $segSigD = $data[$segName . '_significant_digits'];
                $segSuff = ($segExp  == 0) ? ''       :            'E' . ltrim((string)$segExp,  '0');
                $segSuff = ($segSigD == 0) ? $segSuff : $segSuff . 'S' . ltrim((string)$segSigD, '0');
                $segment = $segment . $segSuff;
            }
            
            /**
             * Conditionally add flags for accuracy and/or confidence
             */
            $segAcc  = $data[$segName . '_accuracy_raw'];
            $segConf = $data[$segName . '_confidence_raw'];
            $segFlag = ($segAcc  == 'approximate') ?            '~' : '';
            $segFlag = ($segConf == 'uncertain'  ) ? $segFlag . '?' : $segFlag;
            $segment = ($segFlag == '~?') ? '%' . $segment : $segFlag . $segment;
        }
        return (string)$segment;
    }
    
    
    
    /**
     * determine the number of days in the division (week, month, quarter, third, half, or season
     *
     * @param string  $calType  Type of calendar (Gregorian, ISO-YD, ...)
     * @param string  $divType  Type of division (week, month, quarter, ...)
     * @param integer $year     Year
     * @param integer $division Division of year (10 = October, 21 = Spring, ...)
     */
    function getDaysInDivision($calType, $divType, $year, $division) {
        $daysInYear = (cal_days_in_month(CAL_GREGORIAN, $year, 2) < 29 ) ? 365 : 366;
        switch ($calType) {
            case 'iso-edtf':
                switch ($divType) {
                    case 'week':
                        $daysInDivision = array(7);
                        break;
                        
                    case 'month':
                        $daysInDivision = array(cal_days_in_month(CAL_GREGORIAN, $year, $division));
                        break;
                        
                    case 'quarter':
                    case 'season':
                        $daysInDivision = $daysInYear / 4;
                        $daysInDivision = array(ceil($days), floor($days));
                        break;
                        
                    case 'third':
                        $daysInDivision = $daysInYear / 3;
                        $daysInDivision = array(ceil($days), floor($days));
                        break;
                        
                    case 'half':
                        $daysInDivision = $daysInYear / 2;
                        $daysInDivision = array(ceil($days), floor($days));
                        break;
                }
                break;
                
            case 'iso-yd':
                $daysInDivision = array($daysInYear);
                break;
                
            case 'iso-yw':
                $daysInDivision = array(self::getWeeksInYear($year));
                break;
                
            case 'iso-ywd':
                $daysInDivision = array(self::getWeeksInYear($year));
                break;
                
            case 'julian':
                break;
        }
    }
    
    
    /**
     * Determine if this is a leap year on the proleptic Gregorian calendar
     */
    function isLeapYear($year) {
        if ($year % 100 == 0) {
            return ($year % 400 == 0);
        }
        return ($year % 4 == 0);
    }
    
    
    /**
     * Determine the number of weeks in the ISO-8601 calendar for a given year
     * -- see wikipedia -- https://en.wikipedia.org/wiki/ISO_week_date
     *
     */
    function getWeeksInYear($year) {
        $p = (($year + ($year / 4) - ($year / 100) + ($year / 400)) % 7);
        return (52 + ((($p - 1) == 3) || ($p == 4))) ? 1 : 0;
    }
}
?>





