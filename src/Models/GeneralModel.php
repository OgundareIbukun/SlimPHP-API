<?php
namespace App\Models;

use Exception;
use PHPOnCouch\CouchClient;
use PHPOnCouch\CouchDocument;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use \GuzzleHttp\Exception\ConnectException as ce;
use \GuzzleHttp\Exception\RequestException as re;
use App\Config\Auth;
use stdClass;
use RedBeanPHP\R;

class GeneralModel
{

    // needed in the model to make http request
    public function make_guzzle_request($url, $method, $token = null, $body = null, $query = null)
    {
        if (!empty($url) || !empty($token) || !empty($method)) {
            try {
                $client = new Client();
                if ($query !== null) {
                    $request = $client->createRequest($method, $url, $query);
                } else {
                    $request = $client->createRequest($method, $url);
                }

                $request->setHeader('Content-type', 'application/json');
                if ($token == null || empty($token)) {
                } else {
                    $request->setHeader('Authorization', $token);
                }

                if ($method == "POST" || $method == "PUT") {
                    if ($body == null || empty($body)) {
                    } else {
                        $request->setBody(Stream::factory($body));
                    }
                } elseif ($method == "GET") {
                    goto sendrequest;
                }
                sendrequest:
                $response = $client->send($request);
                $json = $response->json();
                return $json;
            } catch (ce $e) {
                $error = 8081;
                return $error;
            } catch (re $e) {
                $error = 8082;
                return $error;
            }

        } else {
            $error = 5005;
            return $error;
        }
    }

    // needed in the controller
    public function try_get($req_res, $model_method_array, $i, $output_format, $input = null, $params = null)
    {
        try {
//            print_r($model_method_array);
//            die();
            $request = $req_res[0];
            $response = $req_res[1];

            $get_model_name = array_keys($model_method_array)[0];
            $get_model_value = $model_method_array[$get_model_name];

            $class_name = "App\\Models\\" . $get_model_name;
            $my_obj = new $class_name();

            $get_model_single = $get_model_value[$i];

            if (empty($input) && empty($params)) {
                $output_data = ($my_obj)->$get_model_single();
            } elseif (!empty($input) && empty($params)) {
                $output_data = ($my_obj)->$get_model_single($input);
            } elseif (empty($input) && !empty($params)) {
                $output_data = ($my_obj)->$get_model_single($params);
            } elseif (!empty($input) && !empty($params)) {
                $output_data = ($my_obj)->$get_model_single($input, $params);
            }
            
            
             
            if ($output_data['status'] == "success" || $output_data['success'] == "true") {
                $httpstatus = 200;
            } else {
                
                $httpstatus = 400;
            }

            if ($output_format == "xml") {
                $xml_result = $this->output_xml($output_data, new \SimpleXMLElement('<root/>'))->asXML();
                return $response->withHeader('Content-Type', 'application/xml')
                    ->write($xml_result)
                    ->withStatus($httpstatus);
            } elseif ($output_format == "json") {
                return $response->withHeader('Content-Type', 'application/json')
                    ->withJson($output_data)
                    ->withStatus($httpstatus);
            } else {
                $result = ['status' => 'failed', 'message' => 'invalid output response specified'];
                return $response->withHeader('Content-Type', 'application/json')
                    ->withJson($result)
                    ->withStatus(400);
            }

        } catch (\ResourceNotFoundException $e) {
            return $response->withStatus(404);

        } catch (\Exception $e) {
            return $response->withStatus(400)
                ->withHeader('X-Statuses-Reason', $e->getMessage());
        }
    }


    public function output_xml(array $arr, \SimpleXMLElement $xml)
    {
        foreach ($arr as $k => $v) {
            is_array($v)
                ? $this->output_xml($v, $xml->addChild($k))
                : $xml->addChild($k, $v);
        }
        return $xml;
    }

    public function get_model_methods($model_name)
    {
        $class_name = 'App\\Models\\' . $model_name;

        if (class_exists($class_name)) {
            $my_obj = new $class_name();
            $class_methods = get_class_methods($my_obj);

            $array_method = [];
            $i = 0;
            foreach ($class_methods as $method_name) {
                $array_method[$model_name][$i] = $method_name;
                $i++;
            }
            $result = ['status' => true, 'message' => $array_method];
        } elseif (!class_exists($class_name)) {
            $result = ['status' => false, 'message' => "Model Name does not exist"];
        }

        return $result;
    }


    public function state_output_format(Request $request, Response $response, $data)
    {
        $mediaType = (new Auth)->output_app_format;
        switch ($mediaType) {
            case 'application/xml':
                $response->getBody()->write(arrayToXml($data));
                break;
            case 'application/json':
                $response->getBody()->write(json_encode($data));
                break;
        }
        return $response->withHeader("Content-Type", $mediaType);
    }


    public function encrypt3Des($data, $key)
    {
        //Generate a key from a hash
        $key = md5(utf8_encode($key), true);

        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);

        //Pad for PKCS7
        $blockSize = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($data);
        $pad = $blockSize - ($len % $blockSize);
        $data = $data . str_repeat(chr($pad), $pad);

        //Encrypt data
        $encData = mcrypt_encrypt('tripledes', $key, $data, 'ecb');

        //return $this->strToHex($encData);

        return base64_encode($encData);
    }

    public function decrypt3Des($data, $secret)
    {
        //Generate a key from a hash
        $key = md5(utf8_encode($secret), true);

        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);

        $data = base64_decode($data);

        $data = mcrypt_decrypt('tripledes', $key, $data, 'ecb');

        $block = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($data);
        $pad = ord($data[$len - 1]);

        return substr($data, 0, strlen($data) - $pad);
    }

    // set the http header to json by default
    public function check_couchdb($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content_type' => 'application/json',
            'Accept' => '*/*'
        ));

        $response = curl_exec($ch);
        return $response;
    }

    // get all the databases in the couchdb
    public function get_all_database($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content_type' => 'application/json',
            'Accept' => '*/*'
        ));
        $response = curl_exec($ch);
        return $response;
    }

    // create database in the couch db
    public function create_doc($url,$doc_name,$doc_values){
       try {
           $client = new CouchClient($url, $doc_name);
           $client->createDatabase();
           $doc = new stdClass();
           $doc->email = $doc_values['email'];
           $doc->token= $doc_values['token'];
           $doc->expire = $doc_values['expire'];
           $client->storeDoc($doc);
           return $doc->_id;
       }catch (Exception $exception){
           return  "Something weird happened: ".$exception->getMessage()." (errcode=".$exception->getCode().")\n";
       }
    }

    public function cuntry_2_curry($countryname){
        try {
            $finCountry = R::findOne("country", 'name=?', [$countryname]);
            if (count($finCountry)) {
                return $finCountry["currency"];
            } else {
                return "NGN";
            }
        } catch (Exception $e) {
            
        }

    }

    public function bank_2_code($bank_name){
        try {
            $finBank = R::findOne("bank", 'name=?', [$bank_name]);
            if (count($finBank)) {
                return $finBank["code"];
            } else {
                return "000";
            }
        } catch (Exception $e) {
            
        }

    }
    public function externalRequest($url,$method, $token = null,$payload=null){
        
        
        $headers = array(
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            'Authorization: '.$token
        );
        try {
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if(!empty($payload)){
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $results = curl_exec($ch);
            return $results;
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }
}