<?php

namespace App\Config;

use Illuminate\Database\Eloquent\Model;
class Configuration
{
    public function config($mode)
    {
        $boolean_type = "";

        define("KEY","");
        switch ($mode)
        {
            case "production":
                $boolean_type = false;
                break;

            case "debug":
                $boolean_type = true;
                break;
        }

        $configuration =
            [
                'settings' => [
                    'displayErrorDetails' => $boolean_type,
                ],
                'db' => [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'database' => 'database',
                    'username' => 'user',
                    'password' => 'password',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
              ]
            ];

        return $configuration;
    }
}
