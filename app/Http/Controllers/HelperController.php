<?php

namespace App\Http\Controllers;

class HelperController extends Controller
{
    /**
     * Convert a number to spelled-out words in English.
     *
     * @param float|int $value
     * @return string
     */
    public static function numberToWordsEnglish($value)
    {
        if (strpos((string)$value, '.') !== false) {
            list($integer, $decimal) = explode('.', (string)$value, 2);
        } else {
            $integer = $value;
            $decimal = null;
        }

        if ($integer < 0) {
            $result = "minus " . trim(self::spellNumberEnglish(abs($integer)));
        } else {
            $result = trim(self::spellNumberEnglish($integer));
        }

        $result = ucwords($result);

        if ($decimal !== null && rtrim($decimal, '0') !== '') {
            $decimalWords = [];
            $chars = str_split(rtrim($decimal, '0'));
            foreach ($chars as $char) {
                $decimalWords[] = self::spellNumberEnglish((int)$char);
            }
            $result .= " point " . implode(' ', array_map('trim', $decimalWords));
        }

        return $result;
    }

    /**
     * Recursive helper to spell numbers in English.
     *
     * @param float|int $value
     * @return string
     */
    private static function spellNumberEnglish($value)
    {
        $value = abs($value);
        $words = [
            0 => "zero",
            1 => "one",
            2 => "two",
            3 => "three",
            4 => "four",
            5 => "five",
            6 => "six",
            7 => "seven",
            8 => "eight",
            9 => "nine",
            10 => "ten",
            11 => "eleven",
            12 => "twelve",
            13 => "thirteen",
            14 => "fourteen",
            15 => "fifteen",
            16 => "sixteen",
            17 => "seventeen",
            18 => "eighteen",
            19 => "nineteen",
            20 => "twenty",
            30 => "thirty",
            40 => "forty",
            50 => "fifty",
            60 => "sixty",
            70 => "seventy",
            80 => "eighty",
            90 => "ninety"
        ];
        
        $temp = "";
        
        if ($value < 21) {
            $temp = " " . $words[$value];
        } else if ($value < 100) {
            $temp = " " . $words[10 * (int) ($value / 10)] . ($value % 10 != 0 ? "-" . $words[$value % 10] : "");
        } else if ($value < 1000) {
            $temp = " " . $words[(int) ($value / 100)] . " hundred" . ($value % 100 != 0 ? self::spellNumberEnglish($value % 100) : "");
        } else if ($value < 1000000) {
            $temp = self::spellNumberEnglish((int) ($value / 1000)) . " thousand" . ($value % 1000 != 0 ? self::spellNumberEnglish($value % 1000) : "");
        } else if ($value < 1000000000) {
            $temp = self::spellNumberEnglish((int) ($value / 1000000)) . " million" . ($value % 1000000 != 0 ? self::spellNumberEnglish($value % 1000000) : "");
        } else if ($value < 1000000000000) {
            $temp = self::spellNumberEnglish((int) ($value / 1000000000)) . " billion" . ($value % 1000000000 != 0 ? self::spellNumberEnglish(fmod($value, 1000000000)) : "");
        } else if ($value < 1000000000000000) {
            $temp = self::spellNumberEnglish((int) ($value / 1000000000000)) . " trillion" . ($value % 1000000000000 != 0 ? self::spellNumberEnglish(fmod($value, 1000000000000)) : "");
        }
        
        return $temp;
    }
}
