<?PHP

class epavelru_PR extends llmPRMeter {

	var $host          = "epavel.ru";
	var $start_url     = "http://epavel.ru/tools/google-pr-checker/";
	var $unique_string = "url_list";
	var $max_urls      = 99;//явно указана цифра 100, пусть будет 99
	
	//и не вздумайте удалять конструктор, иначе пхп сдохнет
	function epavelru_PR() {}
	
	function getUrlsPR() {
	
		$is_curl_error = false;
		$vars = array(
				"datacenter" => "toolbarqueries.google.com", 
				"url_list"   => implode("\n",$this->work_urls), 
				"submit"     => "запустить проверку",
				"form1"      => ""
					);
		$page = $this->sendSubmit($this->start_url,$vars);
		
		preg_match("|<\/fieldset>(.*)<\/body>|Umsi",$page,$mt);
		if (!isset($mt[1])) return false;
		
		$str = $mt[1];$ret = array();
		$elems = explode("<br>",$str);
		foreach($elems as $one) {
		
			$one = trim($one);
			if (!$one) continue;

			list($pr,$url) = explode("&nbsp;:&nbsp;",$one);
			$pr = (int)$pr;
			if ($pr == -1) $pr = 0;
			
			$ret[$url] = (int)$pr;
		}
		
		return $ret;
	}
}

?>