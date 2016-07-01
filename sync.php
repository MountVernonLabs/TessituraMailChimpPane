<?php

include "config.inc";
include('src/MailChimp.php');

use \DrewM\MailChimp\MailChimp;

$headers = array();
$headers[] = 'Content-Type: application/json'; $headers[] = 'Authorization: Basic ' . base64_encode($tessitura_login);

// Retrieve a list inside Tessitura
$get_list = curl_init();
curl_setopt_array($get_list, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'https://'.$tessitura_ramp.'.tessituranetworkramp.com/LiveAPI/TessituraService/Finance/ListContents?listId='.$argv[1],  // Testing with 6585
    CURLOPT_USERAGENT => 'MailChimp Sync'
));
curl_setopt($get_list, CURLOPT_HTTPHEADER, $headers);
$list = json_decode(curl_exec($get_list),true);

foreach ($list as $record){
  echo "Looking up...".$record["ConstituentId"]."\n";
  // Look up the Constituent record
  $get_person = curl_init();
  curl_setopt_array($get_person, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => 'https://'.$tessitura_ramp.'.tessituranetworkramp.com/LiveAPI/TessituraService/CRM/Constituents/'.$record["ConstituentId"].'/Detail',
      CURLOPT_USERAGENT => 'MailChimp Sync'
  ));
  curl_setopt($get_person, CURLOPT_HTTPHEADER, $headers);
  $person = json_decode(curl_exec($get_person),true);

  // Get last transactional information
  $get_financial = curl_init();
  curl_setopt_array($get_financial, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => 'https://'.$tessitura_ramp.'.tessituranetworkramp.com/LiveAPI/TessituraService/CRM/Constituents/'.$record["ConstituentId"].'/DevelopmentInfo',
      CURLOPT_USERAGENT => 'MailChimp Sync'
  ));
  curl_setopt($get_financial, CURLOPT_HTTPHEADER, $headers);
  $financial = json_decode(curl_exec($get_financial),true);

  // Get last order (mansion tour)
  $get_orders = curl_init();
  curl_setopt_array($get_orders, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => 'https://'.$tessitura_ramp.'.tessituranetworkramp.com/LiveAPI/TessituraService/Custom/tickets?customer_no='.$record["ConstituentId"],
      CURLOPT_USERAGENT => 'MailChimp Sync'
  ));
  curl_setopt($get_orders, CURLOPT_HTTPHEADER, $headers);
  $orders = new SimpleXMLElement(curl_exec($get_orders));

  foreach ($orders->ticket as $ticket){
    //print_r($ticket);
    if ($ticket->perf_name == "Mansion Tour"){
      $last_visit = $ticket->perf_dt;
    }
  }

  // Loop through all of the email addresses and then subscribe them to your MailChimp list
  foreach ($person["ElectronicAddresses"] as $email){

    $MailChimp = new MailChimp($mailchimp_key);

    if (strpos($email["Address"], 'mountvernonmember.org') === false) {
      echo "Subscribing...".$person["FirstName"]." ".$person["LastName"]." ".$email["Address"]."...";
      // First we write the contact with just email (in case they are not already in the system)
      $result = $MailChimp->post("lists/$mailchimp_list/members", [
                      'email_address' => $email["Address"],
                      'status'        => 'subscribed',
                  ]);
      // Then we need to update the record to overwrite any new field changes
      $subscriber_hash = $MailChimp->subscriberHash($email["Address"]);

      $merge_fields = new stdClass;
      if (isset($record["ConstituentId"])){
        $merge_fields->CID = $record["ConstituentId"];
      }
      if (isset($person["Addresses"][0]["PostalCode"])){
        $merge_fields->ZIPCODE = $person["Addresses"][0]["PostalCode"];
      }
      if (isset($person["FirstName"])){
        $merge_fields->FNAME = $person["FirstName"];
      }
      if (isset($person["LastName"])){
        $merge_fields->LNAME = $person["LastName"];
      }
      if (isset($person["Salutations"][0]["LetterSalutation"])){
        $merge_fields->LETTER_SAL = $person["Salutations"][0]["LetterSalutation"];
      }
      if (isset($person["Salutations"][0]["EnvelopeSalutation1"])){
        $merge_fields->ENV_SAL = $person["Salutations"][0]["EnvelopeSalutation1"];
      }
      if (isset($person["PhoneNumbers"][0]["PhoneFormatted"])){
        $merge_fields->PHONE = $person["PhoneNumbers"][0]["PhoneFormatted"];
      }
      if (isset($financial["MembershipLevel"])){
        $merge_fields->MEMB_LEVEL = $financial["MembershipLevel"];
      }
      if (isset($financial["MembershipExpiration"])){
        $merge_fields->MEMB_EXPR = date("Y-m-d",strtotime($financial["MembershipExpiration"]));
      }
      if (isset($last_visit)){
        $merge_fields->NEXT_PERF = date("Y-m-d",strtotime($last_visit));
      }
      // Removing state because it needs to be re-formatted in MailChimp
      //if (isset($person["Addresses"][0]["State"]["StateCode"])){
      //  $merge_fields->STATE = $person["Addresses"][0]["State"]["StateCode"];
      //}

      print_r($merge_fields);
      $result = $MailChimp->patch("lists/$mailchimp_list/members/$subscriber_hash", [
                      'merge_fields' => $merge_fields,
                  ]);
      // Add to segment if passed in
      if (isset($argv[2])){
        $result = $MailChimp->post("lists/$mailchimp_list/segments/".$argv[2]."/members", [ // Testing with 513
                        'id' => $subscriber_hash,
                        'email_address' => $email["Address"],
                        'status'        => 'subscribed'
                    ]);
      }
      echo "done!\n";
    }

  }



}

curl_close($get_list);

 ?>
