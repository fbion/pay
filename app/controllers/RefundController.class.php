<?php
/**
 * @brief 退款控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller ;

use Pay\Common\Excep\PayException ;
use Pay\Bll\ConsumeBiz ;
use Pay\Bll\RechargeBiz ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Validator\RefundValidator ;

class RefundController extends BaseController {

    public function __construct() {
        parent::__construct() ;
        $this->validation = new RefundValidator ;
    }

    public function refundConsumeAndRecharge() {
        if (!$this->validation->passes(RefundValidator::$refundRule)) {
            throw new PayException(ErrCode::ERR_PARAM) ;
        }
        $fields = \Input::All() ;
        $ret = ConsumeBiz::getInstance()->cancelConsumeOrder($fields['pay_consume_id'], $fields['user_id'], $fields['refund_amount']) ;
        if ($ret['pay_consume_id'] > 0 && $ret['pay_recharge_id'] > 0) {
            $fields['pay_recharge_id'] = $ret['pay_recharge_id'] ;
            $cancelRet = RechargeBiz::getInstance()->cancelRechargeOrder($fields) ;
       }
        return $ret ;
    }

}
