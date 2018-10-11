<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/7/17
 * Time: 8:23 AM
 */

namespace App\Config;


use Twilio\Rest\Client;

class SMS
{
    private $mobile;
    private $message;
    private $username;
    private $password;
    private $api_key;
    private $token;
    public $error;
    private $sender_id;
    private $url;


    public function __construct($mobile=null, $api_key=null, $token=null, $message= null, $url = null, $password = null)
    {
        $this->mobile = null;
        $this->message = null;
        $this->error = null;
        $this->url = $url;
        $this->api_key = $api_key;
        $this->token = $token;
        $this->password = $password;
    }

    public function sendMessage($mobile, $message, $url)
    {
        $this->mobile = $message;
        $this->mobile = $mobile;
        $this->url = $url;

        // configured base on the sms provider
        $connect_url = $url . "?user=" . $this->username . "&&password=" . $this->password . "&phone=" .
            $this->mobile . "&text=" . $this->message . "type=t&senderid" . $this->sender_id;
        $ch = curl_init();
        $timeout = 500000;
        curl_setopt($ch, CURLOPT_URL, $connect_url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data === 'Sent') {
            return 1;
        } else {
            $this->error = $data;
            return $this->error;
        }
    }

    public function send($otp,$phone_number)
    {
        $account_id = "AC79d5bce3545f76290fc885f3c40b1ed2";
        $auth_token = "d78766c4ba19f11d4a23fbc8cc5ebe42";
        $client = new Client($account_id, $auth_token);
        $message = $client->messages->create(
            $phone_number,
            array(
                'from'=>'+12053468146',
                'body' => 'your token is '.$otp
            )
        );
        return $message->sid;
    }
}