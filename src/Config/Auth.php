<?php

namespace App\Config;

use App\Config\RedisDatabase as RD;
use PHPMailer;

class Auth
{
    public $apiKey;
    public $secret;
    public $token;
    public $moneywave_staging_url;

    public function __construct()
    {
        /****  Kanmi's details   ****/
//         $this->apiKey = "ts_AXQS7B8FE8OMT4QN8PX7";
//         $this->secret = "ts_QOY2S2C8M3RNQ75O94UD39QM37Z2MN";
//         $this->token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MzgzLCJuYW1lIjoiRmV0Y2hyIiwiYWNjb3VudE51bWJlciI6IiIsImJhbmtDb2RlIjoiOTk5IiwiaXNBY3RpdmUiOnRydWUsImNyZWF0ZWRBdCI6IjIwMTYtMTItMzFUMDM6MjE6NTMuMDAwWiIsInVwZGF0ZWRBdCI6IjIwMTYtMTItMzFUMDM6MjI6MzEuMDAwWiIsImRlbGV0ZWRBdCI6bnVsbCwiaWF0IjoxNDg3NjIyMzYyLCJleHAiOjE0ODc2Mjk1NjJ9.A-7pDD9Uj_8G4Z5G-h13xfrVp5ULwH5kOfuOXOqmShk";

        /****  Uyoyou's details   ****/
        $this->apiKey = "ts_4J2AIL4C0RCQ8H4RBN6O";
        $this->secret = "ts_VR0H80YMVFCPP5PYGK0713N0AIHCWE";

        $this->senderName = "UAgha"; // same as username
        $this->walletPassword = "Aledin2017"; // same as lock
        $this->currency = "NGN";  // naira

        $this->moneywave_staging_url = 'http://moneywave.herokuapp.com/';
        $this->moneywave_test_url = 'https://moneywave.flutterwave.com/';
        $this->flutterwave_staging_url = 'http://staging1flutterwave.co:8080/';

        $this->output_format = "json"; // json or xml
        $this->output_app_format = "application/xml"; // json or xml
    }

    // to get token: save time into database after first request, then to make a request - call dis function to check the difference between time save in the db and server time, if

    public function getToken()
    {
        $get_data = (new Auth)->getTokenForAccess($this->apiKey);
        $get_json = $get_data['data'];
        $get_array_data = json_decode($get_json, true);
        $this->token = $get_array_data['token'];

        return $this->token;
    }

    public function hasTokenExpire()
    {

        $get_data = (new Auth)->getTokenForAccess($this->apiKey);
        $get_json = $get_data['data'];
        $get_array_data = json_decode($get_json, true);
        $token_date = $get_array_data['token_expiration'];
        $token = $get_array_data['token'];

        // $today_date = "2017-02-25 10:59:37";
        $today_date = date("Y-m-d h:i:s");

        $timeFirst = strtotime($token_date);
        $timeSecond = strtotime($today_date);
        $differenceInSeconds = $timeFirst - $timeSecond;

        if ($differenceInSeconds > 0) {
            $token_message = ["status" => false, "message" => $token];
        } elseif ($differenceInSeconds <= 0) {
            $token_message = ["status" => true, "message" => ""];
        }

        return $token_message;
    }


    public function checkRedisConnection()
    {

        $is_connected = (new RD)->single_client();
        $connected = $is_connected ? "yes" : "no";
        return $connected;
    }

    public function getAllRedisKeys()
    {

        $redis_errors = "";

        $isConnected = $this->checkRedisConnection();
        if ($isConnected == "yes") {
            $client = (new RD)->single_server();
            $allkeys = $client->keys('*');

            return $allkeys;

        } elseif ($isConnected == "no") {
            $redis_errors = "Oops! Unable to connect Database";
        }
        // return $redis_errors;
    }


    public function checkApikKeyExist($apiKey)
    {

        $redis_errors = "";

        $client = (new RD)->single_server();

        $id_exists = $client->exists($apiKey);   // print_r($client->keys('*')); // get all redis keys

        if ($id_exists === 1) {
            $redis_errors = "";
        } elseif ($id_exists === 0) {
            $redis_errors = "Invalid request, apikey does not exist ";
        }
        return $redis_errors;
    }

    public function apiKey()
    {
        $apiKey = "aG91c2dpcmxfdG9rZW5fZm9yX2F1dGhlbnRpY2F0aW9u";
        return $apiKey;
    }

