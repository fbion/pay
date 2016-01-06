<?php
/**
 * @brief 验证基类
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Validator ;

abstract class BaseValidator {

    protected $data ;
    public $errors ;
    public static $rules = array() ;

    public function __construct($data=null) {
        $this->data = $data ? : \Input::all() ;
    }

    public function passes($rules=array()) {
        if ($rules) static::$rules = $rules ;
        $validation = \Validator::make($this->data, static::$rules) ;
        if ($validation->passes()) return true ;
        $this->errors = $validation->messages() ;
        return false ;
    }

}
