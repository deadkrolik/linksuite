<?PHP
/**
* ����� ������������� ���-�������� �������� PR. ����� ������������� �� ���������� � �� ������ ��
* ���������� PR ����� ������. ��� ������� ��� �����, � �� ��� �����. �� �������, ��� ���� ���������
* �������������� �������� � ������� �� ������������.
* @author Dead Krolik
*/
class llmPRMeter {

	//�������� ��������, ����������� ��� �������� "�������" �������
	var $form_page = "";
	//�� ��������� ��� ������� ����, ������ ��������������
	var $max_urls  = 0;
	
	/**
	* ���� �� ���� �����������, ������ ���� ���������� � ��������� ��� *.PHP 
	* ����� ����� ����, �������� � ��������� � ������ ������� �� ������.
	*/
	function llmPRMeter() {
	
		$this->last_class_index = 0;
		$this->classes = array();
		$this->disabled_engines = array();
		
		//���� ��� ��������� ������ � ���� ����������
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
	* ���� ������ ��������� - ��������� ��������� ����� � tmp-���������� 
	* �� �������� ��������� �������� ���������.
	*/
	function checkConnect() {
	
		//���� ��� ���� BAD-���� � �������� ���������� ���������, ������� ���
		$path = llmServer::getPPath().'tmp/'.md5($this->host).'.BAD';
		if (file_exists($path)) {
		
			$last_time = file_get_contents($path);
			if ((time() - $last_time) < 3600) {
			
				//���� ������� ������ ������ ����, �� ������� ��� ���� ��� �� ����
				return false;
			}
			else {
			
				//���� ������, �� ��������� �������
				$http = new llmHTTP();
				$p = $http->get($this->start_url,$s,$cu);
				$pos = strpos($p,$this->unique_string);
				if ($pos === false) {
				
					//���� ����
					$f = fopen($path,"w");fwrite($f,time());fclose($f);
					return false;
				}
				else {
				
					//����� ��� ����, ����� � ����� ������
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
	* ������� ��� ������� - ��� �� ���������� ���� ����� �� ��������.
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
	* ���������� ���������� �����, ������� ����� ������� �� ��� ���� �����
	* �.�. ����� ���� �������� �������, ������� �� �����.
	*/
	function getMaxLoopURLS() {
	
		$ret = 0;
		foreach($this->classes as $index => $obj) {
		
			//���� �������� ���������� ������
			if (in_array($index,$this->disabled_engines)) continue;
			
			$ret += $obj->getMaxUrls();
		}
		
		return $ret;
	}
	
	/**
	* �������� ����� ������ ������
	*/
	function setUrls($u) {
	
		$this->work_urls = $u;
	}
	
	/**
	* ������� ������ ����� ������� ����� �� ���� ���
	*/
	function getMaxUrls() {
	
		return $this->max_urls;
	}
	
	/**
	* ����������, ��������� PR ��� ������ �����.
	*/
	function getPR($urls) {
	
		$calculated_urls = array();
		$global_size = sizeof($urls);$tmp_urls = $urls;
		
		//���������� �� ������� ������ �� �������, ������� �� ������ ����������
		foreach($this->classes as $index => $obj) {
		
			//���� �������� ���������� ������
			if (in_array($index,$this->disabled_engines)) continue;
			
			$max = $obj->getMaxUrls();
			
			//�� ���� �� ���
			if (!$max) continue;
			
			$work_urls = array_slice($tmp_urls,0,$max);
			$tmp_urls  = array_slice($tmp_urls,$max);
			
			$obj->setUrls($work_urls);
			$ret = $obj->getUrlsPR();
			
			//����� �����-��, ��������� ������
			if ($ret === false) {
			
				$this->disabled_engines[] = $index;
				continue;
			}
			
			//����� ����� ���������� ������ ��� => Ũ_PR
			//�������� ������
			foreach($ret as $url => $pr) {
			
				$calculated_urls[$url] = $pr;
			}
		}
		
		$this->last_class_index = $index;
		return $calculated_urls;
	}
}
?>