<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// ActivityPub implementation for GNUBOARD 5
// Go Namhyeon <gnh1201@gmail.com>

// References:
//   * https://www.w3.org/TR/activitypub/

define("ACTIVITYPUB_INSTANCE_ID", md5_file(G5_DATA_PATH . "/dbconfig.php"));
define("ACTIVITYPUB_URL", (empty(G5_URL) ? "http://" . ACTIVITYPUB_INSTANCE_ID . ".local" : G5_URL));
define("ACTIVITYPUB_DATA_URL", ACTIVITYPUB_URL . '/' . G5_DATA_DIR);
define("ACTIVITYPUB_G5_TABLENAME", G5_TABLE_PREFIX . "apstreams");
define("ACTIVITYPUB_G5_USERNAME", "apstreams");
define("NAMESPACE_ACTIVITYSTREAMS", "https://www.w3.org/ns/activitystreams");
define("NAMESPACE_ACTIVITYSTREAMS_PUBLIC", "https://www.w3.org/ns/activitystreams#Public");

function activitypub_get_url($action, $params = array()) {
    if (count(array_keys($params)) > 0) {
        return ACTIVITYPUB_URL . "/?route=activitypub." . $action . "&" . http_build_query($params);
    } else {
        return ACTIVITYPUB_URL . "/?route=activitypub." . $action;
    }           
}

function activitypub_json_encode($arr) {
    return json_encode( $arr );
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

function activitypub_get_recent_items() {
    global $g5;

    $items = array();
    $sql = "select * from " . $g5['board_new_table']; 
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $sql2 = "select * from " . ($g5['write_prefix'] . $row['bo_table']) . " where wr_id = '" . $row['wr_id'] . "'";
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
        $recent_items = activitypub_get_recent_items();
        foreach($recent_items as $item) {
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
        $recent_items = activitypub_get_recent_items();
        
        foreach($recent_items as $item) {
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

function activitypub_send_to_inbox($object) {
    $server_list_file = G5_DATA_PATH . "/activitypub-servers.php";

    if (!file_exists($server_list_file))
        return false;

    // 수신자 확인
    $to = $object['to'];
    
    // 외부로 보낼 전문 생성
    $context = array(
        "@context" => NAMESPACE_ACTIVITYSTREAMS,
        "type" => "Create",
        "id" => "",
        "to" => $to,
        "actor" => $object['attributedTo'],
        "object" => $object
    );
    $rawdata = activitypub_json_encode($context);

    // 서버 정보 불러오기
    $servers = json_decode(include($server_list_file), true);
    
    // 수신자 작업
    foreach($to as $_to) {
        // 수신자 정보 파싱
        $url_ctx = activitypub_parse_url($_to);

        // 수신자 서버에 연결
        foreach($servers as $remote_base_url=>$attr) {
            if (!$attr['enabled']) continue;   // 비활성화 상태면 작업하지 않음

            // 일치하는 서버 찾기
            $pos = strpos($remote_base_url, sprintf("%s://%s", $url_ctx['scheme'], $url_ctx['host']));
            if ($pos === 0) {
                // 사용자 정보 조회
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $_to,
                    CURLOPT_HTTPHEADER => array(
                        "Accept" => "application/ld+json; profile=\"" . NAMESPACE_ACTIVITYSTREAMS . "\"",
                        "Authorization" => "Bearer " . $attr['accesstoken'] 
                    ),
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_RETURNTRANSFER => true
                ));
                $response = curl_exec($ch);
                $remote_user_ctx = json_decode($response, true);
                
                // inbox 주소 찾기
                $remote_inbox_url = $remote_user_ctx['inbox']
                if (empty($remote_inbox_url))
                    $remote_inbox_url = $remote_user_ctx['endpoints']['sharedInbox'];

                // inbox 주소가 없으면 건너뛰기
                if (empty($remote_inbox_url)) continue;

                // inbox에 연결
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $remote_inbox_url,
                    CURLOPT_HTTPHEADER => array(
                        "Accept" => "application/ld+json; profile=\"" . NAMESPACE_ACTIVITYSTREAMS . "\"",
                        "Authorization" => "Bearer " . $attr['accesstoken'] 
                    ),
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POST => true,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => $rawdata
                ));
                $response = curl_exec($ch);
                $remote_inbox_ctx = json_decode($response, true);

                // TODO
                
                break;
            }
        }
    }
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

        if (substr($expr, 0, 1) == '@') {
            array_push($entities, array("type" => "id", "value" => $expr));
        } else if (substr($expr, 0, 1) == '#') {
            array_push($entities, array("type" => "hashtag", "value" => $expr));
        } else if (substr($expr, 0, 4) == 'http') {
            array_push($entities, array("type" => "url", "value" => $expr));
        }

        $pos = $get_next_position($pos);
    }
    
    return $entities;
}

