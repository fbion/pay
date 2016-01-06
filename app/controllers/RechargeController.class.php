<?php
/**
 * @brief 充值控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller ;

use Pay\Bll\RechargeBiz ;

class RechargeController extends BaseController {

    public function directBalance() {
        $fields = \Input::All() ;
        $ret = RechargeBiz::getInstance()->directBalance($fields) ;
        return $ret ;
    }

}
