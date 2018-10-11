<?php
    namespace App\Statuses;

    class Statuses
    {
        public function errorCodes()
        {
            $code_array =
                [
                    8050 => "We're currently undergoing maintenance, be back with you shortly",
                    6099 => "date is required",
                    6088 => "date not properly formatted, the proper formate is ",
                    6006 => "Some keys or fields are missing",
                    7005 => "Client id required",
                    7006 => "field cannot be empty",
                    5005 => "bad request or one of the fields empty",
                    5008 => "request(s) not allowed",
                    5009 => "deletion successful",
                    5010 => "operation successful",
                    5011 => "operation failed",
                    8002 => "Client not verified",
                    8003 => "Address Type is valid",
                    8005 => "Client Address Details cannot be found",
                    8006 => "Empty Body",
                    8012 => "Oops this is embarrassing, for some reason we're unable to process your request.",
                    8013 => "page not found",
                    8080 => "method not allowed",
                    8081 => "Error! Please check your internet connection",
                    8082 => "Error! Invalid Request",
                ];
            return $code_array;
        }

        public function getStatusError()
        {
            $status_array =
                [
                    6000 => true,
                    6001 => false
                ];

            return $status_array;
        }

        public function getStatus($code, $object_response=null)
        {
            $code_array = $this->getStatusError();
            $status = $code_array[$code];
            $statusHandler = [ 'code' => $code,'success' => $status, 'data' => $object_response];
            return $statusHandler;
        }

        public function retrunWithError($objectresponse = null, $message = null){
            $statusHandler = ['success' => 'false', 'message' => $objectresponse, 'code' => $message];
            return $statusHandler;
        }

        public function addrStatus($code, $object_response=null)
        {
            $code_array = $this->errorCodes();
            $status = $code_array[$code];
            $statusHandler = [ 'code' => $code,'success' => $status, 'data' => $object_response];
            return $statusHandler;
        }

        public function pageListStatus($statuses, $page=null, $limit=null, $object_response=null)
        {
            $status_array = $this->getStatusError();
            $status = $status_array[$statuses];
            $statusHandler = [ 'success' => $status, 'code' => $statuses, 'page' => $page, 'items_per_page' => $limit, 'data' => $object_response];
            return $statusHandler;
        }

        public function getStatusWithError($statuses, $code)
        {
            $status_array = $this->getStatusError();
            $status = $status_array[$statuses];
            $code_array = $this->errorCodes();
            $status_code = $code_array[$code];
            $statusHandler = [ 'success' => $status, 'code' => $code, 'data' => $status_code ];
            return $statusHandler;
        }

        public function getStatusWithErrors($statuses, $code, $error)
        {
            $status_array = $this->getStatusError();
            $status = $status_array[$statuses];
            $code_array = $this->errorCodes();
            $status_code = $code_array[$code];
            $statusHandler = [ 'success' => $status, 'code' => $code, 'data' => $error];
            return $statusHandler;
        }

        public function getStatusWithErrorAndData($statuses, $code, $format)
        {
            $status_array = $this->getStatusError();
            $status = $status_array[$statuses];
            $code_array = $this->errorCodes();
            $status_code = $code_array[$code];
            $statusHandler = [ 'success' => $status, 'code' => $code, 'data' => $status_code.$format];
            return $statusHandler;
        }
    }