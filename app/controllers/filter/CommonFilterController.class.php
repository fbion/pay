<?php
/**
 * @brief 公共过滤控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller\Filter ;

use Pay\Common\Config\ErrCode ;
use Pay\Common\Util\ChannelUtil ;
use Pay\Common\Util\GatewayUtil ;
use Pay\Common\Util\SignUtil ;

class CommonFilterController extends BaseFilterController {

    public function checkChannel() {
        $channel = \Input::get('channel', 0) ;
        if (false === ChannelUtil::checkChannel($channel)) {
            $this->ret['err_code'] = ErrCode::ERR_PARAM ;
            return $this->render() ;
        }
    }

    public function checkGateway() {
        $gateway = \Input::get('gateway', 0) ;
        if (false === GatewayUtil::checkGateway($gateway)) {
            $this->ret['err_code'] = ErrCode::ERR_PARAM ;
            return $this->render() ;
        }
    }

    public function checkSign() {
        $fields = \Input::All() ;
        if (!\Input::has('sign') || true !== SignUtil::checkSign($fields)) {
            $this->ret['err_code'] = ErrCode::ERR_SIGN_ERROR ;
            return $this->render() ;
        }
    }

}
