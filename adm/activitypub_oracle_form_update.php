<?php
$sub_menu = "200400";
require_once './_common.php';

check_demo();

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();


var_dump($_POST);

exit;

goto_url('./activitypub_oracle_form.php');
