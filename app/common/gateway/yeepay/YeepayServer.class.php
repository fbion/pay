<?php
/**
 * @brief 易宝支付SDK
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-09-17
 * @copyright Copyright (c) www.huangbaoche.com
 */

class YeepayServer {

    //const GATEWAY_URL = 'http://ct.yeepay.com/app-merchant-proxy/groupTransferController.action' ;
    //const GATEWAY_BALANCE_URL = 'http://ct.yeepay.com/app-merchant-proxy/transferController.action' ;
    const GATEWAY_BALANCE_URL = 'https://cha.yeepay.com/app-merchant-proxy/transferController.action' ;
    const GATEWAY_URL = 'https://cha.yeepay.com/app-merchant-proxy/groupTransferController.action' ;
    const H_MAC_URL_PRE = 'http://127.0.0.1:1247/sign?req=' ;

    private $_groupId = null ;
    private $_merId = null ;
    private $_key = null ;

    public function __construct(Array $config) {
        $this->_groupId     = $config['partner'] ;
        $this->_merId       = $config['partner'] ;
        $this->_key         = $config['security_code'] ;
    }

    public function hMac($data) {
        $str = $this->_buildSignStr($data) ;
        $url = self::H_MAC_URL_PRE . $str ;
        return $this->getCurl($url) ;
    }

    public function arrayToXml(Array $arr, $conHeader=true) {
        $xml = $conHeader ? '<?xml version="1.0" encoding="GBK"?>' . "\n" . "<data>\n" : '' ;
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<item>\n" . self::arrayToXml($val, false) . "</item>\n" ;
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">\n"  ;
            }
        }
        $xml .= $conHeader ? "</data>\n" : '' ;
        return $xml ; 
    }

    public function sendRequest($data) {
        $postXml = $this->arrayToXml($data) ;
        $postXml = iconv('utf8', 'gbk', $postXml) ;
        echo $postXml;
        $url = self::GATEWAY_URL ;
        return $this->postXmlCurl($url, $postXml) ; 
    }

    public function postXmlCurl($url, $xml, $second=30) {
        $ch = curl_init() ;
        curl_setopt($ch, CURLOPT_URL, $url) ;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false) ;
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false) ;
        curl_setopt($ch, CURLOPT_HEADER, false) ;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
        curl_setopt($ch, CURLOPT_POST, true) ;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml) ;
        $data = curl_exec($ch) ;
        if ($data) {
            curl_close($ch) ;
            return $data ;
        } else { 
            $error = curl_errno($ch) ;
            echo "curl出错，错误码:$error"."<br>" ; 
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>" ;
            curl_close($ch) ;
            return false ;
        }
    }
     
    public function getCurl($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0) ;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false) ;
        $data = curl_exec($ch)  ;
        curl_close($ch) ;
        return $data ;
    }

    private function _buildSignStr(Array $arr) {
        return implode('', $arr) ;
    }

}
