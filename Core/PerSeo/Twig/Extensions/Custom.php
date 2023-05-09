<?php

namespace PerSeo\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
//use Modules\categories\Classes\Category;

class Custom extends AbstractExtension
{
    public function getName()
    {
        return 'RenderFilters';
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('DateFormat', array($this, 'DateFormat')),
            new TwigFilter('toElement', array($this, 'JSONtoElement')),
            new TwigFilter('cast_to_array', array($this, 'OBJtoArray')),
            new TwigFilter('in_array', array($this, 'OBJ_includes')),
            new TwigFilter('unshift', array($this, 'Array_unshift')),
            new TwigFilter('key_to_array', array($this, 'KEYtoArray')),
            new TwigFilter('key_to_label', array($this, 'KEYtoLabel')),
            new TwigFilter('htmltype_to_db', array($this, 'TypoArray')),
            new TwigFilter('get_table', array($this, 'GET_table')),
            new TwigFilter('json_to_keys', array($this, 'JsonKeys')),
            new TwigFilter('json_to_types', array($this, 'JsonTypes')),
            new TwigFilter('json_to_keys_whenkey', array($this, 'JsonKeys_whenkey')),
            new TwigFilter('json_to_types_whenkey', array($this, 'JsonTypes_whenkey')),
            new TwigFilter('to_table_name', array($this, 'ToTableName')),
            new TwigFilter('keys_fill_table', array($this, 'FillsTable')),
            new TwigFilter('priceFormat', array($this, 'priceFormat')),
            new TwigFilter('endWith', array($this, 'endWith')),
            new TwigFilter('startWith', array($this, 'startWith')),
            new TwigFilter('arrayUnique', array($this, 'arrayUnique')),
            new TwigFilter('numberFormat', array($this, 'numberFormat')),
            new TwigFilter('ucfirst', array($this, 'ucfirst')),
            new TwigFilter('intVal', array($this, 'intVal'))
        );
    }
    public function getFunctions()
    {
        return array(
            new TwigFunction('json_decode', array($this, 'JSONDecode')),
            new TwigFunction('base64_decode', array($this, 'BASE64Decode')),
            new TwigFunction('base64_encode', array($this, 'BASE64Encode')),
            new TwigFunction('explode', array($this, 'explodeStr')),
            new TwigFunction('implode', array($this, 'implodeArr'))
        );
    }

    /**
     * @param string $delimiter
     * @param string $input
     * @return false|string[]
     */
    public function explodeStr(string $delimiter, string $input){
        return explode($delimiter, $input);
    }

    /**
     * @param string $delimiter
     * @param string $input
     * @return string
     */
    public function implodeArr(string $delimiter, array $input){
        return implode($delimiter, $input);
    }

    public function JSONtoElement(string $input)
    {
        $input = json_decode($input, true);
        $inputType = array("_numinput_", "_decinput_", "_textinput_", "_emailinput_", "_passwordinput_", "_urlinput_", "_checkbox_", "_colorpicker_", "_datepicker_", "_timepicker_", "_radio_");
        $result = array();
        if (in_array($input['html_type'], $inputType)) {
            $result['object'] = 'input';
        }
        else if($input['html_type'] == '_select_'){
            $result['object'] = 'select';
            $result['type'] = '';
        }
        if ($input['html_type'] == '_emailinput_'){
            $result['type'] = 'type=email';
        }
        else if($input['html_type'] == '_radio_'){
            $result['type'] = 'type=radio';
        }
        else if (($input['html_type'] == '_textinput_') or ($input['html_type'] == '_colorpicker_')) {
            $result['type'] = 'type=text';
        }
        else if (($input['html_type'] == '_numinput_') or ($input['html_type'] == '_decinput_')) {
            $result['type'] = 'type=number';
        }
        else if ($input['html_type'] == '_passwordinput_') {
            $result['type'] = 'type=password';
        }
        else if ($input['html_type'] == '_checkbox_') {
            $result['type'] = 'type=checkbox';
        }
        else if ($input['html_type'] == '_textarea_' || $input['html_type'] == '_textareack_') {
            $result['object'] = 'textarea';
            $result['type'] = '';
        }
        $result['enabled'] = ($input['enabled'] == 0 ? ' disabled' : '');
        $result['readonly'] = ($input['readonly'] == 1 ? ' readonly' : '');
        $result['required'] = ($input['required'] == 1 ? ' required' : '');
        $result['required'] = ($input['principale'] == 1 ? ' required' : $result['required']);
        $result['ajax_check'] = ($input['ajax_check'] == 1 ? ' ajax_check="1"' : '');
        return $result;
    }

    public function JSONDecode(?string $string = NULL)
    {
        return json_decode($string?? "", true);
    }

    public function BASE64Decode(?string $string = NULL)
    {
        return base64_decode($string);
    }

    public function BASE64Encode(?string $string = NULL)
    {
        return base64_encode($string);
    }

    public function OBJtoArray($stdClassObject)
    {
        //$result = (array)$stdClassObject;
        $result = json_decode(json_encode($stdClassObject), true);
        return (array)$result;
    }

    public function OBJ_includes($string,$array)
    {
        $result = in_array($string,$array);
        return $result;
    }

    public function Array_unshift(array $array, string $string)
    {
        array_unshift($array,$string);
        return $array;
    }

    public function KEYtoArray($input)
    {
        $input = (array)$input;
        foreach ($input as $key => $value) {
            $result[] = $key;
        }
        return $result;
    }

    public function TypoArray(string $type)
    {
        $result = array();
        if(preg_match("/[()]/i", $type)) {
            $result = explode("(", $type);
            $result[1] = preg_replace("/[()]/i", "", $result[1]);
        }
        else {
            $result[0] = $type;
        }
        return $result;
    }

    public function KEYtoLabel(string $string)
    {
        $result = str_replace("MODULO_", "", $string);
        $result = str_replace("_", " ", $result);
        return ucwords($result);
    }

    public function GET_table(string $string)
    {
        $result = implode('_',explode('_', $string,-1));
        return $result;
    }

    public function JsonKeys(string $input = NULL)
    {
        if (!empty($input)) {
            $string = json_decode($input, true);
            $result = array_keys($string);
            return json_encode($result);
        }
        else { return NULL; }
    }

    public function ucfirst(string $input = NULL)
    {
        return ucfirst($input);
    }

    public function JsonTypes(string $input = NULL)
    {
        if (!empty($input)) {
            $string = json_decode($input, true);
            foreach ($string as $key => $value)
                $result[$key] = $string[$key]['type'];
            return json_encode($result);
        }
        else { return NULL; }
    }

    public function JsonKeys_whenkey(string $input = NULL)
    {
        if (!empty($input)) {
            $result = [];
            $string = json_decode($input, true);
            foreach ($string as $key => $value)
                if($string[$key]['iskey'] == 1)
                    array_push($result,$key);
            return json_encode($result);
        }
        else { return NULL; }
    }

    public function JsonTypes_whenkey(string $input = NULL)
    {
        if (!empty($input)) {
            $string = json_decode($input, true);
            foreach ($string as $key => $value)
                if($string[$key]['iskey'] == 1)
                    $result[$key] = $string[$key]['type'];
            return json_encode($result);
        }
        else { return NULL; }
    }

    public function FillsTable(string $input = NULL)
    {
        if (!empty($input)) {
            $string = json_decode($input, true);
            foreach ($string as $key => $value) {
                $result[$value['exttable']] = $key;
            }
            return $result;
        }
        else { return NULL; }
    }

    public function ToTableName(string $input)
    {
        return strtoupper(preg_replace('/\s+/', '_', $input));
    }

    public function DateFormat(string $text, string $type = 'd-m-Y')
    {
        $date = str_replace('/', '-', $text);
        $result = date($type, strtotime($date));
        $date = str_replace('-', '/', $result);
        return $date;
    }

    public function priceFormat($input)
    {
        return number_format($input, 2, '.', '');
    }

    public function endWith(string $string, string $needle)
    {
        return preg_match("#".$needle."$#",$string);
    }

    public function startWith(string $string, string $needle)
    {
        return (substr($string,0,strlen($needle)) === $needle);
    }

    public function arrayUnique(array $a)
    {
        return array_unique($a);
    }

    public function numberFormat($input)
    {
        $round = round($input,2);
        return number_format($round, 2, ',', '.');
    }
    public function intVal($val) {
        return intval($val);
    }

    /**
     * @param $json
     * @return false|string
     * Filter Primary attributes from view CATEGORY_FILTERS
     */
    public function filterPriAttr($array) {

    }
}