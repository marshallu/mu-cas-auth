<?php

namespace Marshall\MUCasAuth\Service\SSO;

use Marshall\MUCasAuth\Model\CasResponse;

class CasRunner
{
  static function authenticate($casHost, $casPath)
  {
    // check for valid ticket and login if needed
    if (!self::isTicketPresent()) {
      $loginUrl = self::getLoginUrl($casHost, $casPath);
      wp_redirect($loginUrl);
      exit;
    }

    // get ticket
    $ticket = self::getTicket();

    if (!$ticket) {
      $loginUrl = self::getLoginUrl($casHost, $casPath);
      wp_redirect($loginUrl);
      exit;
    }

    // validate cas ticket
    $casResponse = self::validateCasTicket($casHost, $casPath, $ticket);

    // check for valid cas response (ie valid ticket)
    if (!$casResponse->isValidResponse()) {
      $loginUrl = self::getLoginUrl($casHost, $casPath);
      wp_redirect($loginUrl);
      exit;
    }

    return [
      'user' => $casResponse->getUser(),
      'attributes' => $casResponse->getAttributes()
    ];
  }

  private static function getLoginUrl($casHost, $casPath)
  {
    $serviceUrl = self::getServiceUrlWithoutTicket();
    return trim($casHost, '/') . $casPath .  '/login?service=' . $serviceUrl;
  }

  private static function getValidationUrl($casHost, $casPath, $ticket)
  {
    $serviceUrl = self::getServiceUrlWithoutTicket();
    return trim($casHost, '/') . $casPath .  '/p3/serviceValidate?service=' . $serviceUrl . '&ticket=' . $ticket;
  }

  private static function isTicketPresent()
  {
    $serviceUrl = self::getServiceUrl();
    return strpos(urldecode($serviceUrl), 'ticket=');
  }

  private static function getTicket()
  {
    parse_str($_SERVER['QUERY_STRING'], $queryStringParts);

    if (!isset($queryStringParts['ticket']))
      return false;

    return $queryStringParts['ticket'];
  }

  private static function getServiceUrl()
  {
	  $scheme = 'http';

	  if ( isset( $_SERVER['HTTP_USER_AGENT_HTTPS'] ) && 'ON' === $_SERVER['HTTP_USER_AGENT_HTTPS'] ) {
		  $scheme = 'https';
	  }

    $currentUrl = $scheme
      . '://' . trim($_SERVER['HTTP_HOST'], '/')
      . '/'
      . ltrim($_SERVER['REQUEST_URI'], '/');

    return urlencode(urldecode($currentUrl));
  }

  private static function getServiceUrlWithoutTicket()
  {
    $serviceUrl = self::getServiceUrl();
    $serviceUrl = urldecode($serviceUrl);
    $serviceUrlParts = parse_url($serviceUrl);
    parse_str($serviceUrlParts['query'], $queryStringParts);

    $queryString = '?';
    foreach ($queryStringParts as $key => $value) {
      if ($key != 'ticket')
        $queryString .= $key . '=' . $value . '&';
    }
    $queryString = rtrim($queryString, '&');

    return urlencode($serviceUrlParts['scheme'] . '://' . $serviceUrlParts['host'] . $serviceUrlParts['path'] . $queryString);
  }

  private static function validateCasTicket($casHost, $casPath, $ticket)
  {
    $validationUrl = self::getValidationUrl($casHost, $casPath, $ticket);
    $data = wp_remote_get($validationUrl);
    return new CasResponse($data['body']);
  }
}
