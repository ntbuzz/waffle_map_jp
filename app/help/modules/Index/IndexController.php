<?php

class IndexController extends AppController {
	public $defaultAction = 'List';		//  デフォルトのアクション
	public $disableAction = [ 'Page', 'Find' ];	// 無視するアクション

//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
//	クラス初期化処理
	protected function ClassInit() {
//		$this->SetEvent('Model.OnGetRecord',$this->View->Helper,"echo_dump");
	}
//===============================================================================
// デフォルトの動作
	public function DisplayAction() {
		APPDEBUG::MSG(24,":Test");
		$this->View->PutLayout();
	}
//===============================================================================
// デフォルトの動作
	public function ListAction() {
		$this->Model->MakeOutline();
        APPDEBUG::arraydump(3, [
            'レコード' => $this->Model->Records,
            'アウトライン' => $this->Model->outline,
		]);
		$this->View->PutLayout();
	}
//===============================================================================
// コンテンツビュー
	public function ViewAction() {
		$Part = App::$Params[0];	// Doc-Part
		$Chap = App::$Params[1];	// Doc-Chapter
		$Tabs = App::$Params[2];	// Doc-Chapter
		$Tabs = MySession::$PostEnv['TabSelect'];
		if(!empty(App::$Params[2])) $Tabs = App::$Params[2];
		MySession::$PostEnv['TabSelect']= 0;
		// ツリーメニューを構築
		$this->Model->MakeOutline();
		// Section データを取得
		$this->Part->getRecordByKey($Part);
		$this->Chapter->getRecordByKey($Chap);
		$this->ViewSet(['PartData' => $this->Part->fields,'ChapterData' => $this->Chapter->fields]);

		$this->Section->getSectionDoc($Chap);
		$this->ViewSet(['Part' => $Part,'Chapter' => $Chap,'Section' => $this->Section->Records, 'Tabmenu' => $Tabs]);
        APPDEBUG::arraydump(13, [
			'パラメータ' => App::$Params,
            'レコード' => $this->Model->Records,
            'アウトライン' => $this->Model->outline,
            'セクション' => $this->Section->Records,
            'タブ' => $Tabs,
		]);
		$this->View->PutLayout();
	}
//===============================================================================
// パートレレコードに追加
public function PartAction() {
	$this->Part->AddRecord(MySession::$PostEnv);
	echo App::$Referer;
}
//===============================================================================
// パートレレコードに追加
public function ChapterAction() {
	$this->Chapter->AddRecord(MySession::$PostEnv);
	echo App::$Referer;
}


}
