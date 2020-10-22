<?php


namespace App;


class Request
{
    private $_proxy = [];

    public function __construct($proxy)
    {
        $this->_proxy = $proxy;
    }


    public function get($url, $headers = [])
    {
        $proxy = $this->_proxy[array_rand($this->_proxy)];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($proxy)
            curl_setopt($ch, CURLOPT_PROXY, $proxy);


        $cookieFile = __DIR__ . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cookies.txt';

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        $res = curl_exec($ch);
        $data = ['data' => $res, 'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE)];
        curl_close($ch);

        return $data;
    }
}
