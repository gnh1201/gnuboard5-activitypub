<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// ActivityPub implementation for GNUBOARD 5
// Go Namhyeon <gnh1201@gmail.com>
// MIT License
// 2022-07-04 (version 0.1.6)

// References:
//   * https://www.w3.org/TR/activitypub/
//   * https://github.com/w3c/activitypub/issues/194
//   * https://docs.joinmastodon.org/spec/webfinger/
//   * https://organicdesign.nz/ActivityPub_Code
//   * https://socialhub.activitypub.rocks/t/posting-to-pleroma-inbox/1184

define("ACTIVITYPUB_INSTANCE_ID", md5_file(G5_DATA_PATH . "/dbconfig.php"));
define("ACTIVITYPUB_HOST", (empty(G5_DOMAIN) ? $_SERVER['HTTP_HOST'] : G5_DOMAIN));
define("ACTIVITYPUB_URL", (empty(G5_URL) ? "http://" . ACTIVITYPUB_INSTANCE_ID . ".local" : G5_URL));
define("ACTIVITYPUB_DATA_URL", ACTIVITYPUB_URL . '/' . G5_DATA_DIR);
define("ACTIVITYPUB_G5_BOARDNAME", "apstreams");
define("ACTIVITYPUB_G5_TABLENAME", $g5['write_prefix'] . ACTIVITYPUB_G5_BOARDNAME);
define("ACTIVITYPUB_G5_USERNAME", "apstreams");
define("ACTIVITYPUB_NEW_DAYS", (empty($config['cf_new_del']) ? 30 : $config['cf_new_del']));
define("ACTIVITYPUB_ACCESS_TOKEN", "mastodon.social=;");  // e,g. server1.example.org=xxuPtHDkMgYQfcy9; server2.example.org=PC6ujkjQXhL6lUtS;
define("NAMESPACE_ACTIVITYSTREAMS", "https://www.w3.org/ns/activitystreams");
define("NAMESPACE_ACTIVITYSTREAMS_PUBLIC", "https://www.w3.org/ns/activitystreams#Public");

function activitypub_json_encode($arr) {
    return json_encode($arr);
}

function activitypub_json_decode($arr) {
    return json_decode($arr, true);
}

function activitypub_get_access_tokens() {
    $_access_tokens = array();

    $terms = array_filter(array_map("trim", explode(";", ACTIVITYPUB_ACCESS_TOKEN)));
    foreach($terms as $term) {
        list($k, $v) = explode('=', $term);
        $_access_tokens[$k] = $v;
    }

    return $_access_tokens;
};

function activitypub_get_url($action, $params = array()) {
    if (count(array_keys($params)) > 0) {
        return ACTIVITYPUB_URL . "/?route=activitypub." . $action . "&" . http_build_query($params);
    } else {
        return ACTIVITYPUB_URL . "/?route=activitypub." . $action;
    }
}

function activitypub_get_icon($mb) {
    global $config;

    $icon_file_url = "";

    if ($config['cf_use_member_icon']) {
        $mb_dir = substr($mb['mb_id'], 0, 2);
        $icon_file = G5_DATA_PATH . '/member/' . $mb_dir . '/' . get_mb_icon_name($mb['mb_id']).'.gif';
        if (file_exists($icon_file)) {
            $icon_filemtile = (defined('G5_USE_MEMBER_IMAGE_FILETIME') && G5_USE_MEMBER_IMAGE_FILETIME) ? '?'.filemtime($icon_file) : '';
            $icon_file_url = ACTIVITYPUB_DATA_URL . '/member/' . $mb_dir . '/' . get_mb_icon_name($mb['mb_id']) . '.gif' . $icon_filemtile;
        }
    }

    if (empty($icon_file_url)) {
        $icon_file_url = "https://www.gravatar.com/avatar/" . md5($mb['mb_email']);
    }

    return $icon_file_url;
}

function activitypub_get_user_interactions() {
    global $g5;

    // 반환할 배열
    $items = array();

    // 최근 활동에서 추출
    $sql = "select * from " . $g5['board_new_table']; 
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $sql2 = "select mb_id from " . ($g5['write_prefix'] . $row['bo_table']) . " where wr_id = '" . $row['wr_id'] . "'";
        $row2 = sql_fetch($sql2);
        if ($row2['mb_id']) {
            array_push($items, array("from" => $row['mb_id'], "to" => $row2['mb_id']));
        }
    }

    // '좋아요'에서 추출
    $sql = "select bo_table, wr_id, mb_id from {$g5['board_good_table']} where bg_flag = 'good'";
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $sql2 = "select mb_id from " . ($g5['write_prefix'] . $row['bo_table']) . " where wr_id = '" . $row['wr_id'] . "'";
        $row2 = sql_fetch($sql2);
        if ($row2['mb_id']) {
            array_push($items, array("from" => $row['mb_id'], "to" => $row2['mb_id']));
        }
    }

    return $items;
}

