<?PHP
//���������� �������
define("LLM_PAGE_STATUS_ACTIVE",0);
define("LLM_PAGE_STATUS_UNACTIVE",1);
//���������� ������
define("LLM_LINK_STATUS_ACTIVE",0);
define("LLM_LINK_STATUS_UNACTIVE",1);

/**
* ���������� ��� ����� ���������� ������.
*/
function getLLMCLIENTDir() {

	$main = dirname(llmServer::getPPath());
	$dirs = glob($main.'/*',GLOB_ONLYDIR);$is_found = false;
	foreach($dirs as $dir) {
	
		if (file_exists($dir.'/get.php') && file_exists($dir.'/do_not_delete.txt')) {
		
			$a = file_get_contents($dir.'/do_not_delete.txt');
			if ($a != 'llm-client') continue;
			
			$is_found = basename($dir);
		}
	}
	
	return $is_found ? $is_found : NULL;
}

class ftpClient {

	static $inst = NULL;
	static $user;
	static $pass;
	static $host;
	static $dir;
	
	function getInstance() {
	
		if (ftpClient::$inst == NULL) {
		
			require_once("ftp.php");
			
			ftpClient::$inst = JFTP::getInstance(ftpClient::$host,21,NULL,ftpClient::$user,ftpClient::$pass);
		}
		
		return ftpClient::$inst;
	}
	
	function store_file($dir,$local,$remote) {
	
		$ftp = ftpClient::getInstance();
		$ftp->chdir($dir);
		$ftp->store($local,$remote);
	}
	
	function ftp_glob($dir) {
	
		$ftp = ftpClient::getInstance();
		$ftp->chdir($dir);
		$n = $ftp->listDetails();
		
		$ret = array();
		foreach($n as $i) {
			
			if ($i['type']==1) $ret[]=$i['name'];
		}
		
		return $ret;
	}
}

class llmTitler {

	static $titles = array();
	
	function put($string) {
	
		llmTitler::$titles[] = $string;
	}
	
	function get() {

		llmTitler::$titles[] = 'LinkSuite';
		return implode(" - ",llmTitler::$titles);
	}
}

class llmServer {

	function getWPath() {

		return llmConfig::get("HTTP_SITE");
	}
	
	function getHPath($mod,$task,$add = '') {
	
		return llmServer::getWPath().'index.php?mod='.$mod.'&task='.$task.($add ? "&".$add : '');
	}
	
	function getPPath() {
	
		static $abs_path;
		
		if (!$abs_path) {
		
			$abs_path = dirname(dirname(__FILE__)).'/';
		}
		
		return $abs_path;
	}
	
	function showError($msg,$die = true) {
	
		//trigger_error($msg,E_USER_NOTICE);
		echo "<pre>Error: $msg\n";
		
		if(function_exists('debug_backtrace')) {
			foreach(debug_backtrace() as $back) {
				if(@$back['file']) {
					echo "  ->".$back['file'].':'.$back['line']."\n";
				}
			}
		}
		
		echo "</pre>LERROR:database error";
		
		if ($die) die();
	}
	
	function getDBO() {
	
		static $dbo = null;
		
		if (!$dbo) {
		
			require_once(llmServer::getPPath().'includes/database.php');
			
			$dbo = new database(llmConfig::get("MYSQL_HOST"),llmConfig::get("MYSQL_USER"),llmConfig::get("MYSQL_PASSWORD"),llmConfig::get("MYSQL_DB"));
		}
		
		return $dbo;
	}
	
	function insertBlock($block_name) {
	
		$block_file = llmServer::getPPath()."blocks/{$block_name}.php";
		if (!file_exists($block_file)) llmServer::showError("Block `$block_name` not found");
		
		ob_start();
		include($block_file);
		$block = ob_get_contents();
		ob_end_clean();
		
		echo $block;
	}
	
