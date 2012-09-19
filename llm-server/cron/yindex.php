<?PHP
include(dirname(__FILE__).'/_common.php');

//тайминги, что бы не забанили
$limit   = llmConfig::get("CRON_YINDEX_MAX_PAGES",4);
$SLEEP_S = llmConfig::get("CRON_YINDEX_SLEEP",120);

//запрос где ѕ–ќ»Ќƒ≈ —»–ќ¬јЌЌќ—“№ равна -1
$database->setQuery("SELECT pages.*,sites.url as site_url FROM pages INNER JOIN sites ON sites.id = pages.site_id WHERE ( pages.is_in_yandex_index = -1 OR pages.is_in_google_index = -1) ORDER BY id LIMIT {$limit}");//170, AND pages.site_id = 23 
$list = $database->loadObjectList('id');

//если нечего считать
if (sizeof($list) == 0) exit;
	
foreach($list as $uri) {
	
	$full_url = $uri->url == '/' ? $uri->site_url : $uri->site_url.$uri->url;
	$is_yandex_index_q = $is_google_index_q = "";
	
	if ($uri->is_in_yandex_index == -1) {
	
		$is_yandex_index   = seoYandex::getYInIndex(str_replace("http://","",$full_url));
		$is_yandex_index_q = "is_in_yandex_index = '".($is_yandex_index ? "1" : "0")."'";
		
		//если €ндекс нас забанил
		if ($is_yandex_index === NULL) {
			
			sleep($SLEEP_S);
			continue;
		}
	}
	
	if ($uri->is_in_google_index == -1) {
	
		$is_google_index   = seoGoogle::getGInIndex($full_url);
		$is_google_index_q = " is_in_google_index = '".($is_google_index ? "1" : "0")."' ";
	}
	
	if ($is_google_index_q || $is_yandex_index_q) {

		$arr = array();
		if($is_yandex_index_q) $arr[] = $is_yandex_index_q;
		if($is_google_index_q) $arr[] = $is_google_index_q;
		
		$s = implode(", ",$arr);

		$database->ping();
		$database->setQuery("UPDATE pages SET {$s} WHERE id = '{$uri->id}'");
		$database->query();
	}
	
	//всем спать
	sleep($SLEEP_S);
}
?>