function activitypub_get_followers($mb) {
    $followers = array();

    if ($mb['mb_id']) {
        $linked_users = activitypub_get_user_interactions();
        foreach($linked_users as $item) {
            if ($item['to'] == $mb['mb_id'] && $item['from'] != $mb['mb_id']) {
                array_push($followers, $item['from']);
            }
        }

        $followers = array_unique($followers);
    }

    return $followers;
}

function activitypub_get_following($mb) {
    $following = array();

    if ($mb['mb_id']) {
        $linked_users = activitypub_get_user_interactions();

        foreach($linked_users as $item) {
            if ($item['from'] == $mb['mb_id'] && $item['to'] != $mb['mb_id']) {
                array_push($following, $item['to']);
            }
        }

        $following = array_unique($following);
    }
    
    return $following;
}

function activitypub_parse_url($url) {
    $ctx = parse_url($url);
    $qstr = $ctx['query'];
    parse_str($qstr, $qctx);
    $ctx['query'] = $qctx;
    return $ctx;
}

function activitypub_add_memo($mb_id, $recv_mb_id, $me_memo) {
    global $g5;
    
    $tmp_row = sql_fetch(" select max(me_id) as max_me_id from {$g5['memo_table']} ");
    $me_id = $tmp_row['max_me_id'] + 1;

    $sql = " insert into {$g5['memo_table']} ( me_recv_mb_id, me_send_mb_id, me_send_datetime, me_memo, me_read_datetime, me_type, me_send_ip ) values ( '$recv_mb_id', '$mb_id', '".G5_TIME_YMDHIS."', '$me_memo', '0000-00-00 00:00:00' , 'recv', '{$_SERVER['REMOTE_ADDR']}' ) ";
    sql_query($sql);

    return ($me_id == sql_insert_id());
}

function activitypub_set_liked($good, $bo_table, $wr_id) {
    global $g5;

    // 추천(찬성), 비추천(반대) 카운트 증가
    sql_query(" update {$g5['write_prefix']}{$bo_table} set wr_{$good} = wr_{$good} + 1 where wr_id = '{$wr_id}' ");

    // 내역 생성
    sql_query(" insert {$g5['board_good_table']} set bo_table = '{$bo_table}', wr_id = '{$wr_id}', mb_id = '" . ACTIVITYPUB_G5_USERNAME . "', bg_flag = '{$good}', bg_datetime = '" . G5_TIME_YMDHIS . "' ");
}

function activitypub_http_noheader_get($url, $access_token = '') {
    $headers = array();
    if (!empty($access_token)) {
        $headers["Authorization"] = "Bearer " . $access_token;
    }

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true
    ));
    $response = curl_exec($ch);
    curl_close($ch);

    return activitypub_json_decode($response, true);
}

function activitypub_http_get($url, $access_token = '') {
    $headers = array("Accept" => "application/ld+json; profile=\"" . NAMESPACE_ACTIVITYSTREAMS . "\"");
    if (!empty($access_token)) {
        $headers["Authorization"] = "Bearer " . $access_token;
    }

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => array(
            "Accept" => $headers
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true
    ));
    $response = curl_exec($ch);
    curl_close($ch);

    // 일부 서버는 ActivityPub을 위한 Accept 헤더를 해석하지 못함
    if (strpos($response, "<title>400 Bad Request</title>") !== false)
        return activitypub_http_noheader_get($url, $access_token);

    return activitypub_json_decode($response, true);
}

function activitypub_http_noheader_post($url, $rawdata, $access_token = '') {
    $headers = array();
    if (!empty($access_token)) {
        $headers["Authorization"] = "Bearer " . $access_token;
    }

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $rawdata,
        CURLOPT_POST => true
    ));
    $response = curl_exec($ch);
    curl_close($ch);

    return activitypub_json_decode($response, true);
}

function activitypub_http_post($url, $rawdata, $access_token = '') {
    $headers = array("Accept" => "application/ld+json; profile=\"" . NAMESPACE_ACTIVITYSTREAMS . "\"");
    if (!empty($access_token)) {
        $headers["Authorization"] = "Bearer " . $access_token;
    }

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $rawdata,
        CURLOPT_POST => true
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    
    // 일부 서버는 ActivityPub을 위한 Accept 헤더를 해석하지 못함
    if (strpos($response, "<title>400 Bad Request</title>") !== false)
        return activitypub_http_noheader_post($url, $rawdata, $access_token);
    
    return activitypub_json_decode($response, true);
}

