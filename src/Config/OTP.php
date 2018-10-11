<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/5/17
 * Time: 4:04 PM
 */

namespace App\Config;

class OTP
{
    private $mobile;
    private $message;
    private $code;

    public function __construct($length,$strength){
//        $this->code
    }

    // this is for retrieving OTP CODE
    public function getOTPCode(){
        return $this->code;
    }

    // this is where we are generating the OTPCODE
    private function generateOTP($length,$strength){
        $vowels = 'aeiou';
        $constants = 'bcdfghjklmnpqrstvwyxz';
        if ($strength & 1){
            $constants .= 'BCDFGHJKLMNPQRSTVWXYZ';
        }
        if ($strength & 2){
            $vowels .="AEIOU";
        }
        if ($strength & 4){
            $constants .= '23456789';
        }
        if ($strength & 8){
            $constants .= '@#$%';
        }
        $password = '';
        $alt = time() /2;
        for ($i=0; $i < $length; $i++){
            if ($alt == 1){
                $password .= $constants[rand() % strlen($constants)];
                $alt =0;
            }else{
                $password .= $vowels[rand() % strlen($vowels)];
                $alt = 1;
            }
        }
        $this->code = $password;
        Session::put("OTPCODE",$this->code);
    }

    // verify
}