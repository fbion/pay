<?php
/**
 * @brief Http请求Util
 * @author IvanHuang <ivanhuang@huangbaoche.com>
 * @since 2015-06-18
 * @copyright Copyright (c) www.huangbaoche.com
 */

namespace Pay\Common\Util  ;

class HttpUtil extends BaseUtil {

    const CONNECT_TIMEOUT = 10 ;
    const READ_TIMEOUT = 10 ;
    
    public static $DEFAULT_CURLOPTS = array(
        CURLOPT_CONNECTTIMEOUT    => 3,
        CURLOPT_TIMEOUT           => 5,
        CURLOPT_USERAGENT         => 'huangbaoche-php-1.0',
        CURLOPT_HTTP_VERSION      => CURL_HTTP_VERSION_1_1,
        CURLOPT_RETURNTRANSFER    => true,
        CURLOPT_HEADER            => false,
        CURLOPT_FOLLOWLOCATION    => false,
    ) ;

    public static function socketGet($url, $connectTimeout, $readTimeout = 0, $retry = 0) {
        $urlInfo = parse_url($url) ;
        $urlInfo['path'] = (empty($urlInfo['path'])) ? '/' : $urlInfo['path'] ;
        $urlInfo['port'] = (!isset($urlInfo['port'])) ? 80 : $urlInfo['port'] ;
        $urlInfo['query'] = (empty($urlInfo['query'])) ? '' : '?' . $urlInfo['query'] ;
        $urlInfo['fragment'] = (empty($urlInfo['fragment']) ? '' : '#' . $urlInfo['fragment']) ;
        $requestUrl = $urlInfo['path'] . $urlInfo['query'] . $urlInfo['fragment'] ;
        
        $scheme = '' ;
        $port = $urlInfo['port'] ;
        if ($urlInfo['scheme'] == 'https') {
             $scheme = 'ssl://' ;
             //ssl端口默认是443， 端口不能是80
             $port = $urlInfo['port'] != 80 ? $urlInfo['port'] : 443 ;
        }
        $fp = @fsockopen($scheme . $urlInfo['host'], $port, $errno, $errstr, $connectTimeout / 1000.0) ;
        if (!$fp) {
            if (($retry - 1) >= 0) {
                self::socketGet($url, $connectTimeout, $readTimeout, $retry - 1) ;
            } else {
                return false ;
            }
        } else {
            $in  = "GET $requestUrl HTTP/1.0\r\n" ;
            $in .= "Host: {$urlInfo['host']}\r\n" ;
            $in .= "Connection: Close\r\n\r\n" ;
            fwrite($fp, $in) ;
            if ($readTimeout != 0) {
                //设置读取超时
                stream_set_timeout($fp, 0, $readTimeout * 1000) ;
            }
            $out = '' ;
            while (!feof($fp)) {
                $result = fread($fp, 512) ;
                if ($result === false || $result === '') {
                    $md = stream_get_meta_data($fp) ;
                    if ($md['timed_out'] == true) {
                        return false ; //读取超时
                    }
                }
                $out .= $result ;
            }
            fclose($fp) ;
            list($head, $body) = explode("\r\n\r\n", $out) ;
            return $body ;
        }
    }
    
