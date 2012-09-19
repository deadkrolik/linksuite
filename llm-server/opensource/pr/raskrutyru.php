<?PHP

class raskrutyru_PR extends llmPRMeter {

	var $host          = "raskruty.ru";
	var $start_url     = "http://www.raskruty.ru/tools/pr/index.php";
	var $unique_string = "Проверить PR";
	var $max_urls      = 99;//явно не сказано, пусть будет 99
	
	//и не вздумайте удалять конструктор, иначе пхп сдохнет
	function raskrutyru_PR() {}
	
	function getUrlsPR() {
	
		$is_curl_error = false;
		$vars = array(
				"urls"   => implode("\n",$this->work_urls), 
				"submit"     => "Проверить PR",
				"form1"      => ""
					);
		$page = $this->sendSubmit($this->start_url,$vars);
		
		preg_match("|Количество URL(.*)<\/form>|Umsi",$page,$mt);
		if (!isset($mt[1])) return false;
		
		$str = $mt[1];$ret = array();
		
		preg_match_all("|<tr><td><b>URL:<\/b>(.*)</td><td>PageRank: <b><font color=\"green\">(.*)</font>|Umsi",$str,$mt2);
		
		if (!isset($mt2[1]) || !isset($mt2[2])) return false;
		
		foreach($mt2[1] as $index => $url) {
		
			$url = trim($url);
			$pr  = (int)$mt2[2][$index];
			
			$ret[$url] = $pr;
		}
		
		return $ret;
	}
}

?>