function activitypub_publish_content($content, $id, $mb = array("mb_id" => ACTIVITYPUB_G5_USERNAME)) {
    // 컨텐츠 파싱
    $terms = activitypub_parse_content($content);

    // 수신자/내용 생성
    $to = array(NAMESPACE_ACTIVITYSTREAMS_PUBLIC);
    $content = "";
    foreach($terms as $term_ctx) {
        switch ($term_ctx['type']) {
            case "account":
                // WebFinger 정보 수신
                $account = substr($term_ctx['value'], 1);
                $account_terms = explode('@', $account);
                $account_ctx = array("username" => $account_terms[0], "host" => $account_terms[1]);
                if (!empty($account_ctx['host'])) {
                    // 그누5 전용 WebFinger에 우선적으로 연결
                    $webfigner_ctx = activitypub_http_get("http://" . $account_ctx['host'] . "/?route=webfinger&resource=acct:" . $account);

                    // 실패시, 공통 WebFinger에 연결
                    if ($webfigner_ctx['subject'] != ("acct:" . $account)) {
                        $webfigner_ctx = activitypub_http_get("http://" . $account_ctx['host'] . "/.well-known/webfinger?resource=acct:" . $account);
                    }

                    // 한번 더 확인
                    if ($webfigner_ctx['subject'] != ("acct:" . $account)) break;

                    // 받은 요청으로 처리
                    $webfigner_links = $webfigner_ctx['links'];
                    foreach($webfigner_links as $link) {
                        if ($link['rel'] == "self" && $link['type'] == "application/activity+json") {
                            array_push($to, $link['href']);  // 수신자에 반영
                        }
                    }
                }
                break;

            case "fulltext":
                $content = $term_ctx['value'];
                break;
        }
    }

    // 전문 생성
    $object = array(
        "type" => "Note",
        "id" => $id,
        "attributedTo" => activitypub_get_url("user", array("mb_id" => $mb['mb_id'])),
        "content" => $content
    );

    // 외부로 보낼 전문 생성
    $data = array(
        "@context" => NAMESPACE_ACTIVITYSTREAMS,
        "type" => "Create",
        "id" => "",
        "to" => $to,
        "actor" => $object['attributedTo'],
        "object" => $object
    );

    // 보낼 전문을 인코딩
    $rawdata = activitypub_json_encode($data);

    // 수신자 작업
    
    foreach($to as $_to) {
        // 공개 네임스페이스인 경우 건너뛰기
        if ($_to == NAMESPACE_ACTIVITYSTREAMS_PUBLIC) continue;

        // 수신자 정보 조회
        $remote_user_ctx = activitypub_http_get($_to);

        // inbox 주소 찾기
        $remote_inbox_url = $remote_user_ctx['inbox'];
        if (empty($remote_inbox_url)) {
            $remote_inbox_url = $remote_user_ctx['endpoints']['sharedInbox'];
        }

        // inbox 주소가 없으면 건너뛰기
        if (empty($remote_inbox_url)) {
            activitypub_add_memo(ACTIVITYPUB_G5_USERNAME, $mb['mb_id'], "Could not find the inbox of " . $_to);
            continue;
        }

        // 엑세스 토큰(Access Token)이 존재하는 목적지인 경우
        $access_token = '';
        $_access_tokens = activitypub_get_access_tokens();
        foreach($_access_tokens as $k=>$v) {
            if(strpos($_to, "http://" . $k . "/") !== false || strpos($_to, "https://" . $k . "/") !== false) {
                $access_token = $v;
                break;
            }
        }

        // inbox로 데이터 전송
        $response = activitypub_http_post($remote_inbox_url, $rawdata, $access_token);
    }

    return $data;
}

function activitypub_parse_content($content) {
    $entities = array();

    $pos = -1;
    $get_next_position = function ($pos) use ($content) {
        try {
            return min(array_filter(array(
                strpos($content, '@', $pos + 1),
                strpos($content, '#', $pos + 1),
                strpos($content, 'http://', $pos + 1),
                strpos($content, 'https://', $pos + 1)
            ), "is_numeric"));
        } catch (ValueError $e) {
            return false;
        }
    };

    $pos = $get_next_position($pos);

    while ($pos !== false) {
        $end = strpos($content, ' ', $pos + 1);

        $expr = "";
        if ($end !== false) {
            $expr = substr($content, $pos, $end - $pos);
        } else {
            $expr = substr($content, $pos);
        }

        // EXAMPLE: @username@target.example.org
        if (substr($expr, 0, 1) == '@' && strpos(substr($expr, 1), '@') !== false) {
            array_push($entities, array("type" => "account", "value" => $expr));
        } else if (substr($expr, 0, 1) == '#') {
            array_push($entities, array("type" => "hashtag", "value" => $expr));
        } else if (substr($expr, 0, 4) == 'http') {
            array_push($entities, array("type" => "url", "value" => $expr));
        }

        array_push($entities, array("type" => "fulltext", "value" => $content));

        $pos = $get_next_position($pos);
    }
    
    return $entities;
}

