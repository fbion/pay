<?php
/**
 * @brief 支付账户业务处理
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Common\Util\ChannelUtil ;
use Pay\Model\AccountModel ;

class AccountBiz extends BaseBiz {

    protected static $instance ;
    private $_accountModel = null ;

    protected function __construct() {
        if (null == $this->_accountModel || empty($this->_accountModel)) {
            $this->_accountModel = new AccountModel() ;
        }
    }

    public function getAccount($userId, $channel=1) {
        $accChannel = ChannelUtil::calculateAccountChannel($channel) ;
        return $this->_accountModel->whereRaw('user_id=? and acc_channel=?', array($userId, $accChannel))->first() ;
    }

    public function getOrCreateAccount($userId, $channel=1) {
        $accChannel = ChannelUtil::calculateAccountChannel($channel) ;
        $account = $this->getAccount($userId, $accChannel) ;
        if (!$account) {
            $primaryId = $this->_accountModel->calculPrimaryId() ;
            $this->_accountModel->id = $primaryId ;
            $this->_accountModel->user_id = $userId ;
            $this->_accountModel->acc_channel = $accChannel ;
            $this->_accountModel->create_time = time() ;
            $this->_accountModel->save() ;
            return $this->_accountModel ;
        }
        return $account ;
    }

    public function updateUserAccount($userId, $dealType, $cash, $channel=1) {
        $account = $this->getOrCreateAccount($userId, $channel) ;
        $totalMoeny = $cash ;
        switch ($dealType) {
            case AccountModel::DEAL_RECHARGE :
                $account->balance += $totalMoeny ;
                $account->total_recharge_amount += $totalMoeny ;
                break ;

            case AccountModel::DEAL_CONSUME :
                if ($account->balance < $totalMoeny) {
                    throw new PayException(ErrCode::ERR_BALANCE_ENOUGH) ;
                }
                $account->balance -= $totalMoeny ;
                $account->total_consume_amount += $totalMoeny ;
                break ;

            case AccountModel::DEAL_IN_REFUND :
                $account->balance += $totalMoeny ;
                break ;

            case AccountModel::DEAL_OUT_REFUND :
                if ($account->balance < $totalMoeny) {
                    throw new PayException(ErrCode::ERR_BALANCE_ENOUGH) ;
                }
                $account->balance -= $totalMoeny ;
                $account->total_refund_amount += $totalMoeny ;
                break ;
            default : 
                throw new PayException(ErrCode::ERR_DEAL_TYPE_EXISTS) ;
                break ;
        }
        $account->update_time = time() ;
        if (true !== $account->save()) {
            throw new PayException(ErrCode::ERR_NOT_MODIFIED, '账户变化失败') ;
        }
        return $account ;
    }

}
