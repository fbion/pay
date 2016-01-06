<?php
/**
 * @brief 支付充值模型
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Model ;

class RechargeModel extends BaseModel {

    protected $table = 'pay_recharge_order' ;

    public function account() {
        return $this->belongsTo('Pay\Model\AccountModel', 'account_id') ;
    }

    public static function getRechargeOrderByMerNo($merNo) {
        return self::where('mer_recharge_no', '=', $merNo)->first() ;
    }
}
