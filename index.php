<html>
<head>
  <link rel="stylesheet" href="/css/forms.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  <style>
    label {width: 350px; float: left;}
  </style>
</head>
<body>

<?php

include("config.inc");
include("src/MailChimp.php");

use \DrewM\MailChimp\MailChimp;
$MailChimp = new MailChimp($mailchimp_key);


// Caching to speed up process
if ( file_exists('cache/categories.json') && filemtime('cache/categories.json') >= strtotime("30 minutes ago") ){ }else {
  file_put_contents('cache/categories.json', json_encode($MailChimp->get("lists/$mailchimp_list/interest-categories")));
}
if ( file_exists('cache/segments.json') && filemtime('cache/segments.json') >= strtotime("30 minutes ago") ){ }else {
  file_put_contents('cache/segments.json', json_encode($MailChimp->get("lists/$mailchimp_list/segments?count=100")));
}


// Build an array of all of the interest group names
$list_groups = array();
$categories = json_decode(file_get_contents('cache/categories.json'), true);
foreach ($categories["categories"] as $category){
  if ( file_exists('cache/group-'.$category["id"].'.json') && filemtime('cache/group-'.$category["id"].'.json') >= strtotime("30 minutes ago") ){ }else {
    file_put_contents('cache/group-'.$category["id"].'.json', json_encode($MailChimp->get("lists/$mailchimp_list/interest-categories/".$category["id"]."/interests?count=100")));
  }
  $groups = json_decode(file_get_contents('cache/group-'.$category["id"].'.json'),true);
  foreach ($groups["interests"] as $group){
    $lists_groups[$group["id"]] = $group["name"];
  }
}

$list_segments = array();
$segments = json_decode(file_get_contents('cache/segments.json'), true);
foreach ($segments["segments"] as $segment){
  if ($segment["type"] = "static"){
    $list_segments[$segment["id"]] = $segment["name"];
  }
}

// Lookup requested user's profile
$subscriber_hash = $MailChimp->subscriberHash('mkbriney@gmail.com');
$subscriptions = $MailChimp->get("lists/$mailchimp_list/members/$subscriber_hash");

//print_r($subscriptions);

?>
<h3>Group Subscriptions</h3>
<div class="label-group">

<?php

foreach ($subscriptions["interests"] as $interest_id=>$interest_value){
  if ($interest_value == 1) {
    $checked = "checked";
  } else {
    $checked = "";
  }
?>
<label>
  <input type="checkbox" class="group" value="<?=$interest_id?>" tabindex="1" <?=$checked?>><?=substr($lists_groups[$interest_id],0,45)?>
</label>
<?php }?>
    </div>

</body>
<script>
$( ".group" ).bind( "click", function() {
      if ($(this).is(':checked')) {
        endpoint = "subscribe.php";
      } else {
        endpoint = "unsubscribe.php";
      }
	    $.ajax({
	       type: "GET",
	       url: "./ajax/"+endpoint,
         data:{ hash : "<?=$subscriber_hash?>", group : $(this).val()},
	       success: function(msg){
	         //alert( "Updated" );
	       }
	     });
});
</script>
</html>
