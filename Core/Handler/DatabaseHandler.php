<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  DatabaseHandler: Database Connection Handling
 */
define('SORTBY_ASCEND', 0);
define('SORTBY_DESCEND', 1);

require_once('Core/Handler/SqlHandler.php');
// 各種データベースへのアクセスハンドラ
require_once('Core/Handler/SQLiteHandler.php');
require_once('Core/Handler/PostgreHandler.php');
require_once('Core/Handler/NullHandler.php');
//require_once('Core/Handler/FMDBHandler.php');

//==============================================================================
// field ALIAS and BIND-columns class
class fieldAlias {
	public $lang_alternate = FALSE;			// use origin field, when lang field empty
	private	$lang_alias = [];
    private	$bind_columns = [];
//==============================================================================
// Setup ALIAS parameter,and BIND-column parameter
public function SetupAlias($alias,$binds) {
	$this->lang_alias = $alias;
	$this->bind_columns = $binds;
}
public function GetAlias() {
	return [ 'Locale' => $this->lang_alias,'Bind' => $this->bind_columns];
}
//==============================================================================
// check exists Locale ALIAS field
public function exists_locale($field_name) {
    return (array_key_exists($field_name,$this->lang_alias));
}
//==============================================================================
// check exists Locale BIND field
public function get_bind_ifexists($fields) {
    foreach($this->bind_columns as $key => $columns) {
        if(count($columns) === count($fields)) {
            $match = TRUE;
            foreach($columns as $fn ) {
                if(!in_array($fn,$fields)) {
                    $match = FALSE;
                    break;
                }
            }
            if($match) return $key;
        }
    }
    return FALSE;
}
//==============================================================================
// Concatenate BIND-column in record field
public function get_bind_key($row,$key) {
    return array_concat_keys($row,$this->bind_columns[$key]);
}
//==============================================================================
//	if exists LOCALE alias, get LOCALE fields name
public function get_lang_alias($field_name) {
     return ($this->exists_locale($field_name)) ? $this->lang_alias[$field_name] : $field_name;
}
//==============================================================================
// ALIAS fields replace to standard field, and BIND-column to record field
public function to_lang_alias(&$row) {
    foreach($this->lang_alias as $key => $lang) {
        if(!empty($row[$lang]) || $this->lang_alternate === FALSE) $row[$key] = $row[$lang];
        unset($row[$lang]);
    }
}
//==============================================================================
// ALIAS fields replace to standard field, and BIND-column to record field
public function to_bind_field(&$row) {
    foreach($this->bind_columns as $key => $columns) {
        $row[$key] = array_concat_keys($row,$columns);
    }
}
//==============================================================================
// ALIAS fields replace to standard field, and BIND-column to record field
public function to_alias_bind(&$row) {
    foreach($this->lang_alias as $key => $lang) {
        if(!empty($row[$lang]) || $this->lang_alternate === FALSE) $row[$key] = $row[$lang];
        unset($row[$lang]);
    }
    foreach($this->bind_columns as $key => $columns) {
        $row[$key] = array_concat_keys($row,$columns);
    }
}
// End-OF-CLASS
}
//==============================================================================
// データベースの接続情報を保持するクラス
// 全体で１度だけ呼び出す
class DatabaseHandler {
    const DatabaseSpec = [
        // SQLite3データベースへの接続情報
        'SQLite' => [
            'datasource' => 'Database/SQLite3',
            'callback' => 'SQLiteDatabase',
            'offset' => false,
        ],
        // PostgreSQLデータベースへの接続情報
        'Postgre' => [
            'datasource' => 'Database/Postgres',
            'callback' => 'PgDatabase',
            'offset' => TRUE,
        ],
        // MariaDB(MySQL)データベースへの接続情報
        'MySQL' => [
            'datasource' => 'Database/MariaDB',
            'callback' => 'MySQLDatabase',
            'offset' => false,
        ],
    ];
    private static $dbHandle = [];
    public static $have_offset = TRUE;
//==============================================================================
// データベースへ接続してハンドルを返す
public static function get_database_handle($handler) {
    if(array_key_exists($handler,static::$dbHandle)) {
        return static::$dbHandle[$handler];
    }
    if(array_key_exists($handler,static::DatabaseSpec)) {
        debug_log(DBMSG_HANDLER,['HANDLER' => $handler, 'DATABASE' => DatabaseParameter[$handler]['database']]);
        $defs = static::DatabaseSpec[$handler];
        $func = $defs['callback'];      // 呼び出し関数
        static::$have_offset = $defs['offset'];     // DBMS has OFFSET command?
        static::$dbHandle[$handler] = static::$func(DatabaseParameter[$handler],'open');
        return static::$dbHandle[$handler];
    }
    return NULL;
}
//==============================================================================
// デストラクタ
public static function CloseConnection() {
    static::closeDb();
}
//==============================================================================
// ハンドルの取得
public function get_callback_func($handler) {
    return static::DatabaseSpec[$handler]['callback'];      // 呼び出し関数
}
//==============================================================================
// クローズ処理
private static function closeDb() {
    foreach(static::$dbHandle as $key => $handle) {
        $func = static::DatabaseSpec[$key]['callback'];      // 呼び出し関数
        static::$func($handle,NULL,'close');
    }
    self:$dbHandle = [];
}
//==============================================================================
// PostgreSQL データベース
private static function PgDatabase($dbdef,$action) {
    switch($action) {       //   [ .ptl, .tpl, .inc, .twg ]
    case 'open':
        $conn = "host={$dbdef['host']} dbname={$dbdef['database']} port={$dbdef['port']}";
        $conn .= " user={$dbdef['login']} password={$dbdef['password']};";
        $dbb = pg_connect($conn);
        if(!$dbb) {
            debug_log(-1,['DEF'=>$dbdef,'CONNECT'=>$conn]);
            die('Postgres 接続失敗' . pg_result_error($dbb)."\n");
        }
        return $dbb;
    case 'close':
        pg_close($dbdef);
        break;
    case 'query':
        break;
    }
}
//==============================================================================
// SQlite3 データベース
private static function SQLiteDatabase($dbdef,$action) {
    switch($action) {       //   [ .ptl, .tpl, .inc, .twg ]
    case 'open':
        $dbb = new SQLite3($dbdef['database']);
        if(!$dbb) {
            die('SQLite3 接続失敗');
        }
        return $dbb;
    case 'close':
        $dbdef->close();
        break;
    case 'query':
        break;
    }
}
//==============================================================================
// MariaDB(MySQL) データベース
private static function MySQLDatabase($dbdef,$action) {
    switch($action) {       //   [ .ptl, .tpl, .inc, .twg ]
    case 'open':
        // DB接続：mysqliクラスをオブジェクト化してから使う
        $dbb = new mysqli($dbdef['host'], $dbdef['login'], $dbdef['password'], $dbdef['database']);
        if($dbb->connect_error) {
            echo $dbb->connect_error;
            die('MariaDB 接続失敗');
        }
        return $dbb;
    case 'close':
        $dbdef->close();
        break;
    case 'query':
        break;
    }
}
//==============================================================================
// 動作確認用(FMDB)
static function Connect($handler,$layout) {
    $dbb = static::$dbHandle[$handler];
}

}
