<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/4/17
 * Time: 5:43 PM
 */

namespace App\Models;


use App\Config\Auth;
use App\Config\Encryption;
use App\Config\OTP;
use App\Config\RandomStringGenerator;
use App\Config\SMS;
use App\Statuses\Statuses;
use ArrayObject;
use RedBeanPHP\R;
use App\Config\Crypt;
use DateTime;
use App\Config\JWT;
use App\Models\GeneralModel;

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Resource\Address;
use Coinbase\Wallet\Enum\CurrencyCode;
use Coinbase\Wallet\Resource\Transaction;
use Coinbase\Wallet\Value\Money;
use Coinbase\Wallet\Exception\TwoFactorRequiredException;


class UserModel
{

    // save user function
    public function createUser($input)
    {
        if (empty($input)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = ['status' => 'failed', 'message' => $status, 'code' => '108500'];
        } else {
            $generalModel = new GeneralModel();
            $encrypt = new Encryption();
            $users = R::dispense("users");
            $users->firstname = (string)$input['firstname'];
            $users->lastname = (string)$input['lastname'];
            $users->email = (string)$input['email'];
            $users->gender = (string)$input['gender'];
            $users->phone = (string)$input['phone_number'];
            $users->username = (string)$input['username'];
            $users->password = (string)$encrypt->encode($input['password']);
            $users->address = (string)$input['address'];
            $users->country = (string)$input['country'];
            $users->city = (string)$input['city'];
            $users->verified = false;
            $users->active = false;
            $users->promocode = $this->promocode();
            $users->created_by = date("Y-m-d h:i:s");
            $users->default_cur = $generalModel->cuntry_2_curry($input['country']);

            // validate email address
            $verifyEmail = (new Auth)->validateEmail($input["email"]);
            if (!$verifyEmail) {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "invalid email address",
                    "code" => $status["code"]
                ];
                $result = $response;
                return $result;
            }


            // generate token
            // save generated token to db
            $token = (new RandomStringGenerator)->generate(6);
            $users->token = $token;

            $findUserByEmail = $this->getUserByEmail($users->email);
            if ($findUserByEmail == "") {
                $getUserByPhone = $this->getUserByPhoneNumber($users->phone);
                if ($getUserByPhone == "") {
                    $data = R::store($users);
                    // $this->SetupAccount($data,$users->email,$users->phone);
                    $sendVerification = (new Auth)->sendMailVerification(
                        $users->email, $users->firstname.' '.$users->lastname,
                        $input["password"],
                        $users->token,"users");

                    if ($sendVerification) {
                        $decoded_users = json_decode($users, true);

                        $users_array = [$decoded_users];

                        $data = "";
                        foreach ($users_array as $key) {
                            unset($key['id']);
                            unset($key['password']);
                            unset($key['token']);
                            $data = $key;
                        }
                        // $data['account_num']=$account_num;
                        $status = (new Statuses)->getStatus(6000, 5010);
                        $response = [
                            'status' => true,
                            'message' => "user successfully created",
                            'code' => $status["code"],
                            "data" => $data
                        ];
                        $result = $response;
                    } else {
                        $status = (new Statuses)->getStatusWithError(6001, 5010);
                        $response = [
                            "status" => false,
                            "message" => "unable to send email verification",
                            "code" => $status["code"]
                        ];
                        $result = $response;
                    }
                } else {
                    $status = (new Statuses)->getStatusWithError(6001, 5010);
                    $response = [
                        "status" => false,
                        "message" => "user with this phone already exist",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                }
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "user with this email already exist",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }

    public function updateUser($input, $email)
    {   
        // $generalModel = new GeneralModel();
        // if(isset($input['accnumber']) || isset($input['accname']) || isset($input['accbank']) ){

        //     $accname = isset($input['accname']) : $input['accname'] ? "";
        //     $accnumber = isset($input['accnumber']) : $input['accnumber'] ? "";
        //     $accbank= isset($input['accbank']) : $input['accbank'] ? "";

        //     $accbank_code =  $generalModel->bank_2_code($accbank);

        //     if($accbank_code=="000"){
        //         $result = [
        //             "status" => false,
        //             "message" => "Bank Unknown please check and try again",
        //             "code" => "000"
        //         ];
        //     }



        //     //verify the account 
        //     $data = [
        //         "account_number" => $accnumber,
        //         "bank_code" => $accbank_code
        //     ]

        //     $result = $generalmodel->externalRequest($url, $method, "", $data);
        //     $res = (array) json_decode($result);
        //     $status = $res['status'];
        //     if (isset($status) && $status == "success") {
        //         $verifyname = $res['data']['account_name'];
                
        //     }


        // }

        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
        } else {

            $getUserEmail = $this->getUserByEmail($email);
            if (count($getUserEmail)) {
                $encrypt = new Encryption();
                if ($getUserEmail['verified'] == false) {
                    $status = (new Statuses)->getStatusWithError(6001, 5010);
                    $response = [
                        "status" => false,
                        "message" => "this user have not be verified",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                    return $result;
                } else {
                    $getUserEmail['firstname'] = !empty($input['firstname']) ? (string)$input['firstname']: $getUserEmail['firstname'];
                    $getUserEmail['lastname'] = !empty($input['lastname']) ? (string)$input['lastname']: $getUserEmail['lastname'];
                    $getUserEmail['email'] = !empty($input['email']) ? ( string)$input['email']: $getUserEmail['email'];
                    $getUserEmail['username'] = !empty($input['username']) ? (string)$input['username']:$getUserEmail['username'];
                    $getUserEmail['password'] = !empty($input['password']) ? (string)$encrypt->encode($input['password']) : $getUserEmail['password'];
                    $getUserEmail['city'] = !empty($input['city']) ? (string)$input['city'] : $getUserEmail['city'];
                    $getUserEmail['state'] = !empty($input['state']) ? (string)$input['state'] : $getUserEmail['state'] ;
                    $getUserEmail['phone_number'] = !empty($input['phone_number']) ? (string)$input['phone_number'] : $getUserEmail['phone_number'];
                    $getUserEmail['address'] = !empty($input['address']) ? (string)$input['address'] : $getUserEmail['address'];
                    $getUserEmail['country'] = !empty($input['country']) ? (string)$input['country'] : $getUserEmail['country'];
                    $getUserEmail['city'] = !empty($input['city']) ? (string)$input['city'] : $getUserEmail['city'] ;
                    $getUserEmail['gender'] = !empty($input['gender']) ? (string)$input['gender'] : $getUserEmail['gender'];
                    $getUserEmail['default_cur'] = !empty($input['default_cur']) ? (string)$input['default_cur'] : $getUserEmail['default_cur'];

                    $getUserEmail['accnumber'] = !empty($input['accnumber']) ? (string)$input['accnumber'] : $getUserEmail['accnumber'];

                    $getUserEmail['accbank'] = !empty($input['accbank']) ? (string)$input['accbank'] : $getUserEmail['accbank'];

                    $getUserEmail['accname'] = !empty($input['accname']) ? (string)$input['accname'] : $getUserEmail['accname'];

                    R::store($getUserEmail);

                    $decoded_users = json_decode($getUserEmail, true);

                    $user_array = [$decoded_users];
                    $data = "";
                    foreach ($user_array as $key) {
                        unset($key['id']);
                        unset($key['password']);
                        $data = $key;
                    }
                    $status = (new Statuses)->getStatusWithError(6000, 5010);
                    $response = [
                        'status' => true,
                        'message' => "user information updated",
                        'code' => $status["code"],
                        "data" => $data
                    ];
                    $result = $response;
                }
            } elseif (!count($getUserEmail)) {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "unable to find account for this email",
                    "code" => $status["code"]
                ];
                $result = $response;
            }

        }
        return $result;
    }

    public function changepass($input, $email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
        } else {

            $getUserEmail = $this->getUserByEmail($email);
            if (count($getUserEmail)) {
                $encrypt = new Encryption();
                if ($getUserEmail['verified'] == false) {
                    $status = (new Statuses)->getStatusWithError(6001, 5010);
                    $response = [
                        "status" => false,
                        "message" => "this user have not be verified",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                    return $result;
                }else if((string)$encrypt->encode($input['currpass'])!=$getUserEmail['password']){
                    $response = [
                        "status" => false,
                        "message" => "Invalid password",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                }else {
                    $getUserEmail['password'] = !empty($input['newpass']) ? (string)$encrypt->encode($input['newpass']) : $getUserEmail['password'];
                    R::store($getUserEmail);
                    $status = (new Statuses)->getStatusWithError(6000, 5010);
                    $response = [
                        'status' => true,
                        'message' => "user information updated",
                        'code' => $status["code"]
                        
                    ];
                    $result = $response;
                }
            } elseif (!count($getUserEmail)) {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "unable to find account for this email",
                    "code" => $status["code"]
                ];
                $result = $response;
            }

        }
        return $result;
    }
    public function deleteUser($email)
    {
        $getUser = $this->getUserByEmail($email);
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        if (count($getUser)) {
            R::trash($getUser);
            $status = (new Statuses)->getStatus(6000, 5010);
            $response = [
                'status' => true,
                'message' => "user information deleted",
                'code' => $status["code"]
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6001, 5010);
            $response = [
                "status" => false,
                "message" => "unable to find account for this email",
                "code" => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    public function getUserByEmail($email)
    {
        $findUser = R::findOne("users", 'email=?', [$email]);
        if (count($findUser)) {
            return $findUser;
        } else {
            return "";
        }
    }


    public function verifyEmailAndToken($email_token)
    {
        if (empty($email_token)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        $email = $email_token[0];
        $token = $email_token[1];
        $getUser = R::findOne("users", "email=? AND token=?", [$email, $token]);

        if (count($getUser)) {
            if ($getUser["token"] == $token) {
                $getUser["verified"] = true;
                $getUser["active"] = true;
                R::store($getUser);
                $status = (new Statuses)->getStatus(6000, 5010);
                
                    $now = new DateTime();
                    $future = new DateTime("now +23 hours");
                    $payload = [
                        "iat" => $now->getTimestamp(),
                        "email" => $getUser->email,
                        "username" => $getUser->username,
                        "id" => $getUser->id,
                        "exp" => $future->getTimestamp(),
                    ];
                    $token = JWT::encode($payload, SECRET, "HS256");
                
                $response = [
                    'status' => true,
                    'message' => "user verified",
                    'code' => $status["code"],
                    'token' => $token,
                    'firstname' => $getUser->firstname,
                    'lastname' => $getUser->lastname,
                    'promocode' => $getUser->promocode,
                    'default_cur' => $getUser->default_cur,
                    'accname' => $getUser->accname,
                    'accnumber' => $getUser->accnumber,
                    'accbank' => $getUser->accbank
                ];
                $result = $response;
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "unable to find user details",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }
    public function verifyEmail($email)
    {
        
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        $getUser = R::findOne("users", "email=?", [$email]);
        
        if (count($getUser)) {
            if ($getUser["verified"] == 1) {
                $status = (new Statuses)->getStatus(6000, 5010);
                $now = new DateTime();
                    $future = new DateTime("now +23 hours");
                    $payload = [
                        "iat" => $now->getTimestamp(),
                        "email" => $getUser->email,
                        "username" => $getUser->username,
                        "id" => $getUser->id,
                        "exp" => $future->getTimestamp(),
                    ];
                    $token = JWT::encode($payload, SECRET, "HS256");
                $response = [
                    'status' => true,
                    'message' => "user verified",
                    'code' => $status["code"],
                    'token' => $token,
                    'firstname' => $getUser->firstname,
                    'lastname' => $getUser->lastname,
                    'promocode' => $getUser->promocode,
                    'default_cur' => $getUser->default_cur,
                    'accname' => $getUser->accname,
                    'accnumber' => $getUser->accnumber,
                    'accbank' => $getUser->accbank
                ];
                $result = $response;
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "unable to find user details",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }

    public function getUserByPhoneNumber($phone)
    {
        
        if($phone==""){
            return "";
        }
        $findUserByPhone = R::findOne("users", "phone=?", [$phone]);
        if (count($findUserByPhone)) {
            return $findUserByPhone;
        } else {
            return "";
        }
    }

    public function getSingleUserByEmail($email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        $findUser = R::findOne("users", 'email=?', [$email]);
        if (count($findUser)) {
            $decoded_users = json_decode($findUser, true);
            $user_array = [$decoded_users];
            $data = "";
            foreach ($user_array as $key) {
                unset($key['id']);
                unset($key['password']);
                unset($key['token']);
                $data = $key;
            }
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => true,
                'message' => "user records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "user does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    public function getAllUsers()
    {
        $findUser = R::findAll("users", "ORDER BY username");
        if (count($findUser)) {
            $data = "";
            foreach ($findUser as $key) {
                unset($key['id']);
                unset($key['password']);
                unset($key['token']);
                $data[] = $key;
            }
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => true,
                'message' => "user records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "user does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    public function getUserByUsername($username)
    {
        $findUser = R::findOne("users", 'username=?', [$username]);
        if (count($findUser)) {
            return $findUser;
        } else {
            return "";
        }
    }

    public function generateAuthKey($length = null)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function generateAccountNumber(){
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }


        $findAccount = R::findOne("wallettbl", 'account_num=?', [$randomString]);
        if (count($findAccount)) {
            $randomString= generateAccountNumber();
        } 
        
        return $randomString;
    }
    function SetupAccount($user_id,$email,$phone){

            
        try{
            $apiKey="C9s8BI6Mm6eMrRhe";
            $apiSecret="IpHnaJgMUQIDnjptmmAHq0LsWnwFwA5X";
            $configuration = Configuration::apiKey($apiKey, $apiSecret);
            $client = Client::create($configuration);
            
            $account = R::dispense("wallettbl");
            $account->wallet_name="BTC wallet";
            $account->userid = $user_id;
            $account->isprimary = 1;
            $account->acctype = "useracc";
            $account->balance = 0.0000;

            $wallet_id = R::store($account);

                $primaryAccount = $client->getPrimaryAccount();
                $address = new Address();
                $client->createAccountAddress($primaryAccount, $address);


                //save the address for the user;
                $transaddr = R::dispense("transaddr");
                $transaddr->userid = $user_id;
                $transaddr->wallet_id = $wallet_id;
                $transaddr->bitcoinadd = $address->getAddress();
                $transaddr->address_id = $address->getId();
                $transaddr->amount = 0.0;
                $transaddr->status =0;
                $dat = R::store($transaddr);

            $account->default_add = $address->getAddress();
            $wallet_id = R::store($account);

            


        }catch (Exception $e) {
            return $response->withStatus(400)
                            ->withHeader('X-Status-Reason', $e->getMessage());
        }
        

        $this->check_for_transaction_in_hold($email,$phone,$wallet_id,$user_id);
        

    }

    function check_for_transaction_in_hold($email,$phone,$wallet_id,$userid){
            
        try{
            $findHolding = R::findAll("holdtransactiontbl", "email=? OR email=?",[$email,$phone]);
            if(count($findHolding)){
                foreach ($findHolding as $data) {
                    $this->dotransaction($data,$wallet_id,$userid);
                }
            }

        }catch (Exception $e) {
            return $response->withStatus(400)
                            ->withHeader('X-Status-Reason', $e->getMessage());
        }

    }

    function dotransaction($data,$wallet_id,$userid){

        try{
            
            $wallet = R::findOne("wallettbl", 'id=?', [$wallet_id]);
            $balance = $wallet['balance'];
            $amount = $data["amount"];
            $wallet['balance'] = (( (double) $amount ) + ( (double) $balance ));
            R::store($wallet); //credit the wallet

            //update the sender transaction 
            $trans_id = $data['trans_id'];

            $transaction = R::findOne("transactiontbl",'id=?',[$trans_id]);
            $transaction->fromid = $wallet_id;
            R::store($transaction);

            $sender_id = $data['send_id'];
            $transaction_desc =$data['description'];

            $transactiontbl = R::dispense("transactiontbl");
            $transactiontbl->trantype = "CR";
            $transactiontbl->tracaddr = "";
            $transactiontbl->tranvalue = $amount;
            $transactiontbl->fromid = $sender_id;
            $transactiontbl->toid = $userid;
            $transactiontbl->transaction_desc = $transaction_desc;
            $transactiontbl->isinapp = 1;
            $transactiontbl->trans_id = "";
            $transactiontbl->wallet_id = $wallet_id;

            $data = R::store($transactiontbl);


        }catch(Exception $e){
                print_r($e);
                die();
        }

    }
    function promocode(){
        try{

            $code = (new RandomStringGenerator)->generate(6);
            $promocode = R::findOne("users", 'promocode=?' [$code]);
            if(count($promocode)){
                return $this->promocode();
            }else{
                return $code;
            }

        }catch (Exception $e) {
            return $response->withStatus(400)
                            ->withHeader('X-Status-Reason', $e->getMessage());
        }
    }
}