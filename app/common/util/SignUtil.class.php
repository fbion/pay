<?php
/**
 * @brief 签名Util
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Util ;

use Pay\Common\Config\PayVars ;

class SignUtil extends BaseUtil {

    public static function checkSign(Array $params) {
        $sign = $params['sign'] ;
        return $sign == self::makeSign($params, true) ? true : false ;
    }

    public static function paraFilter(Array $params) {
        $paraFilter = array() ;
        while (list($key, $val) = each($params)) {
            if (strlen($val) == 0 || $key == 'sign') continue ;
            $paraFilter[$key] = $params[$key] ;
        }   
        return $paraFilter ;
    }

    public static function argSort(Array $params) {
        ksort($params) ;
        reset($params) ;
        return $params ;
    }

    public static function makeSign(Array $params, $urlencode=true) {
        $params = self::argSort(self::paraFilter($params)) ;
        $arg = '' ;
        while (list($key, $val) = each($params)) {
            if ($urlencode) $val = urlencode($val) ;
            $arg .= $key . '=' . $val . '&' ;
        }
        $arg .= 'key=' . PayVars::$channels[$params['channel']]['security_code'] ; 
        $sign = strtolower(md5($arg)) ;
        return $sign ;
    }

} 
