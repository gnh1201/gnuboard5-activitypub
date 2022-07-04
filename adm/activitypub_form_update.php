<?php
$sub_menu = "200100";
require_once './_common.php';

check_demo();

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

$object_id = isset($_POST['object_id']) ? trim($_POST['object_id']) : '';
$to = isset($_POST['to']) ? trim($_POST['to']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

$data = activitypub_publish_content($content, $object_id, $member, array(), explode(',', $to));
activitypub_add_activity("outbox", $data, $member);

goto_url('./activitypub_outbox.php');
