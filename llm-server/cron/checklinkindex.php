<?PHP
include(dirname(__FILE__).'/_common.php');

//тайминги, что бы не забанили
$limit   = llmConfig::get("CRON_YLINKINDEX_MAX_LINKS",2);
$SLEEP_S = llmConfig::get("CRON_YLINKINDEX_SLEEP",240);

$time = time();
$day = 60*60*llmConfig::get('CRON_YLINKINDEX_HOURS',48);

$database->setQuery("SELECT links.*,pages.url as page_url, sites.url as site_url FROM links INNER JOIN pages ON links.page_id = pages.id INNER JOIN sites ON sites.id = pages.site_id WHERE ($time - links.last_index_check)>{$day} ORDER BY links.last_index_check ASC LIMIT {$limit}");
$list = $database->loadObjectList('id');
	
//если нечего считать
if (sizeof($list) == 0) exit;
	
foreach($list as $uri) {
	
	$full_url = $uri->page_url == '/' ? $uri->site_url : $uri->site_url.$uri->page_url;
	
	$is_in_yandex_index = seoYandex::isLinkInYandexIndex($full_url,$uri->html);
	
	//NULL означает бан или тупизмы
	if ($is_in_yandex_index !== NULL) {
		
		$is_in_google_index = seoGoogle::isLinkInGoogleIndex($full_url,$uri->html) ? "1" : "0";
		
		$database->ping();
		$database->setQuery("UPDATE links SET is_in_index = '{$is_in_yandex_index}', is_in_google_index = '{$is_in_google_index}' ,last_index_check='".time()."' WHERE id = '{$uri->id}'");
		$database->query();
	}
	
	sleep($SLEEP_S);
}
?>