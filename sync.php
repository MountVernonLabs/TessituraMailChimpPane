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
  // Loop through all of the email addresses and then subscribe them to your MailChimp list
  foreach ($person["ElectronicAddresses"] as $email){
    echo "Subscribing...".$person["FirstName"]." ".$person["LastName"]." ".$email["Address"]."...";
    $MailChimp = new MailChimp($mailchimp_key);

    // First we write the contact with just email (in case they are not already in the system)
    $result = $MailChimp->post("lists/$mailchimp_list/members", [
                    'email_address' => $email["Address"],
                    'status'        => 'subscribed',
                ]);
    // Then we need to update the record to overwrite any new field changes
    $subscriber_hash = $MailChimp->subscriberHash($email["Address"]);
    $result = $MailChimp->patch("lists/$mailchimp_list/members/$subscriber_hash", [
                    'merge_fields' => ['CID'=>$record["ConstituentId"], 'FNAME'=>$person["FirstName"], 'LNAME'=>$person["LastName"], 'ZIPCODE'=>$person["Addresses"][0]["PostalCode"], 'STATE'=>$person["Addresses"][0]["State"]["StateCode"], 'LETTER_SAL'=>$person["Salutations"][0]["LetterSalutation"], 'ENV_SAL'=>$person["Salutations"][0]["EnvelopeSalutation1"], 'PHONE'=>$person["PhoneNumbers"][0]["PhoneFormatted"] ],
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

curl_close($get_list);

 ?>
