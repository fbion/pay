<?php
/**
 * @brief 支付退款业务处理
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-07-08
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Common\Util\GatewayUtil ;
use Pay\Common\Util\ChannelUtil ;
use Pay\Common\Config\PayVars ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Model\RefundModel ;

class RefundBiz extends BaseBiz {

    protected static $instance ;

    public function createRefundOrder($fields) {
        $account = AccountBiz::getInstance()->getOrCreateAccount($fields['user_id'], $fields['channel']) ;

        $refund =  new RefundModel() ;
        $refund->id = CommonUtil::longId() ;
        $refund->user_id = $fields['user_id'] ;
        $refund->account_id = $account['id'] ;
        $refund->channel = $fields['channel'] ;
        $refund->gateway = $fields['gateway'] ;
        $refund->recharge_id = $fields['recharge_id'] ;
        $refund->mer_refund_no = $refund->id . substr(sprintf('%012s', $fields['user_id']), -12) ;
        if (null !== ($localEnv = \GlobalConfig::getLocalEnv())) {
            $refund->mer_refund_no = substr($refund->mer_refund_no, 0, - strlen($localEnv)) . $localEnv ;
        } 
        $refund->amount = $fields['refund_amount'] ;
        $refund->create_time = time() ;
        $refund->callback_url = $fields['callback_url'] ;
        $refund->subject = $fields['subject'] ;

        $refund->body = isset($fields['body']) ? $fields['body'] : '' ;
        $refund->busi_refund_no = isset($fields['busi_refund_no']) ? $fields['busi_refund_no'] : '' ;
        $refund->seller_partner = isset($fields['seller_partner']) ? $fields['seller_partner'] : '' ;
        if (true !== $refund->save()) {
            throw new PayException(ErrCode::ERR_ORDER_CREATE_FAIL) ;
        }
        return $refund ;
    }

    public function confirmRefundOrderById($orderId, $data) {
        $time = time() ;
        $errCode = 0 ;
        $errMsg = '' ;

        \DB::beginTransaction() ;
        do {
            try {
                $order = RefundModel::find($orderId) ;
                if (!$order) {
                    throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
                } elseif ($order->status == RefundModel::STATUS_SUCCESS) {
                    $errCode = 1 ;
                    break ;
                }

                if (isset($data['ser_refund_no']) && !empty($data['ser_refund_no'])) {
                    $order->ser_refund_no = $data['ser_refund_no'] ;
                }

                if (isset($data['notify_log'])) {
                    $order->ser_notify_log = $data['notify_log'] ;
                }

                if (isset($data['notify_status'])) {
                    $order->ser_notify_status = $data['notify_status'] ;
                }

                if (isset($data['seller_partner'])) {
                    $order->seller_partner = $data['seller_partner'] ;
                }

                $order->refund_time = $data['refund_time'] ;
                $order->ser_notify_time = $data['notify_time'] ;
                $order->save() ;
                AccountBiz::getInstance()->updateUserAccount($order->user_id, RefundModel::DEAL_OUT_REFUND, $order->refund_amount, $order->channel) ;
            } catch (\Exception $e) {
                $errCode = 1 ;
            }
        } while (0) ;

        if ($errCode == 0) {
            \DB::commit() ;
            if (true !== BusiNotifyBiz::getInstance()->notifyCallback($order, 'refund')) { 
                \Queue::later(5, '\Pay\Service\Queue\BusiNotifyQueue', [
                    'id' => $order->id,
                    'notify_type' => 'refund',
                ]) ;
            }
            return true ;
        } else {
            \DB::rollback() ;
            return false ;
        }
    }

}
