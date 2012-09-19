<?PHP
defined('LLM_STARTED') or die("o_0");

switch($task) {

	case 'testllmclient':
		testLLMClient();
		break;
		
	case 'build':
		llmTitler::put("Архив для хостинга");
		buildPackage();
		break;
		
	case 'testcharset':
		testCharset();
		break;
		
	case '':
		llmTitler::put("Создание архива для хостинга");
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
	
	//грузим сайт
	$database->setQuery("SELECT * FROM sites WHERE id = '{$site_id}'");
	$sites = $database->loadObjectList();$site = $sites[0];
	
	switch($type) {
		
		case 'links':
			
			echo "<h3>Шаг1: Код для вставки на сайт {$site->url}</h2>";

			$tpl_insert = file_get_contents(llmServer::getPPath().'conf/insert_template.txt');
			$tpl_insert = str_replace("{{{DOMAIN_KEY}}}",$site->domain_key,$tpl_insert);

			//если выдаватель стоит в субдиректории
			$pu = parse_url($client_url);
			$llmclient_host = $pu['host'];
			$llmclient_path = $pu['path'];
			
			//режем слэши по краям
			if ($llmclient_path{0} == '/') $llmclient_path = substr($llmclient_path,1);
			$len = strlen($llmclient_path);
			if ($llmclient_path{$len-1} == "/") $llmclient_path = substr($llmclient_path,0,$len-1);
			
			//форма с кодом для вставки в шаблон сайта
			echo "<p align='center' width=100%>
			<textarea style='width:100%' rows=8>{$tpl_insert}</textarea>
			</p>";
			
			//генерируем llm.php
			$tpl = file_get_contents(llmServer::getPPath().'conf/llm_template.txt');
			preg_match("|\/\*<LLM_CONFIG>\*\/(.*)\/\*<\/LLM_CONFIG>\*\/|Umsi",$tpl,$mt);
			$tpl = str_replace($mt[0],"/*<LLM_CONFIG>*/
		    var \$_llm_main_host     = '{$llmclient_host}';
		    var \$_llm_main_uri      = '{$llmclient_path}';
		    var \$_llm_is_ftp        = ".($site->is_ftp ? "true" : "false").";
		    var \$_llm_method_simple = false;
			/*</LLM_CONFIG>*/",$tpl);
			
			//создаем архив со ссылками для загрузки
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
			
			//удаляем временные файлы
			unlink(llmServer::getPPath()."tmp/llm.php");
			unlink(llmServer::getPPath()."tmp/llm-links.txt");
		
			//а получился ли файл?
			if (!file_exists($arch_path)) {
			
				echo "<font color=red>По каким-то причинам файл не удалось создать. Проверьте права на директорию.</font>";
				return;
			}
		
			//даем ссылку юзеру
			$link = llmServer::getWPath().'tmp/'."llm-{$site->domain_key}.zip";
			echo "<h3>Шаг2: Скачайте следующий файл</h2>";
			echo "<p><a href='$link' target='_blank'>llm-{$site->domain_key}.zip</a> и распакуйте его, в нем будет находиться директория <b>llm-{$site->domain_key}</b></p>";
			
			//куда
			echo "<h3>Шаг4: Загрузите его в директорию на хостинг</h2>";
			echo "<p>Скопируйте директорию <b>llm-{$site->domain_key}</b> в корень вашего сайта по FTP</p>";
			
			//говорим о правах
			echo "<h3>Шаг5: Смена прав</h2>";
			echo "<p><font color=red>Обязательно</font> смените права директории <b>llm-{$site->domain_key}</b> на <b>777</b></p>";
			
			//удаление старых архивов
			llmServer::deleteOldFiles('tmp/llm-*.zip');
		
		break;
		case 'articles':
			
			//если выдаватель стоит в субдиректории
			$pu = parse_url($client_url);
			$llmclient_host = $pu['host'];
			$llmclient_path = $pu['path'];
			
			$art_extension = getParam('art_extension');
			$art_extension = preg_replace("|[^a-z]|","",$art_extension);
			
			//режем слэши по краям
			if ($llmclient_path{0} == '/') $llmclient_path = substr($llmclient_path,1);
			$len = strlen($llmclient_path);
			if ($llmclient_path{$len-1} == "/") $llmclient_path = substr($llmclient_path,0,$len-1);

			//генерируем php-файл клиент статей
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
			
			//генерация .htaccess
			$tpl2 = file_get_contents(llmServer::getPPath().'conf/htaccess_template.txt');
			$tpl2 = str_replace("{{{DOMAIN_KEY}}}",$site->domain_key,$tpl2);
			$tpl2 = str_replace("{{{ARTICLES_FOLDER}}}",$site->articles_folder,$tpl2);
			$f = fopen(llmServer::getPPath()."tmp/.htaccess",'w');fwrite($f,$tpl2);fclose($f);
			
			//создаем архив со ссылками для загрузки
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
			
			//удаляем временные файлы
			unlink(llmServer::getPPath()."tmp/{$site->domain_key}.php");
			unlink(llmServer::getPPath()."tmp/{$site->domain_key}.txt");
			unlink(llmServer::getPPath()."tmp/.htaccess");
			
			//а получился ли файл?
			if (!file_exists($arch_path)) {
			
				echo "<font color=red>По каким-то причинам файл не удалось создать. Проверьте права на директорию.</font>";
				return;
			}
		
			//даем ссылку юзеру
			$link = llmServer::getWPath().'tmp/'."articles-{$site->domain_key}.zip";
			echo "<h3>Шаг1: Скачайте следующий файл</h2>";
			echo "<p><a href='$link' target='_blank'>articles-{$site->domain_key}.zip</a> и распакуйте его, в нем будет находиться директория <b>{$site->articles_folder}</b></p>";
			
			//куда
			echo "<h3>Шаг2: Загрузите его в директорию на хостинг</h2>";
			echo "<p>Скопируйте директорию <b>{$site->articles_folder}</b> в корень вашего сайта по FTP</p>";
			
			//говорим о правах
			echo "<h3>Шаг3: Смена прав</h2>";
			echo "<p><font color=red>Обязательно</font> смените права директории <b>$site->articles_folder</b> на <b>777</b>. Такие же права необходимо выставить на директорию <b>images</b> внутри директории <b>$site->articles_folder</b>.</p>";
			
			echo "<h3>Шаг4: Проверка работы</h2>";
			echo "<p>После всех действий введите пару тестовых статей через интерфейс скрипта и откройте в браузере ссылку <a href='{$site->url}{$site->articles_folder}/'>{$site->url}{$site->articles_folder}/</a>. Все должно работать отображаются корректно.</p>";

			//удаление старых архивов
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
	
		//если нет в заголовках - ищем в мете
		preg_match("|<meta.*charset=([a-z\-0-9]+)[^a-z\-0-9].*|Ui",$page,$mt);
		if (isset($mt[1])) {
		
			$charset = "<font color=green> &rarr; ".htmlspecialchars(strtoupper(trim($mt[1])))."</font>";
		}
		else {
		
			$charset = "<font color=red>Распознать не удалось</font>";
		}
	}
	
	echo $charset;
}

function defTask() {

	$database    = llmServer::getDBO();
	$def_site_id = (int)getParam("site_id");
	
	//массив сайтов
	$database->setQuery("SELECT * FROM sites ORDER BY id");
	$sites = $database->loadObjectList();
	$sites_sel = "<select name='site_id' id='sitesel'>";
	foreach ($sites as $site) {
		
		$is_sel = $def_site_id == $site->id ? "selected='selected'" : "";
		$sites_sel.= "<option value='{$site->id}' {$is_sel}>{$site->url}</option>";
	}
	$sites_sel .= "</select>";
	
	if (sizeof($sites) == 0) {

		echo "<div class=error>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Сайтов не обнаружено<br><br></div>";
	}
	else {
		
		//выдаватель
		$dir = dirname(llmServer::getWPath()).'/llm-client/';
		$randname = substr(md5(rand().rand().rand()),0,10);
		
		$gen_types = array("links" => "Код ссылок", "articles" => "Код статей");
		$type = getParam("type");
		$gen_list = "<select name='type'>";
		foreach($gen_types as $k => $v) {
		
			$is_sel = $type == $k ? "selected='selected'" : "";
			$gen_list .= "<option value='$k' {$is_sel}>$v</oprion>";
		}
		$gen_list .= "</select>";
		
		echo "<h2 id=files>Файлы для размещения на сайтах</h2><form action='".llmServer::getHPath('builder','build')."' method='post' name='buildsite'>
		<table align='center' width='100%'>
		<tr> <td width='30%'>Сайт</td> <td>{$sites_sel}</td> </tr>
		<tr> <td width='30%'>Папка доставщика ссылок ".showHelp("Слэш на конце и http в начале. Обычно эта папка называется <br>llm-client, но если вы хотите ее можно изменить в файле конфигурации. <br>И именно в таких случаях и нужно проверить - а правильно ли <br>вы с системой поняли друг друга, путем нажатия на специальную кнопочку справа.")."</td> <td><input type='text' style='width:80%' name='client_url' id='client_url' value='$dir'><span style='cursor:pointer;cursor:hand;' onclick=\"checkllmclient()\"> Проверка</span> <span id='llmclientbox'></span>
		</td> </tr>
		<tr> <td width='30%'>Что генерировать ".showHelp("Код ссылок - архив с файлом llm.php, который отвечает за выдачу ссылок проектов<br>Код статей - три файла со странными названиями, расположенные в заданной директории<br>и дающие возможность публиковать статьи на заданном сайте")."</td> <td>
		
		{$gen_list}
		&nbsp; (Расширение файлов для статей ".showHelp("Да, его можно поменять. По умолчанию это транслит названия статьи + HTML, <br>но для еще большего сокрытия факта использования данного скрипта <br>расширение можно поменять, например: php, htm (точку вводить НЕ НАДО, только буквы)")." &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=text name='art_extension' value='html' size=5>)
		</td> </tr>
		
		<tr> <td width='100%' colspan=2 align='center'> <br><input type='submit' value='  Получить файлы  '><br><br> </td> </tr>
		</table></form>";
	}
}

?>