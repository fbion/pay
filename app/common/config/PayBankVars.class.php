<?php
/**
 * @brief 支付银行配置
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-09-21
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Config ;

class PayBankVars {

    /**
     * @brief 工商银行
     * @var int
     */
    const BANK_ICBC = 1 ;
    
    /**
     * @brief 招商银行
     * @var int
     */
    const BANK_CMB  = 2 ;
    
    /**
     * @brief 中国银行
     * @var int
     */
    const BANK_BOC  = 3 ;
    
    /**
     * @brief 建设银行
     * @var int
     */
    const BANK_CCB  = 4 ;
    
    /**
     * @brief 交通银行
     * @var int
     */
    const BANK_COMM = 5 ;
    
    /**
     * @brief 北京银行
     * @var int
     */
    const BANK_BOB = 6 ;
    
    /**
     * @brief 农业银行
     * @var int
     */
    const BANK_ABC = 7 ;
    
    /**
     * @brief 中信银行
     * @var int
     */
    const BANK_CITIC = 8 ;
    
    /**
     * @brief 民生银行
     * @var int
     */
    const BANK_CMBC = 9 ;
    
    /**
     * @brief 光大银行
     * @var int
     */
    const BANK_CEB = 10 ;
    
    /**
     * @brief 邮政
     * @var int
     */
    const BANK_POSTGC = 11 ;
    
    /**
     * @brief 兴业银行
     * @var int
     */
    const BANK_CIB = 12 ;
    
    /**
     * @brief 上海浦东发展银行
     * @var int
     */
    const BANK_SPDB = 13 ;
    
    /**
     * @brief 深圳发展银行
     * @var int
     */
    const BANK_SDB  = 14 ;
    
    /**
     * @brief 广东发展银行
     * @var int
     */
    const BANK_GDB  = 15 ;
    
    /**
     * @brief 平安银行
     * @var int
     */
    const BANK_SPA  = 16 ;

    /**
     * @brief 汇丰银行
     * @var int
     */
    const BANK_HFB = 17 ;

    /**
     * @brief 花旗银行
     * @var int
     */
    const BANK_HQB = 18 ;

    /**
     * @brief 华夏银行
     * @var int
     */
    const BANK_HXB = 19 ;

    /**
     * @brief 银行名称
     * @var array
     */
    public static $bankName = [
        self::BANK_ICBC     => '工商银行',
        self::BANK_CMB      => '招商银行',
        self::BANK_BOC      => '中国银行',
        self::BANK_CCB      => '建设银行',
        self::BANK_COMM     => '交通银行',
        self::BANK_BOB      => '北京银行',
        self::BANK_ABC      => '农业银行',
        self::BANK_CITIC    => '中信银行',
        self::BANK_CMBC     => '民生银行',
        self::BANK_CEB      => '光大银行',
        self::BANK_POSTGC   => '邮政储蓄',
        self::BANK_CIB      => '兴业银行',
        self::BANK_SPDB     => '上海浦东发展银行',
        self::BANK_SDB      => '深圳发展银行',
        self::BANK_GDB      => '广东发展银行',
        self::BANK_SPA      => '平安银行',
        self::BANK_HFB      => '汇丰银行',
        self::BANK_HQB      => '花旗银行',
        self::BANK_HXB      => '华夏银行',
    ] ;

    
    public static $yeepayBankAlis = [
        self::BANK_ICBC     => 'ICBC',
        self::BANK_CMB      => 'CMBCHINA',
        self::BANK_BOC      => 'BOC',
        self::BANK_CCB      => 'CCB',
        self::BANK_COMM     => 'BOCO',
        self::BANK_BOB      => 'BCCB',
        self::BANK_ABC      => 'ABC',
        self::BANK_CITIC    => 'ECITIC',
        self::BANK_CMBC     => 'CMBC',
        self::BANK_CEB      => 'CEB',
        self::BANK_POSTGC   => 'POST',
        self::BANK_CIB      => 'CIB',
        self::BANK_SPDB     => 'SPDB',
        self::BANK_SDB      => 'SDB',
        self::BANK_GDB      => 'CGB',
        self::BANK_SPA      => 'SZCB',
        self::BANK_HFB      => 'HFB',
        self::BANK_HQB      => 'HQYH',
        self::BANK_HXB      => 'HXB',
    ] ;
    
}
