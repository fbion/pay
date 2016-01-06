<?php
/**
 * @brief 支付方式接口
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Gateway\Interfaces ;

interface BaseGatewayInterface {

    public function direct($order) ;

    public function directQuery($order) ;

    public function refund($order) ;

    public function refundQuery($order) ;

    public function notify($channel) ;

}
