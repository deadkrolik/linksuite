<?PHP
define("LLM_STARTED",true);
header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );

//главный файл, в котором есть все
require_once("includes/llm_server.php");
require_once("includes/llm_http.php");
require_once("opensource/_.php");

//авторизация
llmAuth::checkLogged();

//запрашиваемый модуль
$mod = getParam('mod','hello');
$task = getParam('task','');
$mod = preg_replace("|[^a-z]|","",$mod);
$mod_file = llmServer::getPPath()."modules/{$mod}.php";
if (!file_exists($mod_file)) llmServer::showError("Module `$mod` not found");

llmAuth::checkRules();

//исполнение модуля
ob_start();
include($mod_file);
$executed_module = ob_get_contents();
ob_end_clean();

$no_html = getParam('no_html');
//шаблон
if (!$no_html) {

	header("Content-Type: text/html; charset=windows-1251");
	include(llmServer::getPPath().'/template/index.php');
}
else {
	
	//перекодируем если попросили		
	if (defined("ICONV_ME")) {
		
		$executed_module = iconv("CP1251",ICONV_ME,$executed_module);
		$iconv2http_header = array("UTF-8" => "utf-8", "CP1251" => "windows-1251");
		$http_header = isset($iconv2http_header[strtoupper(ICONV_ME)]) ? $iconv2http_header[strtoupper(ICONV_ME)] : "windows-1251";
	}
	else {
	
		$http_header = "windows-1251";
	}

	//и отдаем правильный заголовок
	header("Content-Type: text/html; charset={$http_header}");
	echo $executed_module;
}
exit();
?>