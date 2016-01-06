<?php
/**
 * @brief 公共Util
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Util ;

class CommonUtil extends BaseUtil {

    public static function longId() {
        return date('YmdHisB') . mt_rand(100, 999) ;
    }

    public static function LongIdIncrease(&$longId) {
        $newStr = substr($longId, 0, strlen($longId) - 2) ;
        $end = (int) substr($longId, strlen($longId) - 2) ;
        $end++ ;
        $endStr = (string) $end ;
        if (strlen($endStr) == 1) {
            $longId =  $newStr . '0' . $endStr ;
        } elseif (strlen($endStr) == 3) {
            $longId = self::LongIdIncrease($newStr) . '00' ;
        } else {
            $longId = $newStr . $endStr ;
        }
        return $longId ;
    }

    public static function urlencodeSpec($content, $specChars = "^|$#") {
        $newContent = '';
        for($i = 0; $i < strlen($content); $i++) {
            $ch = $content{$i};
            if (strstr($specChars, $ch)) {
                $newContent .= urlencode($ch);
            } else {
                $newContent .= $ch;
            }
        }
        return $newContent;
    }

    public static function getRequestParam($name, $defaultValue='', $source='GP') {
        $value = null ;
        for($i = 0 ; $i < strlen($source) ; $i++) {
            $ch = $source{$i} ;
            if ($ch == 'G') {
                $value = array_key_exists($name, $_GET) ? $_GET[$name] : null ;
            } elseif ($ch = 'P') {
                $value = array_key_exists($name, $_POST) ? $_POST[$name] : null ;
            } elseif ($ch = 'C') {
                $value = array_key_exists($name, $_COOKIE) ? $_COOKIE[$name] : null ;
            }
            if ($value !== null) {
                break ;
            }
        }
        return $value !== null ? $value : $defaultValue;
    }

    public static function arrayToXml($arr) {
        $xml = "<xml>" ;
        foreach ($arr as $key=>$val) {
            if (is_numeric($val)) {
                 $xml .= "<" . $key . ">" . $val . "</" . $key . ">" ;
            } else {
                 $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">" ;
            }
        }
        $xml .= "</xml>" ;
        return $xml ; 
    }

    public static function xmlToArray($xml) {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true) ;
        return $array_data ;
    }
}
