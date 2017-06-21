<?php

/**
 * Class CurlHelper
 *
 * @author mluex
 */
class CurlHelper {

    protected $cookieFile;
    protected $requestFailed = false;

    public function setCookieFile($cookieFile) {
        $cookieFile = preg_replace('/[^a-zA-Z0-9\-\._]/','', $cookieFile);
        $this->cookieFile = COOKIE_PATH . DS . $cookieFile;
    }

    public function requestFailed() {
        return $this->requestFailed;
    }

    public function get($url) {
        $this->requestFailed = false;
        $cheader   = array();
        $cheader[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
        $cheader[] = 'Cache-Control: max-age=0';
        $cheader[] = 'Connection: keep-alive';
        $cheader[] = 'Keep-Alive: 300';
        $cheader[] = 'Accept-Charset: UTF-8;q=0.7,*;q=0.7';
        $cheader[] = 'Accept-Language: de,de-DE,en-US;q=0.7,en;q=0.3';
        $cheader[] = 'Pragma: no-cache';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $cheader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $result = curl_exec($ch);

        if($result === false) {
            Script::log("warning", "GET request failed: " . curl_error($ch));
            $this->requestFailed = true;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($statusCode !== 200) {
            Script::log("warning", "GET request failed with status code: " . $statusCode);
            $this->requestFailed = true;
        }

        curl_close($ch);
        return $result;
    }


    function post($url, $postData) {
        $this->requestFailed = false;
        $cheader   = array();
        $cheader[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
        $cheader[] = 'Cache-Control: max-age=0';
        $cheader[] = 'Connection: keep-alive';
        $cheader[] = 'Keep-Alive: 300';
        $cheader[] = 'Accept-Charset: UTF-8;q=0.7,*;q=0.7';
        $cheader[] = 'Accept-Language: de,de-DE,en-US;q=0.7,en;q=0.3';
        $cheader[] = 'Pragma: no-cache';
        foreach($postData as $key => $value) {
            $postData[$key] = urlencode($value);
        }
        $postString = '';
        foreach($postData as $key => $value) {
            $postString .= $key . '=' . $value . '&';
        }
        $postString = substr($postString, 0, -1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $cheader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, count($postData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $result = curl_exec($ch);

        if($result === false) {
            Script::log("warning", "POST request failed: " . curl_error($ch));
            $this->requestFailed = true;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($statusCode !== 200) {
            Script::log("warning", "POST request failed with status code: " . $statusCode);
            $this->requestFailed = true;
        }

        curl_close($ch);
        return $result;
    }

}