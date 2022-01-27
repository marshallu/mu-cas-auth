<?php

namespace Marshall\MUCasAuth\Model;

class CasResponse
{
  private $response;

  function __construct($rawResponse)
  {
    $this->parseResponse($rawResponse);
  }

  function getUser()
  {
    if (!$this->isValidResponse())
      return false;

    return $this->response->authenticationSuccess->user;
  }

  function getAttributes()
  {
    if (!$this->isValidResponse())
      return false;

    return json_decode(json_encode($this->response->authenticationSuccess->attributes), true);
  }

  function isValidResponse()
  {
    return isset($this->response->authenticationSuccess);
  }

  private function parseResponse($rawResponse)
  {
    $xml = simplexml_load_string($rawResponse);
    $xml = $xml->children('http://www.yale.edu/tp/cas');

    $json = json_encode($xml);
    $this->response = json_decode($json, false);
  }
}
