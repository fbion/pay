<?php
/**
 * @brief 支付充值业务处理
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Common\Util as Util ;
use Pay\Common\Config\PayVars ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Model\RechargeModel ;

class RechargeBiz extends BaseBiz {

    protected static $instance ;
    private $_rechargeModel = null ;

    protected function __construct() {
        if (null == $this->_rechargeModel || empty($this->_rechargeModel)) {
            $this->_rechargeModel = new RechargeModel() ;
        }
    }

    public function directBalance($fields) {
        $ret = [] ;
        if ($this->createRechargeOrder($fields)) {
            $rechargeUrl = $this->directThirdParty($this->_rechargeModel) ;
            if ($rechargeUrl == false) {
                throw new PayException(ErrCode::ERR_GATEWAY_FAIL) ; 
            }
            $ret = [
                'pay_gateway_url'   => $rechargeUrl,
                'pay_recharge_id'   => $this->_rechargeModel->id,
                'busi_recharge_no'  => $this->_rechargeModel->busi_recharge_no,
                'recharge_amount'   => $this->_rechargeModel->recharge_amount,
            ] ;
            \Queue::later(60 * 10, '\Pay\Service\Queue\SerAutoConfirmQueue', [
                'id' => $this->_rechargeModel->id,
            ]) ;
        }
        return $ret ;
    }

    public function createRechargeOrder($fields) {
        $account = AccountBiz::getInstance()->getOrCreateAccount($fields['user_id'], $fields['channel']) ;
        $primaryId = $this->_rechargeModel->calculPrimaryId() ;
        $this->_rechargeModel->id = $primaryId ;
        $this->_rechargeModel->user_id = $fields['user_id'] ;
        $this->_rechargeModel->account_id = $account['id'] ;
        $this->_rechargeModel->channel = $fields['channel'] ;
        $this->_rechargeModel->gateway = $fields['gateway'] ;
        $this->_rechargeModel->mer_recharge_no = $primaryId . substr(sprintf('%012s', $fields['user_id']), -12) ;
        if (null !== ($localEnv = \GlobalConfig::getLocalEnv())) {
            $this->_rechargeModel->mer_recharge_no = substr($this->_rechargeModel->mer_recharge_no, 0, - strlen($localEnv)) . $localEnv ;
        } 
        $this->_rechargeModel->recharge_amount = $fields['recharge_amount'] ;
        $this->_rechargeModel->create_time = time() ;
        $this->_rechargeModel->return_url = $fields['return_url'] ;
        $this->_rechargeModel->callback_url = $fields['callback_url'] ;
        $this->_rechargeModel->subject = $fields['subject'] ;

        $this->_rechargeModel->expire_time = isset($fields['expire_time']) ? $fields['expire_time'] : 0 ;
        $this->_rechargeModel->consume_id = isset($fields['consume_id']) ? $fields['consume_id'] : '' ;
        $this->_rechargeModel->body = isset($fields['body']) ? $fields['body'] : '' ;
        $this->_rechargeModel->city_id = isset($fields['city_id']) ? $fields['city_id'] : 0 ;
        $this->_rechargeModel->plat = isset($fields['plat']) ? $fields['plat'] : 1 ;
        $this->_rechargeModel->plat_ext = isset($fields['plat_ext']) ? $fields['plat_ext'] : '' ;
        $this->_rechargeModel->mobile_no = isset($fields['mobile']) ? $fields['mobile'] : '' ;
        $this->_rechargeModel->bank_code = isset($fields['bank_code']) ? $fields['bank_code'] : '' ;
        $this->_rechargeModel->busi_recharge_no = isset($fields['busi_recharge_no']) ? $fields['busi_recharge_no'] : '' ;
        $this->_rechargeModel->gateway_account = $fields['gateway'] == PayVars::GATEWAY_WECHAT && isset($fields['open_id']) ? $fields['open_id'] : '' ;
        if (true !== $this->_rechargeModel->save()) {
            throw new PayException(ErrCode::ERR_ORDER_CREATE_FAIL) ;
        }
        return $this->_rechargeModel ;
    }

    public function confirmRechargeOrderById($orderId, $orderAmount, $data) {
        $time = time() ;
        $errCode = 0 ;
        $errMsg = '' ;

        \DB::beginTransaction() ;
        do {
            try {
                $order = RechargeModel::find($orderId) ;
                if (!$order) {
                    throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
                } elseif ($order->status == RechargeModel::STATUS_SUCCESS) {
                    $errCode = 1 ;
                    break ;
                }

                if ($orderAmount) {
                    $order->recharge_amount = $orderAmount ;
                }

                if (isset($data['gateway_account'])) {
                    $order->gateway_account = $data['gateway_account'] ;
                }

                if (isset($data['ser_recharge_no']) && !empty($data['ser_recharge_no'])) {
                    $order->ser_recharge_no = $data['ser_recharge_no'] ;
                }

                if (isset($data['notify_log'])) {
                    $order->ser_notify_log = $data['notify_log'] ;
                }

                if (isset($data['seller_partner'])) {
                    $order->seller_partner = $data['seller_partner'] ;
                }
                $order->status = RechargeModel::STATUS_SUCCESS ;
                $order->pay_time = $data['pay_time'] ;
                $order->ser_notify_time = $time ;
                $order->save() ;
                AccountBiz::getInstance()->updateUserAccount($order->user_id, RechargeModel::DEAL_RECHARGE, $order->recharge_amount, $order->channel) ;
            } catch (\Exception $e) {
                $errCode = 1 ;
            }
        } while (0) ;

        if ($errCode == 0) {
            \DB::commit() ;
            if (empty($order->consume_id)) {
                \Queue::push('\Pay\Service\Queue\BusiNotifyQueue', [
                    'id' => $order->id,
                    'notify_type' => 'direct'
                ]) ;
            }
            return true ;
        } else {
            \DB::rollback() ;
            return false ;
        }
    }

    public function cancelRechargeOrder($fields) {
        $this->_rechargeModel = RechargeModel::find($fields['pay_recharge_id']) ;
        do {
            if ($this->_rechargeModel->id < 0) {
                throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
            }
            if ($this->_rechargeModel->status != rechargeModel::STATUS_SUCCESS) {
                throw new PayException(ErrCode::ERR_ORDER_STATUS) ;
            }
            if ($this->_rechargeModel->user_id != $fields['user_id']) {
                throw new PayException(ErrCode::ERR_ORDER_USER_ID) ;
            }
            if ($this->_rechargeModel->refund_count > 0) {
                throw new PayException(ErrCode::ERR_REFUND, '只允许退款一次') ;
            }
            if ($fields['refund_amount'] > $this->_rechargeModel->recharge_amount) {
                throw new PayException(ErrCode::ERR_REFUND, '退款金额不能大于充值金额') ;
            }
            if (!$this->_rechargeModel->ser_recharge_no) {
                throw new PayException(ErrCode::ERR_REFUND, '该订单不允许通过接口退款') ;
            }
        } while (0) ;

        $fields['recharge_id'] = $this->_rechargeModel->id ;
        $fields['gateway'] = $this->_rechargeModel->gateway ;
        $fields['seller_partner'] = $this->_rechargeModel->seller_partner ;
        $refundModel = RefundBiz::getInstance()->createRefundOrder($fields) ;
        $this->refundRechargeOrderById($this->_rechargeModel->id, $refundModel->amount) ;

        \Queue::later(30, '\Pay\Service\Queue\SerAutoConfirmQueue@serRefund', [
            'id' => $refundModel->id,
        ]) ;
        return [
            'user_id'           => $this->_rechargeModel->user_id,
            'refund_amount'     => $refundModel->amount,
            'pay_recharge_id'   => $this->_rechargeModel->id,
        ] ;
    } 

    public function refundRechargeOrderById($orderId, $amount) {
        $time = time() ;
        $errCode = 0 ;
        $errMsg = '' ;

        \DB::beginTransaction() ;
        do {
            $order = RechargeModel::find($orderId) ; 
            if (!$order) {
                $errCode = ErrCode::ERR_ORDER_NO_EXISTS ;
                throw new PayException($errCode) ;
            } elseif ($order->status != RechargeModel::STATUS_SUCCESS) {
                $errCode = ErrCode::ERR_ORDER_STATUS ;
                throw new PayException($errCode) ;
            }
            $order->refund_count++ ;
            $order->refund_amount += $amount ;
            $order->refund_time = $time ;
            $order->update_time = $time ;
            $order->save() ;
            AccountBiz::getInstance()->updateUserAccount($order->user_id, RechargeModel::DEAL_OUT_REFUND, $amount, $order->channel) ;
        } while (0) ;

        if ($errCode == 0) {
            \DB::commit() ;
            return true ;
        } else {
            \DB::rollback() ;
            return false ;
        }
    }

    public function directThirdParty($rechargeOrder) {
        $fields['channel'] = Util\ChannelUtil::calculateAccountChannel($rechargeOrder['channel']) ;
        $fields['gateway'] = $rechargeOrder['gateway'] ;
        $fields['plat'] = $rechargeOrder['plat'] ;
        $fields['mer_recharge_no'] = $rechargeOrder['mer_recharge_no'] ;
        $fields['bank_code'] = $rechargeOrder['bank_code'] ;
        $fields['timestamp'] = $rechargeOrder['create_time'] ;
        $fields['amount'] = $rechargeOrder['recharge_amount'] ;
        $fields['expire_time'] = $rechargeOrder['expire_time'] ;
        $fields['subject'] = $rechargeOrder['subject'] ;
        $fields['body'] = $rechargeOrder['body'] ;
        $fields['open_id'] = $rechargeOrder['gateway_account'] ;

        $gatewayObj = Util\GatewayUtil::getGatewayObj($fields['gateway']) ;
        $fields['notify_url'] = PayVars::getNotifyUrl() . "notify/direct/{$fields['channel']}/{$fields['gateway']}" ;
        $fields['return_url'] = PayVars::getReturnUrl() . 'return/' . $gatewayObj->makeReturnUri($fields) ;
        return $gatewayObj->direct($fields) ;
    }

}
