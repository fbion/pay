<?php
/**
 * @brief 支付第三方同步返回页面控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller\Web ;

use Pay\Common\Util as Util ;
use Pay\Common\Config\ErrCode ;
use Pay\Bll\SerReturnBiz ;

class ReturnPage extends BasePage {

    public function returnAction($channel, $gateway, $merRechargeNo, $plat, $timestamp, $sign) {
        $fields = array(    
            'channel' => $channel,    
            'gateway' => $gateway,    
            'mer_recharge_no' => $merRechargeNo,    
            'plat' => $plat,    
            'timestamp' => $timestamp,    
            'sign' => $sign,    
        ) ; 

        $ret = $this->_checkParams($fields) ;
        if ($ret['err_code'] == ErrCode::ERR_SUCCESS) {
            $ret = SerReturnBiz::getInstance()->payReturn(array('mer_recharge_no' => $ret['mer_recharge_no'])) ;
            if ($ret->is_success == 'T') {
                $returnUrl = $ret->return_url ;
                if ($ret->data) {
                    $returnUrl .= false === strpos($returnUrl, '?') ? '?' : '&' ;
                    $ret->data->timestamp = time() ;
                    $ret->data->sign = Util\SignUtil::makeSign(get_object_vars($ret->data)) ;
                    $params = get_object_vars($ret->data) ;
                    while (list($key, $val) = each($params)) {
                        $returnUrl .= $key . '=' . $val . '&' ;
                    }
                }   
                return \Redirect::to($returnUrl) ;
            }
        }
    }

    private function _checkParams($fields) {
        $errCode = 0 ;
        $errMsg = '' ;
        do {
            $gateway = substr($fields['gateway'], 1) ;
            $channel = substr($fields['channel'], 1) ;
            $merRechargeNo = substr($fields['mer_recharge_no'], 1) ;
            $gatewayCode = Util\GatewayUtil::checkGateway($gateway) ;
            if (!$gatewayCode) {
                $errCode = 1 ;
                $errMsg = sprintf('gateway not exists gateway=%s', $gateway) ;
                break ;
            }
            if (false === Util\ChannelUtil::checkChannel($channel)) {
                $errCode = 2 ;
                $errMsg = sprintf('channel not exists channel=%s', $channel) ;
                break ;
            }
            $gatewayObj = Util\GatewayUtil::getGatewayObj($gateway) ;
            if (false == ($gatewayObj->checkSign($fields))) {
                $errCode = 3 ;
                $errMsg = '参数验证失败';
                break ;
            }
            if (!$merRechargeNo) {
                $errCode = 4 ;
                $errMsg = '参数传递错误' ;
                break ;
            }
        } while(0) ;

        return array(
            'err_code'  => $errCode,
            'err_msg'   => $errMsg,
            'channel'   => $channel,
            'gateway'   => $gateway,
            'mer_recharge_no' => $merRechargeNo,
        ) ;
    }

}