function activitypub_add_activity($inbox = "inbox", $data, $mb = array("mb_id" => ACTIVITYPUB_G5_USERNAME)) {
    global $g5;

    // 반환할 글 번호
    $wr_id = 0;

    // 기본 파라미터
    $to = $data['to'];
    $object = $data['object'];
    $content = $object['content'];

    // 공개 설정이 없는 경우 비밀글로 설정
    $wr_option = '';
    if (!in_array(NAMESPACE_ACTIVITYSTREAMS_PUBLIC, $to))
        $wr_option = 'secret';

    // 게시글로 등록
    $write_table = ACTIVITYPUB_G5_TABLENAME;
    $wr_num = get_next_num($write_table);
    $wr_reply = '';
    $ca_name = $inbox;    // Inbox/Outbox
    $wr_subject = mb_substr($content, 0, 50);
    $wr_seo_title = $content;
    $wr_content = $content . "\r\n\r\n[외부에서 전송된 글입니다.]";
    $wr_link1 = $data['actor'];
    $wr_link2 = '';
    $wr_homepage = $data['actor'];
    $wr_6 = $data['type'];    // Type of Activity

    // 수신자 확인
    $receivers = array();
    foreach($to as $_to) {
        // 수신자 주소(URL) 처리
        $url_ctx = activitypub_parse_url($_to);
        $host = $url_ctx['host'];
        $query = $url_ctx['query'];

        // 특정 회원이 지목되어 있다면 수신자 추가
        if ($host == ACTIVITYPUB_HOST && !empty($query['mb_id'])) {
            array_push($receivers, $query['mb_id']);
        }
    }
    $wr_7 = implode($receivers);
    
    // 외부에 발행되었는지 여부 ('published')
    $wr_8 = '';

    $sql = "
        insert into $write_table
            set wr_num = '$wr_num',
                wr_reply = '$wr_reply',
                wr_comment = 0,
                ca_name = '$ca_name',
                wr_option = '$wr_option',
                wr_subject = '$wr_subject',
                wr_content = '$wr_content',
                wr_seo_title = '$wr_seo_title',
                wr_link1 = '$wr_link1',
                wr_link2 = '$wr_link2',
                wr_link1_hit = 0,
                wr_link2_hit = 0,
                wr_hit = 0,
                wr_good = 0,
                wr_nogood = 0,
                mb_id = '{$mb['mb_id']}',
                wr_password = '',
                wr_name = '{$mb['mb_name']}',
                wr_email = '',
                wr_homepage = '$wr_homepage',
                wr_datetime = '" . G5_TIME_YMDHIS . "',
                wr_last = '" . G5_TIME_YMDHIS . "',
                wr_ip = '{$_SERVER['REMOTE_ADDR']}',
                wr_1 = '',
                wr_2 = '',
                wr_3 = '',
                wr_4 = '',
                wr_5 = '',
                wr_6 = '$wr_6',
                wr_7 = '$wr_7',
                wr_8 = '$wr_8',
                wr_9 = '',
                wr_10 = ''
    ";
    sql_query($sql);
    $wr_id = sql_insert_id();

    // 요청 전문은 파일로 저장
    $raw_context = activitypub_json_encode($data);
    $time_ms = floor(microtime(true) * 1000);
    $filename = md5($raw_context . $time_ms) . ".json";
    $filepath = G5_DATA_PATH . "/file/" . ACTIVITYPUB_G5_BOARDNAME . "/" . $filename;
    $result = file_put_contents($filepath, $raw_context);
    if ($result !== false) {
        $bf_source = $filename;
        $bf_file = $filename;
        $bf_content = "application/activity+json";
        $bf_filesize = strlen($raw_context);
        $sql = " insert into {$g5['board_file_table']}
                    set bo_table = '" . ACTIVITYPUB_G5_BOARDNAME . "',
                         wr_id = '{$wr_id}',
                         bf_no = 0,
                         bf_source = '{$bf_source}',
                         bf_file = '{$bf_file}',
                         bf_content = '{$bf_content}',
                         bf_fileurl = '',
                         bf_thumburl = '',
                         bf_storage = '',
                         bf_download = 0,
                         bf_filesize = '{$bf_filesize}',
                         bf_width = 0,
                         bf_height = 0,
                         bf_type = 0,
                         bf_datetime = '" . G5_TIME_YMDHIS . "' ";
        sql_query($sql);

        $sql = "update $write_table set wr_file = 1 where wr_id = '{$wr_id}'";
        sql_query($sql);
    }

    return $wr_id;
}

