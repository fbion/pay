<?php
/**
 * @brief 支付Filter规则
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

use Pay\Common\Util\LoggerUtil ;

App::before(function($request) {
}) ;


App::after(function($request, $response) {
    $logs = sprintf("SERVER: {remote_addr: %s}\nSERVICE: %s\nMETHOD: %s\nREQUEST: %s\n",
        $request->ip(),
        $request->getPathInfo(),
        $request->method(),
        json_encode($request->all())
    ) ; 
    if ($response->getStatusCode() == '200') {
        $logs .= sprintf("RESPONSE: %s\n", $response->getContent()) ;
    }
    LoggerUtil::payLog($logs) ;
}) ;


Route::filter('check_gateway', 'Pay\Controller\Filter\CommonFilterController@CheckGateway');
Route::filter('check_channel', 'Pay\Controller\Filter\CommonFilterController@CheckChannel');
Route::filter('check_sign', 'Pay\Controller\Filter\CommonFilterController@CheckSign');
