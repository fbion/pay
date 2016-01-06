<?php
/**
 * @brief 支付账户控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller ;

use Pay\Common\Util\ChannelUtil ;
use Pay\Common\Excep\PayException ;
use Pay\Common\Config\ErrCode ;
use Pay\Bll\AccountBiz ;

class AccountController extends BaseController {

    public static $userId ;
    public static $accChannel ;

    public function getAccount() {
        self::$userId = \Input::get('user_id', '') ;
        self::$accChannel = ChannelUtil::calculateAccountChannel(\Input::get('channel', 0)) ;
        $account = AccountBiz::getInstance()->getAccount(self::$userId, self::$accChannel) ;
        if (!$account) {
            throw new PayException(ErrCode::ERR_ACCOUNT_NO_EXISTS) ;
        }
        return $account ;
    }
}