function activitypub_get_activities($mb_id = '', $inbox = "inbox") {
    $activities = array();

    // 정보 불러오기
    $sql = "";
    if(empty($mb_id)) {
        $sql = "select wr_content from " . ACTIVITYPUB_G5_TABLENAME . "
            where ca_name = '$inbox'
                and wr_datetime BETWEEN CURDATE() - INTERVAL " . ACTIVITYPUB_G5_NEW_DAYS . " DAY AND CURDATE()
        ";
    } else {
        $sql = "select wr_content from " . ACTIVITYPUB_G5_TABLENAME . "
            where ca_name = '$inbox'
                and FIND_IN_SET('$mb_id', wr_7) > 0
                and wr_datetime BETWEEN CURDATE() - INTERVAL " . ACTIVITYPUB_G5_NEW_DAYS . " DAY AND CURDATE()
        ";
        $result = sql_query($sql);
    }
    $result = sql_query($sql);

    // 정보 조회 후 처리
    while ($row = sql_fetch_array($result)) {
        $sql2 = "select * from {$g4['board_file_table']}
            where bo_table = '" . ACTIVITYPUB_G5_TABLENAME . "' and wr_id = '{$row['wr_id']}' and bf_content = 'application/activity+json'";
        $result2 = sql_query($sql2);
        while ($row2 = sql_fetch_array($result)) {
            $filename = $row2['bf_file'];
            $filepath = G5_DATA_PATH . "/file/" . ACTIVITYPUB_G5_BOARDNAME . "/" . $filename;
            if(file_exists($filepath)) {
                array_push($activities, activitypub_json_decode(file_get_contents($filepath), true));
            }
        }
    }

    return $activities;
}

class _GNUBOARD_ActivityPub {
    public static function open() {
        header("Content-Type: application/ld+json; profile=\"" . NAMESPACE_ACTIVITYSTREAMS . "\"");
    }

    public static function webfinger() {
        $params = array(
            "resource" => $_GET['resource']
        );

        if (empty($params['resource'])) {
            return activitypub_json_encode(array("message" => "Resource could not be empty"));
        }
        
        $resource = $params['resource'];
        $resource_type = '';
        $resource_value = '';
        $pos = strpos($resource, ':');
        if ($pos !== false) {
            $resource_type = substr($resource, 0, $pos);
            $resource_value = substr($resource, $pos + 1);
        }
        
        switch($resource_type) {
            case "acct":
                // 값 분리
                list($username, $host) = explode('@', $resource_value);

                // 호스트가 일치하지 않는 경우
                if ($host != ACTIVITYPUB_HOST) {
                    return activitypub_json_encode(array("message" => "Invalid host"));
                }
                

                // 회원 정보 확인
                $mb = get_member($username);
                if (empty($mb['mb_id'])) {
                    return activitypub_json_encode(array("message" => "Not registered user"));
                }

                // 응답 본문 생성
                $context = array(
                    "subject" => $params['resource'],
                    "aliases" => array(
                        activitypub_get_url("user", array("mb_id" => $mb['mb_id']))
                    ),
                    "links" => array(
                        array(
                            "rel" => "http://webfinger.net/rel/profile-page",
                            "type" => "text/html",
                            "href" => G5_BBS_URL . "/profile.php?mb_id=" . $mb['mb_id']
                        ),
                        array(
                            "rel" => "self",
                            "type" => "application/activity+json",
                            "href" => activitypub_get_url("user", array("mb_id" => $mb['mb_id']))
                        ),
                        /*
                        array(
                            "rel" => "http://ostatus.org/schema/1.0/subscribe",
                            "href" => activitypub_get_url("ostatus", array("mb_id" => $mb['mb_id'], "uri" => "{uri}"))
                        )
                        */
                    )
                );

                // 응답 본문 출력
                return activitypub_json_encode($context);

                break;

            case "http":
                return activitypub_json_encode(array("message" => "Not implemented"));
                break;
                
            default:
                return activitypub_json_encode(array("message" => "Not supported resource type"));
                break;
        }
    }

