<?PHP
//константы проверки наличи€ в индексе
//define("YANDEX_MASK","http://yandex.ru/yandsearch?rpt=rad&text=url%3D%22{URL}%22%7Curl%3D%22{WWW_URL}%22");
//помен€лась маска запроса
define("YANDEX_MASK","http://yandex.ru/yandsearch?rpt=rad&text=url%3A{URL}%20%7C%20url%3A{WWW_URL}");
$yword_not_found = iconv("CP1251","UTF-8","»скома€ комбинаци€ слов нигде не встречаетс€");
define("Y_NOT_INDEX",$yword_not_found);
//наличие ссылки в индексе
define("YANDEX_URL","http://yandex.ru/yandsearch?text=%22{TEXT}%22%3C%3C%28url%3D%22{URL}%22%7Curl%3D%22{WWW_URL}%22%29&numdoc=1&ds=&rd=0&pag=u");

/**
* Ћюбимый €ндекс (NOT GAY)
*/
class seoYandex {

	/**
	* ѕолучение “»ц заданного урла
	*/
	function getYandexCY($url) {
		
		$uri = "http://bar-navig.yandex.ru/u?ver=2&url=".urlencode($url)."&show=1";
		
		$http = new llmHTTP();
		$page = $http->get($uri,$s,$c);
	
		preg_match("/<tcy rang=\"(.*)\" value=\"(.*)\"\/>/isU",$page,$cy);
		if (isset($cy[2])) return (int)$cy[2];
			else return -1;
	}
	
	/**
	* ≈сть ли заданный урл (без http) в индексе
	* ≈сли NULL - значит €ндекс на врем€ забанил наш IP
	*/
	function getYInIndex($url) {
	
		$yandex_path = YANDEX_MASK;
		if ($url{strlen($url)-1} == "/") $url = substr($url,0,strlen($url)-1);
		
		$pu = parse_url("http://".$url);
		$host = $pu['host'];
		$pos = strpos($host,"www.");
		//если нет подстроки www. то это не полный адрес
		if ($pos === false) {
		
			$yandex_path = str_replace("{URL}",$url,$yandex_path);
			$full_url = str_replace($host,"www.".$host,$url);
			$yandex_path = str_replace("{WWW_URL}",$full_url,$yandex_path);
		}
		else {
		
			//иначе она есть - надо вырезать
			$yandex_path = str_replace("{WWW_URL}",$url,$yandex_path);
			$tmp = "http://".$url;
			$short_url = str_replace("http://www.","http://",$tmp);
			$tmp = str_replace("http://","",$short_url);
			$yandex_path = str_replace("{URL}",$tmp,$yandex_path);
		}

		$http = new llmHTTP();
		$page = $http->get($yandex_path,$st,$cu);

		//если €ндекс нас забанил
		$pos = strpos($page,"я не робот");
		if ($pos!==false) return NULL;
		
		//тупит сам €ндекс
		$is_ok = strpos($page,"HTTP/1.1 200 OK");
		$pos = strpos($page,"HTTP/1.1 302 Found");
		if ($pos!==false && $is_ok === false) return NULL;

		$pos = strpos($page,Y_NOT_INDEX);
		if ($pos === false) return true;
			else return false;
	}
	
	/**
	* ѕроиндексирована ли ссылка €ндексом
	*/
	function isLinkInYandexIndex($full_url,$html) {
	
		$url  = str_replace("http://","",$full_url);
		
		//режем тэги
		$text = strip_tags($html);

		//формируем запрос к €ндексу
		$yandex_path = YANDEX_URL;
		if ($url{strlen($url)-1} == "/") $url = substr($url,0,strlen($url)-1);

		$pu = parse_url("http://".$url);
		$host = $pu['host'];
		$pos = strpos($host,"www.");
		//если нет подстроки www. то это не полный адрес
		if ($pos === false) {

			$yandex_path = str_replace("{URL}",$url,$yandex_path);
			$full_url = str_replace($host,"www.".$host,$url);
			$yandex_path = str_replace("{WWW_URL}",$full_url,$yandex_path);
		}
		else {

			//иначе она есть - надо вырезать
			$yandex_path = str_replace("{WWW_URL}",$url,$yandex_path);
			$tmp = "http://".$url;
			$short_url = str_replace("http://www.","http://",$tmp);
			$tmp = str_replace("http://","",$short_url);
			$yandex_path = str_replace("{URL}",$tmp,$yandex_path);
		}
		$yandex_path = str_replace("{TEXT}",urlencode($text),$yandex_path);

		$http = new llmHTTP();
		$page = $http->get($yandex_path,$st,$cu);

		//если €ндекс нас забанил
		$pos = strpos($page,"я не робот");
		if ($pos!==false) return NULL;
		
		//тупит сам €ндекс
		$pos = strpos($page,"HTTP/1.1 302 Found");
		if ($pos!==false) return NULL;

		$pos = strpos($page,Y_NOT_INDEX);
		if ($pos === false) $is_in_index = 1;
		else $is_in_index = 0;
		
		return $is_in_index;
	}
}
?>