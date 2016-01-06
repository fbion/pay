<?php
/**
 * @brief 支付方式基类
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Gateway ;

use Pay\Common\Excep\PayException ;
use Pay\Common\Config\ErrCode ;

abstract class BaseGateway implements Interfaces\BaseGatewayInterface {

    const KEY = 'f0e83eeb16ae01ebd7d54fa9e6ada27e' ;

    protected $configs = array() ;
    private static $_returnSignParamsArr = array('gateway', 'channel', 'mer_recharge_no', 'plat', 'timestamp') ;

    public function getConfig($order) {
        do {
            if (isset($order['seller_partner'])) {
                $config = $this->getConfigByPartner($order['channel'], $order['seller_partner']) ;
                if ($config) break ;
            }
            $config = $this->getConfigByIndex($order['channel']) ; 
        } while (0) ;

        return $config ;
    }

    public function getConfigByIndex($channel, $index=-1) {
        if (!array_key_exists($channel, $this->configs)) {
            throw new PayException(ErrCode::ERR_PARAM, '错误的channel参数!') ;
        }
        $configs = $this->configs[$channel] ;
        if ($index == -1) {
            $index = count($configs) - 1 ;
        }
        return $configs[$index] ;
    }

    public function getConfigByPartner($channel, $partner) {
        if (!array_key_exists($channel, $this->configs)) {
            throw new PayException(ErrCode::ERR_PARAM, '错误的channel参数!') ;
        }
        $configs = $this->configs[$channel] ;
        foreach ($configs as $config) {
            if ($config['partner'] == $partner) {
                return $config ;
            }            
        }
        return false ;
    }
    

    public function direct($order) {
        switch ($order['plat']) {
            case 1 :
                return $this->pcDirect($order) ;
                break ;
            case 2 :
                return $this->mDirect($order) ;
                break ;
            case 3 :
                return $this->appDirect($order) ;
                break ;
            default :
                throw new PayException(ErrCode::ERR_PARAM, 'plat平台号错误') ;
        }
    }

    public function makeReturnUri($arr) {
        $arr = $this->argSort($this->paraFilter($arr)) ;
        $newArr = array() ;
        $randArr = array_flip(range('a', 't')) ;
        while (list($key, $val) = each($arr)) {
            $newArr[$key] = array_rand($randArr) . $val ; 
        }
        $newArr['sign'] = $this->makeSign($newArr) ;
        return implode('/', $newArr) ;
    }

    public function checkSign($array) {
        $sign = $array['sign'] ;
        $array = $this->argSort($this->paraFilter($array)) ;
        return $sign == $this->makeSign($array) ? true : false ;
    }

    protected function paraFilter($params) {
        $paraFilter = array() ;
        while (list($key, $val) = each($params)) {
            if (!in_array($key, self::$_returnSignParamsArr) || strlen($val) == 0) continue ;
            $paraFilter[$key] = $params[$key] ;
        }   
        return $paraFilter ;
    }

    protected function argSort($para) {
        ksort($para) ;
        reset($para) ;
        return $para ;
    }

    protected function makeSign($array) {
        $arg = '' ;
        while (list($key, $val) = each($array)) {
            $arg .= $key . '=' . $val . '&' ;
        }   
        $arg = substr($arg, 0, count($arg) - 2) . self::KEY ;
        $sign = strtolower(md5($arg)) ;
        return $sign ;
    }

} 
