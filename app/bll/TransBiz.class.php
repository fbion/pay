<?php
/**
 * @brief 提现(转账)业务处理
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-07-16
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Bll ;

use Pay\Common\Config\PayVars ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Util\CommonUtil ;
use Pay\Common\Util\ChannelUtil ;
use Pay\Common\Util\GatewayUtil ;
use Pay\Common\Excep\PayException ;
use Pay\Common\Validator\TransValidator ;
use Pay\Model\TransModel ;

class TransBiz extends BaseBiz {

    protected static $instance ;
    private $_allowTransGateway = [PayVars::GATEWAY_ALIPAY, PayVars::GATEWAY_YEEPAY] ;

    public function createTransOrderForBatch($fields) {

        $longId = CommonUtil::longId() ;
        //$batchNo = $longId . sprintf('%03s', $fields['channel']) . sprintf('%02s', $fields['gateway']) . mt_rand(100, 999) ;
        $batchNo =  date('ymdHis') . mt_rand(100, 999)  ;
        $requestData = json_decode($fields['request_data'], true) ;
        foreach ($requestData as $field) {
            if (!in_array($fields['gateway'], $this->_allowTransGateway)) {
                throw new PayException(ErrorCode::ERR_GATEWAY_FAIL, '该gateway不允许提现') ;
            }
            $validation = new TransValidator($field) ;
            if (!$validation->passes(TransValidator::$transDetailRule)) {
                throw new PayException(ErrCode::ERR_PARAM, $validation->errors) ;
            }
            if ($fields['gateway'] == PayVars::GATEWAY_YEEPAY) {
                if (!isset($field['bank_code']) || !isset(PayBankVars::$yeepayBankAlis[$field['bank_code']])) {
                    throw new PayException(ErrCode::ERR_PARAM, '银行编码错误!') ;
                }
            }
            $account = AccountBiz::getInstance()->getOrCreateAccount($field['user_id'], $fields['channel']) ;

            $primaryId = CommonUtil::LongIdIncrease($longId) ;
            $merTransNo = $primaryId ;
            $insertArr[] = [ 
                'id'                => $primaryId,
                'user_id'           => $field['user_id'],
                'account_id'        => $account['id'],
                'mer_trans_no'      => null !== ($localEnv = \GlobalConfig::getLocalEnv()) ? $merTransNo . $localEnv : $merTransNo,
                'batch_no'          => $batchNo,
                'trans_amount'      => $field['trans_amount'],
                'create_time'       => time(),
                'person_name'       => $field['user_name'],
                'person_account'    => $field['user_account'],

                'channel'           => $fields['channel'],
                'gateway'           => $fields['gateway'],
                'callback_url'      => $fields['callback_url'],
                'subject'           => $fields['subject'],

                'body'              => isset($field['body']) ? $field['body'] : '',
                'mobile_no'         => isset($field['mobile']) ? $field['mobile'] : '',
                'bank_code'         => isset($field['bank_code']) ? $field['bank_code'] : '',
                'busi_trans_no'     => isset($field['busi_trans_no']) ? $field['busi_trans_no'] : '',
            ] ;
        }
        $trans = new TransModel() ;
        if (true !== $trans->insert($insertArr)) {
            throw new PayException(ErrCode::ERR_SYSTEM, '数据库保存失败') ;
        }
        return $batchNo ;
    }

    public function confirmTransOrderById($orderId, $data) {
        $errCode = 0 ;
        $errMsg = '' ;

        \DB::beginTransaction() ;
        do {
            try {
                $order = TransModel::find($orderId) ;
                if (!$order) {
                    throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
                } elseif ($order->status == TransModel::STATUS_SUCCESS) {
                    $errCode = 1 ;
                    break ;
                }

                if (isset($data['ser_trans_no'])) {
                    $order->ser_trans_no = $data['ser_trans_no'] ;
                }

                if (isset($data['ser_notify_log'])) {
                    $order->ser_notify_log = $data['ser_notify_log'] ;
                }

                $order->ser_notify_status = $data['ser_notify_status'] ;
                $order->pay_time = $data['ser_pay_time'] ;
                $order->ser_notify_time = $data['ser_pay_time'] ;
                $order->save() ;
            } catch (\Exception $e) {
                $errCode = 1 ;
            }
        } while (0) ;

        if ($errCode == 0) {
            \DB::commit() ;
            \Queue::later(5, '\Pay\Service\Queue\BusiNotifyQueue', [
                'id' => $order->id,
                'notify_type' => 'trans'
            ]) ;
            return true ;
        } else {
            \DB::rollback() ;
            return false ;
        }
    }

    public function transThirdParty($batchNo) {
        $transArr = TransModel::where('batch_no', $batchNo)->get() ;
        
        foreach ($transArr as $trans) {
            $fields[] = [
                'channel'       => ChannelUtil::calculateAccountChannel($trans['channel']),
                'gateway'       => $trans['gateway'],
                'mer_trans_no'  => $trans['mer_trans_no'],
                'batch_no'      => $trans['batch_no'],
                'bank_code'     => $trans['bank_code'],
                'timestamp'     => $trans['create_time'],
                'trans_amount'  => $trans['trans_amount'],
                'subject'       => $trans['subject'],
                'user_name'     => $trans['person_name'],
                'user_account'  => $trans['person_account'],
                'notify_url'    => PayVars::getNotifyUrl() . "notify/trans/{$trans['channel']}/{$trans['gateway']}",
            ] ;
        }
        $gatewayObj = GatewayUtil::getGatewayObj($fields[0]['gateway']) ;
        return $gatewayObj->trans($fields) ;
    }

}
