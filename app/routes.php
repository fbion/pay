<?php
/**
 * @brief 支付路由
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

    Route::any('/test/callback', function() {
        file_put_contents('/tmp/a.log', var_export(\Input::All(), true), FILE_APPEND) ;
        $field = \Input::All() ;
        $field['status'] = 300 ;
        echo json_encode($field);
        exit; 
    }) ;
/// test
Route::group(array('before' => 'check_channel|check_sign'), function() {
}) ;
/// interface
Route::group(array('before' => 'check_channel|checksign'), function() {

    /// account
    Route::group(array(), function () {
        Route::any('/account/{func}/{args?}', 'Pay\Controller\AccountController@run') ;
    }) ;

    /// consume
    Route::group(array(), function () {
        Route::any('/consume/{func}/{args?}', 'Pay\Controller\ConsumeController@run') ;
    }) ;

    /// refund
    Route::group(array(), function () {
        Route::any('/refund/{func}/{args?}', 'Pay\Controller\RefundController@run') ;
    }) ;

    /// trans
    Route::group(array(), function () {
        Route::any('/trans/{func}/{args?}', 'Pay\Controller\TransController@run') ;
    }) ;
}) ;

/// notice
Route::group(array(), function () {
    Route::get('/return/{channel}/{gateway}/{mer_recharge_no}/{plat}/{timestamp}/{sign}', 'Pay\Controller\Web\ReturnPage@returnAction')
        ->where(array('channel' => '\w\d+', 'gateway' => '\w\d+', 'mer_recharge_no' => '\w\d+\w+?', 'plat' => '\w\d', 'timestamp' => '\w\d+')) ;

    Route::any('/notify/{notify_type}/{channel}/{gateway}', 'Pay\Controller\Web\NotifyPage@notifyAction') 
        ->where(array('notify_type' => '\w+', 'channel' => '\d+', 'gateway' => '\d+')) ;
}) ;
