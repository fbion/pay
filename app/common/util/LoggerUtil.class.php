<?php
/**
 * @brief 日志Util
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Util ;

class LoggerUtil extends BaseUtil {

    const FILE_LOG_DIR = '/data/logs/pay/' ;
    const PAYCENTER = 'paycenter' ;

    const APP = 'pay' ;

    const LOG_W = 'Warn' ;
    const LOG_E = 'Error' ;
    const LOG_F = 'Fatal' ;
    const LOG_I = 'Info' ;
    const LOG_D = 'Debug' ;

    /**
     * @brief 记录日志 logError
     * @param string $msg 日志内容
     * @param string $category 类别
     */
    public static function e($msg = '', $category = '') {
        self::_log(self::LOG_E, $msg, $category);
    }

    /**
     * @brief 记录日志 logWarn
     * @param string $msg 日志内容
     * @param string $category 类别
     */
    public static function w($msg = '', $category = '') {
        self::_log(self::LOG_W, $msg, $category);
    }

    /**
     * @brief 记录日志logInfo
     * @param string $msg 日志内容
     * @param string $category 类别
     */
    public static function i($msg = '', $category = '') {
        self::_log(self::LOG_I, $msg, $category) ;
    }

    /**
     * @brief 记录日志logFatal
     * @param string $msg 日志内容
     * @param string $category 类别
     */
    public static function f($msg = '', $category = '') {
        self::_log(self::LOG_F, $msg, $category) ;
    }
    
    /**
     * @brief 记录日志logFatal
     * @param string $msg 日志内容
     * @param string $category 类别
     */
    public static function D($msg = '', $category = '') {
        self::_log(self::LOG_D, $msg, $category) ;
    }

    /**
     * @brief 记录文本日志payLog
     * @param string $msg 日志内容
     */
    public static function payLog($msg) {
        self::_fileLog($msg, '') ; 
    }

    /**
     * @brief 记录文本日志paycenterLog
     * @param string $msg 日志内容
     */
    public static function payCenterLog($msg) {
        self::_fileLog($msg, self::PAYCENTER) ; 
    }

    /**
     * @brief 记录日志
     * @param string $m Error|Warn|Info|Debug|Fatal...
     * @param string $msg 日志内容
     * @param string $category 类别
     * @return bool $ret
     */
    private static function _log($m='', $msg='', $category='') {
        $ret = false;
        if (class_exists('Logger', false)) {
            return call_user_func_array(array('Logger', 'log' . $m), array($msg, self::APP . '.' . $category));
        }
        return $ret;
    }

    /**
     * @brief 记录文本log
     * @param string $msg 日志内容
     * @param string $type paycenter |
     */
    private static function _fileLog($msg, $type='') {
        $fileLogDir = storage_path() . '/logs/' ;
        if (!is_dir($fileLogDir)) {
            mkdir($fileLogDir , 0777) ;
        }
        $file = $type == self::PAYCENTER 
            ? $type . '_' . date('Ymd') . '.log'
            : 'pay_' . date('Ymd') . '.log' ;
        $logFile = $fileLogDir . $file ;
        if (!file_exists($logFile)) {
            file_put_contents($logFile, '') ;
            chmod($logFile, 0777) ;
        }
        file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . $msg . "\n", FILE_APPEND) ;
    }

}