    public static function user() {
        $mb = get_member($_GET['mb_id']);

        if (!$mb['mb_id']) {
            return activitypub_json_encode(array("message" => "Could not find the user"));
        }

        $context = array(
            "@context" => array(NAMESPACE_ACTIVITYSTREAMS, array("@language" => "ko")),
            "type" => "Person",
            "id" => activitypub_get_url("user", array("mb_id" => $mb['mb_id'])),
            "name" => $mb['mb_name'],
            "preferredUsername" => $mb['mb_nick'],
            "summary" => $mb['mb_profile'],
            "inbox" => activitypub_get_url("inbox", array("mb_id" => $mb['mb_id'])),
            "outbox" => activitypub_get_url("outbox", array("mb_id" => $mb['mb_id'])),
            "followers" => activitypub_get_url("followers", array("mb_id" => $mb['mb_id'])),
            "following" => activitypub_get_url("following", array("mb_id" => $mb['mb_id'])),
            "liked" => activitypub_get_url("liked", array("mb_id" => $mb['mb_id'])),
            "icon" => array(
                activitypub_get_icon($mb)
            ),
            "endpoints" => array(
                "sharedInbox" => activitypub_get_url("inbox")
            )
        );

        return activitypub_json_encode($context);
    }

    public static function inbox() {
        // HTTP 요청 유형에 따라 작업
        switch ($_SERVER['REQUEST_METHOD']) {
            case "POST":
                // 개인에게 보낸 메시지는 쪽지에 저장
                // 공개(Public) 설정한 메시지는 ACTIVITYPUB_G5_TABLENAME에 저장
                $data = activitypub_json_decode(file_get_contents("php://input"), true);

                if (empty($data['@context'])) {
                    return activitypub_json_encode(array("message" => "This is a broken context"));
                }
                
                if ($data['@context'] != NAMESPACE_ACTIVITYSTREAMS) {
                    return activitypub_json_encode(array("message" => "This is not an ActivityStreams request"));
                }
                
                // 컨텐츠 변수 정의
                $content = '';
                
                // 컨텐츠 처리
                if (!empty($data['type'])) {
                    // 정보 불러오기 
                    $mb = get_member(ACTIVITYPUB_G5_USERNAME);

                    // 수신자 확인
                    $to = $data['to'];

                    // 원글 정보 확인
                    $object = $data['object'];

                    // 타입 별 해야될 일 지정
                    switch ($data['type']) {
                        case "Create":
                            // 스트링 및 오브젝트 타입을 모두 호환하도록 설정
                            if (is_string($object))
                                $object = array("id" => $object);

                            // 컨텐츠가 비어있는 경우
                            if (empty($object['content']))
                                $object['content'] = "[NO CONTENT]";


                            // 수신된 내용 등록
                            $activity_wr_id = activitypub_add_activity("inbox", $data, $mb);
                            
                            // 컨텐츠 설정
                            $bo = get_board_db(ACTIVITYPUB_G5_BOARDNAME, true);
                            $content = sprintf("%s\r\n\r\n[외부에서 전송된 글입니다. 자세한 내용은 %s#%s 글을 확인하세요.]", $object['content'], $bo['bo_subject'], $activity_wr_id);

                            // 답글인지 확인
                            if (!empty($object['inReplyTo'])) {
                                // 답글 정보 확인
                                $query = activitypub_parse_url($object['inReplyTo'])['query'];

                                // 특정 글이 지목되어 있을 때 -> 댓글로 작성
                                if (!empty($query['bo_table']) && !empty($query['wr_id'])) {
                                    $wr_id = $query['wr_id'];
                                    $write_table = G5_TABLE_PREFIX . $query['bo_table'];
                                    $wr = get_write($write_table, $wr_id);

                                    // 글이 존재하는 경우
                                    if (!empty($wr['wr_id'])) {
                                        $mb = get_member(ACTIVITYPUB_G5_USERNAME);
                                        $wr_homepage = $data['actor'];

                                        $sql = "
                                            insert into $write_table
                                                set ca_name = '{$wr['ca_name']}',
                                                     wr_option = '',
                                                     wr_num = '{$wr['wr_num']}',
                                                     wr_reply = '',
                                                     wr_parent = '{$wr['wr_id']}',
                                                     wr_is_comment = 1,
                                                     wr_comment = '',
                                                     wr_comment_reply = '',
                                                     wr_subject = '',
                                                     wr_content = '$content',
                                                     mb_id = '{$mb['mb_id']}',
                                                     wr_password = '',
                                                     wr_name = '{$mb['mb_name']}',
                                                     wr_email = '',
                                                     wr_homepage = '$wr_homepage',
                                                     wr_datetime = '" . G5_TIME_YMDHIS . "',
                                                     wr_last = '',
                                                     wr_ip = '{$_SERVER['REMOTE_ADDR']}',
                                                     wr_1 = '',
                                                     wr_2 = '',
                                                     wr_3 = '',
                                                     wr_4 = '',
                                                     wr_5 = '',
                                                     wr_6 = '',
                                                     wr_7 = '',
                                                     wr_8 = '',
                                                     wr_9 = '',
                                                     wr_10 = ''
                                        ";
                                        sql_query($sql);
                                    }

                                    // 원글이 삭제된 경우
                                    else {
                                        return activitypub_json_encode(array("message" => "Could not find the original message"));
                                    }
                                }
                            }
                            break;

                        case "Like":
                            // 스트링 및 오브젝트 타입을 모두 호환하도록 설정
                            if (is_string($object))
                                $object = array("id" => $object);

                            // object 처리
                            $url_ctx = activitypub_parse_url($object['id']);
                            $host = $url_ctx['host'];
                            $query = $url_ctx['query'];

                            // 원글을 특정한 경우
                            if ($host == ACTIVITYPUB_HOST && !empty($query['bo_table']) && !empty($query['wr_id'])) {
                                $wr_id = $query['wr_id'];
                                $write_table = G5_TABLE_PREFIX . $query['bo_table'];
                                $wr = get_write($write_table, $wr_id);
                                $bo = get_board_db(ACTIVITYPUB_G5_BOARDNAME, true);

                                // 원글이 존재하는 경우
                                if (!empty($wr['wr_id'])) {
                                    activitypub_set_liked("good", $query['bo_table'], $wr['wr_id']);
                                }

                                // 원글이 삭제된 경우
                                else {
                                    return activitypub_json_encode(array("message" => "Could not find the original message"));
                                }
                            }
                            
                            // 특정하지 않은 경우
                            else {
                                return activitypub_json_encode(array("message" => "Could not specify the original message"));
                            }

                            // 보낼 내용 설정
                            $content = sprintf(
                                "아래 사용자가 %s #%s 글을 추천하였습니다.\r\n\r\n%s",
                                $bo['bo_subject'],
                                $wr['wr_id'],
                                $data['actor']
                            );

                            break;

                        case "Dislike":
                            // 스트링 및 오브젝트 타입을 모두 호환하도록 설정
                            if (is_string($object))
                                $object = array("id" => $object);

                            // object 처리
                            $url_ctx = activitypub_parse_url($object['id']);
                            $host = $url_ctx['host'];
                            $query = $url_ctx['query'];

                            // 원글을 특정한 경우
                            if ($host == ACTIVITYPUB_HOST && !empty($query['bo_table']) && !empty($query['wr_id'])) {
                                $wr_id = $query['wr_id'];
                                $write_table = G5_TABLE_PREFIX . $query['bo_table'];
                                $wr = get_write($write_table, $wr_id);
                                $bo = get_board_db(ACTIVITYPUB_G5_BOARDNAME, true);

                                // 원글이 존재하는 경우
                                if (!empty($wr['wr_id'])) {
                                    activitypub_set_liked("nogood", $query['bo_table'], $wr['wr_id']);
                                }

                                // 원글이 삭제된 경우
                                else {
                                    return activitypub_json_encode(array("message" => "Could not find the original message"));
                                }
                            }

                            // 특정하지 않은 경우
                            else {
                                return activitypub_json_encode(array("message" => "Could not specify the original message"));
                            }
                            
                            // 보낼 내용 설정
                            $content = sprintf(
                                "아래 사용자가 %s #%s 글을 비추천하였습니다.\r\n\r\n%s",
                                $bo['bo_subject'],
                                $wr['wr_id'],
                                $data['actor']
                            );

                            break;

                        default:
                            return activitypub_json_encode(array("message" => "This is not implemented type"));
                    }

                    // 받을사람(수신자) 처리
                    foreach($to as $_to) {
                        // 수신자 주소(URL) 처리
                        $url_ctx = activitypub_parse_url($_to);
                        $host = $url_ctx['host'];
                        $query = $url_ctx['query'];

                        // 특정 회원이 지목되어 있다면 쪽지를 보냄
                        if ($host == ACTIVITYPUB_HOST && !empty($query['mb_id'])) {
                            switch ($query['route']) {
                                case "activitypub.user":
                                    activitypub_add_memo($mb['mb_id'], $query['mb_id'], $content);
                                    break;

                                case "activitypub.followers":
                                    $followers = activitypub_get_followers($mb);
                                    foreach($followers as $_mb_id) {
                                        activitypub_add_memo($mb['mb_id'], $_mb_id, $content);
                                    }
                                    break;

                                case "activitypub.following":
                                    $following = activitypub_get_following($mb);
                                    foreach($following as $_mb_id) {
                                        activitypub_add_memo($mb['mb_id'], $_mb_id, $content);
                                    }
                                    break;
                            }
                        }
                    }
                }

                else {
                    return activitypub_json_encode(array("message" => "Type could not be an empty"));
                }

                return activitypub_json_encode(array("message" => "Success"));

            case "GET":
                return activitypub_json_encode(activitypub_get_activities("inbox", $_GET['mb_id']));

            default:
                return activitypub_json_encode(array("message" => "Not supported method"));
        }
    }

