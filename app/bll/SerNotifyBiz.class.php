<?php
/**
 * @brief 支付第三方异步回调处理
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Model\RechargeModel ;
use Pay\Model\ConsumeModel ;
use Pay\Model\RefundModel ;
use Pay\Model\TransModel ;
use Pay\Model\AccountModel ;

class SerNotifyBiz extends BaseBiz {

    protected static $instance ;

    public function payNotify($notice, $noticeType) {
        switch ($noticeType) {
            case 'refund' : 
                return $this->_refundNotify($notice) ;
                break ;
            case 'trans' :
                return $this->_transNotify($notice) ;
                break ;
            default :
                return $this->_rechargeNotify($notice) ;
                break ;
        }
    }

    private function _rechargeNotify($notice) {
        $errCode = 0 ;
        $errMsg = '' ;
        do {
            $rechargeOrder = RechargeModel::where('mer_recharge_no', $notice['mer_recharge_no'])->first() ;
            if (!$rechargeOrder) {
                $errCode = 2 ;
                $errMsg = '充值订单不存在' . $notice['mer_recharge_no'] ;
                break ;
            }

            $rechargeId = $rechargeOrder['id'] ;
            $consumeId = $rechargeOrder['consume_id'] ;

            if ($consumeId) {
                $consumeOrder = ConsumeModel::find($consumeId) ;
            }

            $amount = false ;
            if (isset($notice['amount'])) {
                $amount = $notice['amount'] ;
            }

            /// 需要更新的其他信息
            $moreInfo = [
                'ser_recharge_no'   => $notice['ser_recharge_no'],
                'gateway_account'   => $notice['gateway_account'],
                'seller_partner'    => $notice['seller_partner'],
                'mer_recharge_no'   => $notice['mer_recharge_no'],
                'pay_time'          => $notice['pay_time'],
                'notify_log'        => $notice['notify_log'],
            ] ;

            try {
                if ($rechargeOrder['status'] != RechargeModel::STATUS_SUCCESS) {
                    $res = RechargeBiz::getInstance()->confirmRechargeOrderById($rechargeId, $amount, $moreInfo) ;
                    if (false === $res && empty($consumeOrder)) {
                        $errCode = 3 ;
                        $errMsg = '确认充值订单失败' ;
                        break ;
                    }
                }
                if (!empty($consumeOrder) && $consumeOrder['status'] != ConsumeModel::STATUS_SUCCESS) {
                    $res = ConsumeBiz::getInstance()->confirmConsumeOrderById($consumeOrder['id']) ;
                    if (false === $res) {
                        $errCode = 3 ;
                        $errMsg = '确认消费订单失败' ;
                        break ;
                    }
                }
            } catch (\Exception $e) {
                $errCode = 99 ;
                $errMsg = $e->getMessage() ;
                break ;
            }
        } while (0) ;

        $ret = new \stdClass() ;
        $ret->is_success = $errCode === 0 ? 'T' : 'F' ;
        $ret->code = $errCode ;
        $ret->output = $notice['output'] ;
        $ret->error = $errMsg ;
        return $ret ;
    } 

    private function _refundNotify($notice) {
        $errCode = 0 ;
        $errMsg = '' ;

        do {
            try {
                $refundOrder = RefundModel::where('mer_refund_no', $notice['mer_refund_no'])->first() ;
                if (!$refundOrder) {
                    $errCode = 2 ;
                    $errMsg = '退款订单不存在' . $notice['mer_refund_no'] ;
                    break ;
                }
                $rechargeOrder = RechargeModel::find($refundOrder->recharge_id) ;
                if (!$rechargeOrder) {
                    $errCode = 2 ;
                    $errMsg = '充值订单不存在' . $refundOrder->recharge_id ;
                    break ;
                }
                if ($rechargeOrder->status != RechargeModel::STATUS_SUCCESS) {
                    $errCode = 3 ;
                    $errMsg = '订单状态不正确' ;
                    break ;
                }
                $upInfo = [
                    'refund_time'   => $notice['refund_time'],
                    'notify_time'   => $notice['notify_time'],
                    'ser_refund_no' => $notice['ser_refund_no'],
                    'notify_status' => $notice['notify_status'],
                    'notify_log'    => $notice['notify_log'],
                ] ;
                if (true !== RefundBiz::getInstance()->confirmrefundOrderById($refundOrder->id, $upInfo)) {
                    $errCode = 3 ;
                    $errMsg = '确认退款订单失败' ;
                    break ;
                } 
            } catch (\Exception $e) {
                $errCode = 99 ;
                $errMsg = $e->getMessage() ;
                break ;
            }
        } while (0) ;

        $ret = new \stdClass() ;
        $ret->is_success = $errCode === 0 ? 'T' : 'F' ;
        $ret->code = $errCode ;
        $ret->output = $notice['output'] ;
        $ret->error = $errMsg ;
        return $ret ;
    }

    private function _transNotify($notice) {
        $errCode = 0 ;
        $errMsg = '' ;

        foreach ($notice['details'] as $detail) {
            try {
                $transOrder = TransModel::where('mer_trans_no', $detail['mer_trans_no'])->first() ;
                if (!$transOrder) {
                    $errCode++ ;
                    $errMsg = '提现订单不存在' . $detail['mer_trans_no'] ;
                    continue ;
                }
                if ($transOrder->status != TransModel::STATUS_DEFAULT) {
                    $errCode++ ;
                    $errMsg = '订单状态不正确' ;
                    continue ;
                }
                $upInfo = [
                    'ser_pay_time'      => $detail['pay_time'],
                    'ser_trans_no'      => $detail['ser_trans_no'],
                    'ser_notify_status' => $detail['notify_status'],
                    'ser_notify_time'   => $detail['notify_time'],
                    'ser_notify_log'    => $detail['notify_log'],
                ] ;
                if (true !== TransBiz::getInstance()->confirmTransOrderById($transOrder->id, $upInfo)) {
                    $errCode++ ;
                    $errMsg = '确认转账订单失败' ;
                    continue ;
                } 
            } catch (\Exception $e) {
                $errCode++ ;
                $errMsg = $e->getMessage() ;
                continue ;
            }
        }

        $ret = new \stdClass() ;
        $ret->is_success = $errCode === 0 ? 'T' : 'F' ;
        $ret->code = $errCode ;
        $ret->output = $notice['output'] ;
        $ret->error = $errMsg ;
        return $ret ;
    }
}
