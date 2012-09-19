<?PHP
include(dirname(__FILE__).'/_common.php');

define("LLM_DEBUG",false);
if(LLM_DEBUG) {

	error_reporting(E_ALL);
}

$TMP_DIR = llmServer::getPPath().'tmp/';
//на второй уровень сколько
$MAX_LEVEL2_PAGES = llmConfig::get("LIMIT_LEVEL2_PAGES",1000);
$TIME_START = time();

//грузим
$database->setQuery("SELECT * FROM sites WHERE cron_index = '1' ORDER BY RAND()");
$list = $database->loadObjectList('id');

if (sizeof($list) == 0) exit();

//ищем тот, с которым можно работать и не заблокирован lock-файлом
$SITE_ID = 0;
foreach($list as $site) {

	$lock_file = $TMP_DIR.'indexer.'.$site->id.'.cron';
	if (file_exists($lock_file)) {

		//проверяем время его создания, а-то вдруг уже десять часов индексирует чего-то
		$ftime = file_get_contents($lock_file);
		$diff  = (time() - $ftime)/3600;
		if ($diff > 10) {
		
			//удаляем нафик
			unlink($lock_file);
			//делаем пометку о проиндексированности
			$database->setQuery("UPDATE sites SET cron_index = 0 WHERE id = '{$site->id}'");
			$database->query();
		}
	}
	else {
	
		$SITE_ID = $site->id;
		break;
	}
}

if (!$SITE_ID) exit();
$logger = new llmLogger("siteindexer.txt","a");

//работаем с конкретным сайтом
$site = $list[$SITE_ID];
//какие урлы надо исключить из индексирования
$excl = explode("\n",$site->exclude_urls);$exclude_urls = array();
foreach($excl as $uuu) {
	
	$uuu = trim($uuu);
	if (!$uuu) continue;

	$exclude_urls[] = $uuu;
}
//в этот же массив пихаем урлы и robots.txt, ибо по сути они выполняют одну и ту же функцию
$robott = new llmHTTP();
$ROBOTSTXT = $robott->get($site->url."robots.txt",$st1,$cu1);
$rules = $robott->getRobotsTXTRules($site->url,$ROBOTSTXT);
foreach($rules as $rrule) $exclude_urls[] = $rrule;
$exclude_urls_size = sizeof($exclude_urls);
//конец исключаемых ссылок

//ссылки, сохраненные в БД [хэш_урла] => вложенность
$saved_links = array();

//создаем lock файл о том, что мы начали его мучить
$lock_file = $TMP_DIR.'indexer.'.$site->id.'.cron';
if(!LLM_DEBUG) {$f = fopen($lock_file,'w');fwrite($f,time());fclose($f);}

//на сколько будем усыпать
$SLEEP_US = llmConfig::get("CRON_SITEINDEXER_USLEEP",100);
$MAIN_COOKIE = 'llm_xxx='.$site->domain_key;

//получение главной страницы
$http = new llmHTTP($MAIN_COOKIE);
$page = $http->get($site->url,$status,$current_url);

if(LLM_DEBUG) {

	$needle = "<!-- ".md5($MAIN_COOKIE)." -->";
}

$content_type_check = $http->checkContentType($page);
if (!$content_type_check) $logger->log(LLM_LOG_ERROR,"Bad content type of main page");

$is_installed = $http->checkLLMInstalled($page);
if(LLM_DEBUG) $is_installed = true;
if (!$is_installed) {

	//нечего тут делать, если код не установлен
	$logger->log(LLM_LOG_ERROR,"{$site->url} - llm.php code is not installed");
	cleanUp();
}

//беда какая-то с получением страниц
if ($status!==LLMHTTP_STATUS_OK) {

	//нечего тут делать, если код не установлен
	$logger->log(LLM_LOG_ERROR,"Cannot fetch URL '$site->url'");
	cleanUp();
}

//получаем все ссылки на странице
$links = $http->getPageLinks($page,$current_url);
$page_title = $http->getPageTitle($page);

//сохраняем морду
$http->saveIndexedPage($site,$links,0,$current_url,$page_title);
$saved_links[md5($current_url)] = 0;

