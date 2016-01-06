<?php
/**
 * @brief 支付退款模型
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-07-08
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Model ;

class RefundModel extends BaseModel {

    protected $table = 'pay_refund_order' ;

    public function account() {
        return $this->belongsTo('Pay\Model\AccountModel', 'account_id') ;
    }

    public function rechargeOrder() {
        return $this->hasOne('Pay\Model\RechargeModel', 'recharge_id') ;
    }
}
