<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/5/17
 * Time: 12:46 PM
 */

namespace App\Config;


class Encryption{
    var $key = "aG91c19naXJsX3Bhc3N3b3JkX2tleQ==";

    public function safe_b64encode($string){
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }
    public function safe_b64decode($string){
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4){
            $data .= substr('====',$mod4);
        }
        return base64_decode($data);
    }

    public function getKey(){
        return $this->key;
    }
    public function encode($string){
        if (!$string){
            return false;
        }
        $text = $string;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256,MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size,MCRYPT_RAND);
        $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$this->key,$text,MCRYPT_MODE_ECB,$iv);
        return trim($this->safe_b64encode($crypt));
    }
    public function decode($string){
        if (!$string){
            return false;
        }
        $crypt_text = $this->safe_b64decode($string);
        $iv_size =  mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256,MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size,MCRYPT_RAND);
        $decrypt_text = mcrypt_decrypt(MCRYPT_RIJNDAEL_256,$this->key,$crypt_text,MCRYPT_MODE_ECB,$iv);
        return trim($decrypt_text);
    }
}