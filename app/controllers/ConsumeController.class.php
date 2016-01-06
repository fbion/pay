<?php
/**
 * @brief 消费控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller ;

use Pay\Common\Excep\PayException ;
use Pay\Bll\ConsumeBiz ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Validator\ConsumeValidator ;

class ConsumeController extends BaseController {

    public function __construct() {
        parent::__construct() ;
        $this->validation = new ConsumeValidator ;
    }

    public function directConsume() {
        if (!$this->validation->passes(ConsumeValidator::$directConsumeRule)) { 
            throw new PayException(ErrCode::ERR_PARAM) ;
        }
        return ConsumeBiz::getInstance()->directConsume(\Input::All()) ;
    }

    public function getConsumeOrder() {
        $orderId = \Input::get('pay_consume_id', 0) ;
        if (!$order = ConsumeBiz::getInstance()->getConsumeOrderById($orderId)) {
            throw new PayException(ErrCode::ERR_ORDER_NO_EXISTS) ;
        }
        return $order->toArray() ;
    }

}
