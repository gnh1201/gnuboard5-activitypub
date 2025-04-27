<?php
$sub_menu = "990400";
require_once './_common.php';

auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '액티비티 오라클 설정';
require_once './admin.head.php';

// add_javascript('js 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_javascript(G5_POSTCODE_JS, 0);    //다음 주소 js

// 설정 불러오기
$apconfig = array();
$filename = G5_DATA_PATH . '/' . 'apconfig.php';
if (file_exists($filename)) {
	$rawdata = @include($filename);
	$apconfig = json_decode($rawdata, true);
}
?>

<form name="formActivityOracle" id="formActivityOracle" action="./activitypub_oracle_form_update.php" method="post" enctype="multipart/form-data">

    <div class="local_desc01 local_desc"><strong>액티비티 오라클</strong>은 액티비티 생성 시 어떤 컨텐츠가 어떤 외부 정보를 참조해야 하는지 설정할 수 있습니다. 현재 뉴스, 장소, 날씨, 환율, 증권을 지원합니다.</div>

    <h2 class="h2_frm">외부 정보 참조 키워드 설정</h2>

    <div class="tbl_head01 tbl_wrap">
        <table id="keywordTable">
            <caption>키워드 설정 목록</caption>
            <thead>
                <tr>
                    <th scope="col" width="30"><input type="checkbox" id="chkall" title="전체 선택" onclick="keywordCheckAll(this)"></th>
                    <th scope="col" width="240">검색어</th>
                    <th scope="col">유형</th>
                    <th scope="col">연관 키워드 목록</th>
                    <th scope="col" width="80">삭제</th>
                </tr>
            </thead>
            <template id="keywordRowTemplate" style="display: none;">
                <tr>
                    <td><input type="checkbox" class="row-check"></td>
                    <td><input type="text" name="keyword[]" class="frm_input" placeholder="예: Apple"></td>
                    <td>
                        <select name="type[]">
                            <option value="search" selected>검색</option>
                            <option value="news">뉴스</option>
                            <option value="weather">날씨</option>
                            <option value="exchange">환율</option>
                            <option value="stock">증권</option>
                        </select>
                    </td>
                    <td><input type="text" name="relatedKeywords[]" class="frm_input" placeholder="예: 애플, 아이폰, 아이패드, 스티브 잡스, 팀 쿡, APPL"></td>
                    <td><button type="button" class="btn btn_01" onclick="removeRow(this)">삭제</button></td>
                </tr>
            </template>
            <tbody id="keywordTableBody">
<?php
				$queries = $apconfig['queries'];
				if (count($queries) == 0) {
					echo '<tr><td colspan="5" class="empty_table">자료가 없습니다.</td></tr>';
				}

				foreach($apconfig['queries'] as $query) {
?>
                <tr>
                    <td><input type="checkbox" class="row-check"></td>
                    <td><input type="text" name="keyword[]" class="frm_input" placeholder="예: Apple" value="<?php echo $query['keyword']; ?>"></td>
                    <td>
                        <select name="type[]">
                            <option value="search"<?php echo ($query['type'] == 'search' ? '' : ' selected'); ?>>검색</option>
                            <option value="news"<?php echo ($query['type'] == 'news' ? '' : ' selected'); ?>>뉴스</option>
                            <option value="weather"<?php echo ($query['type'] == 'weather' ? '' : ' selected'); ?>>날씨</option>
                            <option value="exchange"<?php echo ($query['type'] == 'exchange' ? '' : ' selected'); ?>>환율</option>
                            <option value="stock"<?php echo ($query['type'] == 'stock' ? '' : ' selected'); ?>>증권</option>
                        </select>
                    </td>
                    <td><input type="text" name="relatedKeywords[]" class="frm_input" placeholder="예: 애플, 아이폰, 아이패드, 스티브 잡스, 팀 쿡, APPL" value="<?php echo implode(', ', $query['relatedKeywords']); ?>"></td>
                    <td><button type="button" class="btn btn_01" onclick="removeRow(this)">삭제</button></td>
                </tr>
<?php
				}  // endif