	function redirect($url,$msg) {
	
		if (trim($msg)) {
		
		 	if (strpos( $url, '?' )) {
				$url .= '&statusmsg=' . urlencode( $msg );
			} else {
				$url .= '?statusmsg=' . urlencode( $msg );
			}
		}
		
		if (headers_sent()) {
			
			echo "<script>document.location.href='$url';</script>\n";
		} else {
			
			@ob_end_clean(); // clear output buffer
			header( 'HTTP/1.1 301 Moved Permanently' );
			header( "Location: ". $url );
			exit();
		}
	}
	
	function deleteOldFiles($mask,$hours = 24) {
	
		$arh = glob(llmServer::getPPath().$mask);
		foreach($arh as $ar) {
		
			$st = stat($ar);
			$t = $st['mtime'];
			//������� ������ ��� X-�����
			if ((time() - $t) > 60*60*$hours) unlink($ar);
		}
	}
}

define("LLM_LOG_ERROR",1);
define("LLM_LOG_WARNING",2);
define("LLM_LOG_NOTICE",3);

class llmLogger {

	function llmLogger($fname,$mode = "w") {
	
		$this->fname    = $fname;
		$this->messages = array();
		$this->mode     = $mode;
	}
	
	function log($log_status,$msg) {
	
		$db = debug_backtrace();$file = basename($db[0]['file']);
		switch($log_status) {
		
			case LLM_LOG_ERROR:
				$ls = " ERROR ";
				break;
			case LLM_LOG_WARNING:
				$ls = "WARNING";
				break;
			case LLM_LOG_NOTICE:
				$ls = "NOTICE ";
				break;
			default:
				$ls = $log_status;
				break;
		}
		$this->messages[] = "[$file][$ls] $msg";
	}
	
	function save() {
	
		if ($this->messages) {
			
			$f = fopen(llmServer::getPPath().'tmp/logs/'.$this->fname,$this->mode);
			fwrite($f,"\n---------\n".date("d/m/Y H:i:s",time())."\n".implode("\n",$this->messages));
			fclose($f);
		}
	}
}

//�������, ������� ����� ����������� ������������ ������
function err_handler_config($errno, $errstr, $errfile, $errline) {

	preg_match("|Error parsing.*on line ([0-9]+)|Umsi",$errstr,$mt);
	if (isset($mt[1])) {
	
		echo "���� ������������ �����������, ������ � ������ ".$mt[1];
		die;
	}
}
class llmConfig {

	function get($name,$def_value = NULL, $conf_file = 'main') {
	
		static $conf = array();
		
		if (!isset($conf[$conf_file])) {
		
			set_error_handler("err_handler_config");
			$pa = parse_ini_file(llmServer::getPPath().'conf/'.$conf_file.'.ini');
			restore_error_handler();
			$conf[$conf_file] = $pa;
		}
		
		if (isset($conf[$conf_file][$name])) return $conf[$conf_file][$name];
		elseif($def_value!==NULL) {

			return $def_value;
		}
		else {
		
			llmServer::showError("UNDEFINED CONF VARIABLE $name IN FILE $conf_file");
		}
	}
}

function showHelp($msg,$class='help') {

	return "<a href='#' onmouseover=\"Tip('{$msg}')\" onmouseout=\"UnTip()\" class=\"{$class}\"></a>";
}

function getParam($name,$default = NULL) {

	if (isset( $_REQUEST[$name] )) {

		$return = $_REQUEST[$name];
		
		if (!get_magic_quotes_gpc()) {
			$return = addslashes( $return );
		}
		
		return $return;
	}
	else {
	
		return $default;
	}
}

class llmHTML {

	function formBottom($task,$title,$colspan=2,$class='') {
	
		global $mod;
		
		return "<tr><td colspan={$colspan} align='center'>
		<input type='hidden' name='task' value='$task'>
		<input type='hidden' name='mod' value='{$mod}'>
		<input type='submit' value='  $title  ' ".($class ? "class='{$class}'" : "").">
		</td></tr>";
	}
	
