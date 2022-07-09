<?php

namespace FusionExport\Converters;

class BooleanConverter
{
    public static function convert($value)
    {
        if ($value === 'false') $value = false;

        return (bool)$value;
    }
}