    public static function outbox() {
        // HTTP 요청 유형에 따라 작업
        switch ($_SERVER['REQUEST_METHOD']) {
            // 규격 문서에서 POST/Outbox에 해당하는 작업은 그누5에선 훅(Hook)으로 작업
            case "POST":
                return activitypub_json_encode(array(
                    "message" => "Disallowed method. Please go to " . G5_BBS_URL . "/board.php?bo_table=" . ACTIVITYPUB_G5_BOARDNAME
                ));

            // 가장 최근의 활동을 가져옴
            case "GET":
                return activitypub_json_encode(activitypub_get_activities("outbox", $_GET['mb_id']));
        }
    }

    public static function followers() {
        $mb = get_member($_GET['mb_id']);
        return activitypub_json_encode(array("followers" => activitypub_get_followers($mb)));
    }
    
    public static function following() {
        $mb = get_member($_GET['mb_id']);
        return activitypub_json_encode(array("following" => activitypub_get_following($mb)));
    }

    public static function liked() {
        return self::inbox();
    }

    public static function close() {
        exit();
    }
}

// 훅(Hook) 등록
function _activitypub_update_after($board, $wr_id) {
    global $g5;

    // 본문 가져오기
    $sql = "select wr_id, wr_content from {$g5['write_prefix']}{$board['bo_table']} where wr_id = '{$wr_id}'";
    $row = sql_fetch($sql);

    if (!empty($row['wr_id'])) {
        $data = activitypub_publish_content(
            $row['wr_content'],
            G5_BBS_URL . "/bbs/board.php?bo_table={$board['bo_table']}&wr_id={$row['wr_id']}"
        );
        $mb = get_member(ACTIVITYPUB_G5_USERNAME);
        activitypub_add_activity("outbox", $data, $mb);
    }
}