	function trimURL($url,$size=60) {
	
		return strlen($url)>$size ? substr($url,0,$size).'...' : $url;
	}
	
	/**
	* ������� ������� ���������, � ����� �� ������ ���� �������, ��� ����� ������ �� ����� ����� ������ (�)
	* 
	* ������ ������ ������� ���-�� ������ - ��� ���� ������� ������� ��������. �� ����� � ����� �����, ����� 
	* ������� ����� ����� ��������� �� ������ ����������� ��� ������ ����� � ���� ������. � � �������� �� ������
	* �������, ��� ��� ���� ������ ��� �������� �� ������. �� �� ���� �� ����� � "���� ������"(�) ������� ����
	* �� ����-����, ����� ���� �� ������ ����, �������� ���� ����� ����� ������ ����.
	*/
	function modifyLinks(& $links,$site) {
	
		//���� ������ ���, �� ��� ������ ���-�� ��������������
		if (sizeof($links) == 0) return;
		
		$fpath  = llmServer::getPPath().'data/links.txt';
		if (!file_exists($fpath)) {
		
			//�������, � ���� �� ����������, �� �������� �� ������ ������� �� ��������� �����
			$f = @fopen($fpath,"w");
			if (!$f) {
			
				echo "LERROR: check permissions on data directory";
				exit;
			}
			fclose($f);
		}
		$real_links = file($fpath);
		
		foreach($real_links as $site_link) {
		
			$site_link = trim($site_link);
			if (!$site_link) continue;
			
			@list($site_id,$html,$pages_url) = @explode("<<<>>>",$site_link);
			
			if ($site_id == $site->id) {

				//�������� �����-�� ����, ���� ����� ( ��� ��� ��������, ���� )
				if ($html == "---") return;
				
				$elem = new stdClass();
				$elem->html             = $html;
				$elem->pages_url        = $pages_url;
				$elem->sites_url        = $site->url;
				$elem->links_delimiter  = $site->links_delimiter;
				$elem->css_class        = $site->css_class;
				$elem->charset          = $site->charset;

				$links[] = $elem;
				
				return;
			}
		}
		
		//���� �� �� ��� ��� ���, �� ��� ����� ����� ������ ��� �� �������
		$database = llmServer::getDBO();
		
		//� ��������������� �� ����, ���� ���, �� �� ������ ������
		$database->setQuery("SELECT * FROM pages WHERE site_id = '{$site->id}'");
		$pages = $database->loadObjectList();
		if (sizeof($pages) == 0) return;

		//�������� ���� �� ������� ��� ����������, ������� ������� ���
		$database->setQuery("SELECT * FROM pages WHERE site_id = '{$site->id}' AND nesting = 2 LIMIT 1");
		$pages = $database->loadObjectList();
		if (sizeof($pages) == 0) {
		
			//����� ������� ������ �������
			$database->setQuery("SELECT * FROM pages WHERE site_id = '{$site->id}' AND nesting = 1 LIMIT 1");
			$pages = $database->loadObjectList();
			
			if (sizeof($pages) == 0) {
			
				//���� ���� ��� ���, �� �� ����� �� �����, �� ������� ���, ������ �������� ��� ���� ����
				$f = fopen($fpath,"w");
				fwrite($f,"{$site->id}<<<>>>---\n");
				fclose($f);
			}
		}
		
		//������ �� ���� �������� � ����� ��������� ������
		$page = $pages[0];
		
		//��������� html-�����
		//��������� ����� � ��������
		$my_sites = array("http://linksuite.ru" => array("hello/news","hello/demo","hello/otziv","hello/support","docs/"),"http://dead-krolik.info" => array("joomla_software/","textpattern_software/","coding/","joomla/","web/"));
		
		//��������� �����
		$my_html  = array("http://linksuite.ru" => "������ ������������,������������ ������,������������ ����������,������������ ��� ��,���������� ������,������ ������������ ����������� ������,������ sape,������ miralinks,������������,������ ���������� ��������,��� ������������� ����,��� ������� ������������,��� �������� �������������,��� ������������� ��������,sape ��� ����,��������� sape,php ������ ������������,��� �������������,������ ��� ����������,��������� ������������,���������� ������������,linksuite,link suite,������ ������ sape,���������� ��������,������������� �����,������ ��� ������������ ����� ������,������������ ������� ������", "http://dead-krolik.info" => "Dead Krolik");
		
		$domain = array_rand($my_sites);
		$tmp    = $my_sites[$domain];
		$page_  = $tmp[rand(0,sizeof($tmp)-1)];
		$keys   = explode(",",$my_html[$domain]);
		
		$key    = $keys[rand(0,sizeof($keys) - 1)];
		$uurl   = $domain.'/'.$page_;
		$html   = "&nbsp;  <a href='{$uurl}'>{$key}</a> &nbsp;";
		
		$f = fopen($fpath,"w");
		fwrite($f,"{$site->id}<<<>>>{$html}<<<>>>{$page->url}\n");
		fclose($f);
		
		return;
	}
}

