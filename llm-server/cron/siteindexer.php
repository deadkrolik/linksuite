<?PHP
include(dirname(__FILE__).'/_common.php');

define("LLM_DEBUG",false);
if(LLM_DEBUG) {

	error_reporting(E_ALL);
}

$TMP_DIR = llmServer::getPPath().'tmp/';
//�� ������ ������� �������
$MAX_LEVEL2_PAGES = llmConfig::get("LIMIT_LEVEL2_PAGES",1000);
$TIME_START = time();

//������
$database->setQuery("SELECT * FROM sites WHERE cron_index = '1' ORDER BY RAND()");
$list = $database->loadObjectList('id');

if (sizeof($list) == 0) exit();

//���� ���, � ������� ����� �������� � �� ������������ lock-������
$SITE_ID = 0;
foreach($list as $site) {

	$lock_file = $TMP_DIR.'indexer.'.$site->id.'.cron';
	if (file_exists($lock_file)) {

		//��������� ����� ��� ��������, �-�� ����� ��� ������ ����� ����������� ����-��
		$ftime = file_get_contents($lock_file);
		$diff  = (time() - $ftime)/3600;
		if ($diff > 10) {
		
			//������� �����
			unlink($lock_file);
			//������ ������� � ��������������������
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

//�������� � ���������� ������
$site = $list[$SITE_ID];
//����� ���� ���� ��������� �� ��������������
$excl = explode("\n",$site->exclude_urls);$exclude_urls = array();
foreach($excl as $uuu) {
	
	$uuu = trim($uuu);
	if (!$uuu) continue;

	$exclude_urls[] = $uuu;
}
//� ���� �� ������ ������ ���� � robots.txt, ��� �� ���� ��� ��������� ���� � �� �� �������
$robott = new llmHTTP();
$ROBOTSTXT = $robott->get($site->url."robots.txt",$st1,$cu1);
$rules = $robott->getRobotsTXTRules($site->url,$ROBOTSTXT);
foreach($rules as $rrule) $exclude_urls[] = $rrule;
$exclude_urls_size = sizeof($exclude_urls);
//����� ����������� ������

//������, ����������� � �� [���_����] => �����������
$saved_links = array();

//������� lock ���� � ���, ��� �� ������ ��� ������
$lock_file = $TMP_DIR.'indexer.'.$site->id.'.cron';
if(!LLM_DEBUG) {$f = fopen($lock_file,'w');fwrite($f,time());fclose($f);}

//�� ������� ����� �������
$SLEEP_US = llmConfig::get("CRON_SITEINDEXER_USLEEP",100);
$MAIN_COOKIE = 'llm_xxx='.$site->domain_key;

//��������� ������� ��������
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

	//������ ��� ������, ���� ��� �� ����������
	$logger->log(LLM_LOG_ERROR,"{$site->url} - llm.php code is not installed");
	cleanUp();
}

//���� �����-�� � ���������� �������
if ($status!==LLMHTTP_STATUS_OK) {

	//������ ��� ������, ���� ��� �� ����������
	$logger->log(LLM_LOG_ERROR,"Cannot fetch URL '$site->url'");
	cleanUp();
}

//�������� ��� ������ �� ��������
$links = $http->getPageLinks($page,$current_url);
$page_title = $http->getPageTitle($page);

//��������� �����
$http->saveIndexedPage($site,$links,0,$current_url,$page_title);
$saved_links[md5($current_url)] = 0;

//������ ������� �����������
$inner_links1 = $http->getInnerLinks($site,$links);
$inner_links2 = array();//� ���� ����� �������� ���� ������� ������, ����������
foreach($inner_links1 as $inner_link) {

	//�������� ������� ����� ���� � ��� ����������� � ������� ������
	$url_hash = md5($inner_link);
	if (isset($saved_links[$url_hash])) continue;

	//���� ���� ���������� ��� �� ��� ����������, ����� ��� ��� ���-�� �����
	if (llmHTTP::skipExtension($inner_link)) continue;
	
	//���� ��������� ������������� ����������
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
	// -->> $allow_index - ��������� �������������� ������ ��������
	
	$page = $http->get($inner_link,$status,$current_url);
	$content_type_check = $http->checkContentType($page);
	if (!$content_type_check) continue;
	$links = $http->getPageLinks($page,$current_url);
	
	//��� ��� ���������� �� ����� ����� (���� �� ������ ��� ����������� ������)
	$pos = strpos($current_url,$site->url);
	if ($pos===false) continue;

	//���� ������������� ������ llm.php
	$is_installed = $http->checkLLMInstalled($page);
	if(LLM_DEBUG) $is_installed = true;
	if (!$is_installed) $allow_index = false;
	$page_title = $http->getPageTitle($page);
	
	if ($allow_index) $http->saveIndexedPage($site,$links,1,$current_url,$page_title);
	$saved_links[md5($current_url)] = 1;

	//������� ���� ��� ������� ������ ����������� ����� ������ �� ���� �� ��������
	$inner_tmp = $http->getInnerLinks($site,$links);
	
	foreach($inner_tmp as $inn_link)  {
		if (!in_array($inn_link,$inner_links2) && !isset($saved_links[md5($inn_link)]) && !in_array($inn_link,$inner_links1)) $inner_links2[] = $inn_link;

	}
	usleep($SLEEP_US);
}

//����� ���� ����� �� ���������� ������� ������
$inner_links2 = $http->getInnerLinks($site,$inner_links2);
$count2 = 0;
foreach($inner_links2 as $inner_link) {

	//���� ��� ����
	$url_hash = md5($inner_link);
	if (isset($saved_links[$url_hash])) continue;
	
	//���� ���� ���������� ��� �� ��� ����������, ����� ��� ��� ���-�� �����
	if (llmHTTP::skipExtension($inner_link)) continue;

	//���� ��������� ������������� ����������
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
	// -->> $allow_index - ��������� �������������� ������ ��������
	
	if ($allow_index) {
		
		$page = $http->get($inner_link,$status,$current_url);
		$content_type_check = $http->checkContentType($page);
		if (!$content_type_check) continue;
		$links = $http->getPageLinks($page,$current_url);
		
		//��� ��� ���������� �� ����� ����� (���� �� ������ ��� ����������� ������)
		$pos = strpos($current_url,$site->url);
		if ($pos===false) continue;
		
		//���� ������������� ������ llm.php
		$is_installed = $http->checkLLMInstalled($page);
		if(LLM_DEBUG) $is_installed = true;
		$page_title = $http->getPageTitle($page);
		
		if ($is_installed) $http->saveIndexedPage($site,$links,2,$current_url,$page_title);
		$saved_links[md5($current_url)] = 2;
	
		usleep($SLEEP_US);
	}
	
	//���� ����� �����������
	$count2++;
	if ($count2 > $MAX_LEVEL2_PAGES) break;
}

//���������� � ���
$TIME_END = time();
$log_str = "INDEXING SITE: $site->url, pages[0] = 1, pages[1] = ".sizeof($inner_links1).", pages[2] = {$count2}, time = ".($TIME_END - $TIME_START)." seconds";
$logger->log(LLM_LOG_NOTICE,$log_str);

//������������ ����������
$database->ping();
//��������� � �������� ������ ���� � ���, ��� ���� ��� ���������������
$database->setQuery("UPDATE sites SET cron_index = 0 WHERE id = '{$SITE_ID}'");
$database->query();

cleanUp();


//�������� �����
function cleanUp() {

	global $logger,$lock_file;
	
	//����� ���
	$logger->save();
	if(!LLM_DEBUG) unlink($lock_file);
	exit();	
}
?>