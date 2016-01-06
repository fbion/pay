<?php
/**
 * @brief 支付模型基类
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Model ;

abstract class BaseModel extends \Eloquent {

    const STATUS_DEFAULT = 1 ;
    const STATUS_SUCCESS = 2 ;
    const STATUS_FAIL = 3 ;

    const DEAL_RECHARGE = 1 ;
    const DEAL_CONSUME = 2 ;
    const DEAL_IN_REFUND = 3 ;
    const DEAL_OUT_REFUND = 4 ;

    protected $primaryKey = 'id' ;
    public $incrementing = false ;
    public $timestamps = false ;

    public function calculPrimaryId() {
        return date('YmdHisB') . mt_rand(100, 999) ;
    }

    /// 多where
    public function multiWhere($query, $arr) {
        if (!is_array($arr)) return $query ;
        foreach ($arr as $key => $value) {
            $query = $query->where($key, $value) ;
        }
        return $query ;
    }
}