define("LLM_GROUP_ADMINISTRATOR","ADMINISTRATOR");
define("LLM_GROUP_GUEST","GUEST");

class llmAuth {

	function checkLogged() {
	
		//���� ������� �����, �� ��������� ���� ������������
		$login = (int)getParam('login');	
		if ($login) llmAuth::logIn();
		
		//���� ������� �����, �� ��������� ���� ������������
		$logout = (int)getParam('logout');	
		if ($logout) llmAuth::logOut();
		
		$cook = isset($_COOKIE['llm_key']) ? $_COOKIE['llm_key'] : '';

		if (!$cook) $auth = false;
			else {
		
				//����� ��������� ������ � ��
				$cook = preg_replace("|[^a-z0-9]|","",$cook);
				
				$database = llmServer::getDBO();
				
				$addr = long2ip(ip2long($_SERVER['REMOTE_ADDR']));
				$session_id = $addr.':'.$cook;
				$database->setQuery("SELECT * FROM users WHERE session_id = '{$session_id}'");
				$users = $database->loadObjectList();

				if (sizeof($users) > 0) {
				
					//������ ������������
					$auth = true;
					
					//�����������, ���� �
					$database->setQuery("OPTIMIZE TABLE links");
					$database->query();
					$database->setQuery("OPTIMIZE TABLE pages");
					$database->query();
					$database->setQuery("OPTIMIZE TABLE sites");
					$database->query();
					$database->setQuery("OPTIMIZE TABLE projects");
					$database->query();
					$database->setQuery("OPTIMIZE TABLE static_code");
					$database->query();
					$database->setQuery("OPTIMIZE TABLE articles");
					$database->query();
				}
				else {
				
					$auth = false;
				}
			}
		//����� ������� �������� �� ���� � ���� ���, ���������� �����
		if (!$auth) {

			$form = "
			<html>
			<head>
				<link href='".llmServer::getWPath()."template/login.css' rel='stylesheet' type='text/css' />
				<title>�����������</title>
			</head>
			<body>
		<form action='".llmServer::getWPath()."index.php?login=1' method='post'>
	<div id='ctr1' align='center'>
		<div class='login'>
		".($login ? "<font color=red>��� ������������ ��� ������ �� �����</font>" : "")."
		".($logout ? "<font color=green>�� ����� �� �������</font>" : "")."
			<div class='login-form'>
				<form action='index.php' method='post' name='loginForm' id='loginForm'>
					<div class='form-block'>
							�����
							<input name='username' id='username' type='text' class='inputbox' size='15' value='";
			
			$namex = getParam('username');
			if ($namex) $form .= htmlspecialchars($namex);

			$form .= "' />
							������
							<input name='pass' type='password' class='inputbox' size='15' />
						<div align='center'>
							<input type='submit' name='submit' class='button' value='�����' />
						</div>
					</div>
				</form>
			</div>
			<div class='clr'></div>
		</div>
	</div>
	</form>
			</body>		
			</html>";
			
			echo $form;
			exit();
		}
	}
	
