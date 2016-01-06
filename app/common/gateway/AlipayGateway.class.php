<?php
/**
 * @brief 支付宝支付
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Gateway ;

use Pay\Common\Util\CommonUtil ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;

require_once dirname(__FILE__) . '/alipay/alipay_submit.class.php' ;
require_once dirname(__FILE__) . '/alipay/alipay_notify.class.php' ;

class AlipayGateway extends BaseGateway {

    const TRANSPORT = 'http' ;

    protected $configs = array(
        1 => array (
            array (
                'partner'           => '2088611681636031',        
                'security_code'     => 'q20hayuxh6zt5u0ltk2xwkzutweem295',
                'seller_mail'       => 'kateliu@cclx.com',
                'seller_personname' => '北京纯粹旅行有限公司',
            ),
        ),
    ) ;

    public function directQuery($order) {
        $channel = $order['channel'] ;
        $configs = $this->configs[$channel] ;

        foreach($configs as $config) {
            $direct_no = isset($order['ser_recharge_no']) ? : '' ;
            $parameter = [
                'service'           => 'single_trade_query',
                'partner'           => $config['partner'],
                '_input_charset'    => 'utf-8',
                'trade_no'          => $direct_no,
                'out_trade_no'      => $order['mer_recharge_no'],
            ] ;

            $alipayObj = $this->_getAlipayObj($config) ;
            $url = $alipayObj->buildRequestParaToUrl($parameter) ;

            $content = file_get_contents($url) ;
            $content = preg_replace("/encoding=(\"|')GBK(\"|')/i", "encoding=\"utf-8\"", $content) ;
            $service_ret = simplexml_load_string($content) ;
            $ret = new \stdClass() ;
            $ret->is_success = strval($service_ret->is_success) ;
            $ret->error = strval($service_ret->error) ;
            $ret->query_url = $url ;
            $ret->seller_partner = $config['partner'] ;

            if ($ret->is_success == 'T') {
                $trade = $service_ret->response->trade ;
                $ret->ser_recharge_no = strval($trade->trade_no) ;
                $ret->mer_recharge_no = strval($trade->out_trade_no) ;
                $ret->gateway_account = strval($trade->buyer_email) ;
                $ret->amount = strval($trade->total_fee) * 100 ;
                $ret->pay_time =  strtotime(strval($trade->gmt_payment)) ;
                $ret->notify_status = in_array(strval($trade->trade_status), array('TRADE_SUCCESS', 'TRADE_FINISHED')) ? 2 : 1 ;
                if ($ret->notify_status == 2) {
                    return $ret ;
                }
            }
        }
        return $ret ;
    }

    protected function pcDirect($order) {
        $config = $this->getConfigByIndex($order['channel']) ;
        $parameter = [
            'service'           => 'create_direct_pay_by_user',
            'partner'           => $config['partner'],
            '_input_charset'    => 'UTF-8',
            'notify_url'        => $order['notify_url'],
            'return_url'        => $order['return_url'],

            'out_trade_no'      => $order['mer_recharge_no'],
            'subject'           => $order['subject'],
            'payment_type'      => 1,
            'total_fee'         => $order['amount'] / 100,
            'seller_email'      => $config['seller_mail'],
            'body'              => $order['body'],
            'it_b_pay'          => $this->_formatExpireTime($order['expire_time']),
        ] ;

        $alipayObj = $this->_getAlipayObj($config) ;
        $url = $alipayObj->buildRequestParaToUrl($parameter) ;
        return $url ;
    }

    protected function appDirect($order) {
        $config = $this->getConfig($order) ;
        if (!file_exists($privKeyFile = dirname(__FILE__) . '/alipay/cacert/' . $config['partner'] . '_private.pem')) {
            throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '暂时未配置RSA密钥，不支持支付宝移动支付') ;
        }
        $parameter = [
            'partner'           => $config['partner'],
            'seller_id'         => $config['partner'],
            'out_trade_no'      => $order['mer_recharge_no'],
            'subject'           => $order['subject'],
            'body'              => $order['body'] ? $order['body'] : $order['subject'],
            'total_fee'         => intval($order['amount']) / 100,
            'notify_url'        => $order['notify_url'],
            'service'           => 'mobile.securitypay.pay',
            'payment_type'      => 1,
            '_input_charset'    => 'utf-8',
        ] ;

        $arg = '' ;
        while (list($key, $val) = each($parameter)) {
            $arg .= $key . '="' . $val . '"&' ;
        }
        $arg = substr($arg, 0, count($arg) - 2) ;
        $sign = rsaSign($arg, $privKeyFile) ;
        return "{$arg}&sign=\"{$sign}\"&sign_type=\"RSA\"" ;
    }

    public function refundQuery($order) {
        $ret = new \stdClass() ;
        $config = $this->getConfig($order) ;
        if (!$config) {
            $ret->is_success = 'F' ;
            $ret->error = "不存在的交易号:$order[ser_recharge_no]" ;
            return $ret ;
        }
        $parameter = array(
            'service'           => 'refund_fastpay_query',
            'partner'           => $config['partner'],
            '_input_charset'    => 'utf-8',
            'trade_no'          => $order['ser_recharge_no'],
        ) ;

        $alipayObj = $this->_getAlipayObj($config) ;
        $url = $alipayObj->buildRequestParaToUrl($parameter) ;

        $content = file_get_contents($url) ;
        $content = preg_replace("/encoding=(\"|')GBK(\"|')/i", "encoding=\"utf-8\"", $content) ;
        $service_ret = simplexml_load_string($content) ;
        $ret->is_success = strval($service_ret->is_success) ;
        $ret->error = strval($service_ret->error) ;
        if ($ret->is_success == 'T') {
            $ret->RefundDetail = strval($service_ret->result_details) ;
            if (false !== strpos($ret->RefundDetail, 'SUCCESS')) {
                $ret->code = 1 ;
                $ret->is_refund = true ;
                $ret->error = '该笔订单已经退过款，退款不允许超过两次' ;
            }
        }
        return $ret ;
    }

    public function refund($order) {
        $ret = new \stdClass() ;
        $ret->haveNotify = true ;
        $config = $this->getConfig($order) ;
        if (!$config) {
            $ret->is_success = 'F' ;
            $ret->error = "不存在的交易号:$order[ser_recharge_no]" ;
            return $ret ;
        }
        $detail_data = $order['ser_recharge_no'] . '^' . $order['refund_amount'] / 100 . '^' . $order['subject'] ;
        $parameter = array(
            'service'           => 'refund_fastpay_by_platform_nopwd',
            'partner'           => $config['partner'],
            '_input_charset'    => 'utf-8',
            'notify_url'        => $order['notify_url'],
            'batch_no'          => $order['mer_refund_no'],
            'refund_date'       => date('Y-m-d H:i:s', $order['create_time']),
            'batch_num'         => 1,
            'detail_data'       => $detail_data,
        ) ;

        $alipayObj = $this->_getAlipayObj($config) ;
        $url = $alipayObj->buildRequestParaToUrl($parameter) ;

        $content = file_get_contents($url) ;
        $content = preg_replace("/encoding=(\"|')GBK(\"|')/i", "encoding=\"utf-8\"", $content) ;
        $service_ret = simplexml_load_string($content) ;
        $ret->is_success = strval($service_ret->is_success) ;
        if (isset($service_ret->error)){
            $ret->error = strval($service_ret->error) ;
        }
        return $ret ;
    }

    public function trans($orders) {
        $ret = new \stdClass() ;
        $config = $this->getConfig($orders[0]) ;

        $batchFee = 0 ;
        $detailData = '' ;
        foreach ($orders as $order) {
            $batchFee += $order['trans_amount'] ;
            $detailData .= sprintf("%s^%s^%s^%s^%s",
                $order['mer_trans_no'],
                $order['user_account'],
                $order['user_name'],
                $order['trans_amount'] / 100,
                CommonUtil::urlencodeSpec($order['subject'], "^|#$"
            )) ;
            $detailData .= "|" ;

        }
        $parameter = [
            'service'           => 'batch_trans_notify',
            'partner'           => $config['partner'],
            '_input_charset'    => 'utf-8',
            'notify_url'        => $orders[0]['notify_url'],

            'account_name'      => $config['seller_personname'],
            'detail_data'       => substr($detailData, 0, -1),
            'batch_no'          => $orders[0]['batch_no'],
            'batch_num'         => count($orders),
            'batch_fee'         => $batchFee / 100,
            'pay_date'          => date('Ymd', $orders[0]['timestamp']),
            'email'             => $config['seller_mail'],

        ] ;
        $alipayObj = $this->_getAlipayObj($config) ;
        return $alipayObj->buildRequestParaToUrl($parameter) ;
    }

    public function notify($channel) {
        $signType = CommonUtil::getRequestParam('sign_type', 'MD5', 'P') ;
        switch ($signType) {
            case 'RSA' :
                return $this->appNotify($channel);
                break ;
            default :
                return $this->pcNotify($channel);
                break ;
        }
    }

    protected function pcNotify($channel) {
        $configs = $this->configs[$channel] ;

        $verifyResult = false ;
        foreach ($configs as $config) {
            $notifyObj = new \AlipayNotify($this->_alipayConf($config)) ;
            $verifyResult = $notifyObj->verifyNotify() ;
            if ($verifyResult) {
                break ;
            }
        }

        $notice = [] ;
        if ($verifyResult) {
            $notifyType = CommonUtil::getRequestParam('notify_type') ;
            switch($notifyType) {
                case 'trade_status_sync' :
                    $tradeStatus = CommonUtil::getRequestParam('trade_status') ;
                    if ($tradeStatus == 'TRADE_SUCCESS') {
                        $buyerEmail = CommonUtil::getRequestParam('buyer_email') ;
                        $bankSeqNo = CommonUtil::getRequestParam('bank_seq_no') ;
                        $notice['type'] = 'recharge' ;
                        $notice['mer_recharge_no'] = CommonUtil::getRequestParam('out_trade_no') ;
                        $notice['ser_recharge_no'] = CommonUtil::getRequestParam('trade_no') ;
                        $notice['gateway_account'] = empty($bankSeqNo) ? $buyerEmail : $bankSeqNo ;
                        $notice['seller_partner'] = $config['partner'] ;
                        $notice['notify_status'] = 2 ;
                        $notice['notify_log'] = '支付成功' ;
                        $notice['pay_time'] =  strtotime(CommonUtil::getRequestParam('gmt_payment')) ;
                        $notice['notify_time'] = CommonUtil::getRequestParam('notify_time') ;

                        $notice['output'] = 'success' ;
                    } else {
                        throw new \Exception('fail') ;
                    }
                    break ;

                case 'batch_refund_notify' :
                    $refundDetail = explode('^', CommonUtil::getRequestParam('result_details'));
                    $notice = [
                        'type'              => 'refund',
                        'mer_refund_no'     => CommonUtil::getRequestParam('batch_no'),
                        'ser_refund_no'     => $refundDetail[0],
                        'notify_status'     => $refundDetail[2] == 'SUCCESS' ? 2 : 3,
                        'notify_log'        => $refundDetail[2],
                        'notify_time'       => strtotime(CommonUtil::getRequestParam('notify_time')),
                        'refund_time'       => strtotime(CommonUtil::getRequestParam('notify_time')),

                        'output'            => 'success',
                    ] ;
                    break ;

                case 'batch_trans_notify' :
                    $notice['type'] = 'trans' ;
                    $notice['details'] = [] ;
                    $successDetails = explode('|', CommonUtil::getRequestParam('success_details')) ;
                    $failDetails = explode('|', CommonUtil::getRequestParam('fail_details')) ;
                    $resultDetails = array_merge($successDetails, $failDetails) ;
                    #成功:流水号^收款方账号^收款账号姓名^付款金额^成功标识(S)^成功原因(null)^支付宝内部流水号^完成时间
                    #失败:流水号^收款方账号^收款账号姓名^付款金额^失败标识(F)^失败原因^支付宝内部流水号^完成时间
                    foreach($resultDetails as $result) {
                        if (!$result) continue ;
                        $detail = explode('^', $result) ;
                        $notice['details'][] = [
                            'seller_partner'    => CommonUtil::getRequestParam('pay_user_id'),
                            'batch_no'          => CommonUtil::getRequestParam('batch_no'),
                            'mer_trans_no'      => $detail[0],
                            'user_account'      => $detail[1],
                            'user_name'         => $detail[2],
                            'trans_amount'      => intval($detail[3] * 100),
                            'notify_status'     => $detail[4] == 'S' ? 2 : 3,
                            'notify_log'        => !empty($detail[5]) ? $detail[5] : $detail[4],
                            'ser_trans_no'      => $detail[6],
                            'notify_time'       => strtotime(CommonUtil::getRequestParam('notify_time')),
                            'pay_time'          => strtotime($detail[7]),
                        ] ;
                    }
                    $notice['output'] = 'success' ;
                    break ;
            }
        }
        return $notice ;
    }

    protected function appNotify($channel) {
        $configs = $this->configs[$channel] ;
        $verifyResult = false ;
        foreach ($configs as $config) {
            $config['sign_type'] = 'RSA' ;
            $notifyObj = new \AlipayNotify($this->_alipayConf($config)) ;
            $verifyResult = $notifyObj->verifyNotify() ;
            if ($verifyResult) {
                break ;
            }
        }
        $notice = [] ;
        if ($verifyResult) {
            if (in_array(strval(CommonUtil::getRequestParam('trade_status')), ['TRADE_FINISHED', 'TRADE_SUCCESS'])) {
                $notice['type'] = 'recharge' ;
                $notice['mer_recharge_no'] = strval(CommonUtil::getRequestParam('out_trade_no')) ;
                $notice['ser_recharge_no'] = strval(CommonUtil::getRequestParam('trade_no')) ;
                $notice['gateway_account'] = strval(CommonUtil::getRequestParam('buyer_email')) ;
                $notice['seller_partner'] = CommonUtil::getRequestParam('seller_id') ;
                $notice['notify_status'] = 2 ;
                $notice['notify_log'] = '支付成功' ;
                $notice['pay_time'] =  strtotime(CommonUtil::getRequestParam('gmt_payment')) ;
                $notice['notify_time'] = CommonUtil::getRequestParam('notify_time') ;

                $notice['output'] = 'success' ;
            }
        }
        return $notice ;
    }

    private function _getAlipayObj($config) {
        return new \AlipaySubmit($this->_alipayConf($config)) ;
    }

    private function _alipayConf($config) {
        return [
            'partner'       => $config['partner'],
            'key'           => $config['security_code'],
            'seller_email'  => $config['seller_mail'],
            'sign_type'     => isset($config['sign_type']) ? $config['sign_type'] : strtoupper('MD5'),
            'input_charset' => strtolower('utf-8'),
            'transport'     => self::TRANSPORT,
        ] ;
    }

    private function _formatExpireTime($expireTime) {
        $expireTime = ceil(($expireTime - time()) / 60) ;
        if ($expireTime >= 21600) {
            $expireTime = '15d' ;
        } elseif ($expireTime > 0) {
            $expireTime = "{$expireTime}m" ;
        } else {
            $expireTime = '15d' ;
        }
        return $expireTime ;
    }
}
