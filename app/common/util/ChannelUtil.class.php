<?php
/**
 * @brief 频道Util
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Util ;

use Pay\Common\Config\PayVars ;

class ChannelUtil extends BaseUtil {

    public static function checkChannel($channel) {
        return isset(PayVars::$channels) && isset(PayVars::$channels[$channel]) 
            ? $channel : false ;
    }

    public static function calculateAccountChannel($channel) {
        if (false === self::checkChannel($channel)) {
            return false ;
        }
        return PayVars::$channels[$channel]['acc_channel'] ;
    }
}
