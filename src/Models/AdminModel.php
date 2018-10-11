<?php
/**
 * Created by PhpStorm.
 * admin: funmi
 * Date: 3/8/17
 * Time: 8:22 AM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Config\Auth;
use App\Config\Crypt;
use App\Config\Encryption;
use App\Config\RandomStringGenerator;
use App\Statuses\Statuses;
use ArrayObject;
use RedBeanPHP\R;

class AdminModel extends Model
{
    // index 0
    public function createAdmin($input)
    {
        if (empty($input)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = ['status' => 'failed', 'message' => $status, 'code' => '108500'];
        } else {
            $encrypt = new Encryption();
            $admin = R::dispense("admin");
            $admin->email = (string)$input['email'];
            $admin->username = (string)$input['username'];
            $admin->password = (string)$encrypt->encode($input['password']);
            $admin->role = (string)$input['role'];
            $admin->can_read = (bool)$input['can_read'];
            $admin->can_write = (bool)$input['can_write'];
            $admin->can_delete = (bool)$input['can_delete'];
            $admin->verified = false;
            $admin->active = false;
            $admin->created_by = date("Y-m-d h:i:s");

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
            $token = (new RandomStringGenerator)->generate(32);
            $admin->token = $token;
            $findAdminByEmail = $this->getAdminByEmail($admin->email);

            if ($findAdminByEmail == "") {
                $get_admin_by_phone = $this->getAdminByPhoneNumber($admin->phone);
                if ($get_admin_by_phone == "") {
                    R::store($admin);
                    $sendVerification = (new Auth)->sendMailVerification(
                        $admin->email, $admin->username,
                        $input["password"],
                        $admin->token,"admin");
                    if ($sendVerification) {
                        $decoded_admins = json_decode($admin, true);

                        $admin_array = [$decoded_admins];

                        $data = "";
                        foreach ($admin_array as $key) {
                            unset($key['id']);
                            unset($key['password']);
                            unset($key['token']);
                            $data = $key;
                        }
                        $status = (new Statuses)->getStatus(6000, 5010);
                        $response = [
                            'status' => true,
                            'message' => "admin successfully created",
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
                        "message" => "admin with this phone already exist",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                }
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "admin with this email already exist",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }

    // index 1
    public function updateAdmin($input, $email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
        } else {

            $get_admin_mail = $this->getAdminByEmail($email);
            if (count($get_admin_mail)) {
                $encrypt = new Encryption();
                if ($get_admin_mail['verified'] == false) {
                    $status = (new Statuses)->getStatusWithError(6001, 5010);
                    $response = [
                        "status" => false,
                        "message" => "this admin have not be verified",
                        "code" => $status["code"]
                    ];
                    $result = $response;
                    return $result;
                } else {
    //              $get_admin_mail['firstname'] = (string)$input['firstname'];
      //            $get_admin_mail['lastname'] = (string)$input['lastname'];
                    $get_admin_mail['email'] = (string)$input['email'];
        //          $get_admin_mail['adminname'] = (string)$input['adminname'];
                    $get_admin_mail['password'] = (string)$encrypt->encode($input['password']);
        //          $get_admin_mail['city'] = (string)$input['city'];
        //          $get_admin_mail['state'] = (string)$input['state'];
        //          $get_admin_mail['phone_number'] = (string)$input['phone_number'];
        //          $get_admin_mail['address'] = (string)$input['address'];
        //          $get_admin_mail['country'] = (string)$input['country'];
        //          $get_admin_mail['city'] = (string)$input['city'];
        //          $get_admin_mail['gender'] = (string)$input['gender'];
                    $get_admin_mail['role'] = (string)$input['role'];
                    $get_admin_mail['can_read'] = (string)$input['can_read'];
                    $get_admin_mail['can_write'] = (string)$input['can_write'];
                    $get_admin_mail['can_delete'] = (string)$input['can_delete'];
                    $get_admin_mail['verified'] = false;
                    $get_admin_mail['active'] = false;
                    R::store($get_admin_mail);

                    $decoded_admins = json_decode($get_admin_mail, true);

                    $admin_array = [$decoded_admins];
                    $data = "";
                    foreach ($admin_array as $key) {
                        unset($key['id']);
                        unset($key['password']);
                        $data = $key;
                    }
                    $status = (new Statuses)->getStatusWithError(6000, 5010);
                    $response = [
                        'status' => true,
                        'message' => "admin information updated",
                        'code' => $status["code"],
                        "data" => $data
                    ];
                    $result = $response;
                }
            } elseif (!count($get_admin_mail)) {
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
    public function deleteAdmin($email)
    {
        $get_admin = $this->getAdminByEmail($email);
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        if (count($get_admin)) {
            R::trash($get_admin);
            $status = (new Statuses)->getStatus(6000, 5010);
            $response = [
                'status' => true,
                'message' => "admin information deleted",
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
    public function getAdminByEmail($email)
    {
        $find_admin = R::findOne("admin", 'email=?', [$email]);
        if (count($find_admin)) {
            return $find_admin;
        } else {
            return "";
        }
    }


    // index 4
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
        $get_admin = R::findOne("admin", "email=? AND token=?", [$email, $token]);
        if (count($get_admin)) {
            if ($get_admin["token"] == $token) {
                $get_admin["verified"] = true;
                $get_admin["active"] = true;
                R::store($get_admin);
                $status = (new Statuses)->getStatus(6000, 5010);
                $response = [
                    'status' => true,
                    'message' => "admin verified",
                    'code' => $status["code"]
                ];
                $result = $response;
            } else {
                $status = (new Statuses)->getStatusWithError(6001, 5010);
                $response = [
                    "status" => false,
                    "message" => "unable to find admin details",
                    "code" => $status["code"]
                ];
                $result = $response;
            }
        }
        return $result;
    }

    // index 5
    public function getAdminByPhoneNumber($phone)
    {
        $find_admin_by_phone = R::findOne("admin", "phone=?", [$phone]);
        if (count($find_admin_by_phone)) {
            return $find_admin_by_phone;
        } else {
            return "";
        }
    }

    // index 6
    public function getSingleAdminByEmail($email)
    {
        if (empty($email)) {
            $status = (new Statuses)->getStatusWithError(6001, 5005);
            $result = [
                "status" => false,
                "message" => $status
            ];
            return $result;
        }
        $find_admin = R::findOne("admin", 'email=?', [$email]);
        if (count($find_admin)) {
            $decoded_admins = json_decode($find_admin, true);
            $admin_array = [$decoded_admins];
            $data = "";
            foreach ($admin_array as $key) {
                unset($key['id']);
                unset($key['password']);
                unset($key['token']);
                $data = $key;
            }
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => true,
                'message' => "admin records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "admin does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    // index 7
    public function getAllAdmin()
    {
        $find_admin = R::findAll("admin", "ORDER BY username");
        if (count($find_admin)) {
            $data = "";
            foreach ($find_admin as $key) {
                unset($key['id']);
                unset($key['password']);
                unset($key['token']);
                $data[] = $key;
            }
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => true,
                'message' => "admin records",
                'code' => $status["code"],
                "data" => $data
            ];
            $result = $response;
        } else {
            $status = (new Statuses)->getStatusWithError(6000, 5010);
            $response = [
                'status' => false,
                'message' => "admin does not exist",
                'code' => $status["code"]
            ];
            $result = $response;
        }
        return $result;
    }

    // index 8
    public function getAdminByUsername($username)
    {
        $find_admin = R::findOne("admin", 'username=?', [$username]);
        if (count($find_admin)) {
            return $find_admin;
        } else {
            return "";
        }
    }
}
