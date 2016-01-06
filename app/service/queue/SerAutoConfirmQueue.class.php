<?php
/**
 * @brief 支付第三方支付平台相关队列
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-27
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Service\Queue ;

use Pay\Common\Config\PayVars ;
use Pay\Common\Util\ChannelUtil ;
use Pay\Common\Util\GatewayUtil ;
use Pay\Bll\RechargeBiz ;
use Pay\Bll\ConsumeBiz ;
use Pay\Bll\RefundBiz ;
use Pay\Bll\SerNotifyBiz ;
use Pay\Model\RechargeModel ;
use Pay\Model\ConsumeModel ;
use Pay\Model\RefundModel ;

class SerAutoConfirmQueue extends BaseQueue {

    public function fire($job, $data) {
        $errCode = 0 ;
        $rechargeOrder = RechargeModel::find($data['id']) ;

        do {
            if ($rechargeOrder->status == RechargeModel::STATUS_SUCCESS) {
                $errCode = 1 ;
                break ;
            }
            if (!$rechargeOrder->gateway || !$rechargeOrder->channel) {
                 $errCode = 2 ;
                 break ;
            }
            
            $channel = ChannelUtil::calculateAccountChannel($rechargeOrder->channel)  ;
            if (false === $channel) {
                 $errCode = 3 ;
                 break ;
            }
            $gatewayObj = gatewayUtil::getGatewayObj($rechargeOrder->gateway) ;
            if (!$gatewayObj) {
                 $errCode = 4 ;
                 break ;
            }
        } while (0) ;

        if ($errCode == 0) {
            $orderData = $rechargeOrder->toArray() ;
            $consumeId = $orderData['consume_id']  ;
            $queryParams = array(
                'channel' => $channel,
                'gateway' => $orderData['gateway'],
                'mer_recharge_no' => $orderData['mer_recharge_no'], 
            ) ;
            $queryResult = $gatewayObj->directQuery($queryParams) ;
            if ($queryResult->is_success == 'T' && $queryResult->notify_status == 2) {
                $realAmount = intval($queryResult->amount) ;
                $moreInfo  = array(
                    'ser_recharge_no'   => $queryResult->ser_recharge_no,
                    'gateway_account'   => $queryResult->gateway_account,
                    'seller_partner'    => $queryResult->seller_partner,
                    'pay_time'          => $queryResult->pay_time,
                ) ;
                $ret = RechargeBiz::getInstance()->confirmRechargeOrderById($orderData['id'], $realAmount, $moreInfo) ;
                if ($ret == true && $consumeId > 0) {
                    $ret2 = ConsumeBiz::getInstance()->confirmConsumeOrderById($consumeId) ;
                }
            }
            if ($rechargeOrder->status == RechargeModel::STATUS_SUCCESS || $rechargeOrder->create_time <= time() - 20 * 60) {
                $job->delete() ;
            } else {
                $job->release(60) ;
            }
        } else {
            $job->delete() ;
        }
    }

    public function serRefund($job, $data) {
        $errCode = 0 ;
        $refundOrder = RefundModel::find($data['id']) ;
        $rechargeOrder = RechargeModel::find($refundOrder->recharge_id) ;

        do {
            if ($rechargeOrder->status != RechargeModel::STATUS_SUCCESS || $refundOrder->ser_notify_status != RefundModel::STATUS_DEFAULT) {
                $errCode = 1 ;
                break ;
            }
            if (!$refundOrder->gateway || !$refundOrder->channel) {
                 $errCode = 2 ;
                 break ;
            }
            
            $channel = ChannelUtil::calculateAccountChannel($refundOrder->channel)  ;
            if (false === $channel) {
                 $errCode = 3 ;
                 break ;
            }
            $gatewayObj = gatewayUtil::getGatewayObj($rechargeOrder->gateway) ;
            if (!$gatewayObj) {
                 $errCode = 4 ;
                 break ;
            }
            $orderData = $refundOrder->toArray() ;
            $queryParams = array(
                'ser_recharge_no'   => $rechargeOrder->ser_recharge_no, 
                'channel'           => $channel,
                'seller_partner'    => $orderData['seller_partner'],
            ) ;
            $queryResult = $gatewayObj->refundQuery($queryParams) ;
            if ($queryResult->is_success == 'T') {
                /// 第三方已经退过款
                if ($queryResult->is_refund == true) {
                    $errCode = 5 ;
                    break ;
                } 
            }
        } while (0) ;

        if ($errCode == 0) {
            $notifyUrl = PayVars::getNotifyUrl() . "notify/refund/{$channel}/{$rechargeOrder->gateway}" ;
            $order = array(
                'channel'           => $channel,
                'gateway'           => $rechargeOrder->gateway,
                'create_time'       => $orderData['create_time'],
                'mer_refund_no'     => $orderData['mer_refund_no'],
                'notify_url'        => $notifyUrl,
                'ser_recharge_no'   => $rechargeOrder->ser_recharge_no,
                'recharge_amount'   => $rechargeOrder->recharge_amount,
                'refund_amount'     => $orderData['amount'],
                'subject'           => $orderData['subject'],
                'mer_recharge_no'   => $rechargeOrder->mer_recharge_no,
                'seller_partner'    => $rechargeOrder->seller_partner,
            );  
            $ret = $gatewayObj->refund($order) ;
            if ($ret->is_success == 'F') {
                $data = array(
                    'ser_notify_status' => 3,
                    'ser_notify_log'    => $ret->error,
                    'ser_notify_time'   => time(),
                ); 
                RefundBiz::getInstance()->confirmRefundOrderById($refundOrder->id, $data) ;
            }
            $job->release(60 * 5) ;
        } else {
            $job->delete() ;
        }
    }

    public function seConfirmTrans($job, $data) {
        $errCode = 0 ;
        $transOrder = TransModel::find($data['id'])->first() ;

        do {
            if (!$transOrder || $transOrder->status != TransModel::STATUS_DEFAULT) {
                $errCode = 1 ;
                break ;
            }
            if (!$transOrder->gateway || !$transOrder->channel) {
                 $errCode = 2 ;
                 break ;
            }
            
            
            if (false === ($channel = ChannelUtil::calculateAccountChannel($transOrder->channel))) {
                 $errCode = 3 ;
                 break ;
            }
            $gatewayObj = gatewayUtil::getGatewayObj($transOrder->gateway) ;
            if (!$gatewayObj) {
                 $errCode = 4 ;
                 break ;
            }
            if (!method_exists($gatewayObj, 'transQuery')) {
                $errCode = 5 ;
                break ;
            }
        } while (0) ;

        if ($errCode == 0) {
            $order = [
                'channel'           => $channel,
                'gateway'           => $transOrder->gateway,
                'batch_no'          => $transOrder->batch_no,
                'mer_trans_no'      => $transOrder->mer_trans_no,
            ] ;
            $notice = $gatewayObj->transQuery($order) ;
            $ret = SerNotifyBiz::getInstance()->payNotify($notice, 'trans') ;  
            if ($ret->is_success == 'T') {
                $job->delete() ;
            } else {
                $job->release(60 * 60) ;
            }
        } else {
            $job->delete() ;
        }
    }
}
