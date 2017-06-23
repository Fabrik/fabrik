// Here is a function that produces the same output as number_format() but also works with numbers bigger than 2^53.

function a_number_format($number_in_iso_format, $no_of_decimals=3, $decimals_separator='.', $thousands_separator='', $digits_grouping=3){
    // Check input variables
    if (!is_numeric($number_in_iso_format)){
        error_log("Warning! Wrong parameter type supplied in my_number_format() function. Parameter \$number_in_iso_format is not a number.");
        return false;
    }
    if (!is_numeric($no_of_decimals)){
        error_log("Warning! Wrong parameter type supplied in my_number_format() function. Parameter \$no_of_decimals is not a number.");
        return false;
    }
    if (!is_numeric($digits_grouping)){
        error_log("Warning! Wrong parameter type supplied in my_number_format() function. Parameter \$digits_grouping is not a number.");
        return false;
    }
    
    
    // Prepare variables
    $no_of_decimals = $no_of_decimals * 1;
    
    
    // Explode the string received after DOT sign (this is the ISO separator of decimals)
    $aux = explode(".", $number_in_iso_format);
    // Extract decimal and integer parts
    $integer_part = $aux[0];
    $decimal_part = isset($aux[1]) ? $aux[1] : '';
    
    // Adjust decimal part (increase it, or minimize it)
    if ($no_of_decimals > 0){
        // Check actual size of decimal_part
        // If its length is smaller than number of decimals, add trailing zeros, otherwise round it
        if (strlen($decimal_part) < $no_of_decimals){
            $decimal_part = str_pad($decimal_part, $no_of_decimals, "0");
        } else {
            $decimal_part = substr($decimal_part, 0, $no_of_decimals);
        }
    } else {
        // Completely eliminate the decimals, if there $no_of_decimals is a negative number
        $decimals_separator = '';
        $decimal_part       = '';
    }
    
    // Format the integer part (digits grouping)
    if ($digits_grouping > 0){
        $aux = strrev($integer_part);
        $integer_part = '';
        for ($i=strlen($aux)-1; $i >= 0 ; $i--){
            if ( $i % $digits_grouping == 0 && $i != 0){
                $integer_part .= "{$aux[$i]}{$thousands_separator}";
            } else {
                $integer_part .= $aux[$i];            
            }
        }
    }
    
    $processed_number = "{$integer_part}{$decimals_separator}{$decimal_part}";
    return $processed_number;
}

$original_number= 9223372036854775805;
echo a_number_format($original_number, 4, '.',"'",3);
// Outputs: 9'223'372'036'854'775'805.1230