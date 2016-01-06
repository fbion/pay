<?php
/**
 * @brief 支付业务处理基类
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */
namespace Pay\Bll ;

abstract class BaseBiz {

    protected static $instance ;

    protected function __construct() {
    }

    private function __clone() {
    }

    final public static function &getInstance() {
        if(!static::$instance || !(static::$instance instanceof self)){
            static::$instance = new static() ;
        }
        return static::$instance ;
    }
    
    public function __call($name, $arguments) {
    }

}
