<?PHP
include(dirname(__FILE__).'/_common.php');

$MAX_LOOP = llmConfig::get("CRON_PRMETER_MAX_LOOP",5);
$SLEEP_S  = llmConfig::get("CRON_PRMETER_SLEEP",60);

//запросище на те урлы, где PR равен -1
$database->setQuery("SELECT pages.*,sites.url as site_url FROM pages INNER JOIN sites ON sites.id = pages.site_id WHERE pages.pr = -1 ORDER BY pages.id LIMIT {$MAX_LOOP}");//AND sites.id = 25
$list = $database->loadObjectList('id');

foreach($list as $element) {
	
	//полный урл считается вот так вот странно
	$full_url = $element->url == '/' ? $element->site_url : $element->site_url.$element->url;

	$pr = seoGoogle::getpr($full_url);

	//если этого не делать, то тогда эта страница будет считаться бесконечно
	if ($pr == -1) $pr = 0;
	
	$query = "UPDATE pages SET pr = '{$pr}' WHERE url_hash = '{$element->url_hash}'";
	
	$database->ping();
	$database->setQuery($query);
	$database->query();

	sleep($SLEEP_S);
}

exit();
?>