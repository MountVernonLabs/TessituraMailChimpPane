<?php
include "config.inc";
include('src/MailChimp.php');

use \DrewM\MailChimp\MailChimp;

$MailChimp = new MailChimp($mailchimp_key);
$result = $MailChimp->get('lists/'.$mailchimp_list.'/members');

foreach ($result["members"] as $member){
  print_r($member["merge_fields"]);
}



?>