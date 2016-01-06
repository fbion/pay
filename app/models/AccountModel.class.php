<?php
/**
 * @brief 支付账户模型
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Model ;

class AccountModel extends BaseModel {

    protected $table = 'pay_user_account' ;

    public function rechargeOrders() {
        return $this->hasMany('Pay\Model\RechargeModel', 'account_id') ;
    }
    
    public function consumeOrders() {
        return $this->hasMany('Pay\Model\ConsumeModel', 'account_id') ;
    }

    public function refundOrders() {
        return $this->hasMany('Pay\Model\RefundModel', 'account_id') ;
    }
}