	/**
	* ������������ ���� ����� � ����������� �������
	*/
	function logIn() {
	
		$database = llmServer::getDBO();
		
		$name = getParam('username');
		$pass = getParam('pass');

		if (!$name || !$pass) return false;
		
		$database->setQuery("SELECT * FROM users WHERE username='{$name}'");
		$users = $database->loadObjectList();

		if (sizeof($users) == 0) return false;
		$user = $users[0];
						
		list($salt,$hash) = explode(":",$user->password);
		
		if (md5($salt.$pass) == $hash) {
		
			//��� ������� ���� ���� ������ ���������
			$rand_sess = md5(rand().rand().rand());
			
			//������ ����
			setcookie('llm_key',$rand_sess);
			$_COOKIE['llm_key'] = $rand_sess;
			
			$addr = long2ip(ip2long($_SERVER['REMOTE_ADDR']));
			
			//������� � ����
			$database->setQuery("UPDATE users SET session_id = '{$addr}:{$rand_sess}' WHERE id = '{$user->id}'");
			$database->query();
		}
		else {
		
			return false;
		}
	}
	
	function logOut() {
	
		$database = llmServer::getDBO();
		
		$user = llmAuth::getUser();
		
		if ($user) {
			
			//������ ������ � ��
			$database->setQuery("UPDATE users SET session_id = '' WHERE id = {$user->id}");
			$database->query();
		}
		
		//� ����
		setcookie('llm_key',"");
		$_COOKIE['llm_key'] = "";
	}
	
	function getUser() {
	
		static $saved_user = NULL;
		
		if ($saved_user === NULL) {
			
			$database = llmServer::getDBO();
			
			$cook = isset($_COOKIE['llm_key']) ? $_COOKIE['llm_key'] : '';
			$cook = preg_replace("|[^a-z0-9]|","",$cook);
			
			$addr = long2ip(ip2long($_SERVER['REMOTE_ADDR']));
			$session_id = $addr.':'.$cook;
			$database->setQuery("SELECT * FROM users WHERE session_id = '{$session_id}'");
			$users = $database->loadObjectList();
			
			//�� ������ ����
			$saved_user = isset($users[0]) ? $users[0] : NULL;
		}
		
		return $saved_user;
	}
	
	function checkRules() {
	
		$user = llmAuth::getUser();
		
		//������ ����� ���
		if ($user->user_group == LLM_GROUP_ADMINISTRATOR) return true;
		
		global $mod,$task;
		$guest_rules = array(
		
			'builder'    => array('','testcharset'),
			'categories' => array('','edit_category'),
			'docs'       => array('*'),
			'hello'      => array('*'),
			'projects'   => array('','edit_project','show_links','start_setup','search_links'),
			'sites'      => array('','show_pages','get_links_on_page','show_our_links_on_page','edit_site'),
			'users'      => array('-'),
			'articles'   => array('view_site_articles','create_template','add_article')
		);
		
		foreach($guest_rules as $mod_name => $rules) {
		
			if ($mod_name == $mod) {
			
				//��������� ���
				if (sizeof($rules)==1 && $rules[0] == "*") return true;
				
				//��������� ���
				if (sizeof($rules)==1 && $rules[0] == "-") die("Not allowed for guest [1]");
				
				$allowed = false;
				
				foreach($rules as $allowed_task) {
				
					if ($allowed_task === $task) {
					
						$allowed = true;
						break;
					}
				}
				
				if (!$allowed) die("Not allowed for guest [2]");
				
				break;
			}
		}
	}
}

