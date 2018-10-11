<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/5/17
 * Time: 12:32 PM
 */

namespace App\Models;


use App\Config\Auth;
use App\Config\Encryption;
use App\Config\RandomStringGenerator;
use App\Statuses\Statuses;
use RedBeanPHP\R;

class MerchantModel
{

    // create Merchant account
    // index 0
    public function createMerchant($input)
    {
        if (empty($input)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status,
                "code" => 108500
            ];
        } else {
            $encrypt = new Encryption();
            $merchants = R::dispense("merchants");
            $merchants->firstname = (string)$input["firstname"];
            $merchants->lastname = (string)$input["lastname"];
            $merchants->email = (string)$input["email"];
            $merchants->username = (string)$input["username"];
            $merchants->password = (string)$encrypt->encode($input['password']);
            $merchants->brand_name = (string)$input["brand_name"];
            $merchants->city = (string)$input["city"];
            $merchants->state = (string)$input["state"];
            $merchants->phone_number = (string)$input["phone_number"];
            $merchants->alt_phone_number = (string)$input["alt_phone_number"];
            $merchants->address = (string)$input["address"];
            $merchants->verified = false;
            $merchants->active = false;

            // generate token
            // save generated token to db
            $token = (new RandomStringGenerator)->generate(32);
            $merchants->token = $token;

            $findUserByEmail = $this->getmerchantByEmail($merchants->email);

            if ($findUserByEmail == "") {
                $getUserByPhone = $this->getmerchantByPhoneNumber($merchants->phone_number);
                if ($getUserByPhone == "") {
                    $data = R::store($merchants);

                    $sendVerification = (new Auth)->sendMailVerification(
                        $merchants->email, $merchants->username,
                        $input["password"],
                        $merchants->token,"merchants");

                    if ($sendVerification) {
                        $decoded_users = json_decode($merchants, true);

                        $users_array = [$decoded_users];

                        $data = "";
                        foreach ($users_array as $key) {
                            unset($key['id']);
                            unset($key['password']);
                            unset($key['token']);
                            $data = $key;
                        }
                        $status = (new Statuses)->getStatus(6000, 5010);
                        $response = [
                            'status' => true,
                            'message' => "merchant successfully created",
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
                        "message" => "merchant with this phone already exist",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                }
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "merchant with this email already exist",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }

    // index 1
    public function updateMerchant($input, $email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
        } else {

            $getmerchantEmail = $this->getmerchantByEmail($email);
            if (count($getmerchantEmail)) {
                $encrypt = new Encryption();
                $getmerchantEmail['firstname'] = (string)$input['firstname'];
                $getmerchantEmail['lastname'] = (string)$input['lastname'];
                $getmerchantEmail['email'] = (string)$input['email'];
                $getmerchantEmail['username'] = (string)$input['username'];
                $getmerchantEmail['password'] = (string)$encrypt->encode($input['password']);
                $getmerchantEmail['brand_name'] = (string)$input['brand_name'];
                $getmerchantEmail['city'] = (string)$input['city'];
                $getmerchantEmail['state'] = (string)$input['state'];
                $getmerchantEmail['phone_number'] = (string)$input['phone_number'];
                $getmerchantEmail['alt_phone_number'] = (string)$input['alt_phone_number'];
                $getmerchantEmail['address'] = (string)$input['address'];


                R::store($getmerchantEmail);

                $decoded_merchants = json_decode($getmerchantEmail, true);

                $merchant_array = [$decoded_merchants];
                $data = "";
                foreach ($merchant_array as $key) {
                    unset($key['id']);
                    unset($key['password']);
                    $data[] = $key;
                }
                $status = (new Statuses)->getStatusWithError(6000, 5010);
                $response = [
                    'status' => true,
                    'message' => "merchant information updated",
                    'code' => $status["code"],
                    "data" => $data
                ];
                $result = $response;
            } elseif (!count($getmerchantEmail)) {
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

    // index 2
    public function deleteMerchant($email)
    {
        $getmerchant = $this->getmerchantByEmail($email);
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        if (count($getmerchant)) {
            R::trash($getmerchant);
            $status = (new Statuses)->getStatus(6000, 5010);
            $response = [
                'status' => true,
                'message' => "merchant information deleted",
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

    // index 3
    public function getMerchantByEmail($email)
    {
        $findUser = R::findOne("merchants", 'email=?', [$email]);
        if (count($findUser)) {
            return $findUser;
        } else {
            return "";
        }
    }

    // index 4
    public function getMerchantByPhoneNumber($phone)
    {
        $findUserByPhone = R::findOne("merchants", "phone=?", [$phone]);
        if (count($findUserByPhone)) {
            return $findUserByPhone;
        } else {
            return "";
        }

    }

    // index 5
    public function getSingleMerchantByEmail($email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        $findUser = R::findOne("merchants", 'email=?', [$email]);
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
                'message' => "merchant records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "merchant does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    // index 6
    public function getAllMerchant()
    {
        $findUser = R::findAll("merchants", "ORDER BY username");
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
                'message' => "merchant records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "merchant does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    // index 7
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
        $getUser = R::findOne("merchants", "email=? AND token=?", [$email, $token]);

        if (count($getUser)) {
            if ($getUser["token"] == $token) {
                $getUser["verified"] = true;
                $getUser["active"] = true;
                R::store($getUser);
                $status = (new Statuses)->getStatus(6000, 5010);
                $response = [
                    'status' => true,
                    'message' => "merchant verified",
                    'code' => $status["code"]
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

    public function encryptPassword($password)
    {
        $encrypt = new Encryption();
        $encrypt->encode($password);
        return $encrypt;
    }

    public function decryptPassword($password)
    {
        $decrypt = new Encryption();
        $decrypt->decode($password);
        return $decrypt;
    }
}