<?php
/**
 * @brief 退款验证
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Validator ;

class RefundValidator extends BaseValidator {

    public static $refundRule = array(
        'pay_consume_id'        => 'required|max:20', 
        'user_id'               => 'required|max:20', 
        'refund_amount'         => 'required|integer|min:1|max:999999999', 
        //'subject'               => 'required|max:100', 
        'callback_url'          => 'required|max:255', 
        //'return_url'            => 'required|max:255', 

        //'body'                  => 'integer|between:1,3', 
    ) ;
}
