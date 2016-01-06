<?php
/**
 * @brief 易宝支付
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-09-17
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Gateway ;

use Pay\Common\Util\CommonUtil ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;

require_once dirname(__FILE__) . '/yeepay/YeepayServer.class.php' ;

class YeepayGateway extends BaseGateway {

    protected $configs = array(
        1 => array (
            /// 测试
            //array (
            //    'partner'           => '10040003895',
            //    'security_code'     => 'm609xmpgnvqznmqhytote9wdv4a7rnklpeqkjgesbe0dok3x7nh5vs7enxs6',
            //    'seller_personname' => '测试账号',
            //),
            /// 正式
            array (
                'partner'           => '10012431575',
                'security_code'     => '0w98ut113nVYQ11047yzNk69pv1J2JI55pYf0T44316u9w81MFdxR18n2223',
                'seller_personname' => '北京纯粹旅行有限公司',
            ),
        ),
    ) ;

    public function directQuery($order) {
        throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '暂不支持该支付方式') ;
    }

    public function direct($order) {
        throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '暂不支持该支付方式') ;
    }

    public function refundQuery($order) {
        throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '暂不支持该支付方式') ;
    }

    public function refund($order) {
        throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '暂不支持该支付方式') ;
    }

    public function trans($orders) {
        $ret = new \stdClass() ;
        $config = $this->getConfig($orders[0]) ;
        $yeeServer = new \YeepayServer($config) ;

        $batchFee = 0 ;
        $itmeXml = '' ;
        foreach ($orders as $order) {
            $batchFee += $order['trans_amount'] ;
            $itemXml .= $yeeServer->arrayToXml(['item' => [
                'order_Id'          => $order['mer_trans_no'],
                'bank_Code'         => $order['bank_code'],
                'amount'            => $order['trans_amount'] / 100,
                'account_Name'      => $order['user_name'],
                'account_Number'    => $order['user_account'],
                'account_Type'      => 'pr',
                'fee_Type'          => 'SOURCE',    //TARGET
                'urgency'           => 0,           //是否加急

                'payee_Mobile'      => $order['mobile_no'],
                'abstractInfo'      => $order['subject'],
                'remarksInfo'       => $order['body'] ? $order['body'] : $order['subject'],
            ]], false) ;
        }
        $parameter = [
            'cmd'           => 'TransferBatch',
            'mer_Id'        => $config['partner'],
            'batch_No'      => $orders[0]['batch_no'],
            'total_Num'     => count($orders),
            'total_Amt'     => $batchFee / 100,
            'is_Repay'      => 1,

        ] ;
        if (!$parameter['hmac'] = $yeeServer->hMac($parameter)) {
            throw new PayException(ErrCode::ERR_GATEWAY_FAIL, '获取hMac失败!') ;
        }
        $parameter['Version'] = '1.0' ;
        $parameter['group_Id'] = $config['partner'] ;
        $parameter['list'] = $itemXml ;

        $resultXml = $yeeServer->sendRequest($parameter) ;
        $result = CommonUtil::xmlToArray($resultXml) ;
        dd($result) ;
        if (is_array($result)) {
            if ($result['ret_Code'] !== 1) {
                throw new PayException(ErrCode::ERR_GATEWAY_FAIL, $result['error_Msg']) ;
            }
        }
        return '' ;
    }

    public function transQuery($order) {
        $config = $this->getConfig($order) ;
        $parameter = [
            'cmd'           => 'BatchDetailQuery',
            'mer_Id'        => $config['partner'],
            'batch_No'      => $order['batch_no'],
            'order_Id'      => $order['mer_tran_no'], 
            'page_No'       => 1,
        ] ;
        $parameter['hmac'] = $yeeServer->hMac($parameter) ;
        $parameter['version'] = '1.0' ;
        $parameter['group_Id'] = $config['partner'] ;                  
        $parameter['query_Mode'] = 1 ;

        $resultXml = $yeeServer->sendRequest($parameter) ;
        $result = CommonUtil::xmlToArray($resultXml) ;

        $notice['type'] = 'trans' ;
        $notice['details'] = [] ;
        if (is_array($result)) {
            if ($result['ret_Code'] == 1) {
                foreach ($result['list'] as $item) {
                    $notice['details'][] = [
                        'seller_partner'    => $item['mer_id'],
                        'batch_no'          => $result['batch_No'],
                        'mer_trans_no'      => $item['order_Id'],
                        'user_account'      => $item['payee_Bank_Account'],
                        'user_name'         => $item['payee_Name'],
                        'trans_amount'      => $item['real_pay_amount'],
                        'notify_status'     => $item['bank_Status'] == 'S' ? 2 : 3,     //待处理
                        'notify_log'        => !empty($item['fail_Desc']) ? $item['fail_Desc'] : 'Success',
                        'ser_trans_no'      => '',
                        'notify_time'       => time(),
                        'pay_time'          => strtotime($item['complete_Date']),
                    ] ;
                }
            }
        }
        return $notice ;
    }

    public function notify($channel) {
    }

}
