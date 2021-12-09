<?php namespace App\Models;

use CodeIgniter\Model;

class NmsApi extends Model
{
  //This class retrieves system availability from LibreNMS

  private $nmsUrl = NULL;
  private $nmsToken = NULL;

  private $devices = NULL;
  private $services = NULL;
  private $alerts = NULL;
  private $rules = NULL;
  private $error = false;

  public function __construct() {
    $this->nmsUrl = getenv("nmsUrl");
    $this->nmsToken = getenv("nmsApiToken");

    $parameters = array();
    $headers = array(
      'X-Auth-Token: df598ff47e6cbbce3a5294ecff3d4a26'
    );

    $url = $this->nmsUrl . '/api/v0/devices';
    $response = $this->request('GET', $url, $parameters, $headers);
    $obj = json_decode($response->body);

    if (isset($obj->message) && $obj->message == "Unauthenticated.") {

      //echo "API is unauthenticated";

      $this->services = array();
      $this->rules = array();
      $this->alerts = array();
      $this->devices = array();

      $this->error = true;

    } else {

      $this->devices = $obj->devices;

      $url = $this->nmsUrl . '/api/v0/alerts';
      $response = $this->request('GET', $url, $parameters, $headers);
      $obj = json_decode($response->body);
      $this->alerts = $obj;

      $url = $this->nmsUrl . '/api/v0/rules';
      $response = $this->request('GET', $url, $parameters, $headers);
      $obj = json_decode($response->body);
      $this->rules = $obj;

      $url = $this->nmsUrl . '/api/v0/services';
      $response = $this->request('GET', $url, $parameters, $headers);
      $obj = json_decode($response->body);
      $this->services = $obj;

    }

  }

  public function getError() {
    return $this->error;
  }

  //This class provides data from the LibreNMS API
  private function request($requestType, $url, $parameters = null, $headers = null){

    // instantiate the response object
    $response = new \stdClass();

    // check if cURL is enabled
    if(!function_exists('curl_init')){

      $response->success = false;
      $response->body = 'cURL is not enabled.';

      return $response;
    }

    // instantiate a cURL instance and set the handle
    $ch = curl_init();

    // build http query if $parameters is not null. Parameters with null as value will be removed from query.
    ($parameters !== null) ? $query = http_build_query($parameters) : $query = '';

    // POST:
    if($requestType === 'POST'){

      // 1 tells libcurl to do a regular HTTP post and sets a "Content-Type: application/www-form-urlencoded" header by default
      curl_setopt($ch,CURLOPT_POST, 1);
      // add the query as POST body
      curl_setopt($ch,CURLOPT_POSTFIELDS, $query);

      // GET:
    } elseif ($requestType === 'GET') {

      // if not empty, add parameters to URL
      if($query) $url = $url . '?' . $query;

      // ELSE:
    }else{

      $response->success = false;
      $response->body = 'request type GET or POST is missing.';

      return $response;
    }

    // set the URL
    curl_setopt($ch, CURLOPT_URL, $url);
    // tell cURL to return the response body. A successful request will return true if not set.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // disable ssl certificate checks. Dirty, insecure workaround for common error "SSL Error: unable to get local issuer certificate". Fix it the correct way and remove the line!
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // more options here: http://php.net/manual/en/function.curl-setopt.php

    // add headers if present
    if ($headers !== null) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // execute and store the result
    $result = curl_exec($ch);

    // check if request was successful. If yes, return result. If not, return error and its code.
    if($result !== false){

      $response->success = true;
      $response->body = $result;

    }else{

      $response->success = false;
      $response->body = curl_error($ch);
      $response->error = curl_errno($ch);
    }

    // close session and delete handle
    curl_close($ch);

    // return response object
    return $response;
  }

  public function getCriticalDevices() {

    $critical = array();

    //if (count($this->devices) != 0) {

      foreach ($this->devices as $device) {
        if ($device->status == 0) {
          array_push($critical, $device);
        }
      }

      return $critical;

    //}

  }

  public function checkHostAlerted($hostname) {
    foreach ($this->alerts->alerts as $alert) {
      if ($alert->hostname == $hostname) {
        return true;
      }
    }
    return false;
  }

  public function getOkayDevices() {

    $ok = array();

    foreach($this->devices as $device) {
      if ($device->status == 1) {

        $badge = "success";
        if ($this->checkHostAlerted($device->hostname)) {
          $badge = "warning";
        }

        array_push($ok, array(
          "host" => $device,
          "badge" => $badge
        ));
      }
    }

    return $ok;

  }

  public function getDevices() {
    return $this->devices;
  }

  public function getAlerts() {

    $output = array();

    foreach($this->alerts->alerts as $alert) {
      //Find rule that triggered alert
      foreach($this->rules->rules as $rule) {
        if ($alert->rule_id == $rule->id) {
          array_push($output, array(
            "alert" => $alert,
            "rule" => $rule
          ));
          break;
        }
      }
    }

    return array(
      "alerts" => $output,
      "count" => count($this->alerts->alerts)
    );
  }

  public function getRules() {
    return $this->rules;
  }

  public function getServices() {
    return $this->services;
  }

  /*public function getRule($id) {
  foreach ($this->rules as $rule) {
  if ($rule->rule_id == $id) {
  return $rule;
}
}
}*/

public function getRule($id) {
  foreach ($this->rules->rules as $rule) {
    if ($rule->rule_id == $id) {
      return $rule;
    }
  }
}


}