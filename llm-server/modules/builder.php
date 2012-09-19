<?PHP
defined('LLM_STARTED') or die("o_0");

switch($task) {

	case 'testllmclient':
		testLLMClient();
		break;
		
	case 'build':
		llmTitler::put("����� ��� ��������");
		buildPackage();
		break;
		
	case 'testcharset':
		testCharset();
		break;
		
	case '':
		llmTitler::put("�������� ������ ��� ��������");
		defTask();
		break;
}

function testLLMClient() {

	define("ICONV_ME","UTF-8");
	
	$url = getParam('url');
	
	$http = new llmHTTP();
	$page = $http->get($url."get.php?check=1",$s,$c);
	
	$pos = strpos($page,"LLM_AGA_AGA");
	
	if ($pos!==false) {
	
		$result = "<font color=green>OK</font>";
	}
	else {
	
		$result = "<font color=red>Error</font>";
	}
	
	echo $result;
}

function buildPackage() {

	$database   = llmServer::getDBO();
	
	$site_id    = (int)getParam('site_id');
	$client_url = getParam('client_url');
	$type       = getParam("type");
	
	//������ ����
	$database->setQuery("SELECT * FROM sites WHERE id = '{$site_id}'");
	$sites = $database->loadObjectList();$site = $sites[0];
	
	switch($type) {
		
		case 'links':
			
			echo "<h3>���1: ��� ��� ������� �� ���� {$site->url}</h2>";

			$tpl_insert = file_get_contents(llmServer::getPPath().'conf/insert_template.txt');
			$tpl_insert = str_replace("{{{DOMAIN_KEY}}}",$site->domain_key,$tpl_insert);

			//���� ���������� ����� � �������������
			$pu = parse_url($client_url);
			$llmclient_host = $pu['host'];
			$llmclient_path = $pu['path'];
			
			//����� ����� �� �����
			if ($llmclient_path{0} == '/') $llmclient_path = substr($llmclient_path,1);
			$len = strlen($llmclient_path);
			if ($llmclient_path{$len-1} == "/") $llmclient_path = substr($llmclient_path,0,$len-1);
			
			//����� � ����� ��� ������� � ������ �����
			echo "<p align='center' width=100%>
			<textarea style='width:100%' rows=8>{$tpl_insert}</textarea>
			</p>";
			
			//���������� llm.php
			$tpl = file_get_contents(llmServer::getPPath().'conf/llm_template.txt');
			preg_match("|\/\*<LLM_CONFIG>\*\/(.*)\/\*<\/LLM_CONFIG>\*\/|Umsi",$tpl,$mt);
			$tpl = str_replace($mt[0],"/*<LLM_CONFIG>*/
		    var \$_llm_main_host     = '{$llmclient_host}';
		    var \$_llm_main_uri      = '{$llmclient_path}';
		    var \$_llm_is_ftp        = ".($site->is_ftp ? "true" : "false").";
		    var \$_llm_method_simple = false;
			/*</LLM_CONFIG>*/",$tpl);
			
			//������� ����� �� �������� ��� ��������
			define("PCLZIP_TEMPORARY_DIR",llmServer::getPPath().'tmp/');
			require_once(llmServer::getPPath()."includes/pclzip.lib.php");
			$arch_path = llmServer::getPPath()."tmp/llm-{$site->domain_key}.zip";
			@unlink($arch_path);
			$archive = new PclZip($arch_path);
			$dir_name = "llm-".$site->domain_key;
		
			$f = fopen(llmServer::getPPath()."tmp/llm.php",'w');fwrite($f,$tpl);fclose($f);
			$f = fopen(llmServer::getPPath()."tmp/llm-links.txt",'w');fwrite($f,"");fclose($f);
		
			$archive->add(llmServer::getPPath()."tmp/llm.php"      , PCLZIP_OPT_ADD_PATH,$dir_name,PCLZIP_OPT_REMOVE_ALL_PATH);
			$re = $archive->add(llmServer::getPPath()."tmp/llm-links.txt", PCLZIP_OPT_ADD_PATH,$dir_name,PCLZIP_OPT_REMOVE_ALL_PATH);
			
			//������� ��������� �����
			unlink(llmServer::getPPath()."tmp/llm.php");
			unlink(llmServer::getPPath()."tmp/llm-links.txt");
		
			//� ��������� �� ����?
			if (!file_exists($arch_path)) {
			
				echo "<font color=red>�� �����-�� �������� ���� �� ������� �������. ��������� ����� �� ����������.</font>";
				return;
			}
		
			//���� ������ �����
			$link = llmServer::getWPath().'tmp/'."llm-{$site->domain_key}.zip";
			echo "<h3>���2: �������� ��������� ����</h2>";
			echo "<p><a href='$link' target='_blank'>llm-{$site->domain_key}.zip</a> � ���������� ���, � ��� ����� ���������� ���������� <b>llm-{$site->domain_key}</b></p>";
			
			//����
			echo "<h3>���4: ��������� ��� � ���������� �� �������</h2>";
			echo "<p>���������� ���������� <b>llm-{$site->domain_key}</b> � ������ ������ ����� �� FTP</p>";
			
			//������� � ������
			echo "<h3>���5: ����� ����</h2>";
			echo "<p><font color=red>�����������</font> ������� ����� ���������� <b>llm-{$site->domain_key}</b> �� <b>777</b></p>";
			
			//�������� ������ �������
			llmServer::deleteOldFiles('tmp/llm-*.zip');
		
		break;
		case 'articles':
			
			//���� ���������� ����� � �������������
			$pu = parse_url($client_url);
			$llmclient_host = $pu['host'];
			$llmclient_path = $pu['path'];
			
			$art_extension = getParam('art_extension');
			$art_extension = preg_replace("|[^a-z]|","",$art_extension);
			
			//����� ����� �� �����
			if ($llmclient_path{0} == '/') $llmclient_path = substr($llmclient_path,1);
			$len = strlen($llmclient_path);
			if ($llmclient_path{$len-1} == "/") $llmclient_path = substr($llmclient_path,0,$len-1);

			//���������� php-���� ������ ������
			$tpl = file_get_contents(llmServer::getPPath().'conf/art_template.txt');
			preg_match("|\/\*<LLM_CONFIG_2>\*\/(.*)\/\*<\/LLM_CONFIG_2>\*\/|Umsi",$tpl,$mt);
			$tpl = str_replace($mt[0],"/*<LLM_CONFIG_2>*/
	var \$_llm_main_host     = '{$llmclient_host}';
	var \$_llm_main_uri      = '{$llmclient_path}';
	/*</LLM_CONFIG_2>*/",$tpl);
			preg_match("|\/\*<LLM_CONFIG_1>\*\/(.*)\/\*<\/LLM_CONFIG_1>\*\/|Umsi",$tpl,$mt);
			$tpl = str_replace($mt[0],"/*<LLM_CONFIG_1>*/
\$articles_extension = '{$art_extension}';
/*</LLM_CONFIG_1>*/",$tpl);
			
			//��������� .htaccess
			$tpl2 = file_get_contents(llmServer::getPPath().'conf/htaccess_template.txt');
			$tpl2 = str_replace("{{{DOMAIN_KEY}}}",$site->domain_key,$tpl2);
			$tpl2 = str_replace("{{{ARTICLES_FOLDER}}}",$site->articles_folder,$tpl2);
			$f = fopen(llmServer::getPPath()."tmp/.htaccess",'w');fwrite($f,$tpl2);fclose($f);
			
			//������� ����� �� �������� ��� ��������
			define("PCLZIP_TEMPORARY_DIR",llmServer::getPPath().'tmp/');
			require_once(llmServer::getPPath()."includes/pclzip.lib.php");
			$arch_path = llmServer::getPPath()."tmp/articles-{$site->domain_key}.zip";
			@unlink($arch_path);
			$archive = new PclZip($arch_path);
			$dir_name = $site->articles_folder;
		
			$f = fopen(llmServer::getPPath()."tmp/{$site->domain_key}.php",'w');fwrite($f,$tpl);fclose($f);
			$f = fopen(llmServer::getPPath()."tmp/{$site->domain_key}.txt",'w');fwrite($f,"");fclose($f);
		
			$archive->add(llmServer::getPPath()."tmp/{$site->domain_key}.php", PCLZIP_OPT_ADD_PATH,$dir_name,PCLZIP_OPT_REMOVE_ALL_PATH);
			$archive->add(llmServer::getPPath()."tmp/{$site->domain_key}.txt", PCLZIP_OPT_ADD_PATH,$dir_name,PCLZIP_OPT_REMOVE_ALL_PATH);
			$archive->add(llmServer::getPPath()."data/{$site->domain_key}.html", PCLZIP_OPT_ADD_PATH,$dir_name,PCLZIP_OPT_REMOVE_ALL_PATH);
			$archive->add(llmServer::getPPath()."tmp/.htaccess", PCLZIP_OPT_ADD_PATH,$dir_name,PCLZIP_OPT_REMOVE_ALL_PATH);
			$archive->add(llmServer::getPPath()."tmp/images/", PCLZIP_OPT_ADD_PATH,$dir_name,PCLZIP_OPT_REMOVE_ALL_PATH);
			
			//������� ��������� �����
			unlink(llmServer::getPPath()."tmp/{$site->domain_key}.php");
			unlink(llmServer::getPPath()."tmp/{$site->domain_key}.txt");
			unlink(llmServer::getPPath()."tmp/.htaccess");
			
			//� ��������� �� ����?
			if (!file_exists($arch_path)) {
			
				echo "<font color=red>�� �����-�� �������� ���� �� ������� �������. ��������� ����� �� ����������.</font>";
				return;
			}
		
			//���� ������ �����
			$link = llmServer::getWPath().'tmp/'."articles-{$site->domain_key}.zip";
			echo "<h3>���1: �������� ��������� ����</h2>";
			echo "<p><a href='$link' target='_blank'>articles-{$site->domain_key}.zip</a> � ���������� ���, � ��� ����� ���������� ���������� <b>{$site->articles_folder}</b></p>";
			
			//����
			echo "<h3>���2: ��������� ��� � ���������� �� �������</h2>";
			echo "<p>���������� ���������� <b>{$site->articles_folder}</b> � ������ ������ ����� �� FTP</p>";
			
			//������� � ������
			echo "<h3>���3: ����� ����</h2>";
			echo "<p><font color=red>�����������</font> ������� ����� ���������� <b>$site->articles_folder</b> �� <b>777</b>. ����� �� ����� ���������� ��������� �� ���������� <b>images</b> ������ ���������� <b>$site->articles_folder</b>.</p>";
			
			echo "<h3>���4: �������� ������</h2>";
			echo "<p>����� ���� �������� ������� ���� �������� ������ ����� ��������� ������� � �������� � �������� ������ <a href='{$site->url}{$site->articles_folder}/'>{$site->url}{$site->articles_folder}/</a>. ��� ������ �������� ������������ ���������.</p>";

			//�������� ������ �������
			llmServer::deleteOldFiles('tmp/articles-*.zip');
			
			break;
	}
}

function testCharset() {

	define("ICONV_ME","UTF-8");
	
	$url = getParam('url');
	
	$http = new llmHTTP();
	$page = $http->get($url,$s,$c);
	
	preg_match("|Content\-Type:.*charset=(.*)\n|",$page,$mt);
	if (isset($mt[1])) {
	
		$charset = "<font color=green> &rarr; ".strtoupper(htmlspecialchars(trim($mt[1])))."</font>";
	}
	else {
	
		//���� ��� � ���������� - ���� � ����
		preg_match("|<meta.*charset=([a-z\-0-9]+)[^a-z\-0-9].*|Ui",$page,$mt);
		if (isset($mt[1])) {
		
			$charset = "<font color=green> &rarr; ".htmlspecialchars(strtoupper(trim($mt[1])))."</font>";
		}
		else {
		
			$charset = "<font color=red>���������� �� �������</font>";
		}
	}
	
	echo $charset;
}

function defTask() {

	$database    = llmServer::getDBO();
	$def_site_id = (int)getParam("site_id");
	
	//������ ������
	$database->setQuery("SELECT * FROM sites ORDER BY id");
	$sites = $database->loadObjectList();
	$sites_sel = "<select name='site_id' id='sitesel'>";
	foreach ($sites as $site) {
		
		$is_sel = $def_site_id == $site->id ? "selected='selected'" : "";
		$sites_sel.= "<option value='{$site->id}' {$is_sel}>{$site->url}</option>";
	}
	$sites_sel .= "</select>";
	
	if (sizeof($sites) == 0) {

		echo "<div class=error>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;������ �� ����������<br><br></div>";
	}
	else {
		
		//����������
		$dir = dirname(llmServer::getWPath()).'/llm-client/';
		$randname = substr(md5(rand().rand().rand()),0,10);
		
		$gen_types = array("links" => "��� ������", "articles" => "��� ������");
		$type = getParam("type");
		$gen_list = "<select name='type'>";
		foreach($gen_types as $k => $v) {
		
			$is_sel = $type == $k ? "selected='selected'" : "";
			$gen_list .= "<option value='$k' {$is_sel}>$v</oprion>";
		}
		$gen_list .= "</select>";
		
		echo "<h2 id=files>����� ��� ���������� �� ������</h2><form action='".llmServer::getHPath('builder','build')."' method='post' name='buildsite'>
		<table align='center' width='100%'>
		<tr> <td width='30%'>����</td> <td>{$sites_sel}</td> </tr>
		<tr> <td width='30%'>����� ���������� ������ ".showHelp("���� �� ����� � http � ������. ������ ��� ����� ���������� <br>llm-client, �� ���� �� ������ �� ����� �������� � ����� ������������. <br>� ������ � ����� ������� � ����� ��������� - � ��������� �� <br>�� � �������� ������ ���� �����, ����� ������� �� ����������� �������� ������.")."</td> <td><input type='text' style='width:80%' name='client_url' id='client_url' value='$dir'><span style='cursor:pointer;cursor:hand;' onclick=\"checkllmclient()\"> ��������</span> <span id='llmclientbox'></span>
		</td> </tr>
		<tr> <td width='30%'>��� ������������ ".showHelp("��� ������ - ����� � ������ llm.php, ������� �������� �� ������ ������ ��������<br>��� ������ - ��� ����� �� ��������� ����������, ������������� � �������� ����������<br>� ������ ����������� ����������� ������ �� �������� �����")."</td> <td>
		
		{$gen_list}
		&nbsp; (���������� ������ ��� ������ ".showHelp("��, ��� ����� ��������. �� ��������� ��� �������� �������� ������ + HTML, <br>�� ��� ��� �������� �������� ����� ������������� ������� ������� <br>���������� ����� ��������, ��������: php, htm (����� ������� �� ����, ������ �����)")." &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text name='art_extension' value='html' size=5>)
		</td> </tr>
		
		<tr> <td width='100%' colspan=2 align='center'> <br><input type='submit' value='  �������� �����  '><br><br> </td> </tr>
		</table></form>";
	}
}

?>