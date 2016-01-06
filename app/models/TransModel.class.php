<?php
/**
 * @brief 提现(转账)模型
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-07-16
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Model ;

class TransModel extends BaseModel {

    protected $table = 'pay_trans_order' ;

    public function account() {
        return $this->belongsTo('Pay\Model\AccountModel', 'account_id') ;
    }

}
