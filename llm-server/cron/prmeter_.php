<?PHP
include(dirname(__FILE__).'/_common.php');

require_once("../opensource/pr/pr.php");
$pr_meter = new llmPRMeter();
$MAX_LOOP = llmConfig::get("CRON_PRMETER_MAX_LOOP",5);
$SLEEP_S = llmConfig::get("CRON_PRMETER_SLEEP",60);

$count = 0;
while ($count < $MAX_LOOP) {

	//сколько мы максимально сможем обработать за один раз по всем движкам
	$limit = $pr_meter->getMaxLoopURLS();
	
	//запросище на те урлы, где PR равен -1
	$database->setQuery("SELECT pages.*,sites.url as site_url FROM pages INNER JOIN sites ON sites.id = pages.site_id WHERE pages.pr = -1 ORDER BY id LIMIT {$limit}");//AND sites.id = 1 
	$list = $database->loadObjectList('id');

	//если нечего считать
	if (sizeof($list) == 0) exit();
	
	$urls = array();$hashes = array();
	foreach($list as $uri) {
		
		//полный урл считается вот так вот странно
		$full_url = $uri->url == '/' ? $uri->site_url : $uri->site_url.$uri->url;
		
		$urls[]            = $full_url;
		$hashes[$full_url] = $uri->url_hash;
	}
	
	//собственно расчет
	$calc_urls = $pr_meter->getPR($urls);

	//сохраняем результаты в таблицу, к сожалению только по одному
	foreach($calc_urls as $url => $pr) {
	
		//если не рассчитался
		if (!isset($hashes[$url])) continue;
		
		$hash = addslashes($hashes[$url]);
		$query = "UPDATE pages SET pr = '{$pr}' WHERE url_hash = '$hash'";
		
		$database->ping();
		$database->setQuery($query);
		$database->query();
	}

	$count++;
	sleep($SLEEP_S);
}

exit();
?>