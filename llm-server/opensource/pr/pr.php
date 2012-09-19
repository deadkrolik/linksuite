<?PHP
/**
* Класс изнасилования веб-сервисов проверки PR. Очень рекомендуется не зарываться и не мучить их
* проверками PR очень сильно. Они сделаны для людей, а не для ботов. Но ресурсы, где явно запрещена
* автоматическая проверка в системе не используются.
* @author Dead Krolik
*/
class llmPRMeter {

	//хранится страница, запрошенная при проверке "живости" ресурса
	var $form_page = "";
	//по умолчанию для движков ноль, должны переопределить
	var $max_urls  = 0;
	
	/**
	* Весь из себя конструктор, читает свою директорию и выцепляет все *.PHP 
	* файлы кроме себя, инклудит и добавляет в список движков их классы.
	*/
	function llmPRMeter() {
	
		$this->last_class_index = 0;
		$this->classes = array();
		$this->disabled_engines = array();
		
		//ищем все доступные классы в этой директории
		$li = glob(llmServer::getPPath()."opensource/pr/*.php");

		foreach($li as $cfile) {
		
			if (basename($cfile) == basename(__FILE__)) continue;
								
			require_once($cfile);
			
			$cname = basename($cfile)."_PR";
			$cname = str_replace(".php","",$cname);
			$o = new $cname();

			if (!$o->checkConnect()) continue;
			
			$this->classes[] = $o;
		}
	}
	
	/**
	* Если движок отвалился - создается временная метка в tmp-директории 
	* со временем последней проверки состояния.
	*/
	function checkConnect() {
	
		//если уже есть BAD-файл с временем последнего обращения, смотрим его
		$path = llmServer::getPPath().'tmp/'.md5($this->host).'.BAD';
		if (file_exists($path)) {
		
			$last_time = file_get_contents($path);
			if ((time() - $last_time) < 3600) {
			
				//если разница меньше одного часа, то считаем что пока что не ожил
				return false;
			}
			else {
			
				//если больше, то проверяем коннект
				$http = new llmHTTP();
				$p = $http->get($this->start_url,$s,$cu);
				$pos = strpos($p,$this->unique_string);
				if ($pos === false) {
				
					//ваще беда
					$f = fopen($path,"w");fwrite($f,time());fclose($f);
					return false;
				}
				else {
				
					//вроде как ожил, можно и убить файлик
					unlink($path);
				}
			}
		}

		$http = new llmHTTP();
		$this->form_page = $http->get($this->start_url,$s,$cu);
		$pos = strpos($this->form_page,$this->unique_string);
		if ($pos === false) {
		
			$f = fopen($path,"w");fwrite($f,time());fclose($f);
			return false;
		}

		return true;
	}
	
	/**
	* Функция для движков - что бы отправлять свои формы на проверку.
	*/
	function sendSubmit($url,$vars) {
	
		$post_fields = array();
		foreach($vars as $k => $v) {
		
			$post_fields[] = "{$k}=".urlencode($v);
		}
		$str = implode("&",$post_fields);
		
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, llmConfig::get("PRMETER_CURL_TIMEOUT",9));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
		$result = curl_exec($ch);
		
		curl_close($ch);
		
		return $result; 
	}
	
	/**
	* Вычисление количества урлов, которые может сделать за раз весь класс
	* т.е. сумма всех активных движков, которые он нашел.
	*/
	function getMaxLoopURLS() {
	
		$ret = 0;
		foreach($this->classes as $index => $obj) {
		
			//если выключен вследствие ошибки
			if (in_array($index,$this->disabled_engines)) continue;
			
			$ret += $obj->getMaxUrls();
		}
		
		return $ret;
	}
	
	/**
	* Передача урлов внутрь движка
	*/
	function setUrls($u) {
	
		$this->work_urls = $u;
	}
	
	/**
	* Сколько движок может сделать урлов за один раз
	*/
	function getMaxUrls() {
	
		return $this->max_urls;
	}
	
	/**
	* Собственно, получение PR для списка урлов.
	*/
	function getPR($urls) {
	
		$calculated_urls = array();
		$global_size = sizeof($urls);$tmp_urls = $urls;
		
		//откусываем от каждого движка по столько, сколько он сможет обработать
		foreach($this->classes as $index => $obj) {
		
			//если выключен вследствие ошибки
			if (in_array($index,$this->disabled_engines)) continue;
			
			$max = $obj->getMaxUrls();
			
			//ну мало ли что
			if (!$max) continue;
			
			$work_urls = array_slice($tmp_urls,0,$max);
			$tmp_urls  = array_slice($tmp_urls,$max);
			
			$obj->setUrls($work_urls);
			$ret = $obj->getUrlsPR();
			
			//косяк какой-то, выключаем движок
			if ($ret === false) {
			
				$this->disabled_engines[] = $index;
				continue;
			}
			
			//иначе здесь содержится список УРЛ => ЕЁ_PR
			//добиваем массив
			foreach($ret as $url => $pr) {
			
				$calculated_urls[$url] = $pr;
			}
		}
		
		$this->last_class_index = $index;
		return $calculated_urls;
	}
}
?>