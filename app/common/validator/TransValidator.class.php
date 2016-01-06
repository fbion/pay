<?php
/**
 * @brief 提现(转账)验证
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Validator ;

class TransValidator extends BaseValidator {

    public static $transRule = [
        'gateway'               => 'required|integer', 
        'subject'               => 'required|max:100', 
        'callback_url'          => 'required|max:255', 
        'request_data'          => 'required'
    ] ;

    public static $transDetailRule = [
        'user_id'               => 'required|max:20', 
        'busi_trans_no'         => 'required|max:50', 
        'trans_amount'          => 'required|integer|min:1|max:999999999', 
        'user_name'             => 'required|max:50', 
        'user_account'          => 'required|max:100', 
    ] ;
}
