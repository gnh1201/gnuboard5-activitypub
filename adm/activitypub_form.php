<?php
$sub_menu = "990100";
require_once './_common.php';

auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '액티비티 생성';
require_once './admin.head.php';

// add_javascript('js 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_javascript(G5_POSTCODE_JS, 0);    //다음 주소 js
?>

<form name="factivity" id="factivity" action="./activitypub_form_update.php" method="post" enctype="multipart/form-data">
    <div>
		<input type="hidden" name="token" value="">
	</div>
	
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?></caption>
            <colgroup>
                <col class="grid_4">
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row"><label for="object_id">원글 URL</label></th>
                    <td>
                        <input type="text" id="object_id" name="object_id" value="" class="frm_input" size="45">
                    </td>
                </tr>
				<tr>
                    <th scope="row"><label for="to">전송할 위치</label></th>
                    <td>
                        <input type="text" id="to" name="to" value="" class="frm_input" size="45"> (쉼표로 구분)
                    </td>
                </tr>
				<tr>
                    <th scope="row"><label for="content">내용</label></th>
                    <td>
						<textarea id="content" name="content" rows="20"></textarea>
                    </td>
				</tr>
			</tbody>
		</table>
	</div>
	
    <div class="btn_fixed_top">
        <input type="submit" name="act_button" value="전송" onclick="document.pressed=this.value" class="btn btn_01">
    </div>
</form>

<?php
require_once './admin.tail.php';
