<?php
/**
 * @brief 支付控制器基类
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller ;

use Pay\Common\Util\SignUtil ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;

abstract class BaseController extends \Controller {

    protected $validation = null ;

    protected $ret = [
        'is_success'    => 'T',
        'err_code'      => ErrCode::ERR_SUCCESS, 
        'err_msg'       => '',
    ] ;

    public function __construct() {
        $this->ret['timestamp'] = time() ;
    }

    public function run($func, $args=null){
        try {
            $data = call_user_func_array(array($this, camel_case($func)), array($args)) ;
            $this->ret = array_merge($this->ret, (array) $data) ;
        } catch (PayException $e) {
            $this->ret['err_code'] = $e->getCode() ;
            $this->ret['err_msg'] = $e->getMessage() ;
        } catch (\Exception $e) {
            $exceMsg = sprintf("MSG=%s\nTRACE=%s\nPARAM=%s",
                $e->getMessage(),
                $e->getTraceAsString(),
                var_export(\Input::All(), true)
            ) ;
            $this->ret['err_code'] = ErrCode::ERR_SYSTEM ;
            $this->ret['err_msg'] = ErrCode::$errMsg[ErrCode::ERR_SYSTEM] ;
        } 
        return $this->render() ;
    }

    protected function render() {
        if (!isset($this->ret['channel'])) {
            $this->ret['channel'] = \Input::get('channel') ;
        }
        $this->ret['is_success'] = $this->ret['err_code'] == ErrCode::ERR_SUCCESS ? 'T' : 'F' ;
        $this->ret['sign'] = SignUtil::makeSign((array)$this->ret) ;
        return \Response::json($this->ret) ;
    }
}
