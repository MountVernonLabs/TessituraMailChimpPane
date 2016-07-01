<?php
include "config.inc";
include('src/MailChimp.php');

use \DrewM\MailChimp\MailChimp;

$MailChimp = new MailChimp($mailchimp_key);
$result = $MailChimp->get("lists/$mailchimp_list/segments/".$argv[1]."/members");

foreach ($result["members"] as $member){
  echo $member["email_address"]."\n";
  print_r($member["merge_fields"]);
}



?>
