<?php

include('config.inc');
include('src/MailChimp.php');

use \DrewM\MailChimp\MailChimp;

$MailChimp = new MailChimp($mailchimp_key);

// Build an array of all of the interest group names
$list = array();
$categories = $MailChimp->get("lists/$mailchimp_list/interest-categories");
foreach ($categories["categories"] as $category){
  $groups = $MailChimp->get("lists/$mailchimp_list/interest-categories/".$category["id"]."/interests?count=100");
  foreach ($groups["interests"] as $group){
    //echo $category["title"] . " | ". $group["name"] . "\n";
    $lists[$group["id"]] = $group["name"];
  }

}

// Lookup requested user's profile
$subscriber_hash = $MailChimp->subscriberHash('mkbriney@gmail.com');
$subscriptions = $MailChimp->get("lists/$mailchimp_list/members/$subscriber_hash");

//print_r($subscriptions);

foreach ($subscriptions["interests"] as $interest_id=>$interest_value){
  if ($interest_value == 1) {
    echo $lists[$interest_id]."\n";
  }
}

?>
