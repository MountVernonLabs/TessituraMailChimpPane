<?php

include "config.inc";
include('src/MailChimp.php');

use \DrewM\MailChimp\MailChimp;

$MailChimp = new MailChimp($mailchimp_key);

$result = $MailChimp->get("lists/$mailchimp_list/segments/".$argv[1]."/members");

while (count($result["members"]) > 0){
  $result = $MailChimp->get("lists/$mailchimp_list/segments/".$argv[1]."/members");
  foreach ($result["members"] as $member){
      echo "Removing ".$member["email_address"]."\n";
      $remove = $MailChimp->delete("lists/$mailchimp_list/segments/".$argv[1]."/members/".$member["id"]);
  }
}

?>
