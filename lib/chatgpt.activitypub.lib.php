<?php
// GhatGPT-ActivityPub implementation for GNUBOARD 5
// Go Namhyeon <abuse@catswords.net>
// MIT License
// 2023-02-16

if (!defined('_GNUBOARD_') || !defined("ACTIVITYPUB_INSTANCE_ID")) exit; // 개별 페이지 접근 불가

// ChatGPT API 키 발급: https://platform.openai.com/account/api-keys
define("CHATGPT_API_KEY", "YOUR_API_KEY");   // API 키 입력
define("CHATGPT_API_URL", "https://api.openai.com/v1/completions");    // GhatGPT API 주소 입력
define("LINGVA_API_URL", "https://lingva.ml/api/v1");   // Lingva Translate (구글 번역기 프론트엔드) API 주소 입력

function lingva_translate($content, $source = 'ko', $target = 'en') {
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, LINGVA_API_URL . '/' . $source . '/' . $target . '/' . urlencode($content));
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    $response = json_decode(curl_exec($handle), true);
    curl_close($handle);
    return $response['translation'];
}

function lingva_ko2en($content) {
    return lingva_translate($content, 'ko', 'en');
}

function lingva_en2ko($content) {
    return lingva_translate($content, 'en', 'ko');
}

function chatgpt_request($content, $mb) {
    // "What is the capital of France?"
    $prompt = lingva_ko2en(filter_var($content, FILTER_SANITIZE_STRING));
    $prompt = filter_var($content, FILTER_SANITIZE_STRING);

    $data = array(
        "model" => "text-davinci-003",
        "prompt" => $prompt,
        "max_tokens" => 3000,
        "temperature" => 0.5,
    );

    $data_string = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CHATGPT_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . CHATGPT_API_KEY,
        "Content-Length: " . strlen($data_string))
    );

    $output = curl_exec($ch);
    curl_close($ch);
    // print_r($output);

    $output_json = json_decode($output, true);
    $response = $output_json["choices"][0]["text"];
    // echo $response;
    
    return lingva_en2ko($response);
}

function chatgpt_send_conversation($content) {
    global $member;
    
    // 로그인 상태인 경우 해당 회원, 아닌 경우 ActivityPub 공통 계정
    $mb = isset($member['mb_id']) ? $member : get_member(ACTIVITYPUB_G5_USERNAME);

    // 수신자 목록
    $to = array();

    // 참고: 아래에 기술된 역할은 외부 프로그램이 담당하게 할 수도 있음 (service:chatgpt)
    if (!empty(CHATGPT_API_KEY)) {
        $response = chatgpt_request($content, $mb);
        $to[] = "service:chatgpt";
    }

    // Activity 발행 (발신: 그누보드5 -> ChatGPT)
    activitypub_publish_content(
        $content,
        activitypub_get_url("user", array("mb_id" => $mb['mb_id'])),
        get_member(ACTIVITYPUB_G5_USERNAME),
        array(),
        $to
    );

    // Activity 발행 (수신: ChatGPT -> 그누보드5)
    activitypub_publish_content(
        $response,
        "service:chatgpt",
        get_member(ACTIVITYPUB_G5_USERNAME),
        array(),
        array(
            activitypub_get_url("user", array("mb_id" => $mb['mb_id']))
        )
    );
}

function _chatgpt_memo_form_update_after($member_list, $str_nick_list, $redirect_url, $me_memo) {
    // 수신자에 'apstreams' 계정이 있는지 확인
    if (!in_array(ACTIVITYPUB_G5_USERNAME, $member_list['id'])) return;

    // ChatGPT에게 대화 걸기
    chatgpt_send_conversation($me_memo);
}

add_event("memo_form_update_after", "_chatgpt_memo_form_update_after", 1, 4);
