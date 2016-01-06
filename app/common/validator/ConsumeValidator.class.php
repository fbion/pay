<?php
/**
 * @brief 消费验证
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Validator ;

class ConsumeValidator extends BaseValidator {

    public static $directConsumeRule = array(
        'user_id'               => 'required|max:20', 
        'gateway'               => 'required|integer', 
        'busi_consume_no'       => 'required|max:50', 
        'consume_amount'        => 'required|integer|min:1|max:999999999', 
        'subject'               => 'required|max:100', 
        'callback_url'          => 'required|max:255', 
        'return_url'            => 'required|max:255', 

        'recharge_amount'       => 'integer|min:1|max:999999999', 
        'plat'                  => 'integer|between:1,3', 
        //'expire_time'           => 'date', 
        'goods_show_url'        => 'max:255', 
    ) ;
}
