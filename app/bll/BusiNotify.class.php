<?php
/**
 * @brief 支付回调业务线
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Common\Util\HttpUtil ;
use Pay\Common\Util\SignUtil ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Model\BaseModel ;
use Pay\Model\RechargeModel ;
use Pay\Model\ConsumeModel ;
use Pay\Model\RefundModel ;
use Pay\Model\TransModel ;

class BusiNotifyBiz extends BaseBiz {

    protected static $instance ;

    public function notifyCallback(BaseModel $order, $notifyType) {
        $params = new \stdClass() ;
        if (!$order) {
            throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
        } elseif ($order->callback_url == '') {
            throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS, '订单callback_url不存在!') ;
        } 
        $params->channel = $order->channel ;
        $params->user_id = $order->user_id ;

        switch ($notifyType) {
            case 'direct' :
                $params->is_success = $order->status == RechargeModel::STATUS_SUCCESS ? 'T' : 'F' ; 
                $params->error = $order->status == RechargeModel::STATUS_SUCCESS ? '' : ('充值失败:' . $order->status) ;
                $params->pay_recharge_id = $order->id ;
                $params->mer_recharge_no = $order->mer_recharge_no ;
                $params->ser_recharge_no = $order->ser_recharge_no ;
                $params->busi_recharge_no = $order->busi_recharge_no ;
                $params->recharge_amount = $order->recharge_amount ;
                $params->geteway = $order->gateway ;
                $params->gateway_account = $order->gateway_account  ;
                break ;

            case 'consume' :
                $params->is_success = $order->status == RechargeModel::STATUS_SUCCESS ? 'T' : 'F' ; 
                $params->error = $order->status == RechargeModel::STATUS_SUCCESS ? '' : ('消费失败:' . $order->status) ;
                $params->pay_consume_id = $order->id ;
                $params->busi_consume_no = $order->busi_consume_no ;
                $params->recharge_amount = $order->recharge_amount ;
                $params->consume_amount = $order->consume_amount ;
                $rechargeOrder = RechargeModel::find($order->recharge_id) ;
                if ($rechargeOrder) {
                    $params->pay_recharge_id = $rechargeOrder->id ;
                    $params->recharge_amount = $rechargeOrder->recharge_amount ;
                    $params->geteway = $rechargeOrder->gateway ;
                }
                break ;
            case 'refund' :
                $params->is_success = $order->ser_notify_status == RefundModel::STATUS_SUCCESS ? 'T' : 'F' ; 
                $params->error = $order->ser_notify_status == RefundModel::STATUS_SUCCESS ? '' : ('退款失败:' . $order->ser_notify_log) ;
                $params->pay_refund_id = $order->id ;
                $params->busi_refund_no = $order->busi_refund_no ;
                $params->refund_amount = $order->amount ;
                $params->gateway = $order->gateway ;
                $params->refund_time = $order->refund_time ;
                $rechargeOrder = RechargeModel::find($order->recharge_id) ;
                if ($rechargeOrder) {
                    $params->mer_recharge_no = $rechargeOrder->mer_recharge_no ;
                    $params->recharge_amount = $rechargeOrder->recharge_amount ;
                }
                break ;
            case 'trans' :
                $params->is_success = $order->ser_notify_status == TransModel::STATUS_SUCCESS ? 'T' : 'F' ; 
                $params->error = $order->ser_notify_status == TransModel::STATUS_SUCCESS ? '' : ('提现失败:' . $order->ser_notify_log) ;
                $params->gateway = $order->gateway ;
                $params->pay_trans_id = $order->id ;
                $params->busi_trans_no = $order->busi_trans_no ;
                $params->trans_amount = $order->trans_amount ;
                $params->trans_time = $order->pay_time ;
                break ;
            default :
                throw new PayException(ErrCode::ERR_REQUEST_METHOD, "notifyFail. 未知通知类型:{$notifyType}") ;
                break ;
        }

        $params->timestamp = time() ;
        $params->sign = SignUtil::makeSign(get_object_vars($params)) ;
        $ret = $this->_sendCallback($order->callback_url, $params) ;

        $order->callback_count++ ;
        $order->callback_time = time() ;
        $order->callback_status = $ret == 'succeed' ? 2 : 1 ;
        $order->save() ;
        return $ret == 'succeed' ? true : false ;
    }

    private function _sendCallback($callbackUrl, $callbackData, $timeOut=3) {
        try {
            $callbackData = json_encode($callbackData) ;
            $ret = HttpUtil::socketPostJson($callbackUrl, $callbackData, $timeOut * 1000) ;
            return $ret ;
        } catch (\Exception $e) {
            return $e->getMessage() ;
        }
    }

}
