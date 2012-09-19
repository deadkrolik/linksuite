<?PHP

class smartpagerankcom_PR extends llmPRMeter {

	var $host          = "www.smartpagerank.com";
	var $start_url     = "http://www.smartpagerank.com/bulk-pagerank-checker.php";
	var $unique_string = "Find the pagerank of any page you desire";
	var $max_urls      = 19;//явно сказано 20
	
	//и не вздумайте удалять конструктор, иначе пхп сдохнет
	function smartpagerankcom_PR() {}
	
	function getUrlsPR() {
	
		$is_curl_error = false;
		$vars = array(
				"several"    => implode("\n",$this->work_urls), 
				"submit"     => "Get Pagerank",
					);
		$page = $this->sendSubmit($this->start_url,$vars);
		
		preg_match("|<th width=150 align=center>Website</th>(.*)\*Disclaimer|Umsi",$page,$mt);
		if (!isset($mt[1])) return false;
		
		$str = $mt[1];$ret = array();
		
		preg_match_all("|<TR><td align=center>(.*)</td><td valign=center align=center><img src=primages/pr([0-9]).bmp><|Umsi",$str,$mt2);
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