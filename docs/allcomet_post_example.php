<?php
//Allcomet post example
function pay_curl($url, $request, $header='')
{
    $curl = curl_init();

    curl_setopt($curl,CURLOPT_TIMEOUT,30);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    $post_data = http_build_query($request);
    if(!empty($header)) {
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
    }else {
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type: application/x-www-form-urlencoded; charset=utf-8','Content-Length:'.strlen($post_data)]);
    }
    curl_setopt($curl,CURLOPT_POST,1);
    curl_setopt($curl,CURLOPT_URL,$url);
    curl_setopt($curl,CURLOPT_POSTFIELDS,$post_data);
    $return = curl_exec($curl);
    $reponse_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    if($return == NULL) {
        $error_info = 'call http error info :'.curl_errno($curl) . '-'.curl_error($curl);
        curl_close($curl);
        return $error_info;
    }else if($reponse_code != 200) {
        $error_info = 'call http error httpcode :'.$reponse_code;
        curl_close($curl);
        return $error_info;
    }

    curl_close($curl);
    return $return;
}

function createSign($signField, $signKey = '') {

    ksort($signField);
    $signString = '';
    foreach ($signField as $name=>$value)
    {
        if($name == 'md5Info')
            continue;
        $signString .= $name . '=' . $value . '&';
    }
    $md5Info = md5($signString . 'key=' . $signKey);
    return $md5Info;
}

$merNo  = "";//Merchant number
$key    = "";//Merchant key
$url    = "";//pay request url


$signField = [
        'merNo'     => $merNo,
        'billNo'    => time(),
        'amount'    => '111',
        'currency'  =>  1,
        'firstName' => 'firstName',
        'lastName'  => 'lastName',
        'phone'     => 'phone',
        'email'     => 'email@email.com',
        'address'   => 'address',
        'city'      => 'city',
        'state'     => 'state',
        'country'   => 'country',
        'zipCode'   => 'zipCode',
        'shippingFirstName' => 'firstName',
        'shippingLastName'  => 'lastName',
        'shippingAddress'   => 'address',
        'shippingCity'      => 'city',
        'shippingState'     => 'state',
        'shippingCountry'   => 'country',
        'shippingZipCode'   => 'zipCode',
        'shippingPhone'     => 'phone',
        'shippingEmail'     => 'email',
        'productInfo'   => '{}',
        'returnURL'     => 'returnURL',
        'tradeUrl'      => 'tradeUrl',
        'notifyUrl'     => "notifyUrl" ,
        'ip'            => '0.0.0.0',
        'cardNum'        => '40000000000000001',
        'cvv2'   => '432',
        'year'    => '2025',
        'month'   => '12',
        'isThreeDPay' => "N",
        "dataTime" => date("YmdHis")
    ];
$post = $signField;
$post['md5Info'] = createSign($signField,$key);

$return_data = pay_curl($url,$post);
$reponse = json_decode($return_data);

var_dump($reponse);


