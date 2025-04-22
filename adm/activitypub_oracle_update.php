<?php
$sub_menu = "200400";
require_once './_common.php';

check_demo();

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

$object_id = isset($_POST['object_id']) ? trim($_POST['object_id']) : '';
$to = isset($_POST['to']) ? trim($_POST['to']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

goto_url('./activitypub_oracle.php');
