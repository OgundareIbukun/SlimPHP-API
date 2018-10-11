<?php
/**
 * Created by PhpStorm.
 * User: funmi
 * Date: 3/5/17
 * Time: 2:33 AM
 */

namespace App\Controllers;


use App\Config\Auth;
use App\Models\GeneralModel;
use App\Statuses\Statuses;
use Exception;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use App\Models\Verification;

class StudentController
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->output_format = (new Auth)->output_format;
        $this->method_names = (new GeneralModel())->get_model_methods("StudentModel");
    }

    public function createStudent(Request $request, Response $response, $args)
    {
        try {
            $req_res = [$request, $response];
            $method_identity = $this->method_names;

            $data = (array)$request->getParsedBody();
            
            

            if (empty($data)) {
                $status = (new Statuses)->getStatusWithError(6001, 5005);
                $result = ["status" => false, "message" => $status];
                return (new GeneralModel)->state_output_format($request, $response, $result);
            } else {
                $verification = new Verification();
                $errors_array = $verification->VerifyUser($data);

                if ($errors_array[0] !== null) {
                    $resultHandler = (new Statuses)->retrunWithError($errors_array[0], $errors_array[1]);
                    return $response->withHeader('Content-Type', 'application/json')
                                ->withJson($resultHandler)
                                ->withStatus(400);
                } 

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

    public function updateStudent(Request $request, Response $response, $args)
    {
        $email = $request->getAttribute("email");
        $data = $request->getParsedBody();
        $req_res = [$request, $response];
        $method_identity = $this->method_names;
        if ($method_identity["status"] == true) {
            $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 1, $this->output_format, $data, $email);
            return $resp;
        } elseif ($method_identity["status"] == false) {
            return $response->withHeader("Content-type", "application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }
    public function changepass(Request $request, Response $response, $args)
    {
        $email = $request->getAttribute("email");
        $data = $request->getParsedBody();
        $req_res = [$request, $response];
        $method_identity = $this->method_names;
        if ($method_identity["status"] == true) {
            $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 2, $this->output_format, $data, $email);
            return $resp;
        } elseif ($method_identity["status"] == false) {
            return $response->withHeader("Content-type", "application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }

    public function deleteStudent(Request $request, Response $response, $args)
    {
        $email = $request->getAttribute("email");
        $req_res = [$request, $response];
        $method_identity = $this->method_names;
        if ($method_identity["status"] == true) {
            $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 3, $this->output_format, "", $email);
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
            $resp = (new GeneralModel)->try_get($req_res, $method_identity["message"], 5, $this->output_format, "", $email_token);
            return $resp;
        } elseif ($method_identity["status"] == false) {
            return $response->withHeader("Content-type", "application/json")
                ->withJson($method_identity)
                ->withStatus(400);
        }
    }
    public function verifyEmail(Request $request, Response $response, $args)
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
    
    
    
    public function getSingleStudent(Request $request, Response $response, $args)
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

    public function getAllStudent(Request $request, Response $response, $args)
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

}