    public static function socketPost($url, $post_string, $connectTimeout=10, $readTimeout=10) {
        $urlInfo = parse_url($url) ;
        $urlInfo["path"] = ($urlInfo["path"] == "" ? "/" : $urlInfo["path"]) ;
        $urlInfo["port"] = (!isset($urlInfo["port"]) ? 80 : $urlInfo["port"]) ;
        $hostIp = gethostbyname($urlInfo["host"]) ;

        $urlInfo["request"] =  $urlInfo["path"]    .
            (empty($urlInfo["query"]) ? "" : "?" . $urlInfo["query"]) .
            (empty($urlInfo["fragment"]) ? "" : "#" . $urlInfo["fragment"]) ;

        $fsock = fsockopen($hostIp, $urlInfo["port"], $errno, $errstr, $connectTimeout) ;
        if (false == $fsock) {
            throw new Exception(sprintf('open socket failed, errno=%s, errstr=%s ', $errno, $errstr)) ;
        }
        /* begin send data */
        $in = "POST " . $urlInfo["request"] . " HTTP/1.0\r\n" ;
        $in .= "Accept: */*\r\n" ;
        $in .= "User-Agent: huangbaoche.com API PHP5 Client 1.0 (non-curl)\r\n" ;
        $in .= "Host: " . $urlInfo["host"] . "\r\n" ;
        $in .= "Content-type: application/x-www-form-urlencoded\r\n" ;
        $in .= "Content-Length: " . strlen($post_string) . "\r\n" ;
        $in .= "Connection: Close\r\n\r\n" ;
        $in .= $post_string . "\r\n\r\n" ;

        stream_set_timeout($fsock, $readTimeout) ;
        if (!fwrite($fsock, $in, strlen($in))) {
            fclose($fsock) ;
            throw new Exception('fclose socket failed!') ;
        }
        unset($in) ;

        $out = "" ;
        while ($buff = fgets($fsock, 2048)) {
            $out .= $buff ;
        }

        fclose($fsock) ;
        $pos = strpos($out, "\r\n\r\n") ;
        $head = substr($out, 0, $pos) ;        //http head
        $status = substr($head, 0, strpos($head, "\r\n")) ;        //http status line
        $body = substr($out, $pos + 4, strlen($out) - ($pos + 4)) ;        //page body
        if (preg_match("/^HTTP\/\d\.\d\s([\d]+)\s.*$/", $status, $matches)) {
            if (intval($matches[1]) / 100 == 2) {//return http get body
                return $body ;
            } else {
                throw new \Exception('http status not ok:' . $matches[1]) ;
            }
        } else {
            throw new \Exception('http status invalid:' . $status . "\nOUT: " . var_export($out, true)) ;
        }
    }

    public static function socketPostJson($url, $post_string, $connectTimeout=3, $readTimeout=3) {
        $urlInfo = parse_url($url) ;
        $urlInfo["path"] = ($urlInfo["path"] == "" ? "/" : $urlInfo["path"]) ;
        $urlInfo["port"] = (!isset($urlInfo["port"]) ? 80 : $urlInfo["port"]) ;
        $hostIp = gethostbyname($urlInfo["host"]) ;

        $urlInfo["request"] =  $urlInfo["path"]    .
            (empty($urlInfo["query"]) ? "" : "?" . $urlInfo["query"]) .
            (empty($urlInfo["fragment"]) ? "" : "#" . $urlInfo["fragment"]) ;

        $fsock = fsockopen($hostIp, $urlInfo["port"], $errno, $errstr, $connectTimeout) ;
        if (false == $fsock) {
            throw new \Exception(sprintf('open socket failed, errno=%s, errstr=%s ', $errno, $errstr)) ;
        }
        $in = "POST " . $urlInfo["request"] . " HTTP/1.0\r\n" ;
        $in .= "Accept: */*\r\n" ;
        $in .= "User-Agent: huangbaoche.com API PHP5 Client 1.0 (non-curl)\r\n" ;
        $in .= "Host: " . $urlInfo["host"] . "\r\n" ;
        $in .= "Content-type: application/json\r\n" ;
        $in .= "Content-Length: " . strlen($post_string) . "\r\n" ;
        $in .= "Connection: Close\r\n\r\n" ;
        $in .= $post_string . "\r\n\r\n" ;

        stream_set_timeout($fsock, $readTimeout) ;
        if (!fwrite($fsock, $in, strlen($in))) {
            fclose($fsock) ;
            throw new \Exception('fclose socket failed!') ;
        }
        unset($in) ;

        $out = "" ;
        while ($buff = fgets($fsock, 2048)) {
            $out .= $buff ;
        }
        fclose($fsock) ;
        $pos = strpos($out, "\r\n\r\n") ;
        $head = substr($out, 0, $pos) ;
        $status = substr($head, 0, strpos($head, "\r\n")) ;
        $body = substr($out, $pos + 4, strlen($out) - ($pos + 4)) ;
        if (preg_match("/^HTTP\/\d\.\d\s([\d]+)\s.*$/", $status, $matches)) {
            if (intval($matches[1]) / 100 == 2) {
                return $body ;
            } else {
                throw new \Exception('http status not ok:' . $matches[1]) ;
            }
        } else {
            throw new \Exception('http status invalid:' . $status . "\nOUT: " . var_export($out, true)) ;
        }
    }

    public static function get($url, $timeout=2) {
        $timeout = intval($timeout) ;
        $timeout = $timeout > 0 ? $timeout : 2 ;
        $readTimeoutMs  = $timeout * 1000 ;
        //连接超时设置为读超时的一半。
        $connectTimeoutMs = ceil($readTimeoutMs/2) ;
        return self::socketGet($url, $connectTimeoutMs, $readTimeoutMs, 0) ;
    }

}
