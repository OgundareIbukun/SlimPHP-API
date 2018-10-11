<?php

namespace App\Models;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;
use RedBeanPHP\R;
// use App\Models\GlobalModel as globalmodel;
use \App\Jasny\ISO\Countries as Country;

class Verification extends Country {

    public function VerifyUser($input) {


        $is_request_valid = $this->isJson($input);
        if (!$is_request_valid) {
            return ["invalid request type", "104017"];
        }

        $v_order = $this->user_detailsVerify($input);
        if (!empty($v_order)) {
            return $v_order;
        }
    }

    function isJson($string) {
        $json_val = json_encode($string);
        
        if ($json_val === "null") {
            return false;
        }
        return true;
    }

    public function stringToLowerCase($payment_type) {
        return strtolower($payment_type);
    }

    

    

    public function user_detailsVerify($input) {
        $receiver_data = $input;
        v::with('App\\CustomValidation\\Rules\\');
        $firstname = v::stringType()->notEmpty()->setName('firstname');
        $lastname = v::stringType()->notEmpty()->setName('lastname');
        $email = v::email()->setName("user email");
        $password = v::stringType()->notEmpty()->setName('Buyer state');
        $phone_number = v::stringType()->notEmpty()->setName('Buyer alternamt phone');
        

        $rules = array(
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'password' => $password
        );

        foreach ($rules as $key => $value) {
            # code...
            try {

                $rules[$key]->check($input[$key]);
            } catch (ValidationException $exception) {
                return ["invalid " . $key, "104001"];
            }
        }


         
        if (!($receiver_data['phone_number'] == null || $receiver_data['phone_number']=="")) {
            
             if ($this->PhoneNumberValidation("NG", $receiver_data['phone_number']) !== null) {
                return ["invalid receivers phone", "104006"];
            }
        }
        
    }


    

    public function PhoneNumberValidation($user_country, $user_phone_number) {

        $country_code = $this->countryCode($user_country);
        $phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
        $swiss_number_proto = $phone_util->parse($user_phone_number, $country_code);
        $is_valid_no = $phone_util->isValidNumber($swiss_number_proto);
        $is_valid_no_possible = $phone_util->isPossibleNumber($swiss_number_proto, $country_code);
        if ($is_valid_no !== true || $is_valid_no_possible !== true) {
            return $error = "Phone number must be a valid telephone number";
        } else {
            return null;
        }
    }

    

}
