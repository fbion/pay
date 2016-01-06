<?php
/**
 * @brief 提现(转账)控制器
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-07-16
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Controller ;

use Pay\Bll\TransBiz ;
use Pay\Common\Config\ErrCode ;
use Pay\Common\Excep\PayException ;
use Pay\Common\Validator\TransValidator ;

class TransController extends BaseController {

    public function __construct() {
        parent::__construct() ;
        $this->validation = new TransValidator ;
    }

    public function batchTrans() {
        $fields = \Input::All() ;
        if (!$this->validation->passes(TransValidator::$transRule)) { 
            //throw new PayException(ErrCode::ERR_PARAM) ;
        }
        //$batchNo = TransBiz::getInstance()->createTransOrderForBatch($fields) ;
        $batchNo = $fields['batch_no'] ;
        $url = TransBiz::getInstance()->transThirdParty($batchNo) ;
        return [
            'batch_no'          => $batchNo,
            'pay_gateway_url'   => $url,
        ] ;

    }
}
