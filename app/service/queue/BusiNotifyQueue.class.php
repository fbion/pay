<?php
/**
 * @brief 支付回调业务线队列
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-27
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Service\Queue ;

use Pay\Bll\BusiNotifyBiz ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Model\BaseModel ;
use Pay\Model\RechargeModel ;
use Pay\Model\ConsumeModel ;
use Pay\Model\RefundModel ;
use Pay\Model\TransModel ;

class BusiNotifyQueue extends BaseQueue {

    public function fire($job, $data) {
        $orderId = $data['id'] ;
        $ret = true ;
        switch ($data['notify_type']) {
            case 'direct' :
                $order = RechargeModel::find($orderId) ;
                break ;
            case 'consume' :
                $order = ConsumeModel::find($orderId) ;
                break ;
            case 'refund' :
                $order = RefundModel::find($orderId) ;
                break ;
            case 'trans' :
                $order = TransModel::find($orderId) ;
                break ;
        }

        if ($order && $order->callback_status != 2 && $order->callback_count < 20) {
            try {
                $ret = BusiNotifyBiz::getInstance()->notifyCallback($order, $data['notify_type']) ; 
            } catch (PayException $e) {
                $ret = true ; 
            } catch (\Exception $e) {
                $ret = false ; 
            }
            $job->release(pow($order->callback_count, 2) * 60) ;
        }
        if ($ret == true) {
            $job->delete() ;
        }
    }
}
