<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/8/17
 * Time: 12:54 PM
 */

namespace App\Controllers;
use App\Config\Auth;
use App\Models\GeneralModel;
use App\Statuses\Statuses;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Exception;

class AdminController
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->output_format = (new Auth)->output_format;
        $this->method_names = (new GeneralModel())->get_model_methods("AdminModel");
    }

    public function createAdmin(Request $request, Response $response)
    {
        try {
            $req_res = [$request, $response];
            $method_identity = $this->method_names;

            $data = (array)$request->getParsedBody();

            if (empty($data)) {
                $status = (new Statuses)->getStatusWithError(6001, 5005);
                $result = [
                    "status" => false,
                    "message" => $status
                ];
                return (new GeneralModel)->state_output_format($request, $response, $result);
            } else {
                if ($method_identity["status"] == true) {
                    $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 0, $this->output_format, $data);
                    return $resp;
                } elseif ($method_identity["status"] == false) {
                    return $response->withHeader("Content-type", "application/json")
                        ->withJson($method_identity)
                        ->withStatus(400);
                }
            }

        } catch (Exception $exception) {
            return $exception;
        }
    }

    public function updateAdmin(Request $request,Response $response,$args){
        $email = $request->getAttribute("email");
        $data = $request->getParsedBody();
        $req_res = [$request,$response];
        $method_identity =$this->method_names;
        if ($method_identity["status"] ==  true){
            $resp = (new GeneralModel)->try_get($req_res,$method_identity["message"],1,$this->output_format,$data,$email);
            return $resp;
        }elseif ($method_identity["status"] == false){
            return $response->withHeader("Content-type","application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }
    public function deleteAdmin(Request $request,Response $response,$args){
        $email = $request->getAttribute("email");
        $req_res = [$request,$response];
        $method_identity =$this->method_names;
        if ($method_identity["status"] ==  true){
            $resp = (new GeneralModel)->try_get($req_res,$method_identity["message"],2,$this->output_format,"",$email);
            return $resp;
        }elseif ($method_identity["status"] == false){
            return $response->withHeader("Content-type","application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }

    public function getSingleAdmin(Request $request, Response $response, $args)
    {
        $email = $request->getAttribute("email");
        $req_res = [$request, $response];
        $method_identity = $this->method_names;
        if ($method_identity["status"] == true) {
            $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 6, $this->output_format, "", $email);
            return $resp;
        } elseif ($method_identity["status"] == false) {
            return $response->withHeader("Content-type", "application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }

    public function getAllAdmin(Request $request, Response $response, $args)
    {
        $req_res = [$request, $response];
        $method_identity = $this->method_names;
        if ($method_identity["status"] == true) {
            $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 7, $this->output_format, "", "");
            return $resp;
        } elseif ($method_identity["status"] == false) {
            return $response->withHeader("Content-type", "application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }

    public function verifyEmailAndToken(Request $request, Response $response, $args)
    {
        $email = $request->getAttribute("email");
        $token = $request->getAttribute("token");
        $email_token = [$email,$token];
        $req_res = [$request, $response];
        $method_identity = $this->method_names;
        if ($method_identity["status"] == true) {
            $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 4, $this->output_format, "", $email_token);
            return $resp;
        } elseif ($method_identity["status"] == false) {
            return $response->withHeader("Content-type", "application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }
}