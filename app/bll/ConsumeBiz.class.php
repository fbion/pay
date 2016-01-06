<?php
/**
 * @brief 支付消费业务处理
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Model\ConsumeModel ;

class ConsumeBiz extends BaseBiz {

    protected static $instance ;
    private $_consumeModel = null ;

    protected function __construct() {
        if (null == $this->_consumeModel || empty($this->_consumeModel)) {
            $this->_consumeModel = new ConsumeModel() ;
        }
    }

    public function directConsume($fields) {
        $ret = [] ;
        if ($this->createConsumeOrder($fields)) {
            $fields['consume_id'] = $this->_consumeModel->id ;
            $fields['recharge_amount'] = $this->_consumeModel->consume_amount ;
            $rechargeOrder = RechargeBiz::getInstance()->createRechargeOrder($fields) ;

            $this->_consumeModel->recharge_id = $rechargeOrder->id ;
            $this->_consumeModel->recharge_amount = $rechargeOrder->recharge_amount ;
            $this->_consumeModel->save() ;

            $rechargeUrl = RechargeBiz::getInstance()->directThirdParty($rechargeOrder) ;
            if ($rechargeUrl == false) {
                throw new PayException(ErrCode::ERR_GATEWAY_FAIL) ; 
            }
            $ret = [
                'pay_gateway_url'   => $rechargeUrl,
                'user_id'           => $this->_consumeModel->user_id,
                'pay_consume_id'    => $this->_consumeModel->id,
                'busi_consume_no'   => $this->_consumeModel->busi_consume_no,
                'consume_amount'    => $this->_consumeModel->consume_amount,
            ] ;
            \Queue::later(60 * 10, '\Pay\Service\Queue\SerAutoConfirmQueue', [
                'id' => $rechargeOrder['id'],
            ]) ;
        }
        return $ret ;
    }

    public function createConsumeOrder($fields) {
        $account = AccountBiz::getInstance()->getOrCreateAccount($fields['user_id'], $fields['channel']) ;

        $this->_consumeModel->id = $this->_consumeModel->calculPrimaryId() ;
        $this->_consumeModel->user_id = $fields['user_id'] ;
        $this->_consumeModel->account_id = $account['id'] ;
        $this->_consumeModel->channel = $fields['channel'] ;
        $this->_consumeModel->create_time = time() ;
        $this->_consumeModel->consume_amount = $fields['consume_amount'] ;
        $this->_consumeModel->return_url = $fields['return_url'] ;
        $this->_consumeModel->callback_url = $fields['callback_url'] ;
        $this->_consumeModel->subject = $fields['subject'] ; 

        $this->_consumeModel->expire_time = isset($fields['expire_time']) ? $fields['expire_time'] : 0 ;
        $this->_consumeModel->recharge_amount = isset($fields['recharge_amount']) ? $fields['recharge_amount'] : 0 ;
        $this->_consumeModel->recharge_id = isset($fields['recharge_id']) ? $fields['recharge_id'] : '' ;
        $this->_consumeModel->body = isset($fields['body']) ? $fields['body'] : '' ;
        $this->_consumeModel->city_id = isset($fields['city_id']) ? $fields['city_id'] : 0 ;
        $this->_consumeModel->plat = isset($fields['plat']) ? $fields['plat'] : 1 ;
        $this->_consumeModel->plat_ext = isset($fields['plat_ext']) ? $fields['plat_ext'] : '' ;
        $this->_consumeModel->busi_consume_no = isset($fields['busi_consume_no']) ? $fields['busi_consume_no'] : '' ;
        $this->_consumeModel->busi_show_url = isset($fields['busi_show_url']) ? $fields['busi_show_url'] : '' ;

        if (true !== $this->_consumeModel->save()) {
            throw new PayException(ErrCode::ERR_ORDER_CREATE_FAIL) ;
        }
        return $this->_consumeModel ;
    }

    public function confirmConsumeOrderById($orderId) {
        $time = time() ;
        $errCode = 0 ;
        $errMsg = '' ;

        \DB::beginTransaction() ;
        do {
            try {
                $order = ConsumeModel::find($orderId) ;
                if (!$order) { 
                    throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
                } elseif ($order->status == ConsumeModel::STATUS_SUCCESS) {
                    $errCode = 1 ;
                    break ;
                }
                $order->status = ConsumeModel::STATUS_SUCCESS ;
                $order->pay_time = $time ;
                $order->update_time = $time ;
                $order->save() ;
                AccountBiz::getInstance()->updateUserAccount($order->user_id, ConsumeModel::DEAL_CONSUME, $order->consume_amount, $order->channel) ;
            } catch (\Exception $e) {
                $errCode = 2 ;
            }
        } while (0) ;

        if ($errCode == 0) {
            \DB::commit() ;
            if (true !== BusiNotifyBiz::getInstance()->notifyCallback($order, 'consume')) { 
                \Queue::later(5, '\Pay\Service\Queue\BusiNotifyQueue', [
                    'id' => $order->id,
                    'notify_type' => 'consume'
                ]) ;
            }
            return true ;
        } else {
            \DB::rollback() ;
            return false ;
        }
    }

    public function cancelConsumeOrder($consumeId, $userId, $amount) {
        $this->_consumeModel = ConsumeModel::find($consumeId) ;
        do {
            if ($this->_consumeModel->id < 0) {
                throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
            }
            if ($this->_consumeModel->status != ConsumeModel::STATUS_SUCCESS) {
                throw new PayException(ErrCode::ERR_ORDER_STATUS) ;
            }
            if ($this->_consumeModel->user_id != $userId) {
                throw new PayException(ErrCode::ERR_ORDER_USER_ID) ;
            }
            if ($this->_consumeModel->refund_count > 0) {
                throw new PayException(ErrCode::ERR_REFUND, '只允许退款一次') ;
            }
            if ($amount > $this->_consumeModel->consume_amount) {
                throw new PayException(ErrCode::ERR_REFUND, '退款金额不能大于消费金额') ;
            }
        } while (0) ;

        if (false == $this->refundConsumeOrderById($consumeId, $amount)) {
            throw new PayException(ErrCode::ERR_NOT_MODIFIED, '退消费订单更新失败') ;
        }
        return [
            'user_id'           => $this->_consumeModel->user_id,
            'pay_consume_id'    => $this->_consumeModel->id,
            'refund_amount'     => $amount,
            'pay_recharge_id'   => $this->_consumeModel->recharge_id,
        ] ;
    } 

    public function refundConsumeOrderById($orderId, $amount) {
        $time = time() ;
        $errCode = 0 ;
        $errMsg = '' ;

        \DB::beginTransaction() ;
        do {
            $order = ConsumeModel::find($orderId) ; 
            if (!$order) {
                $errCode = ErrCode::ERR_ORDER_NO_EXISTS ;
                throw new PayException($errCode) ;
            } elseif ($order->status != ConsumeModel::STATUS_SUCCESS) {
                $errCode = ErrCode::ERR_ORDER_STATUS ;
                throw new PayException($errCode) ;
            }
            $order->refund_count++ ;
            $order->refund_amount += $amount ;
            $order->refund_time = $time ;
            $order->update_time = $time ;
            $order->save() ;
            AccountBiz::getInstance()->updateUserAccount($order->user_id, ConsumeModel::DEAL_IN_REFUND, $amount, $order->channel) ;
        } while (0) ;

        if ($errCode == 0) {
            \DB::commit() ;
            return true ;
        } else {
            \DB::rollback() ;
            return false ;
        }
    }

    public function getConsumeOrderById($consumeId) {
        return ConsumeModel::find($consumeId) ;
    }

}
