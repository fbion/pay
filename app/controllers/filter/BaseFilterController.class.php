<?php
/**
 * @brief 支付过滤控制器基类
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller\Filter ;

use Pay\Common\Config\ErrCode ;
use Pay\Controller\BaseController ;

class BaseFilterController extends BaseController {

    public function __construct() {
        parent::__construct() ;
    }

    protected function render() {
        if ($this->ret['err_code'] != ErrCode::ERR_SUCCESS) {
            $this->ret['is_success'] = 'F' ;
            $this->ret['err_msg'] = ErrCode::$errMsg[$this->ret['err_code']] ;
            return \Response::json($this->ret) ;
        }
    }
}