function buildCatSel($sefault_cat_id=0,$add = "") {

	static $categories = NULL;
	$database = llmServer::getDBO();
	
	if ($categories === NULL) {
		
		$database->setQuery("SELECT * FROM categories ORDER BY id");
		$categories = $database->loadObjectList('id');
	}
	
	$is_sel = $sefault_cat_id == 0 ? "selected='selected'" : "";
	
	$cat_sel = "<select name='category_id' {$add}><option value='0' $is_sel>�� �����</option>";
	foreach($categories as $c) {
	
		$is_sel = $sefault_cat_id == $c->id ? "selected='selected'" : "";
		$cat_sel .= "<option value='{$c->id}' $is_sel>{$c->name}</option>";
	}
	$cat_sel .= "</select>";
	
	return $cat_sel;
}

/**
* ����� ����������� ������������ �������/����������
*/
class shFileUpload {

	var $_error = null;

	/**
	* �� ���� �������� ��� $_FILES ����������, ���������� ���������� � �����
	*/
	function shFileUpload($var_name) {
	
		//����
		$this->bitmap        = NULL;
		$this->uploaded_file = NULL;
		$this->is_file_uploaded = false;
		
		//������������� ������ ���
		$this->_error = "";
		
		//� ���������� �� ����� ����
		if (!isset($_FILES[$var_name])) {
		
			$this->_error = "�� ������ ���� ��� �������� [1]";
			return false;
		}
		
		$fupload = $_FILES[$var_name];
		
		//������, ������������ ����� PHP
		if ($fupload['error'] != 0) {
		
			switch ($fupload['error']) {
			
				case UPLOAD_ERR_INI_SIZE:
					$msg = "������ ����� ������, ��� upload_max_filesize";
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$msg = "������ ����� ������, ��� MAX_FILE_SIZE � �����";
					break;
				case UPLOAD_ERR_PARTIAL:
					$msg = "���� �������� ������ ��������";
					break;
				case UPLOAD_ERR_NO_FILE:
					$msg = "�� ������ ���� ��� �������� [2]";
					break;
			}
			$this->_error = $msg;
			return false;
		}
		
		//����� �� ��� ��
		$this->uploaded_file = $fupload;
		$this->is_file_uploaded = true;
	}
	
	/**
	* � ��� �� �������� ���� ������
	*/
	function isFileUploaded() {
	
		return $this->is_file_uploaded;
	}
	
	/**
	* �������� - �������� �� ����������� ���� ������������
	*/
	function isImage() {
	
		$this->_error = "";
		
		//����������
		if (!$this->checkAllowedExtensions("JPG,GIF,JPEG,PNG")) {
			
			$this->_error = "���������������� ����������";
			return false;
		}
		
		//������� � GD
		$image_func = $this->_getImageCreateFunction();
		if (!$image_func) {
			
			$this->_error = "�������� ������� ��� � GD";
			return false;
		}
		
		//�������� ������� GD-������
		$this->bitmap = @$image_func($this->uploaded_file['tmp_name']);
		if (!$this->bitmap) {
		
			$this->_error = "���������� ��������� ������� imagecreatefrom... � �����������";
			return false;
		}
		
		return true;
	}
	
	/**
	* ���������� ��� �����, ������� ��� ������ � ����� ��� ��������
	*/	
	function getUploadedFileName() {
	
		if (!$this->isFileUploaded()) return NULL;
		
		return basename($this->uploaded_file['name']);
	}
	
	/**
	* ����������� �����, ������ � ��������� ������ ����������
	*/
	function copy($dest_file) {
	
		$this->_error = "";
		
		//���� ����� ����� ���������� ��� ��� - ������� �� ���������
		if (!$this->_createDirectory(dirname($dest_file))) {
		
			return false;
		}
		
		$result = @copy($this->uploaded_file['tmp_name'],$dest_file);
		
		return $result;
	}
	