class _GNUBOARD_ActivityPub {
    public static function open() {
        header("Content-Type: application/ld+json; profile=\"" . NAMESPACE_ACTIVITYSTREAMS . "\"");
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
            )
            "endpoints" => array(
                "sharedInbox" => activitypub_get_url("inbox")
            )
        );

        return activitypub_json_encode($context);
    }

    public static function streams() {
        $params = array(
            "bo_table" => $_GET['bo_table'],
            "wr_id" => $_GET['wr_id']
        );

        if (!empty($params['bo_table']) && !empty($params['wr_id'])) {
            $qstr = http_build_query(array(
                "bo_table" => $params['bo_table'],
                "wr_id" => $params['wr_id']
            ));
            header("Location: " . G5_BBS_URL . "/board.php?" . $qstr);
        } else {
            return activitypub_json_encode(array("message" => "Could not find the stream"));
        }
    }

    public static function inbox() {
        global $g5;

        // 개인에게 보낸 메시지는 쪽지에 저장
        // 공개(Public) 설정한 메시지는 ACTIVITYPUB_G5_TABLENAME에 저장
        // 게시물이 특정된 경우 댓글로 저장 (그누 전용)

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['@context'])) {
            return activitypub_json_encode(array("message" => "This is a broken context"));
        }
        
        if ($data['@context'] != NAMESPACE_ACTIVITYSTREAMS) {
            return activitypub_json_encode(array("message" => "This is not an ActivityStreams request"));
        }
        
        if (!empty($data['type'])) {
            // 멤버정보 처리
            $mb_id = activitypub_parse_url($data['actor'])['query']['mb_id'];
            if (empty($mb_id)) {
                $mb_id = ACTIVITYPUB_G5_USERNAME;   // 그누보드에 호환되는 액터가 아니므로 기본 액터(mb_id=apstreams) 사용
            }
            $mb = get_member($mb_id);
            
            // 수신자 확인
            $to = $data['to'];

            // 원글 정보 확인
            $object = $data['object'];
        
            // 타입 별 해야될 일 지정
            switch ($data['type']) {
                case "Create":
                    // 내용 처리
                    if (empty($object['content'])) {
                        return activitypub_json_encode(array("message" => "Content is empty"));
                    }
                    $content = $object['content'];

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
                    // '좋아요'는 원글 표시가 문자열로 되어 있음
                    if (is_string($object)) {
                        $query = activitypub_parse_url($object);

                        // 원글을 특정한 경우
                        if (!empty($query['bo_table']) && !empty($query['wr_id'])) {
                            $wr_id = $query['wr_id'];
                            $write_table = G5_TABLE_PREFIX . $query['bo_table'];
                            $wr = get_write($write_table, $wr_id);

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
                            return activitypub_json_encode(array("message" => "Please specify the original message"));
                        }
                    }
                    
                    // 문자열이 아닌 경우
                    else {
                        return activitypub_json_encode(array("message" => "'Object' must be set type to 'String' to request 'Like' type"));
                    }
                    break;
                    
                case "Dislike":
                    // '싫어요'는 원글 표시가 문자열로 되어 있음
                    if (is_string($object)) {
                        $query = activitypub_parse_url($object);

                        // 원글을 특정한 경우
                        if (!empty($query['bo_table']) && !empty($query['wr_id'])) {
                            $wr_id = $query['wr_id'];
                            $write_table = G5_TABLE_PREFIX . $query['bo_table'];
                            $wr = get_write($write_table, $wr_id);

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
                            return activitypub_json_encode(array("message" => "Please specify the original message"));
                        }
                    }
                    
                    // 문자열이 아닌 경우
                    else {
                        return activitypub_json_encode(array("message" => "'Object' must be set type to 'String' to request 'Like' type"));
                    }
                    break;

                default:
                    return activitypub_json_encode(array("message" => "This is not implemented type"));
            }

            // 받을사람(수신자) 처리
            foreach($to as $_to) {
                $query = activitypub_parse_url($_to)['query'];

                // 공개 게시물일 때
                if ($_to == NAMESPACE_ACTIVITYSTREAMS_PUBLIC) {
                    $mb = get_member(ACTIVITYPUB_G5_USERNAME);

                    $write_table = ACTIVITYPUB_G5_TABLENAME;
                    $wr_num = get_next_num($write_table);
                    $wr_reply = '';
                    $ca_name = 'ActivityStreams';
                    $wr_subject = mb_substr($content, 0, 45);
                    $wr_seo_title = mb_substr($content, 0, 45);
                    $wr_content = $content;
                    $wr_link1 = $data['actor'];
                    $wr_link2 = '';
                    $wr_homepage = $data['actor'];

                    $sql = "
                        insert into $write_table
                            set wr_num = '$wr_num',
                                wr_reply = '$wr_reply',
                                wr_comment = 0,
                                ca_name = '$ca_name',
                                wr_option = '',
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
                                wr_6 = '',
                                wr_7 = '',
                                wr_8 = '',
                                wr_9 = '',
                                wr_10 = ''
                    ";
                    sql_query($sql);

                    return activitypub_json_encode(array("message" => "Success"));
                }

                // 특정 회원이 지목되어 있을 때 -> 메모로 작성
                else if (!empty($query['mb_id'])) {
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
    }

    public static function outbox() {
        // TODO
    }

    public static function followers() {
        $params = array(
            "mb_id" => $_GET['mb_id']
        );

        $mb = get_member($params['mb_id']);
        return activitypub_json_encode(array("followers" => activitypub_get_followers($mb)));
    }
    
    public static function following() {
        $params = array(
            "mb_id" => $_GET['mb_id']
        );

        $mb = get_member($params['mb_id']);
        return activitypub_json_encode(array("following" => activitypub_get_following($mb)));
    }

    public static function liked() {
        return self::inbox();
    }

    public static function close() {
        exit();
    }
}

$route = $_GET['route'];

switch ($route) {
    case "activitypub.user":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::user();
        _GNUBOARD_ActivityPub::close();
        break;

    case "activitypub.streams":
        _GNUBOARD_ActivityPub::open();
        echo _GNUBOARD_ActivityPub::streams();
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