    public function getTokenForAccess($apiKey)
    {

        $result_error = $this->checkApikKeyExist($apiKey);
        if ($result_error == "" || empty($result_error)) {

            $client = (new RD)->single_server();
            $tracking_key = $apiKey;

            $response = $client->get($tracking_key);

            $result = ['success' => true, 'data' => $response];

        } else {

            $result = ['success' => false, 'data' => $result_error];
        }

        return $result;
    }

    public function saveTokenForAccess($apiKey, $token)
    {
        $redis_errors = "";

        $isConnected = $this->checkRedisConnection();
        if ($isConnected == "yes") {

            $token_expiration = date('Y-m-d h:i:s', strtotime('+2 hour')); //the expiration date will be in two hour from the current moment

            // $token_expiration = date('Y-m-d h:i:sa');

            $client = (new RD)->single_server();
            $track_token = json_encode([
                'apiKey' => $apiKey,
                'token' => $token,
                'token_expiration' => $token_expiration
            ]);

            $client->set($apiKey, $track_token);
            $client->expire($track_token, 1200);
            $client->ttl($track_token);

            $redis_errors = null;

        } elseif ($isConnected == "no") {
            $redis_errors = "Oops! Unable to connect Database";
        }

        return $redis_errors;
    }

    public function validateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }


    public function sendMailVerification($email, $username, $password, $token,$account)
    {
        //Tell PHPMailer to use SMTP
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->isHTML(true);
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';
        $mail->Host = 'smtp.elasticemail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;
        $mail->Username = "paypro.ng@gmail.com";
        $mail->Password = "75949b1d-bb86-4604-9da7-5e0561704a1f";
        $mail->setFrom('no-reply@getpaypro.com', 'PayPro');
        //Set who the message is to be sent to
        $mail->addAddress($email, $username);

        //Set the subject line
        $message  = "<html><body>";
   
   $message .= "<table width='100%' bgcolor='#e0e0e0' cellpadding='0' cellspacing='0' border='0'>";
   
   $message .= "<tr><td>";
   
   $message .= "<table align='center' width='100%' border='0' cellpadding='0' cellspacing='0' style='max-width:650px; background-color:#fff; font-family:Verdana, Geneva, sans-serif;'>";
    
   $message .= "<thead>
      <tr height='80'>
       <th colspan='4' style='background-color:#f5f5f5; border-bottom:solid 1px #bdbdbd; font-family:Verdana, Geneva, sans-serif; color:#333; font-size:34px;' >Welcome and Thanks you!</th>
      </tr>
      </thead>";
    
   $message .= "<tbody>
      <tr align='center' height='50' style='font-family:Verdana, Geneva, sans-serif;'>
       
      </tr>
      
      <tr>
       <td colspan='4' style='padding:15px;'>
        <p style='font-size:20px;'>Hi' ".$username.",</p>
        <p>Thanks for signing up!
        Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below.
        
        Please click this link to activate your account: http://dev.api.paypro.com.ng:81/api/v1/accounts/". $account ."/verify/" . $email . "/" . $token." or complete the registeration with the token: ".$token."</p>
        <hr />
       </td>
      </tr>
      
      <tr height='80'>
       <td colspan='4' align='center' style='background-color:#f5f5f5; border-top:dashed #00a2d1 2px; font-size:24px; '>
       <label>
       Paypro : 
       <a href='https://facebook.com/' target='_blank'><img style='vertical-align:middle' src='https://cdnjs.cloudflare.com/ajax/libs/webicons/2.0.0/webicons/webicon-facebook-m.png' /></a>
       <a href='https://twitter.com/' target='_blank'><img style='vertical-align:middle' src='https://cdnjs.cloudflare.com/ajax/libs/webicons/2.0.0/webicons/webicon-twitter-m.png' /></a>
       <a href='https://plus.google.com/' target='_blank'><img style='vertical-align:middle' src='https://cdnjs.cloudflare.com/ajax/libs/webicons/2.0.0/webicons/webicon-googleplus-m.png' /></a>
       <a href='https://feeds.feedburner.com/' target='_blank'><img style='vertical-align:middle' src='https://cdnjs.cloudflare.com/ajax/libs/webicons/2.0.0/webicons/webicon-rss-m.png' /></a>
       </label>
       </td>
      </tr>
      
      </tbody>";
    
   $message .= "</table>";
   
   $message .= "</td></tr>";
   $message .= "</table>";
   
   $message .= "</body></html>";
   
        $mail->Subject = "Account Verification";
        $mail->Body = $message;

        //send the message, check for errors
        if (!$mail->send()) {
            $check = false;
            return $check;
        } else {
            $check = true;
            return $check;
        }
    }

}
