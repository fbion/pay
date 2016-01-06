<?php
/**
 * @brief 支付第三方同步返回处理
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Model\RechargeModel ;
use Pay\Model\ConsumeModel ;

class SerReturnBiz extends BaseBiz {

	protected static $instance ;

    public function payReturn($fields) {
        $ret = new \stdClass() ;

        $rechargeOrder = RechargeModel::getRechargeOrderByMerNo($fields['mer_recharge_no']) ;
        if (!$rechargeOrder) {
            $ret->is_success = 'F' ;
            $ret->code = 2 ;
            $ret->error = '充值订单不存在' ;
            return $ret ;
        }

        $consumeId = $rechargeOrder['consume_id'] ;
        $params = new \stdClass() ;

        /// 消费
        if ($consumeId) { 
            $consumeOrder = ConsumeModel::find($consumeId) ;
            $returnUrl = $consumeOrder['return_url'] ? $consumeOrder['return_url'] : $rechargeOrder['return_url'] ;
            $params->is_success = $consumeOrder['status'] == ConsumeModel::STATUS_SUCCESS ? 'T' : 'F' ;
            $params->busi_consume_no = $consumeOrder['busi_consume_no'] ;
            $params->pay_consume_id = $consumeOrder['id'] ;
            $params->recharge_amount = $consumeOrder['recharge_amount'] ;
            $params->consume_amount = $consumeOrder['consume_amount'] ;
            $params->channel = $consumeOrder['channel'] ;
            $params->user_id = $consumeOrder['user_id'] ;
        /// 充值
        } else {
            $returnUrl = $rechargeOrder['return_url'] ;
            $params->is_success = $rechargeOrder['status'] == RechargeModel::STATUS_SUCCESS ? 'T' : 'F' ;
            $params->error = $rechargeOrder['status'] == RechargeModel::STATUS_SUCCESS ? '' : '充值未成功:' . $rechargeOrder['status'] ;
            $params->pay_recharge_id = $rechargeOrder['id'] ;
            $params->mer_recharge_no = $rechargeOrder['mer_recharge_no'] ;
            $params->busi_recharge_no = $rechargeOrder['busi_recharge_no'] ;
            $params->channel = $rechargeOrder['channel'] ;
            $params->user_id = $rechargeOrder['user_id'] ;
            $params->recharge_amount = $rechargeOrder['recharge_amount'] ;
        }
        $ret->is_success = 'T' ;
        $ret->return_url = $returnUrl ;
        $ret->data = $params ;
        return $ret ;
    }
}