<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// ActivityPub/WebHook implementation for GNUBOARD 5
// Go Namhyeon <gnh1201@gmail.com>
// MIT License
// 2022-07-07

// [Reference]
// * https://github.com/gnh1201/reasonableframework/blob/master/helper/webhooktool.php

// `NateOn` is trademark of SK Communications Co Ltd., SK Planet Co Ltd.
// `Discord' is trademark of Discord Inc. (Former, Hammer And Chisel)
// `Slack` is trademark of Slack Technologies Inc.

// 내려받기: https://sir.kr/g5_plugin/10381
if (!defined("ACTIVITYPUB_INSTANCE_ID")) return;

define("NATEON_WEBHOOK_URL", "");
define("DISCORD_WEBHOOK_URL", "https://discord.com/api/webhooks/994470705125134347/ZQUe-LZYDED6MVFSIJb5A_u0kin2hyI26c_LP5lcFje3DpmeVF1AvLB7ap0wFjNrTmNN");
define("SLACK_WEBHOOK_URL", "https://hooks.slack.com/services/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");
define("SLACK_WEBHOOK_CHANNEL", "#webhook");

function nateon_send_webhook($content, $mb) {
    $headers = array(
        "Content-Type" => "application/x-www-form-urlencoded",
    );

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => NATEON_WEBHOOK_URL,
        CURLOPT_HTTPHEADER => activitypub_build_http_headers($headers),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => "content=" . urlencode($content),
        CURLOPT_POST => true
    ));
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array("status" => $status);
}

function discord_send_webhook($content, $mb) {
    $headers = array(
        "Content-Type" => "application/json",
    );
    
    $rawdata = activitypub_json_encode(array(
        "content" => $content,
        "username" => $mb['mb_nick']
    ));

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => DISCORD_WEBHOOK_URL,
        CURLOPT_HTTPHEADER => activitypub_build_http_headers($headers),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $rawdata,
        CURLOPT_POST => true
    ));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array("status" => $status);
}

function slack_send_webhook($content, $mb) {
    $headers = array(
        "Content-Type" => "application/json",
    );

    $rawdata = activitypub_json_encode(array(
        "channel" => SLACK_WEBHOOK_CHANNEL,
        "username" => $mb['mb_nick'],
        "text" => $content,
        "icon_emoji" => ":ghost:"
    ));

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => SLACK_WEBHOOK_URL,
        CURLOPT_HTTPHEADER => activitypub_build_http_headers($headers),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $rawdata,
        CURLOPT_POST => true
    ));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array("status" => $status);
}

function send_webhooks($content) {
    global $member;

    // 로그인 상태인 경우 해당 회원, 아닌 경우 ActivityPub 공통 계정
    $mb = isset($member['mb_id']) ? $member : get_member(ACTIVITYPUB_G5_USERNAME);

    // 수신자 목록
    $to = array();

    // 참고: 아래 함수의 역할은 외부 프로그램이 담당하게 할 수도 있음 (ActivityPub Outbox로부터 데이터 받은 후 처리 가능)
    // nateon_send_webhook, discord_send_webhook, slack_send_webhook

    if (!empty(NATEON_WEBHOOK_URL))
        nateon_send_webhook($content, $mb);
        $to[] = "webhook:nateon";
    
    if (!empty(DISCORD_WEBHOOK_URL))
        discord_send_webhook($content, $mb);
        $to[] = "webhook:discord";

    if (!empty(SLACK_WEBHOOK_URL))
        slack_send_webhook($content, $mb);
        $to[] = "webhook:slack";

    // Activity로 발행
    activitypub_publish_content(
        $content,
        activitypub_get_url("user", array("mb_id" => $mb['mb_id'])),
        get_member(ACTIVITYPUB_G5_USERNAME),
        array(),
        $to
    );
}

function _webhook_memo_form_update_after($member_list, $str_nick_list, $redirect_url, $me_memo) {
    send_webhooks($me_memo);   // 웹훅 보내기
}

function _webhook_write_update_after($board, $wr_id, $w, $qstr, $redirect_url) {
    global $g5;

    // 본문 가져오기
    $sql = "select wr_id, wr_content from {$g5['write_prefix']}{$board['bo_table']} where wr_id = '{$wr_id}'";
    $row = sql_fetch($sql);
    if (empty($row['wr_id'])) return;

    // 웹훅 보내기
    send_webhooks($row['wr_content']);
}

function _webhook_comment_update_after($board, $wr_id, $w, $qstr, $redirect_url, $comment_id, $reply_array) {
    global $g5;

    // 본문(댓글) 가져오기
    $sql = "select wr_id, wr_parent, wr_content from {$g5['write_prefix']}{$board['bo_table']} where wr_id = '{$comment_id}'";
    $row = sql_fetch($sql);
    if (empty($row['wr_id'])) return;

    // 웹훅 보내기
    send_webhooks($row['wr_content']);
}

add_event("write_update_after", "_webhook_write_update_after", 1, 5);
add_event("comment_update_after", "_webhook_comment_update_after", 1, 7);
add_event("memo_form_update_after", "_webhook_memo_form_update_after", 1, 4);
