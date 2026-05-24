<?php

namespace App\Http\Controllers;

class HelperController extends Controller
{
    /**
     * Convert a number to spelled-out words in Indonesian.
     *
     * @param float|int $value
     * @return string
     */
    public static function numberToWordsIndonesian($value)
    {
        if ($value < 0) {
            $result = "minus " . trim(self::spellNumberIndonesian($value));
        } else {
            $result = trim(self::spellNumberIndonesian($value));
        }
        return ucwords($result) . " Rupiah";
    }

    /**
     * Recursive helper to spell numbers in Indonesian.
     *
     * @param float|int $value
     * @return string
     */
    private static function spellNumberIndonesian($value)
    {
        $value = abs($value);
        $words = [
            0 => "",
            1 => "satu",
            2 => "dua",
            3 => "tiga",
            4 => "empat",
            5 => "lima",
            6 => "enam",
            7 => "tujuh",
            8 => "delapan",
            9 => "sembilan",
            10 => "sepuluh",
            11 => "sebelas"
        ];
        $temp = "";
        
        if ($value < 12) {
            $temp = " " . $words[$value];
        } else if ($value < 20) {
            $temp = self::spellNumberIndonesian($value - 10) . " belas";
        } else if ($value < 100) {
            $temp = self::spellNumberIndonesian((int) ($value / 10)) . " puluh" . self::spellNumberIndonesian($value % 10);
        } else if ($value < 200) {
            $temp = " seratus" . self::spellNumberIndonesian($value - 100);
        } else if ($value < 1000) {
            $temp = self::spellNumberIndonesian((int) ($value / 100)) . " ratus" . self::spellNumberIndonesian($value % 100);
        } else if ($value < 2000) {
            $temp = " seribu" . self::spellNumberIndonesian($value - 1000);
        } else if ($value < 1000000) {
            $temp = self::spellNumberIndonesian((int) ($value / 1000)) . " ribu" . self::spellNumberIndonesian($value % 1000);
        } else if ($value < 1000000000) {
            $temp = self::spellNumberIndonesian((int) ($value / 1000000)) . " juta" . self::spellNumberIndonesian($value % 1000000);
        } else if ($value < 1000000000000) {
            $temp = self::spellNumberIndonesian((int) ($value / 1000000000)) . " milyar" . self::spellNumberIndonesian(fmod($value, 1000000000));
        } else if ($value < 1000000000000000) {
            $temp = self::spellNumberIndonesian((int) ($value / 1000000000000)) . " trilyun" . self::spellNumberIndonesian(fmod($value, 1000000000000));
        }
        
        return $temp;
    }
}
