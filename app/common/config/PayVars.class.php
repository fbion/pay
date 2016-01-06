<?php
/**
 * @brief 支付基本配置
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Config ;

require_once HBC_CONF . '/InterfaceConfig.class.php' ;

class PayVars {

    const GATEWAY_ALIPAY = 1 ;
    const GATEWAY_WECHAT = 2 ;
    const GATEWAY_YEEPAY = 3 ;

    public static $gateways = array(
        self::GATEWAY_ALIPAY => array(
            'code' => 'alipay',
            'name' => '支付宝'
        ),
        self::GATEWAY_WECHAT => array(
            'code' => 'wechatpay',
            'name' => '微信支付'
        ),
        self::GATEWAY_YEEPAY => array(
            'code' => 'yeepay',
            'name' => '易宝支付'
        ),
    ) ;

    public static $channels = array(
        1 => array(
            'code' => 'recharge',
            'name' => '充值',
            'acc_channel' => 1,
            'security_code' => 'f577c26020ccb48f0733f5c7bde91b88',
        ),
        2 => array(
            'code' => 'C_consume',
            'name' => 'C消费',
            'acc_channel' => 1,
            'security_code' => '1142d14ddb4459b7c19d2c2cae2a3977',
        ),
        3 => array(
            'code' => 'GDS_consume',
            'name' => 'GDS消费',
            'acc_channel' => 1,
            'security_code' => '358ef17dfa967d3c2fc431d160ed37c0',
        ),
    );

    public static function getReturnUrl() {
        return \InterfaceConfig::PAY_CENTER_URL ? \InterfaceConfig::PAY_CENTER_URL : 'http://pay.huangbaoche.com/' ;
    }

    public static function getNotifyUrl() {
        return \InterfaceConfig::PAY_CENTER_URL ? \InterfaceConfig::PAY_CENTER_URL : 'http://pay.huangbaoche.com/' ;
    }

}
