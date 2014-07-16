<?php
/**
 * Created by PhpStorm.
 * User: Sergio
 * Date: 23-12-13
 * Time: 03:16 PM
 */

class Account_gen {
    static private function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }
    static private function bcdechex($dec)
    {
        $hex = '';
        while ($dec!='0') {
            $mod=bcmod($dec,16);
            $dec=bcdiv($dec, 16);
            $hex=dechex($mod).$hex;
        }
        return $hex;
    }

    static private function Mask($Hex) {
        $Mask=self::bcdechex('8707267941106');
        while (strlen($Hex)<strlen($Mask)) {
            $Hex="0$Hex";
        }
        while (strlen($Mask)<strlen($Hex)) {
            $Mask="0$Mask";
        }
        $A=str_split($Hex);
        $B=str_split($Mask);
        $X='';
        $N=0;
        foreach ($A as $D) {
            $a=$A[$N];
            $b=$B[$N];
            $x=hexdec($a)^hexdec($b);
            $x=dechex($x);
            bcadd($X,$x);
            bcmul($X,16);
            $X.=$x;
            $N++;
        }
        $X=self::bchexdec($X);
        return($X);
    }

    static public function Encode($EFIN,$SSN) {
        $S=bcmul($SSN,'10000');
        $E=bcadd($S,$EFIN);
        $Hex=self::bcdechex($E);
        $X=self::Mask($Hex);
        return($X);
    }

    static public function Decode($Acct,&$EFIN,&$SSN) {
        $Acct=self::bcdechex($Acct);
        $RV=self::Mask($Acct);
        $EFIN=substr($RV,-4);
        $SSN=substr($RV,0,-4);
        return($RV);
    }
} 