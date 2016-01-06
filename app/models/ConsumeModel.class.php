<?php
/**
 * @brief 支付消费模型
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Model ;

class ConsumeModel extends BaseModel {

    protected $table = 'pay_consume_order' ;

    public function account() {
        return $this->belongsTo('Pay\Model\AccountModel', 'account_id') ;
    }
}
