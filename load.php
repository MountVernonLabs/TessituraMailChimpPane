<?php

include "config.inc";
include('src/MailChimp.php');

use \DrewM\MailChimp\MailChimp;

$MailChimp = new MailChimp($mailchimp_key);

$list = file_get_contents($argv[1]);
$emails = explode("\n",$list);

foreach ($emails as $email){
  echo "Subscribing ".$email."\n";
  $subscriber_hash = $MailChimp->subscriberHash($email);
  $result = $MailChimp->post("lists/$mailchimp_list/segments/".$argv[2]."/members", [ // Testing with 513
                  'id' => $subscriber_hash,
                  'email_address' => $email,
                  'status'        => 'subscribed'
              ]);
}

?>
