<?php
include("../config.inc");
include("../src/MailChimp.php");

use \DrewM\MailChimp\MailChimp;
$MailChimp = new MailChimp($mailchimp_key);

$subscriber_hash = $_GET["hash"];

$result = $MailChimp->patch("lists/$mailchimp_list/members/$subscriber_hash", [
            'interests'    => [$_GET["group"] => false],
        ]);

print_r($result);
?>
