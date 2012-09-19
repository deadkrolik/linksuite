<?PHP
//заголовки запрета кэширования, на всякий случай
header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );

//проверка корректности ввода урла в модуле получения скрипта на сайт
if (isset($_GET['check'])) {

	echo "LLM_AGA_AGA";
	exit();
}

//поиск директории, где установлен сервер
$parent_dir = dirname(dirname(__FILE__));
$dirs = glob($parent_dir.'/*',GLOB_ONLYDIR);$is_found = false;

if (sizeof($dirs) == 0 || $dirs === false) {

	echo "LERROR: check permissions for directory `{$parent_dir}`";
	exit;
}

foreach($dirs as $dir) {

	$ini_file = $dir.'/conf/main.ini';
	if (!file_exists($ini_file)) continue;
	
	$pi = parse_ini_file($ini_file);

	//сравниваем имена директорий в конфиге и текущей (откуда работаем)
	$client_dir_name_ini  = @$pi["LLM_CLIENT_DIR"];
	$client_dir_name_real = basename(dirname(__FILE__));
	
	if ($client_dir_name_ini == $client_dir_name_real) {
	
		$is_found = true;
		break;
	}
}

//если вдруг не отыскался, то так и говорим об этом
if (!$is_found) {

	echo "LERROR: main.ini not found";
	exit;
}

//инициализация
require_once($dir."/includes/llm_server.php");
$database   = llmServer::getDBO();
$type       = getParam("type","links");
$method     = getParam("method","secure");

//чистим переменные
$domain_key = preg_replace("|[^a-z0-9]|","",getParam('domain_key'));
$domain     = getParam('domain');

//для отдачи ссылок по domain_key
if ($method == 'secure') {
	
	//грузим домен по ключу
	$database->setQuery("SELECT * FROM sites WHERE domain_key = '{$domain_key}'");
	$sites = $database->loadObjectList();
}
else {//если домен определяется только по имени

	//грузим домен по ключу
	$database->setQuery("SELECT * FROM sites WHERE url = 'http://{$domain}/'");
	$sites = $database->loadObjectList();
}

if (!isset($sites[0])) {

	echo "LERROR: loading site";
	$logger = new llmLogger("security.txt","a");
	$logger->log(LLM_LOG_ERROR,"Bad domain key: '{$domain_key}', IP = ".long2ip(ip2long($_SERVER['REMOTE_ADDR'])));
	$logger->save();
	exit;
}
$site = $sites[0];

//обновляем время последнего обращения за ссылками сайта
$database->setQuery("UPDATE sites SET last_time_get_links='".time()."' WHERE id='{$site->id}'");
$database->query();

switch($type) {
	
	case 'links':
		
		//грузим все ссылки для нашего домена, а скрипт пусть дальше сам разбирается что и как
		$database->setQuery("SELECT links.html,pages.url as pages_url,sites.url as sites_url,sites.links_delimiter,sites.css_class,sites.charset FROM links INNER JOIN pages ON pages.id = links.page_id INNER JOIN sites ON sites.id = pages.site_id WHERE pages.site_id = '{$site->id}' AND links.status = '".LLM_LINK_STATUS_ACTIVE."' AND pages.status = '".LLM_PAGE_STATUS_ACTIVE."'");
		$links = $database->loadObjectList();
		
		$charset = "";
		if (sizeof($links) > 0) {
		
			$charset = $links[0]->charset;
		}
		
		//подготовка ссылок к показу
		llmHTML::modifyLinks($links,$site);
		
		//перекодируем это дело
		foreach($links as $ind => $link) {
			
			if ($charset) $links[$ind]->html = iconv("CP1251",$charset,$links[$ind]->html);
		}
		
		//упаковываем для единообразия
		$data = serialize($links);
		
		//если клиент достаточно новый, то посылаем ему статичный код, что бы он смог у себя его сохранить и отображать
		$send_static_code = (int)getParam('send_static_code');
		if ($send_static_code) {
		
			$sid = $site->id;
			
			//отобор по сайтам
			$database->setQuery("SELECT * FROM static_code WHERE show_on_sites = '0' OR show_on_sites LIKE '$sid,%' OR show_on_sites LIKE '%,$sid,%' OR show_on_sites LIKE '%,$sid' OR show_on_sites = '$sid'");
			$scodes = $database->loadObjectList();
			
			$sdata = serialize($scodes);
			
			$data .= "LLM_STATIC_CODE_DELIMITER".$sdata;
		}
		
		break;
		
	case 'article':
		
		$database->setQuery("SELECT a.*,s.charset FROM articles AS a INNER JOIN sites AS s ON a.site_id = s.id WHERE site_id='{$site->id}' AND (time_end = 0 OR time_end > ".time().") ORDER BY ID asc");
		$articles = $database->loadObjectList();
		
		$data = serialize($articles);
		break;
		
	case 'image':
		
		$artcile_id = (int)getParam("article_id");
		
		$database->setQuery("SELECT image FROM articles WHERE id='{$artcile_id}'");
		$img = $database->loadResult();
		
		$dfile = llmServer::getPPath().'data/images/'.$img;
		if (file_exists($dfile)) $data = file_get_contents($dfile);
			else $data = file_get_contents(llmServer::getPPath().'template/icons/_.png');
	
		break;
		
}

//новый метод проверки - не умер ли отдающий хост или вдруг вместо этого файла кто-то отдаст другой контент
//бывает при переносе доменов, когда старые еще отвечают и иногда отвечают непонятно что - посылаем перед всем 
//нашим контентом магическую строку, которую никто другой послать не в состоянии
$send_magic = getParam('send_magic');
if ($send_magic) {

	echo "LLM_MAGIC_CONSTANT";
}

//отдаем основной контент
echo base64_encode($data);
?>