<?php

namespace common\extend\baidubce;

use GuzzleHttp\Client;

class BceApi
{

    /**
     * @param $host
     * @param $credient
     * @param $method
     * @param $path
     * @param array $query
     * @param array $params
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function request($host,$credient,$method,$path,$query=[],$params=[])
    {
       $headers = [
           "Host" => $host,
           "Content-Type" => 'application/json'
       ];
       $sign = $this->sign($method,$path,$headers,$query,$credient);
       $headers['Authorization'] = $sign;
       $client = new Client();
       $full_url = $host.$path;
       $options = [];
       $options['headers'] = $headers;
       if(!empty($query)){
           $options['query'] = $query;
       }

       if(!empty($params)){
           $options['json'] = $params;
       }
//       echo \GuzzleHttp\json_encode($options['json']);exit;


       $res = $client->requestAsync($method,$full_url,$options);
       return $res;


    }

    public function sign($method,$path,$headers,$params,$credient)
    {
        $signer = new Signer();
        $timestamp = new \DateTime();
        $timestamp->setTimestamp(time());
        $options = [SignOption::TIMESTAMP => $timestamp];
//        $credient= config('baidubce.default');
        return $signer->sign($credient,$method,$path,$headers,$params,$options);
    }


}