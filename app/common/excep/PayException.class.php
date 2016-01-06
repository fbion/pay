<?php
/**
 * @brief 支付异常处理类
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Excep ;

use Pay\Common\Config\ErrCode ;

class PayException extends \Exception {

    public function __construct($code, $message='') {
        if (!isset(ErrCode::$errMsg[$code])) {
            $code = ErrCode::ERR_SYSTEM ;
        }
        $message = empty($message) ? ErrCode::$errMsg[$code] : $message ;
        parent::__construct($message, $code) ;
    }
}
