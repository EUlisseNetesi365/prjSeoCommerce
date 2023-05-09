<?php


namespace PerSeo;


class Validator
{
    /**
     * @param $email
     * @return false|int
     */
    public function isEmail($email){
        return preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email);
    }

    /**
     * @param $sytr
     * @return false|int
     */
    public function isUserName($str){
        return preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]+$/', $str);
    }

    /**
     * @param bool $cf
     * @return bool
     */
    public function isCfIva($cf = false){
        $cf = strtoupper($cf);
        if(strlen($cf) == 11){
            return $this->isIva($cf);
        }

        if(!empty($cf) && strlen($cf) == 16 && preg_match("/^[A-Z0-9]+$/", $cf) == true) {


            $s = 0;
            for ($i = 1; $i <= 13; $i += 2) {
                $c = $cf[$i];
                if ('0' <= $c && $c <= '9')
                    $s += ord($c) - ord('0');
                else
                    $s += ord($c) - ord('A');
            }
            for ($i = 0; $i <= 14; $i += 2) {
                $c = $cf[$i];
                switch ($c) {
                    case '0':
                        $s += 1;
                        break;
                    case '1':
                        $s += 0;
                        break;
                    case '2':
                        $s += 5;
                        break;
                    case '3':
                        $s += 7;
                        break;
                    case '4':
                        $s += 9;
                        break;
                    case '5':
                        $s += 13;
                        break;
                    case '6':
                        $s += 15;
                        break;
                    case '7':
                        $s += 17;
                        break;
                    case '8':
                        $s += 19;
                        break;
                    case '9':
                        $s += 21;
                        break;
                    case 'A':
                        $s += 1;
                        break;
                    case 'B':
                        $s += 0;
                        break;
                    case 'C':
                        $s += 5;
                        break;
                    case 'D':
                        $s += 7;
                        break;
                    case 'E':
                        $s += 9;
                        break;
                    case 'F':
                        $s += 13;
                        break;
                    case 'G':
                        $s += 15;
                        break;
                    case 'H':
                        $s += 17;
                        break;
                    case 'I':
                        $s += 19;
                        break;
                    case 'J':
                        $s += 21;
                        break;
                    case 'K':
                        $s += 2;
                        break;
                    case 'L':
                        $s += 4;
                        break;
                    case 'M':
                        $s += 18;
                        break;
                    case 'N':
                        $s += 20;
                        break;
                    case 'O':
                        $s += 11;
                        break;
                    case 'P':
                        $s += 3;
                        break;
                    case 'Q':
                        $s += 6;
                        break;
                    case 'R':
                        $s += 8;
                        break;
                    case 'S':
                        $s += 12;
                        break;
                    case 'T':
                        $s += 14;
                        break;
                    case 'U':
                        $s += 16;
                        break;
                    case 'V':
                        $s += 10;
                        break;
                    case 'W':
                        $s += 22;
                        break;
                    case 'X':
                        $s += 25;
                        break;
                    case 'Y':
                        $s += 24;
                        break;
                    case 'Z':
                        $s += 23;
                        break;
                }
            }
            if (chr($s % 26 + ord('A')) != $cf[15]):
                return false; //"Il codice fiscale $cf non &egrave; corretto:\n"
            //."il codice di controllo non corrisponde.";
            else:
                return true;//"OK IL CODICE $cf ט corretto";//
            endif;
        } else {
            return false; //"Il codice fiscale $cf non &egrave; corretto:\n";
        }

    }

    /**
     * @param bool $pi
     * @return bool
     */
    public static function isIva($pi = false)
    {
        $s = 0;
        for( $i = 0; $i <= 9; $i += 2 ){
            $s += ord($pi[$i]) - ord('0');
        }
        for( $i = 1; $i <= 9; $i += 2 ){
            $c = 2*( ord($pi[$i]) - ord('0') );
            if( $c > 9 ) {
                $c = $c - 9;
            }
            $s += $c;
        }
        if( ( 10 - $s%10 )%10 != ord($pi[10]) - ord('0') || strlen($pi) != 11 || ! preg_match("/^[0-9]+$/", $pi)):
            return false;
        else:
            return true;
        endif;
    }

    /**
     * @param $gs1
     * @return bool
     */
    public static function checkGs1($gs1 ){

        $len = strlen($gs1);
        if( $len < 12 || $len > 14 )
            return false;
        $gs1code = substr($gs1, 0, $len-1);
        $checkDigit = substr($gs1, -1, 1);
        $reverseArr = str_split(strrev($gs1code));
        $arrDigit = [];

        $count = 1;
        foreach($reverseArr as $digit){
            if( $count%2 == 0){
                $arrDigit[] = $digit;
            } else {
                $arrDigit[] = $digit*3;
            }
            ++$count;
        }
        $tot = array_sum($arrDigit);
        $lastDigit = substr($tot, -1, 1);
        $findCheck = 10-$lastDigit ;

        return ($checkDigit == $findCheck);
    }

    /**
     * @param $str
     * @param string $separator
     * @param bool $max_length
     * @return false|string|string[]|null
     *
     */
    function sanitizeUtf8($str, $separator = '_', $max_length = false){

        $reset = strtolower($str);
        $str = iconv('utf-8', 'ASCII//TRANSLIT', $reset);

        $str = preg_replace("/\s{1,200}/i", $separator, $str);
        $str = preg_replace("/[^a-z0-9".$separator."]/", '', $str);

        return ((int)$max_length > 0) ? substr($str, 0, (int)$max_length) : $str;

    }

    /**
     * @param $data
     * @return bool
     */
    function json_validator($data) {
        if (!empty($data)) {
            return is_string($data) && is_array(json_decode($data, true)) ? true : false;
        }
        return false;
    }

}