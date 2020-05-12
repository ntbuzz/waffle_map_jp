<?php

class IndexHelper extends AppHelper {
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
// ヘッダー付きのテーブルリスト表示
public function makeOutlineTree() {
dump_debug(0,[ "アウトライン" => $this->MyModel->outline]);
    echo "<ul class='filetree' id='sitemenu'>\n";
    $first=' class="open"';
    foreach($this->MyModel->outline as $key => $chapter) {
        echo "<li${first}><span class='folder'>{$chapter[title]}</span>\n";
        echo "<ul>\n";
        foreach($chapter['child'] as $kk => $val) {
            $cls = ($val['id'] == App::$Params[1]) ? ' selected':'';
            echo "<li><span class='file{$cls}'>";
            $this->ALink("view/{$key}/{$val[id]}",$val['title']);
            echo "</span></li>\n";
        }
        echo "</ul>\n";
        echo "</li>\n";
        $first='';//' class="closed"';
    }
    echo "</ul>\n";
}
//===============================================================================
// セクション名をキーにタブ表示する
public function Part_Chapter_Data() {
    APPDEBUG::arraydump(3, [
        'パート' => $this->PartData,
        'チャプタ' => $this->ChapterData,
        'セクション' => $this->Section,
    ]);    
    echo "var PartData = {\n";
    foreach($this->PartData as $key => $val) {
        $vv = str_replace("\r\n", "\\n", $val);
        echo "\t{$key}: \"{$vv}\",\n";
    }
    echo "};\n";
    echo "var ChapterData = {\n";
    foreach($this->ChapterData as $key => $val) {
        $vv = str_replace("\r\n", "\\n", $val);
        echo "\t{$key}: \"{$vv}\",\n";
    }
    echo "};\n";
}
//===============================================================================
// セクション名をキーにタブ表示する
public function SectionTab() {
    APPDEBUG::arraydump(3, [
        'パート' => $this->PartData,
        'チャプタ' => $this->ChapterData,
        'セクション' => $this->Section,
    ]);    
    echo "<ul class='tab' id='{$this->Chapter}' data-parent='{$this->Part}'>\n";
    if(!empty($this->Section)) {
        $n = 0;
        foreach($this->Section as $key => $val) {
            $sel = ($this->Tabmenu == $n++) ? ' class="selected"' : '';
            $ttl = ($val['tabset']==='') ? $val['title']:$val['tabset'];
            echo "<li{$sel} id='$val[id]'>$ttl</li>\n";
            $first = '';
        }
    }
    // チャプターが選択されているときだけ
    if(!empty($this->Chapter)) {
        echo "<li class='add-section' id='add_baloon'>＋</li>\n";
    }
    echo "</ul>\n";
}
//===============================================================================
// セクションのコンテンツをリスト表示する
public function SectionContents() {
    echo "<ul class='content'>\n";
    if(!empty($this->Section)) {
        $n = 0;
        foreach($this->Section as $key => $sec) {
            $sel = ($this->Tabmenu == $n++) ? '' : ' class="hide"';
            echo "<li{$sel}>";
            echo "<div class='section' id='$sec[id]' data-disp='$sec[disp_id]' data-parent='$sec[chapter_id]' value='$sec[short_title]'>";
            echo "<h2 class='title'>$sec[title]</h2>\n";
            if(isset($sec['contents'])) {
                echo "<div class='description'>$sec[contents]</div>\n";
            }
            echo "<hr>\n";
            foreach($sec['本文'] as $val) {
                echo "<div class='paragraph' id='$val[id]' data-disp='$val[disp_id]' data-parent='$val[section_id]'>";
                if($val['title']) {
                    echo "<h3 class='caption'>$val[title]</h3>\n";
                }
                echo "<div class='data'>".$val[$this->_('.Schema.contents')]."</div>";
                echo "</div>\n";
            }
            echo "</div>\n";
            echo "</li>\n";
        }
    }
    echo "</ul>\n";
}
//===============================================================================
// セレクトリストをjavascript配列へ
public function SelectList($key) {
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        echo "[{$id},\"{$ttl}\"],";
    }
}
//===============================================================================
// セクションのコンテンツをリスト表示する
public function ChapterSelector($key) {
	echo "<SELECT name='part_id'>";
    foreach($this->MyModel->PartSelect as $val) {
        list($id,$text) = $val;
		echo "<OPTION value='{$id}'>{$text}</option>\n";
    }
	echo "</SELECT>\n";
}

}