function _activitypub_write_update_after($board, $wr_id, $w, $qstr, $redirect_url) {
    _activitypub_update_after($board, $wr_id);
}

function _activitypub_comment_update_after($board, $wr_id, $w, $qstr, $redirect_url, $comment_id, $reply_array) {
    _activitypub_update_after($board, $wr_id);
}

add_event("write_update_after", "_activitypub_write_update_after", 0, 5);
add_event("comment_update_after", "_activitypub_comment_update_after", 0, 7);

// 모든 준비가 완료되고 작업 시작
$route = $_GET['route'];

switch ($route) {
    // 액펍(ActivityPub)과 웹핑거(WebFinger)는 다른 개념이지만, 여기서는 액펍(ActivityPub) 전용으로 사용한다.
    // 액펍(ActivityPub)에서 사용자를 조회하기 전단계에서 이뤄지는 요청이다.
    //
    // .htaccess에 추가 (추가해야만 그누보드 외 다른 플랫폼과 통신 가능, 그누보드 사이에서만 연결할 경우 필수사항 아님)
    //
    //     <IfModule mod_rewrite.c>
    //         RewriteEngine on
    //         RewriteRule ^\.well-known/webfinger /?route=webfinger [QSA,L]
    //     </IfModule>
    //
    // Reference: https://wordpress.org/support/topic/htaccess-conflict/
    //
    case "webfinger":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::webfinger();
        _GNUBOARD_ActivityPub::close();
        break;

    case "activitypub.user":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::user();
        _GNUBOARD_ActivityPub::close();
        break;

    case "activitypub.inbox":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::inbox();
        _GNUBOARD_ActivityPub::close();
        break;

    case "activitypub.outbox":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::outbox();
        _GNUBOARD_ActivityPub::close();
        break;

    case "activitypub.followers":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::followers();
        _GNUBOARD_ActivityPub::close();
        break;

    case "activitypub.following":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::following();
        _GNUBOARD_ActivityPub::close();
        break;
        
    case "activitypub.liked":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::liked();
        _GNUBOARD_ActivityPub::close();
        break;
}
