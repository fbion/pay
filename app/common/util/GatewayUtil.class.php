<?php
/**
 * @brief 支付方式Util
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Util ;

use Pay\Common\Config\PayVars ;
use Pay\Common\Excep\PayException ;
use Pay\Common\Config\ErrCode ;

class GatewayUtil extends BaseUtil {

    public static function checkGateway($gateway) {
        return isset(PayVars::$gateways) && isset(PayVars::$gateways[$gateway]) 
            ? PayVars::$gateways[$gateway]['code'] : false ;
    }

    public static function getGatewayObj($gateway) {
        static $gatewayObjs = array() ;
        if (!isset($gatewayObjs[$gateway])) {
            $gatewayName = self::checkGateway($gateway) ;
            if (!$gatewayName) {
                throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '错误的支付方式') ;
            }
            $className = "\Pay\Common\Gateway\\" . ucfirst($gatewayName) . 'Gateway' ;
            $gatewayObjs[$gateway] = new $className() ;
        }
        return $gatewayObjs[$gateway] ;
    } 
}
