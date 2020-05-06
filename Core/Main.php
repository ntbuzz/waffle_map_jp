<?php

/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *   Main: メイン処理
 */
require_once('Class/session.php');

require_once('AppDebug.php');
// デバッグ用のクラス
APPDEBUG::INIT(10);

require_once('Config/appConfig.php');
require_once('Common/appLibs.php');

require_once('App.php');

require_once('Base/AppObject.php');
require_once('Base/AppController.php');
require_once('Base/AppModel.php');
require_once('Base/AppFilesModel.php');
require_once('Base/AppView.php');
require_once('Base/AppHelper.php');

require_once('Base/LangUI.php');           // static class

require_once('Class/fileclass.php');


// タイムゾーンの設定
date_default_timezone_set('Asia/Tokyo');

/*
autoload のサンプル
function classnamesplit($name) {
    $nm = strtoupper($name);
    for($i=strlen(name) - 1; $i > 0; $i-- ) {
        if(substr($name,$i,1) == substr($nm,$i,1) ) {
            return array(substr($name,0,$i), substr($name,$i));
        }
    }
    return array($name);
}
spl_autoload_register(function ($name) {
    $sp = classnamesplit($name);
    var_dump($sp);
});
if(!class_exists('DbSupportModel')) $view = 'AppModel';
*/
//echo setlocale(LC_ALL,0);
list($fwroot,$rootURI,$appname,$controller,$params,$q_str) = getFrameworkParameter(__DIR__);
parse_str($q_str, $query);

$scriptname = $_SERVER['SCRIPT_NAME'];
if($appname == '') {
    $appname = 'help';
}
// アプリケーションのコンフィグを読込む
require_once("{$appname}/Config/config.php");

$redirect = false;

$action = array_shift($params);         // パラメータ先頭はメソッドアクション
if(is_numeric($action) ) {              // アクション名が数値ならパラメータに戻す
    array_unshift($params, $action);
    $action = 'list';      // 指定がなければ list
    $redirect = true;
}
// アクションのキャメルケース化とURIの再構築
$action = ucfirst(strtolower($action));
// コントローラーファイルが存在するか確認する
if(!is_extst_module($appname,$controller,'Controller')) {
    $controller = ucfirst(strtolower(DEFAULT_CONTROLLER));     // 指定がなければ 
    $redirect = true;
}
// URLを再構成する
$ReqCont = [
    'controller' => strtolower($controller),
    'action' => strtolower($action ),
    'query' => implode('/',$params)
];
// コントローラー、アクションのキャメルケース化とURIの再構築
$requrl = str_replace('//','/',"{$rootURI}".implode('/',$ReqCont));
// フレームワーク直接
if(strpos($rootURI,"/{$fwroot}/") !== FALSE) {
/*
    dump_debug("MAIN", [
        'デバッグ情報' => [
            "SERVER" => $_SERVER['REQUEST_URI'],
            "RootURI"=> $rootURI,
            "fwroot"=> $fwroot,
            "appname"=> $appname,
            "Controller"=> $controller,
            "Action"    => $action,
            "Param"    => $params,
        ],
        "ReqCont" => $ReqCont,
    ]);
*/
    $requrl = str_replace('//','/',"/{$appname}/".implode('/',$ReqCont));
    $redirect = true;
}
// コントローラ名やアクション名が書き換えられてリダイレクトが必要なら終了
if($redirect) {
//    echo "Location:{$requrl}\n"; exit;
    header("Location:{$requrl}");
    exit;
}
// アプリケーション変数を初期化する
App::__Init($rootURI, $appname, $requrl, $params, $q_str);

// コントローラ名/アクションをクラス名/メソッドに変換
$className = "{$controller}Controller";
$method = "{$action}Action";

// 共通サブルーチンライブラリを読み込む
$libs = GetPHPFiles("{$appname}/common/");
foreach($libs as $files) {
    require_once $files;
}
// コアクラスのアプリ固有の拡張クラス
$libs = GetPHPFiles("{$appname}/extends/");
foreach($libs as $files) {
    require_once $files;
}
// 言語ファイルの対応
$lang = (isset($query['lang'])) ? $query['lang'] : $_SERVER['HTTP_ACCEPT_LANGUAGE'];

// コントローラ用の言語ファイルを読み込む
LangUI::construct($lang,$appname);
LangUI::LangFiles(['#common',$controller]);
// データベースハンドラを初期化する */
DatabaseHandler::InitConnection();

// モジュールファイルを読み込む
App::appController($controller);
/*
dump_debug("MAIN", [
    'デバッグ情報' => [
        "sysRoot"=> $sysRoot,
        "fwroot"=> $fwroot,
        "appname"=> $appname,
        "Controller"=> $controller,
        "Action"    => $action,
        "Param"    => $params,
    ],
    "REQ" => $ReqCont,
]);
exit;
*/
// コントローラインスタンス生成
$controllerInstance = new $className();
// 指定メソッドが存在するか、無視アクションかをチェック
if(!method_exists($controllerInstance,$method) || 
    in_array($action,$controllerInstance->disableAction) ) {
    // クラスのデフォルトメソッド
    $action = $controllerInstance->defaultAction;
    $method = "{$action}Action";
    App::$SysVAR['method'] = strtolower($action);
}
// 残りの引数を与え メソッド実行
App::$ActionClass = $controller;
App::$ActionMethod= $action;
APPDEBUG::debug_dump(1, [
    'システム変数情報' => [ App::$SysVAR ],
    'パラメータ情報' => [ App::$Params ],
],1);

// =================================
APPDEBUG::debug_dump(1, [
    'デバッグ情報' => [
        "Controller"=> $controller,
        "Class"     => $className,
        "Method"    => $method,
        "URI"       => $requrl,
        "SCRIPT"    => $scriptname,
        "QUERY"     => $q_str,
        "Module"    => App::$ActionClass,
        "Action"    => App::$ActionMethod,
    ],
    "QUERY" => App::$Query,
    "SESSION" => MySession::$PostEnv,
]);
// セッション変数を初期化
MySession::InitSession();
//$controllerInstance->__Startup();       // コントローラーのスタートアップ処理
APPDEBUG::RUN_START();

$controllerInstance->$method();

APPDEBUG::RUN_FINISH(0);
// リクエスト情報を記憶
MySession::SetVars('sysVAR',App::$SysVAR);
APPDEBUG::arraydump(1, [
    "クローズセッション" => MySession::$PostEnv,
]);
// クローズメソッドを呼び出して終了
$controllerInstance->__TerminateApp();

MySession::CloseSession();
DatabaseHandler::CloseConnection();