//первый уровень вложенности
$inner_links1 = $http->getInnerLinks($site,$links);
$inner_links2 = array();//а сюда будем собирать урлы второго уровня, постепенно
foreach($inner_links1 as $inner_link) {

	//проверка наличия этого урла в уже сохраненных в текущем сеансе
	$url_hash = md5($inner_link);
	if (isset($saved_links[$url_hash])) continue;

	//если надо пропустить урл по его расширению, архив там или что-то такое
	if (llmHTTP::skipExtension($inner_link)) continue;
	
	//ищем подстроку игнорирования индексации
	$allow_index = true;
	if ($exclude_urls_size > 0) {
	
		foreach($exclude_urls as $eurl) {
		
			$pos = strpos($inner_link,$eurl);
			if ($pos!==false) {
			
				$allow_index = false;
				break;
			}
		}
	}
	// -->> $allow_index - разрешает индексирование данной страницы
	
	$page = $http->get($inner_link,$status,$current_url);
	$content_type_check = $http->checkContentType($page);
	if (!$content_type_check) continue;
	$links = $http->getPageLinks($page,$current_url);
	
	//это при редиректах на чужие сайты (клик по банеру или редиректной ссылке)
	$pos = strpos($current_url,$site->url);
	if ($pos===false) continue;

	//ищем установленный скрипт llm.php
	$is_installed = $http->checkLLMInstalled($page);
	if(LLM_DEBUG) $is_installed = true;
	if (!$is_installed) $allow_index = false;
	$page_title = $http->getPageTitle($page);
	
	if ($allow_index) $http->saveIndexedPage($site,$links,1,$current_url,$page_title);
	$saved_links[md5($current_url)] = 1;

	//собраем урлы для второго уровня вложенности среди ссылок на этой же странице
	$inner_tmp = $http->getInnerLinks($site,$links);
	
	foreach($inner_tmp as $inn_link)  {
		if (!in_array($inn_link,$inner_links2) && !isset($saved_links[md5($inn_link)]) && !in_array($inn_link,$inner_links1)) $inner_links2[] = $inn_link;

	}
	usleep($SLEEP_US);
}

//далее тоже самое со страницами второго уровня
$inner_links2 = $http->getInnerLinks($site,$inner_links2);
$count2 = 0;
foreach($inner_links2 as $inner_link) {

	//если уже есть
	$url_hash = md5($inner_link);
	if (isset($saved_links[$url_hash])) continue;
	
	//если надо пропустить урл по его расширению, архив там или что-то такое
	if (llmHTTP::skipExtension($inner_link)) continue;

	//ищем подстроку игнорирования индексации
	$allow_index = true;
	if ($exclude_urls_size > 0) {
	
		foreach($exclude_urls as $eurl) {
		
			$pos = strpos($inner_link,$eurl);
			if ($pos!==false) {
			
				$allow_index = false;
				break;
			}
		}
	}
	// -->> $allow_index - разрешает индексирование данной страницы
	
	if ($allow_index) {
		
		$page = $http->get($inner_link,$status,$current_url);
		$content_type_check = $http->checkContentType($page);
		if (!$content_type_check) continue;
		$links = $http->getPageLinks($page,$current_url);
		
		//это при редиректах на чужие сайты (клик по банеру или редиректной ссылке)
		$pos = strpos($current_url,$site->url);
		if ($pos===false) continue;
		
		//ищем установленный скрипт llm.php
		$is_installed = $http->checkLLMInstalled($page);
		if(LLM_DEBUG) $is_installed = true;
		$page_title = $http->getPageTitle($page);
		
		if ($is_installed) $http->saveIndexedPage($site,$links,2,$current_url,$page_title);
		$saved_links[md5($current_url)] = 2;
	
		usleep($SLEEP_US);
	}
	
	//если стоит ограничение
	$count2++;
	if ($count2 > $MAX_LEVEL2_PAGES) break;
}

//сохранение в лог
$TIME_END = time();
$log_str = "INDEXING SITE: $site->url, pages[0] = 1, pages[1] = ".sizeof($inner_links1).", pages[2] = {$count2}, time = ".($TIME_END - $TIME_START)." seconds";
$logger->log(LLM_LOG_NOTICE,$log_str);

//возобновляем соединение
$database->ping();
//обновляем в табличке сайтов поле о том, что сайт уже проиндексирован
$database->setQuery("UPDATE sites SET cron_index = 0 WHERE id = '{$SITE_ID}'");
$database->query();

cleanUp();


//красивый выход
function cleanUp() {

	global $logger,$lock_file;
	
	//пишем лог
	$logger->save();
	if(!LLM_DEBUG) unlink($lock_file);
	exit();	
}
?>