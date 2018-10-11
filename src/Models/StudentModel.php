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


class StudentModel
{
    //setting a default value ::: $table->string('email')->default('admin@admin.com');
    // save user function
    public function createStudent($input)
    {
        if (empty($input)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = ['status' => 'failed', 'message' => $status, 'code' => '108500'];
        } else {
            $generalModel = new GeneralModel();
            $encrypt = new Encryption();
            $students = R::dispense("students");
            $students->firstname = (string)$input['firstname'];
            $students->lastname = (string)$input['lastname'];
            $students->middlename = (string)$input['middlename'];
            $students->email = (string)$input['email'];
            $students->gender = (string)$input['gender'];
            $students->phone = (string)$input['phone_number'];
            $students->username = (string)$input['username'];
            $students->password = (string)$encrypt->encode($input['password']);
            $students->birthdate = date("Y-m-d h:i:s");
            $students->language = (string)$input['language'];
            $students->physician_name = (string)$input['physician_name'];

            $students->physician_phone1 = (string)$input['physician_phone1'];
            $students->physician_hospital = (string)$input['physician_hospital'];
            $students->estimated_grad_date = date("Y-m-d h:i:s");

            $students->alt_id = (string)$input['alt_id'];
            $students->physician_email = (string)$input['physician_email'];
            $students->physician_phone2 = (string)$input['physician_phone2'];
            $students->is_disable = (string)$input['is_disable'];

            $students->student_address = (string)$input['student_address'];
            $students->student_address_state = (string)$input['student_address_state'];
            $students->sch_drop_off = (string)$input['sch_drop_off'];
            $students->sch_pick_off = (string)$input['sch_pick_off'];

            $students->bus_no = (string)$input['bus_no'];
            $students->prim_con_rel = (string)$input['prim_con_rel'];
            $students->prim_con_address = (string)$input['prim_con_address'];
            $students->prim_con_state = (string)$input['prim_con_state'];

            $students->prim_con_f_name = (string)$input['prim_con_f_name'];
            $students->prim_con_o_name = (string)$input['prim_con_o_name'];
            $students->prim_con_phone1 = (string)$input['prim_con_phone1'];
            $students->prim_con_phone2 = (string)$input['prim_con_phone2'];

            $students->sec_con_rel = (string)$input['sec_con_rel'];
            $students->sec_con_address = (string)$input['sec_con_address'];
            $students->sec_con_state = (string)$input['sec_con_state'];
            $students->sec_con_f_name = (string)$input['sec_con_f_name'];
            $students->sec_con_o_name = (string)$input['sec_con_o_name'];

            $students->sec_con_phone1 = (string)$input['sec_con_phone1'];
            $students->sec_con_phone2 = (string)$input['sec_con_phone2'];


            $students->verified = false;
            $students->active = false;
            $students->promocode = $this->promocode();
            $students->created_by = date("Y-m-d h:i:s");


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
            $students->token = $token;

            $findStudentByEmail = $this->getStudentByEmail($students->email);
            if ($findStudentByEmail == "") {
                $getStudentByPhone = $this->getStudentByPhoneNumber($students->phone);
                if ($getStudentByPhone == "") {
                    $data = R::store($students);
                    // $this->SetupAccount($data,$users->email,$users->phone);
                    $sendVerification = (new Auth)->sendMailVerification(
                        $students->email, $students->firstname.' '.$students->lastname,
                        $input["password"],
                        $students->token,"students");

                    if ($sendVerification) {
                        $decoded_students = json_decode($students, true);

                        $students_array = [$decoded_students];

                        $data = "";
                        foreach ($students_array as $key) {
                            unset($key['id']);
                            unset($key['password']);
                            unset($key['token']);
                            $data = $key;
                        }
                        // $data['account_num']=$account_num;
                        $status = (new Statuses)->getStatus(6000, 5010);
                        $response = [
                            'status' => true,
                            'message' => "Student Account successfully created",
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
                        "message" => "Student with this phone already exist",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                }
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "Student with this email already exist",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }

    public function updateStudent($input, $email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
        } else {

            $getStudentEmail = $this->getStudentByEmail($email);
            if (count($getStudentEmail)) {
                $encrypt = new Encryption();
                if ($getStudentEmail['verified'] == false) {
                    $status = (new Statuses)->getStatusWithError(6001, 5010);
                    $response = [
                        "status" => false,
                        "message" => "This Student has not been verified",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                    return $result;
                } else {
                    $getStudentEmail['firstname'] = !empty($input['firstname']) ? (string)$input['firstname']: $getStudentEmail['firstname'];
                    $getStudentEmail['lastname'] = !empty($input['lastname']) ? (string)$input['lastname']: $getStudentEmail['lastname'];
                    $getStudentEmail['email'] = !empty($input['email']) ? ( string)$input['email']: $getStudentEmail['email'];
                    $getStudentEmail['username'] = !empty($input['username']) ? (string)$input['username']:$getStudentEmail['username'];
                    $getStudentEmail['password'] = !empty($input['password']) ? (string)$encrypt->encode($input['password']) : $getStudentEmail['password'];
                    $getStudentEmail['city'] = !empty($input['city']) ? (string)$input['city'] : $getStudentEmail['city'];
                    $getStudentEmail['state'] = !empty($input['state']) ? (string)$input['state'] : $getStudentEmail['state'] ;
                    $getStudentEmail['phone_number'] = !empty($input['phone_number']) ? (string)$input['phone_number'] : $getStudentEmail['phone_number'];
                    $getStudentEmail['address'] = !empty($input['address']) ? (string)$input['address'] : $getStudentEmail['address'];
                    $getStudentEmail['country'] = !empty($input['country']) ? (string)$input['country'] : $getStudentEmail['country'];
                    $getStudentEmail['city'] = !empty($input['city']) ? (string)$input['city'] : $getStudentEmail['city'] ;
                    $getStudentEmail['gender'] = !empty($input['gender']) ? (string)$input['gender'] : $getStudentEmail['gender'];
                    $getStudentEmail['default_cur'] = !empty($input['default_cur']) ? (string)$input['default_cur'] : $getStudentEmail['default_cur'];

                    $getStudentEmail['accnumber'] = !empty($input['accnumber']) ? (string)$input['accnumber'] : $getStudentEmail['accnumber'];

                    $getStudentEmail['accbank'] = !empty($input['accbank']) ? (string)$input['accbank'] : $getStudentEmail['accbank'];

                    $getStudentEmail['accname'] = !empty($input['accname']) ? (string)$input['accname'] : $getStudentEmail['accname'];

                    R::store($getStudentEmail);

                    $decoded_students = json_decode($getStudentEmail, true);

                    $students_array = [$decoded_students];
                    $data = "";
                    foreach ($students_array as $key) {
                        unset($key['id']);
                        unset($key['password']);
                        $data = $key;
                    }
                    $status = (new Statuses)->getStatusWithError(6000, 5010);
                    $response = [
                        'status' => true,
                        'message' => "Student's information updated",
                        'code' => $status["code"],
                        "data" => $data
                    ];
                    $result = $response;
                }
            } elseif (!count($getStudentEmail)) {
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

            $getStudentEmail = $this->getStudentByEmail($email);
            if (count($getStudentEmail)) {
                $encrypt = new Encryption();
                if ($getStudentEmail['verified'] == false) {
                    $status = (new Statuses)->getStatusWithError(6001, 5010);
                    $response = [
                        "status" => false,
                        "message" => "This Student has not been verified",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                    return $result;
                }else if((string)$encrypt->encode($input['currpass'])!=$getStudentEmail['password']){
                    $response = [
                        "status" => false,
                        "message" => "Invalid password",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                }else {
                    $getStudentEmail['password'] = !empty($input['newpass']) ? (string)$encrypt->encode($input['newpass']) : $getStudentEmail['password'];
                    R::store($getStudentEmail);
                    $status = (new Statuses)->getStatusWithError(6000, 5010);
                    $response = [
                        'status' => true,
                        'message' => "Student's information updated",
                        'code' => $status["code"]

                    ];
                    $result = $response;
                }
            } elseif (!count($getStudentEmail)) {
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
    public function deleteStudent($email)
    {
        $getStudent = $this->getStudentByEmail($email);
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        if (count($getStudent)) {
            R::trash($getStudent);
            $status = (new Statuses)->getStatus(6000, 5010);
            $response = [
                'status' => true,
                'message' => "Student information deleted",
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

    public function getStudentByEmail($email)
    {
        $findStudent = R::findOne("students", 'email=?', [$email]);
        if (count($findStudent)) {
            return $findStudent;
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
        $getStudent = R::findOne("students", "email=? AND token=?", [$email, $token]);

        if (count($getStudent)) {
            if ($getStudent["token"] == $token) {
                $getStudent["verified"] = true;
                $getStudent["active"] = true;
                R::store($getStudent);
                $status = (new Statuses)->getStatus(6000, 5010);

                    $now = new DateTime();
                    $future = new DateTime("now +23 hours");
                    $payload = [
                        "iat" => $now->getTimestamp(),
                        "email" => $getStudent->email,
                        "username" => $getStudent->username,
                        "id" => $getStudent->id,
                        "exp" => $future->getTimestamp(),
                    ];
                    $token = JWT::encode($payload, SECRET, "HS256");

                $response = [
                    'status' => true,
                    'message' => "Student verified",
                    'code' => $status["code"],
                    'token' => $token,
                    'firstname' => $getStudent->firstname,
                    'lastname' => $getStudent->lastname,
                    'promocode' => $getStudent->promocode,
                    'default_cur' => $getStudent->default_cur,
                    'accname' => $getStudent->accname,
                    'accnumber' => $getStudent->accnumber,
                    'accbank' => $getStudent->accbank
                ];
                $result = $response;
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "unable to find Student details",
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
        $getStudent = R::findOne("students", "email=?", [$email]);

        if (count($getStudent)) {
            if ($getStudent["verified"] == 1) {
                $status = (new Statuses)->getStatus(6000, 5010);
                $now = new DateTime();
                    $future = new DateTime("now +23 hours");
                    $payload = [
                        "iat" => $now->getTimestamp(),
                        "email" => $getStudent->email,
                        "username" => $getStudent->username,
                        "id" => $getStudent->id,
                        "exp" => $future->getTimestamp(),
                    ];
                    $token = JWT::encode($payload, SECRET, "HS256");
                $response = [
                    'status' => true,
                    'message' => "Student verified",
                    'code' => $status["code"],
                    'token' => $token,
                    'firstname' => $getStudent->firstname,
                    'lastname' => $getStudent->lastname,
                    'promocode' => $getStudent->promocode,
                    'default_cur' => $getStudent->default_cur,
                    'accname' => $getStudent->accname,
                    'accnumber' => $getStudent->accnumber,
                    'accbank' => $getStudent->accbank
                ];
                $result = $response;
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "unable to find student details",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }

    public function getStudentByPhoneNumber($phone)
    {

        if($phone==""){
            return "";
        }
        $findStudentByPhone = R::findOne("students", "phone=?", [$phone]);
        if (count($findStudentByPhone)) {
            return $findStudentByPhone;
        } else {
            return "";
        }
    }

    public function getSingleStudentByEmail($email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        $findStudent = R::findOne("students", 'email=?', [$email]);
        if (count($findStudent)) {
            $decoded_students = json_decode($findStudent, true);
            $students_array = [$decoded_students];
            $data = "";
            foreach ($students_array as $key) {
                unset($key['id']);
                unset($key['password']);
                unset($key['token']);
                $data = $key;
            }
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => true,
                'message' => "Student records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "student does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    public function getAllStudents()
    {
        $findStudent = R::findAll("students", "ORDER BY username");
        if (count($findStudent)) {
            $data = "";
            foreach ($findStudent as $key) {
                unset($key['id']);
                unset($key['password']);
                unset($key['token']);
                $data[] = $key;
            }
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => true,
                'message' => "Students records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "Student does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    public function getStudentByStudentname($username)
    {
        $findStudent = R::findOne("students", 'username=?', [$username]);
        if (count($findStudent)) {
            return $findStudent;
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
