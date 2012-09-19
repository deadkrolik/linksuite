<?PHP
//проверка наличия страницы в индексе
define("GOOGLE_MASK","http://www.google.com/search?client=opera&rls=ru&q=site:{URL}&sourceid=opera&ie=utf-8&oe=utf-8");

/**
* Класс гугло-параметров
*/
class seoGoogle {

	//     ------------ Методы для расчета PR ---------------
	//     взял отсюда: http://zhilinsky.ru/wp-content/uploads/files/Other/Development/pagerank.phps
	//     --------------------------------------------------
	function StrToNum($Str, $Check, $Magic) {
		$Int32Unit = 4294967296;

		$length = strlen($Str);
		for ($i = 0; $i < $length; $i++) {
			$Check *= $Magic;
			if ($Check >= $Int32Unit) {
				$Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
				$Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
			}
			$Check += ord($Str{$i});
		}
		return $Check;
	}

	function HashURL($String) {
		$Check1 = seoGoogle::StrToNum($String, 0x1505, 0x21);
		$Check2 = seoGoogle::StrToNum($String, 0, 0x1003F);

		$Check1 >>= 2;
		$Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
		$Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
		$Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);

		$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
		$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );

		return ($T1 | $T2);
	}

	function CheckHash($Hashnum) {
		$CheckByte = 0;
		$Flag = 0;

		$HashStr = sprintf('%u', $Hashnum) ;
		$length = strlen($HashStr);

		for ($i = $length - 1;  $i >= 0;  $i --) {
			$Re = $HashStr{$i};
			if (1 === ($Flag % 2)) {
				$Re += $Re;
				$Re = (int)($Re / 10) + ($Re % 10);
			}
			$CheckByte += $Re;
			$Flag ++;
		}

		$CheckByte %= 10;
		if (0 !== $CheckByte) {
			$CheckByte = 10 - $CheckByte;
			if (1 === ($Flag % 2) ) {
				if (1 === ($CheckByte % 2)) {
					$CheckByte += 9;
				}
				$CheckByte >>= 1;
			}
		}

		return '7'.$CheckByte.$HashStr;
	}

	function getch($url) { return seoGoogle::CheckHash(seoGoogle::HashURL($url)); }

	function getpr($url) {
		
		$ch = seoGoogle::getch($url);
		
		$http = new llmHTTP();
		$page = $http->get("http://toolbarqueries.google.com/search?client=navclient-auto&ch=$ch&features=Rank&q=info:$url",$s,$c,false);
		
		$pos = strpos($page, "Rank_");
		if($pos === false) {

			$pr = -1;
		}
		else
		{
			$pr=substr($page, $pos + 9);
			$pr=trim($pr);
			$pr=str_replace("\n",'',$pr);
		}

		return $pr;
	}
	//     -------------- Конец методов расчета PR
	
	/**
	* Есть ли страница в кэше гугля
	*/
	function getGInIndex($url) {
	
		$http = new llmHTTP();
		$mask = GOOGLE_MASK;
		$mask = str_replace("{URL}",$url,$mask);
		$page = $http->get($mask,$st,$cu);
		
		preg_match("|<a href=\"({$url})\".*>|Ui",$page,$mt);
		if(isset($mt[1]) && trim($mt[1])==$url) return true;
			else return false;
	}
	
	/**
	* Проиндексирована ли ссылка гуглем
	*/
	function isLinkInGoogleIndex($full_url,$html) {
	
		$html = strip_tags($html);
		
		$http = new llmHTTP();
		$page = $http->get("http://www.google.com/search?client=opera&rls=ru&q=site:{$full_url}+%22".urlencode(iconv("CP1251","UTF-8",$html))."%22&sourceid=opera&ie=utf-8&oe=utf-8",$st,$cu);
		preg_match("|<a href=\"({$full_url})\".*>|Ui",$page,$mt);

		if(isset($mt[1]) && trim($mt[1])==$full_url) return true;
			else return false;
	}
}
?>