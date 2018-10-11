<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/6/17
 * Time: 8:39 PM
 */

namespace App\Config;


class Session
{
    public static function put($name,$value){
        return $_SESSION[$name] = $value;
    }

    public static function exists($name){
        return (isset($_SESSION[$name]))? true:false;
    }

    public static function get($name){
        return $_SESSION[$name];
    }
    public static function destroy(){
        session_destroy();
    }


}