<?php
$sub_menu = "990200";
require_once './_common.php';

auth_check_menu($auth, $sub_menu, 'r');

$sql_common = " from " . ACTIVITYPUB_G5_TABLENAME . " ";

$sql_search = " where ca_name = 'inbox' ";

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case "mb_id":
            $sql_search .= " ({$sfl} = '{$stx}') ";
            break;
        default:
            $sql_search .= " ({$sfl} like '%{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}

if ($sst) {
    $sql_order = " order by {$sst} {$sod} ";
} else {
    $sql_order = " order by wr_datetime desc ";
}

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) {
    $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
}
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="' . $_SERVER['SCRIPT_NAME'] . '" class="ov_listall">처음</a>';

$g5['title'] = '액티비티 수신함';
require_once './admin.head.php';

$colspan = 3;
?>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="wr_7" <?php echo get_selected($sfl, "wr_7"); ?>>수신자</option>
        <option value="wr_content" <?php echo get_selected($sfl, "wr_content"); ?>>내용</option>
    </select>
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" id="stx" value="<?php echo $stx ?>" required class="required frm_input">
    <input type="submit" value="검색" class="btn_submit">
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
            <tr>
                <th scope="col">수신시간</th>
                <th scope="col">수신자</th>
                <th scope="col">내용</th>
            </tr>
        </thead>
        <tbody>
<?php
        for ($i = 0; $row = sql_fetch_array($result); $i++) {
            
            $activities = array();
            $sql2 = "select * from {$g5['board_file_table']}
                where bo_table = '" . ACTIVITYPUB_G5_BOARDNAME . "' and wr_id = '{$row['wr_id']}' and bf_content = 'application/activity+json'";
            $result2 = sql_query($sql2);
            while ($row2 = sql_fetch_array($result2)) {
                $filename = $row2['bf_file'];
                $filepath = G5_DATA_PATH . "/file/" . ACTIVITYPUB_G5_BOARDNAME . "/" . $filename;
                if(file_exists($filepath)) {
                    array_push($activities, activitypub_json_decode(file_get_contents($filepath), true));
                }
            }
            
?>
        <tr>
            <td><?php echo $row['wr_datetime']; ?></td>
            <td><?php echo implode(', ', explode(',', $row['wr_7'])); ?></td>
            <td><?php echo $row['wr_content']; ?></td>
        </tr>

        <tr>
            <td colspan="<?php echo $colspan; ?>">
<?php
                if (count($activities) > 0) {
                    foreach($activities as $activity) {
?>
                <textarea rows="10" readonly="readonly" style="border: 0; margin:0; padding: 0; background: none;"><?php echo json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></textarea>
<?php
                    }
                } else {
?>
                <textarea rows="10" readonly="readonly" style="border: 0; margin:0; padding: 0; background: none;">첨부된 전문이 없습니다.</textarea>
<?php
                }
?>
            </td>
        </tr>
<?php
        }
?>
        </tbody>
    </table>
</div>

<?php
$pagelist = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'] . '?' . $qstr . '&amp;page=');
echo $pagelist;
?>

<?php
require_once './admin.tail.php';