	/**
	* ������� ����������� �����-����������� � �������� ������� ������������ � ���������������� � �������� ������ � ������
	*/
	function copyResized($dest_file,$width,$height) {
	
		$this->_error = "";
		
		//���� ����� ����� ���������� ��� ��� - ������� �� ���������
		if (!$this->_createDirectory(dirname($dest_file))) {
		
			return false;
		}
		
		//������ ������ �������� �� ������ ��������
		if (!$this->_createThumb($dest_file,$width,$height)) {
		
			return false;
		}
		
		return true;
	}
	
	/**
	* http://icant.co.uk/articles/phpthumbnails/
	* ����� ����������� �� ����� ��������� �������
	*/
	function _createThumb($dst_filename,$new_w,$new_h){
	
		$src_img = $this->bitmap;
	
		//���������������
		$old_x=imageSX($src_img);
		$old_y=imageSY($src_img);
		
		if ($old_x > $old_y) {
			$thumb_w=$new_w;
			$thumb_h=$old_y*($new_w/$old_x);
		}
		if ($old_x < $old_y) {
			$thumb_h=$new_h;
			$thumb_w=$old_x*($new_h/$old_y);
		}
		if ($old_x == $old_y) {
			$thumb_w=$new_w;
			$thumb_h=$new_h;
		}
	
		//�����������
		$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
	
		switch($this->getNormalExtension()) {
		
			case 'png':
				imagepng($dst_img,$dst_filename);
				break;
			case 'jpg':
			case 'jpeg':
				imagejpeg($dst_img,$dst_filename);
				break;
			case 'gif':
				imagegif($dst_img,$dst_filename);
				break;
			default:
				$this->_error = "���������������� ������ �����";
				return false;
		}
		
		imagedestroy($dst_img);
		imagedestroy($src_img);
		
		return true;
	}
	
	/**
	* ���������� ������� ����������, ���� �� �� ����������
	* 
	* http://snipplr.com/view/2685/make-directory-recursively/
	*/
	function _createDirectory($path, $rights = 0777) {
	
		if (!@is_dir($path)) {
			
			$folder_path = array($path);
			
		} else {
			
			return true;
		}

		while( !@is_dir(dirname(end($folder_path)) )
		&& dirname(end($folder_path)) != '/'
		&& dirname(end($folder_path)) != '.'
		&& dirname(end($folder_path)) != '')
		{
			array_push($folder_path, dirname(end($folder_path)));
		}

		while($parent_folder_path = array_pop($folder_path)) {
			
			if(!@mkdir($parent_folder_path, $rights)) {
				
				$this->_error = "�� ���� ������� ����������: '{$parent_folder_path}'";
				return false;
			}
		}
		
		return true;
	}
	
	/**
	* �������� - ������ �� ���������� ����� � �������� �����������
	* ���������� �������� ����� �������: "TXT,ZIP,RAR"
	*/
	function checkAllowedExtensions($exts) {
	
		$ext = strtoupper($this->_getExtension());
		
		$allowed = explode(",",$exts);
		foreach($allowed as $one_ext) {
		
			$one_ext = strtoupper(trim($one_ext));
			if ($one_ext == $ext) {
			
				return true;
			}
		}
		
		return false;
	}
	
	/**
	* ���������� ���������� �����, ������� ����� ������������ � ������ ��������
	*/
	function getNormalExtension() {
	
		return strtolower($this->_getExtension());
	}
	/**
	* �������� ���������� ������������ �����
	*/
	function _getExtension() {
	
		return end(explode(".",basename($this->uploaded_file['name'])));
	}
	
	/**
	* ���������� ������� ������� ����������� � PHP ��� ��������� ����������
	*/
	function _getImageCreateFunction() {
	
		$func = strtolower($this->_getExtension());
		if ($func == "jpg") $func = 'jpeg';
		
		$ifunc = "imagecreatefrom".$func;
		if (function_exists($ifunc)) return $ifunc;
			else return false;
	}
	
	/**
	* ���������� ��������� ������.
	*/
	function getError() {
	
		return $this->_error;
	}
}

?>