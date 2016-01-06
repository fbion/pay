<?php
/**
 * @brief 支付错误配置
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Config ;

class ErrCode {

    //系统 -1000 ~ -1099
    const ERR_SUCCESS                   = 0 ;
    const ERR_SYSTEM                    = 1000 ;
    const ERR_REQUEST_METHOD            = 1001 ;
    const ERR_URI_NOT_FOUND             = 1002 ;
    const ERR_PARAM                     = 1003 ;
    const ERR_NOT_FOUND                 = 1004 ;
    const ERR_NOT_MODIFIED              = 1005 ;
    const ERR_NOT_COMPLETE              = 1006 ;
    const ERR_SIGN_ERROR                = 1007 ; 
    
    const ERR_ACCOUNT_NO_EXISTS         = 2001 ;
    const ERR_ORDER_NO_EXISTS           = 2002 ;
    const ERR_ORDER_STATUS              = 2003 ;
    const ERR_ORDER_USER_ID             = 2004 ;
    const ERR_ORDER_CREATE_FAIL         = 2005 ;
    const ERR_GATEWAY_FAIL              = 2006 ;
    const ERR_BALANCE_ENOUGH            = 2007 ;
    const ERR_DEAL_TYPE_EXISTS          = 2008 ;
    const ERR_REFUND                    = 2009 ;

    //错误信息
    public static $errMsg = array(
        //系统
        self::ERR_SUCCESS               => '成功',
        self::ERR_SYSTEM                => '系统错误',
        self::ERR_REQUEST_METHOD        => '不允许的请求方式',
        self::ERR_URI_NOT_FOUND         => '资源不存在',
        self::ERR_PARAM                 => '参数错误',
        self::ERR_NOT_FOUND             => '未找到',
        self::ERR_NOT_MODIFIED          => '未修改',
        self::ERR_NOT_COMPLETE          => '未完成',
        self::ERR_SIGN_ERROR            => 'sign验证错误',

        self::ERR_ACCOUNT_NO_EXISTS     => '账户不存在',
        self::ERR_ORDER_NO_EXISTS       => '订单不存在',
        self::ERR_ORDER_STATUS          => '订单状态不正确',
        self::ERR_ORDER_USER_ID         => '用户id与订单不符',
        self::ERR_ORDER_CREATE_FAIL     => '订单创建失败',
        self::ERR_GATEWAY_FAIL          => '第三方支付平台错误',
        self::ERR_BALANCE_ENOUGH        => '账户余额不足',
        self::ERR_DEAL_TYPE_EXISTS      => '账户变化类型不存在',
        self::ERR_REFUND                => '退款错误',

    ) ;
}