?>
            </tbody>
        </table>
    </div>
    
    <div class="btn_list01 btn_list">
        <button type="button" class="btn btn_01" onclick="addRow()">키워드 추가</button>
        <button type="button" class="btn btn_02" onclick="removeCheckedRows()">선택 삭제</button>
    </div>

    <h2 class="h2_frm">API 키 설정</h2>

    <div class="tbl_head01 tbl_wrap">
        <table id="apiKeyTable">
            <caption>API 키 목록</caption>
            <thead>
                <tr>
                    <th scope="col" width="240">서비스</th>
                    <th scope="col" width="80">신청</th>
                    <th scope="col" width="120">유형</th>
                    <th scope="col">API Key</th>
                </tr>
            </thead>
            <tbody id="apiKeyTableBody">
                <tr>
                    <td>searchapi.io</td>
                    <td><button type="button" class="btn btn_02" data-href="https://www.searchapi.io/?via=namhyeon">신청</button></button>
                    <td>검색, 뉴스</td>
                    <td><input type="text" name="apikey[searchapi]" class="frm_input" value="<?php echo $apconfig['apikey']['searchapi']; ?>"></td>
                <tr>
                <tr>
                    <td>Marketstack</th>
                    <td><button type="button" class="btn btn_02" data-href="https://marketstack.com?utm_source=FirstPromoter&utm_medium=Affiliate&fpr=namhyeon19">신청</button></button>
                    <td>증권</td>
                    <td><input type="text" name="apikey[marketstack]" class="frm_input" value="<?php echo $apconfig['apikey']['marketstack']; ?>"></td>
                </tr>
                <tr>
                    <td>OpenWeatherMap</td>
                    <td><button type="button" class="btn btn_02" data-href="https://openweathermap.org/">신청</button></button>
                    <td>날씨</td>
                    <td><input type="text" name="apikey[openweathermap]" class="frm_input" value="<?php echo $apconfig['apikey']['openweathermap']; ?>"></td>
                </tr>
                <tr>
                    <td>한국수출입은행 환율정보</td>
                    <td><button type="button" class="btn btn_02" data-href="https://www.koreaexim.go.kr/ir/HPHKIR020M01?apino=2">신청</button></button>
                    <td>환율</td>
                    <td><input type="text" name="apikey[koreaexim]" class="frm_input" value="<?php echo $apconfig['apikey']['koreaexim']; ?>"></td>
                </tr>
                <tr>
                    <td>네이버클라우드 GeoLocation</td>
                    <td><button type="button" class="btn btn_02" data-href="https://www.ncloud.com/product/applicationService/geoLocation">신청</button></button>
                    <td>위치정보</td>
                    <td><input type="text" name="apikey[navercloud_geolocation]" class="frm_input" value="<?php echo $apconfig['apikey']['navercloud_geolocation']; ?>"></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="btn_fixed_top">
        <input type="submit" name="act_button" value="전송" onclick="document.pressed=this.value" class="btn btn_01">
    </div>
</form>

<script>
function addRow() {
    const tbody = document.getElementById("keywordTableBody");
    const row = document.createElement("tr");
    
    const emptyRow = tbody.querySelector(".empty_table");
    if (emptyRow) {
      emptyRow.parentElement.remove();
    }

    row.innerHTML = document.getElementById("keywordRowTemplate").innerHTML;
    tbody.appendChild(row);
    updateEmptyRow();
}

function removeRow(button) {
    const row = button.closest("tr");
    row.remove();
    updateEmptyRow();
}

function updateEmptyRow() {
    const tbody = document.getElementById("keywordTableBody");
    const rows = Array.from(tbody.querySelectorAll("tr"));
    console.log(rows);
    
    const hasDataRows = rows.some(tr => !tr.classList.contains("empty_table"));
    
    if (!hasDataRows) {
        const emptyRow = document.createElement("tr");
        emptyRow.innerHTML = '<td class="empty_table" colspan="5">자료가 없습니다.</td>';
        tbody.appendChild(emptyRow);
    }
}

function keywordCheckAll(source) {
    const tbody = document.getElementById("keywordTableBody");
    const checkboxes = tbody.querySelectorAll(".row-check");
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function removeCheckedRows() {
    const tbody = document.getElementById("keywordTableBody");
    const rows = Array.from(tbody.querySelectorAll("tr"));

    let anyRemoved = false;

    rows.forEach(row => {
        const checkbox = row.querySelector(".row-check");
        if (checkbox && checkbox.checked) {
            row.remove();
            anyRemoved = true;
        }
    });

    if (anyRemoved) {
        document.getElementById("chkall").checked = false;
        updateEmptyRow();
    }
}

document.addEventListener('click', function(event) {
    var target = event.target;
    if (target.tagName.toLowerCase() === 'button' && target.dataset.href) {
        var a = document.createElement('a');
        a.href = target.dataset.href;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
});
</script>

<?php
require_once './admin.tail.php';
