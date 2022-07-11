<?php

namespace FusionExport\Converters;

class NumberConverter
{
    public static function convert($value)
    {
        return (int)$value;
    }
}