<?php
/**
 * @brief 支付第三方异步通知页面控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller\Web ;

use Pay\Common\Util as Util ;
use Pay\Common\Config\ErrCode ;
use Pay\Bll\SerNotifyBiz ;

class NotifyPage extends BasePage {

    public function notifyAction($notifyType, $channel, $gateway) {
        do {
            $ret = $this->_checkParams($channel, $gateway) ;
            if ($ret['err_code'] !== ErrCode::ERR_SUCCESS) {
                return $ret['err_msg'] ;
            }
            $notice = $this->_getPayNotifyArgs($channel, $gateway) ;
            if (!$notice) {
                $errMsg = '验证失败' ;
                return $errMsg ;
            }
        } while (0) ;

        $ret = SerNotifyBiz::getInstance()->payNotify($notice, $notifyType) ;
        return $ret->is_success == 'T' ? $ret->output : $ret->error ;
    }

    private function _getPayNotifyArgs($channel, $gateway) {
        $channel = Util\ChannelUtil::calculateAccountChannel($channel) ;
        $gatewayObj = Util\GatewayUtil::getGatewayObj($gateway) ;
        try {
            $notice = $gatewayObj->notify($channel) ;
            if (!$notice) return false ;
            return $notice ;
        } catch(\Exception $e) {
            return $e->getMessage() ;
        }
    }

    private function _checkParams($channel, $gateway) {
        $errCode = 0 ;
        $errMsg = '' ;
        do {
            $gatewayCode = Util\GatewayUtil::checkGateway($gateway) ;
            if (!$gatewayCode) {
                $errCode = 1 ;
                $errMsg = sprintf('gateway not exists gateway=%s', $gateway) ;
                break ;
            }
            if (false === Util\ChannelUtil::checkChannel($channel)) {
                $errCode = 1 ;
                $errMsg = sprintf('channel not exists channel=%s', $channel) ;
                break ;
            }
        } while(0) ;

        return array(
            'err_code'  => $errCode,
            'err_msg'   => $errMsg,
            'channel'   => $channel,
            'gateway'   => $gateway,
        ) ;
    }
}
