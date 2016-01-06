<?php
/**
 * @brief 微信支付
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Gateway ;

use Pay\Common\Util\CommonUtil ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Bll\SerNotifyBiz ;

require_once dirname(__FILE__) . '/wechatpay/WxPayServer.class.php' ;

class WechatpayGateway extends BaseGateway {

    protected $configs = array (
        1 => array(
            /// 皇包车公众号
            array(
                'partner'           => '1227304102',
                'security_code'     => 'beijingchuncuilvxingyouxian20150',
                'appid'             => 'wx62ad814ba9bf0b68',
                'appsercert'        => '273d636d5ec55a2609eaf3f903ef5c4c',
                'ver'               => 2.0,
            ),
        ),
    ) ;

    public function directQuery($order) {
        $config = $this->getConfig($order) ;
        $orderQueryObj = new \OrderQuery_pub($config) ;
        //商户订单号 
        $orderQueryObj->setParameter('out_trade_no', $order['mer_recharge_no']) ;   //商户订单号
        //$orderQuery->setParameter("sub_mch_id","XXXX") ;                          //子商户号 

        //获取订单查询结果
        $orderQueryResult = $orderQueryObj->getResult();

        /// 商户根据实际情况设置相应的处理流程,此处仅作举例
        $ret = new \stdClass() ;
        if ($orderQueryResult["return_code"] == "SUCCESS") {
            if ($orderQueryResult["result_code"] == "SUCCESS"){
                $ret->is_success = 'T' ;
                //商户订单号
                $ret->mer_recharge_no   = strval($orderQueryResult['out_trade_no']) ;
                //第三方订单号
                $ret->ser_recharge_no   = strval($orderQueryResult['transaction_id']) ;
                $ret->gateway_account   = strval($orderQueryResult['openid']) ;
                $ret->seller_partner    = $orderQueryResult['mch_id'] ;
                $ret->notify_status     = in_array($orderQueryResult['trade_state'], ['SUCCESS', 'REFUND']) ? 2 : 1 ;
                $ret->notify_log        = in_array($orderQueryResult['trade_state'], ['SUCCESS', 'REFUND']) ? '支付成功' : '' ;
                $ret->pay_time          = strtotime($orderQueryResult['time_end']) ;
                $ret->notify_time       = strtotime($orderQueryResult['time_end']) ;

                $ret->amount            = strval($orderQueryResult['total_fee']) ;
                $ret->is_refund         = $orderQueryResult['trade_state'] == 'REFUND' ? true : false ;
            } else {
                $ret->is_success = 'F' ;
                $ret->code = $orderQueryResult['err_code'] ;
                $ret->error = $orderQueryResult['err_code_des'] ;
            }   
        } else {
            $ret->is_success = 'F' ;
            $ret->exception = true ;
            $ret->error = $orderQueryResult['return_msg'] ;
        }
        return $ret ;
    }

    private function _unifiedOrder($order) {
        $config = $this->getConfig($order) ;
        $unifiedObj = new \UnifiedOrder_pub($config) ;
        $package = [
            'body'              => str_replace(' ', '', $order['subject']),
            'attach'            => '' ,
            'out_trade_no'      => $order['mer_recharge_no'],
            'total_fee'         => $order['amount'],
            'notify_url'        => $order['notify_url'],
            'trade_type'        => $order['trade_type'],
            'openid'            => $order['open_id'],
        ] ;
        foreach ($package as $k=>$v) {
            $unifiedObj->setParameter($k, $v) ;
        }
        return $unifiedObj ;
    }

    protected function mDirect($order) {
        return $this->_jsDirect($order) ;
    }

    private function _jsDirect($order) {
        if (!isset($order['open_id']) || empty($order['open_id'])) {
            throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '缺少参数open_id') ;
        }
        $config = $this->getConfig($order) ;
        $order['trade_type'] = 'JSAPI' ;
        $unifiedOrder = $this->_unifiedOrder($order) ;
        $prepayId = $unifiedOrder->getPrepayId() ;
        if (!$prepayId) {
             throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '获取prepayId失败') ;
        }
        $jsApi = new \JsApi_pub($config) ;
        $jsApi->setPrepayId($prepayId);
        return $jsApi->getParameters() ;
    }

    protected function pcDirect($order) {
        return $this->_nativeDirect($order) ;
    }

    private function _nativeDirect($order) {
        $order['trade_type'] = 'NATIVE' ;
        $unifiedOrder = $this->_unifiedOrder($order) ;
        $result = $unifiedOrder->getResult() ;
        return $result['code_url'] ;
    }

    protected function appDirect($order) {
        $order['trade_type'] = 'APP' ;
        $unifiedOrder = $this->_unifiedOrder($order) ;
        $prepayId = $unifiedOrder->getPrepayId() ;
        $config = $this->getConfig($order) ;
        $appApi = new \AppApi_pub($config) ;
        $appApi->setPrepayId($prepayId) ;
        return $appApi->getParameters() ;
    }

    public function refundQuery($order) {
        $ret = new \stdClass() ;
        $ret->is_success = '' ;

        $config = $this->getConfig($order) ;
        if (!$config) {
            $ret->is_success = 'F' ;
            $ret->error = '不存在的交易号' ;
            return $ret ;        
        }

        $refundQueryObj = new \RefundQuery_pub($config) ;
        //$refundQueryObj->setParameter('out_trade_no', "$out_trade_no") ;              //商户订单号
        $refundQueryObj->setParameter('transaction_id', $order['ser_recharge_no']) ;    //微信订单号

        //$refundQuery->setParameter("out_refund_no","XXXX");//商户退款单号
        //$refundQuery->setParameter("refund_id","XXXX");//微信退款单号
        //$refundQuery->setParameter("transaction_id","XXXX");//微信退款单号
        
        //非必填参数，商户可根据实际情况选填
        //$refundQuery->setParameter("sub_mch_id","XXXX");//子商户号 
        //$refundQuery->setParameter("device_info","XXXX");//设备号 

        //退款查询接口结果
        $refundQueryResult = $refundQueryObj->getResult();

        if ($refundQueryResult["return_code"] == "SUCCESS") {
            if ($refundQueryResult['result_code'] == 'SUCCESS' && $refundQueryResult['refund_count'] >= 1) {
                $ret->is_success = 'T' ;
                $ret->code = 1 ;    //已经退过款
                $ret->error = '该笔订单已经退过款，退款不允许超过两次' ;
                $ret->refund_count = $refundQueryResult['refund_count'] ;
                $ret->mer_refund_no = $refundQueryResult['out_refund_no'] ;
                $ret->ser_refund_no = $refundQueryResult['refund_id'] ;
            }
        } else {
            $ret->is_success = 'F' ;
            $ret->code = 999 ;
            $ret->error = '通信出错:errmsg=' . $refundQueryResult['return_msg'] ;
        }
        return $ret ;
    }

    public function refund($order) {
        $ret = new \stdClass() ;
        $ret->haveNotify = false ;
        $config = self::getConfig($order) ;
        if (!$config) {
            $ret->is_success = 'F' ;
            $ret->error = "不存在的交易号:$order[ser_recharge_no]" ;
            return $ret ;        
        }

        //使用退款接口
        $refundObj = new \Refund_pub($config);
        $refundObj->setParameter("transaction_id", $order['ser_recharge_no']) ; //微信订单号
        $refundObj->setParameter("out_refund_no", $order['mer_refund_no']) ;    //商户退款单号
        $refundObj->setParameter("total_fee", $order['recharge_amount']) ;      //总金额
        $refundObj->setParameter("refund_fee", $order['refund_amount']) ;       //退款金额
        $refundObj->setParameter("op_user_id", $config['partner']) ;            //操作员

        //$refundObj->setParameter("out_trade_no", 'xxxx') ;                    //商户订单号
        //$refund->setParameter("sub_mch_id","XXXX") ;                          //子商户号 
        //$refund->setParameter("device_info","XXXX") ;                         //设备号 

        //调用结果
        $refundResult = $refundObj->getResult();

        if ($refundResult["return_code"] == "SUCCESS") {
            if ($refundResult['result_code'] == 'SUCCESS') {
                for ($i=0; $i<3; $i++) {
                    sleep(1) ;
                    $refundQueryResult = $this->refundQuery($order) ; 
                    if ($refundQueryResult->is_success == 'T' && $refundQueryResult->code == 1) {
                        $notice = [
                            'type'              => 'refund',
                            'mer_refund_no'     => $order['mer_refund_no'],
                            'ser_refund_no'     => $order['ser_refund_no'],
                            'notify_status'     => 2,
                            'notify_log'        => 'SUCCESS',
                            'notify_time'       => time(),
                            'refund_time'       => time(),
                        ] ;
                        SerNotifyBiz::getInstance()->payNotify($notice, 'refund') ;
                        break ;
                    }
                }
            } else {
                $ret->is_success = 'F' ;
                $ret->code = 998 ;
                $ret->error = '提交业务失败:retmsg=' . $refundResult['err_code_des'] ;
            }
        } else {
            $ret->is_success = 'F' ;
            $ret->code = 999 ;
            $ret->exception = true ;
            $ret->error = '通信失败:retmsg=' . $refundResult['err_code_des'] ;
        }
        return $ret ;
    }

    public function notify($channel) {
        $xml = file_get_contents("php://input") ;
        //$xml = '<xml><appid><![CDATA[wx62ad814ba9bf0b68]]></appid>
        //    <bank_type><![CDATA[CFT]]></bank_type>
        //    <cash_fee><![CDATA[1]]></cash_fee>
        //    <fee_type><![CDATA[CNY]]></fee_type>
        //    <is_subscribe><![CDATA[Y]]></is_subscribe>
        //    <mch_id><![CDATA[1227304102]]></mch_id>
        //    <nonce_str><![CDATA[yj1vuhafyyuvmcpz2p9992a91ul06edd]]></nonce_str>
        //    <openid><![CDATA[oFA64s5NHmfwEaEl5jIfjTgsy1Pw]]></openid>
        //    <out_trade_no><![CDATA[test0702152451350439000000000095]]></out_trade_no>
        //    <result_code><![CDATA[SUCCESS]]></result_code>
        //    <return_code><![CDATA[SUCCESS]]></return_code>
        //    <sign><![CDATA[4E0910D81068E34969D263BE21A7BF99]]></sign>
        //    <time_end><![CDATA[20150702152605]]></time_end>
        //    <total_fee>1</total_fee>
        //    <trade_type><![CDATA[JSAPI]]></trade_type>
        //    <transaction_id><![CDATA[1008410170201507020336471776]]></transaction_id>
        //    </xml>' ;
        $params = CommonUtil::xmlToArray($xml) ;
        $partner = $params['mch_id'] ;
        $config = $this->getConfigByPartner($channel, $partner) ;
        $notify = new \Notify_pub($config) ;
        $notify->saveData($xml) ;

        $notice = [] ;
        if($notify->checkSign() == TRUE) {
            /// 通信出错
            if ($notify->data["return_code"] == "FAIL") {
                $notify->setReturnParameter("return_code", "FAIL") ;         //返回状态码
                $notify->setReturnParameter("return_msg", "通信出错") ;      //返回信息
            /// 业务出错
            } elseif ($notify->data["result_code"] == "FAIL") {
                $notify->setReturnParameter("return_code", "FAIL") ;         //返回状态码
                $notify->setReturnParameter("return_msg", "业务出错") ;      //返回信息
            /// 支付成功
            } else {
                $notify->setReturnParameter("return_code", "SUCCESS") ;     //设置返回码
                $notice['type'] = 'direct' ;
                $notice['mer_recharge_no']  = $params['out_trade_no'] ;     //商户订单号
                $notice['ser_recharge_no']  = $params['transaction_id'] ;   //第三方支付单号
                $notice['gateway_account']  = $params['openid'];            //用户在商户appid下的唯一标识     
                $notice['seller_partner']   = $params['mch_id'] ;           //商户号
                $notice['notify_status']    = 2 ;
                $notice['notify_log']       = '支付成功' ;
                $notice['pay_time']         = strtotime($params['time_end']) ;
                $notice['notify_time']      = strtotime($params['time_end']) ;

                $notice['amount']           = $params['total_fee'] ;        //订单金额
                $notice['bank_id']          = $params['bank_type'] ;        //银行类型
                //$notice['attach']           = $params['attach'] ;           //商家数据包
            }
        } else {
            $notify->setReturnParameter("return_code", "FAIL") ;            //返回状态码
            $notify->setReturnParameter("return_msg", "签名错误") ;         //返回信息
        }
        $notice['output'] = $notify->returnXml() ;                //给第三方显示
        return $notice ;
    }

}
