<?PHP

define("LLMHTTP_STATUS_OK",1);
class llmHTTP {

	function llmHTTP($cookie = NULL) {
	
		if ($cookie) $this->_cookie = $cookie;
			else $this->_cookie = NULL;
	}
	
	function checkLLMInstalled($page) {
	
		if ($this->_cookie) {
		
			$needle = "<!-- ".md5($this->_cookie)." -->";
			$pos = strpos($page,$needle);
			if ($pos!==false) return true;
				else return false;
		}
		else {
		
			return true;
		}
	}
	
	function get($url,& $status, & $current_url,$incl_header = true) {
	
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		if ($this->_cookie) curl_setopt($this->curl, CURLOPT_COOKIE, $this->_cookie);
		@curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);//�������� ������
		if ($incl_header) curl_setopt($this->curl, CURLOPT_HEADER, 1);
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_VERBOSE, 1);
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'Opera/9.00 (Windows NT 5.1; U; ru)');
		curl_setopt($this->curl, CURLOPT_REFERER, $url);
		
		if (llmConfig::get("USE_PROXY",false)) {
		
			$file = file(llmServer::getPPath()."conf/proxy_list.txt");
			$sz = sizeof($file);
			$proxy = trim($file[rand(0,$sz-1)]);
		
			curl_setopt($this->curl, CURLOPT_PROXY, $proxy);
		}
		
		$out = curl_exec($this->curl);
		
		//��������� ������
		$status = LLMHTTP_STATUS_OK;
		$errno  = curl_errno($this->curl);
		if ($errno > 0) {
		
			$status = curl_error($this->curl);
		}
		
		$current_url = curl_getinfo($this->curl,CURLINFO_EFFECTIVE_URL);
		curl_close($this->curl);
		return $out;
	}
	
	function getPageLinks($page,$current_url) {
	
		//��������� ��� base, ������������ ���� � ����� ������� ��� ������
		preg_match("|<base(.*)href=['\"]?([^ \t'\"]+)['\"].*>|i",$page,$bh);
		if (isset($bh[2])) $this->_base_href = trim($bh[2]);

		//������ ������ ����
		preg_match("|<body.*>(.*)</body>|msi",$page,$mt);
		if (!isset($mt[0])) return array();
		$next = $mt[0];unset($mt);
		
		//�������� ����������� ���� ��� �� ����� ������
		$next = preg_replace("|<\!\-\-.*\-\->|Umsi","",$next);
		$bad_tags = array("script","noindex","style","textarea","select");
		foreach($bad_tags as $tag) {
		
			$next = preg_replace("|<$tag.*>.*</$tag>|Umsi","",$next);
		}
		
		//����� ������
		$page_links = array();
		preg_match_all("|<a(.*)>.*</a>|Umsi",$next,$mt);
		foreach ($mt[1] as $inner_links) {
		
			preg_match("|.*href([ ]+)?=['\"]?([^ \t'\"]+)['\"]?.*|i",$inner_links,$mt2);
			$link = isset($mt2[2]) ? trim($mt2[2]) : "";unset($mt2);

			//������ �������
			if (!$link) continue;
			
			//�������� ���� �������
			$pos = strpos($link,"mailto:");
			if ($pos !== false) continue;
			
			//���� �� ����� ��� ��������
			$pos = strpos($link,"https://");
			if ($pos !== false) continue;
			
			//����������
			$pos = strpos($link,"javascript:");
			if ($pos !== false) continue;
			
			//����� �����
			$pos = strrpos($link,"#");
			if ($pos!==false) $link = substr($link,0,$pos);

			//���� �������������
			$link = str_replace("&amp;","&",$link);
			
			//��� SMF ����� ��� PHPSESSID
			$pos = strpos($link,"PHPSESSID=");
			if ($pos!==false) {
			
				$link = preg_replace("|PHPSESSID=[a-z0-9]+|i","",$link);
				$link = str_replace("?&","?",$link);
			}
			
			//��� osCommerce ����� ��� osCsid
			$pos = strpos($link,"osCsid=");
			if ($pos!==false) {
			
				$link = preg_replace("|osCsid=[a-z0-9]+|i","",$link);
				$link = str_replace("?&","?",$link);
			}

			
			if ($link) $page_links[] = $this->getAbsURL2($current_url,$link);
		}
		unset($next);
		
		//���������� ������ ����������
		return array_unique($page_links);
	}
	
	function getRobotsTXTRules($base_url,$answer) {
	
		$lines = explode("\n",$answer);$check_rules = false;$ret = array();
		foreach($lines as $line) {
		
			$line = trim($line);
			if (!$line) continue;
			
			preg_match("|^user\-agent\:(.*)\$|Umsi",$line,$mt);
			if (isset($mt[1])) {
			
				if ($check_rules) {
				
					//�.�. � ����� ����� ������ � ����� ������ ������ ��� ������ �����, ���� ������
					break;
				}
				
				if (trim($mt[1]) == "*") {
				
					//�.�. ��� ������� ��� ���� �������, ����� ��� ������������
					$check_rules = true;
					continue;
				}
			}
			
			//���� �� ��������� �������� - ��������� ������ � ����
			if ($check_rules) {
			
				preg_match("|^Disallow:(.*)\$|Umsi",$line,$lm);
				if (isset($lm[1])) {
				
					//���� ����� �������� ���-�� ����
					$durl = trim($lm[1]);
					if ($durl!='') {
						
						//��� �������, ��� �� ����� ���� ����
						if ($durl{0} == '/') $durl = substr($durl,1);
						//��� �� ������ ����� ���� ������
						$ret[] = $base_url.$durl;
					}
				}
			}
		}
		
		return $ret;
	}
	
	function getAbsURL2($base,$url) {
	
		if ($url == "." || $url == "") return $base;
		
		$adress = $base;
		$href   = $url;
		
		for ($i=strlen($adress)-1;$i>0 && $adress{$i}!='/';$i--) {}
		$base_adress = substr($adress,0,$i+1);
		//���� ����� ��� BASE
		if (isset($this->_base_href)) $base_adress = $this->_base_href;
		
		$adress_parsed = parse_url($adress);

		$href_upper = strtoupper($href);
		if (strpos($href_upper,'HTTP://')===0) $href2 = $href;//������ stripos
		//if (stripos($href,'http://')===0) $href2 = $href;
		elseif($href{0}=='#') $href2 = $base_adress;//$base_adress;
		elseif($href{0}=='.' && isset($href{1}) && $href{1}=='/') $href2 = $base_adress.substr($href,2);//$base_adress.substr($href,2);
		elseif($href{0}=='/') $href2 = 'http://'.$adress_parsed['host'].$href;
		else $href2 = $base_adress.$href;//$base_adress.$href;
		
		return $href2;
	}

	function getInnerLinks($site,$links) {
	
		$inner_links = array();
		foreach($links as $link) {
		
			$pos = strpos($link,$site->url);
			if ($pos !== false) $inner_links[] = $link;
		}
		
		return $inner_links;
	}
	
	function getOutherLinks($site,$links) {
	
		$inner_links = array();
		//���� ���� ������ ��� ����� �� �����, �� ���� ������� ������, ���� http://site.ru/  <<-- ����
		//� ��� http://site.ru ���� ���� ������ ��� ����
		$url_no_slash = false;
		//�������� ���� ����
		if (substr($site->url,strlen($site->url)-1,1) == "/") $url_no_slash = substr($site->url,0,strlen($site->url) - 1);

		foreach($links as $link) {
		
			$pos  = strpos($link,$site->url);
			$pos2 = $url_no_slash === false ? true : strpos($link,$url_no_slash) === false;
			if ($pos === false && $pos2) $inner_links[] = $link;
		}

		return $inner_links;
	}
	
	/**
	* ���������� ����� �������� �������� ��� ��������
	*/
	function getPageTitle(& $page) {
	
		preg_match("|<head>.*<title>(.*)</title>.*</head>|Umsi",$page,$mt);
		
		$title = isset($mt[1]) ? $mt[1] : "";
		return trim($title);
	}
	
	function saveIndexedPage($site,$links,$nesting,$url,$page_title="") {
	
		//������ ���� �����, ����������� � ������� ������ �����
		//��� �� ������� ��� ���� ������ �� �������������� � �������
		static $saved_in_this_session = array();
		//�������� � �������������� �����������, ��� �� ��� ���� �� �������
		static $recounted_in_this_session = array();
		
		$database = llmServer::getDBO();
		
		//������� ��������� �� ������
		$url = str_replace($site->url,'',$url);
		if ($url == '') $url = "/";//�����
		
		$inner_links_count  = sizeof($this->getInnerLinks($site,$links));
		$outher_links_count = sizeof($links) - $inner_links_count;
		$url_hash           = md5($url);
		
		$page_title = $database->getEscaped(iconv($site->charset,"CP1251",$page_title));
		
		//��������� ���� �� ���� ��� � ����
		$query = "SELECT id FROM pages WHERE url_hash = '{$url_hash}' AND site_id = '{$site->id}'";
		$database->setQuery($query);
		$ids = $database->loadResultArray();
		//���� ����� ������ ����, �� �������� ��� �������������� - ���� �������� ��������� ���������
		if (sizeof($ids) > 0) {
		
			if (!isset($saved_in_this_session[$url_hash]) && !isset($recounted_in_this_session[$url_hash])) {
			
				//�� ���� ��� �������� �� �������� ����� � �������� ��������� ���������� (��, ����� � ����� ����������, ����
				//� ��� � �� ����� ����� � ����� ��� ���� � ����, ����� �����)
				$database->ping();
				$database->setQuery("UPDATE pages SET time_last_index='".time()."',title='".$page_title."',external_links_count='{$outher_links_count}',pr = '-1'  WHERE url_hash = '{$url_hash}' AND site_id = '{$site->id}'");
				$database->query();
				
				$recounted_in_this_session[$url_hash] = true;
			}
			
			return;
		}
		
		//����� ��������
		$database->ping();
		$database->setQuery("INSERT INTO pages (site_id,url,nesting,external_links_count,time_last_index,pr,url_hash,links_on_page,status,is_in_yandex_index,is_in_google_index) VALUES ('$site->id','".addslashes($url)."','{$nesting}','{$outher_links_count}','".time()."',-1,'{$url_hash}','0','0','-1','-1')");
		$database->query();
		
		$saved_in_this_session[$url_hash] = true;
	}
	
	function checkContentType($page) {
	
		static $loaded_types = array();
		
		//������ �������
		if (sizeof($loaded_types) == 0) {
		
			$tmp = explode(',',llmConfig::get("CONTENT_TYPES","text/html,text/xhtml"));
			foreach($tmp as $t) $loaded_types[] = trim($t);
		}
		
		preg_match("|Content\-Type: ([^ ;]+).*\n|",$page,$mt);
		if (isset($mt[1])) {
		
			$ct = trim($mt[1]);
			//������ ������ ������
			$pos = strpos($ct,"\n");
			if ($pos!==false) {
			
				list($t) = explode("\n",$ct);
				$ct = trim($t);
			}

			if (in_array($ct,$loaded_types)) return true;
				else return false;
		}
		else {
		
			//������ ���
			return true;
		}
	}
	
	function skipExtension($url) {
	
		static $loaded_extsnsions = NULL;
		
		if ($loaded_extsnsions === NULL) {
		
			$tmp = explode(",",llmConfig::get("SKIP_EXTENSIONS"));
			foreach($tmp as $t) $loaded_extsnsions[] = strtoupper(trim($t));
		}
		
		$pos = strrpos($url,".");
		if ($pos!==false) {
		
			$ext = strtoupper(@substr($url,$pos+1));
			//���� ��� ���� � ������� �����������, �� ��� ���� ����������
			if (in_array($ext,$loaded_extsnsions)) return true;
				else return false;
		}
		
		//���������� ����
		return false;
	}
}

class googlepr {
	
	function get($url) {
		
		return seoGoogle::getpr($url);
	}
}
?>