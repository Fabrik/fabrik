<?php

// Result.php
#################################################
##
## PHPLicengine
##
#################################################
## Copyright 2009-{current_year} PHPLicengine
## 
## Licensed under the Apache License, Version 2.0 (the "License");
## you may not use this file except in compliance with the License.
## You may obtain a copy of the License at
##
##    http://www.apache.org/licenses/LICENSE-2.0
##
## Unless required by applicable law or agreed to in writing, software
## distributed under the License is distributed on an "AS IS" BASIS,
## WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
## See the License for the specific language governing permissions and
## limitations under the License.
#################################################

namespace PHPLicengine\Api;

class Result {

      protected $error;
      protected $message;
      protected $headers;
      protected $curlInfo;
      protected $reasonPhrases = array(
                // INFORMATIONAL CODES
                100 => 'Continue',
                101 => 'Switching Protocols',
                102 => 'Processing',
                // SUCCESS CODES
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                207 => 'Multi-status',
                208 => 'Already Reported',
                // REDIRECTION CODES
                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                306 => 'Switch Proxy', // Deprecated
                307 => 'Temporary Redirect',
                // CLIENT ERROR
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Time-out',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Large',
                415 => 'Unsupported Media Type',
                416 => 'Requested range not satisfiable',
                417 => 'Expectation Failed',
                418 => 'I\'m a teapot',
                422 => 'Unprocessable Entity',
                423 => 'Locked',
                424 => 'Failed Dependency',
                425 => 'Unordered Collection',
                426 => 'Upgrade Required',
                428 => 'Precondition Required',
                429 => 'Too Many Requests',
                431 => 'Request Header Fields Too Large',
                // SERVER ERROR
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                504 => 'Gateway Time-out',
                505 => 'HTTP Version not supported',
                506 => 'Variant Also Negotiates',
                507 => 'Insufficient Storage',
                508 => 'Loop Detected',
                511 => 'Network Authentication Required'
      );

      public function __construct($body, $headers, $curlInfo) 
      {
             $this->body = $body;
             $this->headers = $headers;
             $this->curlInfo = $curlInfo;
      }

      public function isError () 
      {
             return isset($this->getResponseObject()->errors) && $this->getResponseObject()->errors;
      }
     
      public function getDescription () 
      {
             return $this->getResponseObject()->description;
      }

      public function getHeaders()
      {
             return $this->headers;
      }

      public function getResponse()
      {
             return $this->body;
      }

      public function getResponseObject() 
      {
             return json_decode($this->body);
      }

      public function getResponseArray () 
      {
             return json_decode($this->body, true);
      }

      public function getResponseCode()
      {
             return $this->curlInfo['http_code'];
      }

      public function getReasonPhrase()
      {
             return $this->reasonPhrases[$this->curlInfo['http_code']];
      }

      public function getContentType()
      {
             return $this->curlInfo['content_type'];
      }

      public function isOk()
      {
             return ($this->curlInfo['http_code'] === 200);
      }

      public function isSuccess()
      {
             return (200 <= $this->curlInfo['http_code'] && 300 > $this->curlInfo['http_code']);
      }

      public function isNotFound()
      {
             return ($this->curlInfo['http_code'] === 404);
      }

      public function isInformational()
      {
             return ($this->curlInfo['http_code'] >= 100 && $this->curlInfo['http_code'] < 200);
      }

      public function isRedirect()
      {
             return (300 <= $this->curlInfo['http_code'] && 400 > $this->curlInfo['http_code']);
      }

      public function isClientError()
      {
             return ($this->curlInfo['http_code'] < 500 && $this->curlInfo['http_code'] >= 400);
      }

      public function isServerError()
      {
             return (500 <= $this->curlInfo['http_code'] && 600 > $this->curlInfo['http_code']);
      }

}
