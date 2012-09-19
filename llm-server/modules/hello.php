<?PHP
defined('LLM_STARTED') or die("o_0");

switch($task) {

	case 'deletelog':
		deleteLog();
		break;
	case 'clearlog':
		clearLog();
		break;
	case 'special_page':
		llmTitler::put("Волшебная страница");
		magicPage();
		break;
		
	case "":
		llmTitler::put("Приветствие");
		defTask();
		break;
	
}

function defTask() {

	echo "<h2 id=home>Добро пожаловать</h2>
	Я рад Вас приветствовать в админке скрипта обмена ссылками для сетки своих сайтов.
	Если вы читаете это сообщение, значит вы чего-то добились в таком озере как web и теперь
	хотите двигать свои новые проекты, за счет старых. И мой скрипт вам несомненно поможет.
	<br><br>
	Первичная навигация осуществляется в верхнем меню. Всего меню два - главное и не главное.
	Второе появляется в очень редких случаях и служит для дополнительной навигации внутри одного модуля.
	Там же доступен пункт меню - документация, в котором для самых маленьких все доходчиво объяснено.
	<br><br>
	В добрый путь.
	<br><br>
	<small>(Кстати, наверху вы, наверное, заметили значок <img src='".llmServer::getWPath()."template/icons/settings.gif' width=16 height=16 border=0 />, так вот - очень рекомендуется нажать на него хотя бы один раз. Откроется волшебная страница, на которой скрипт сам попробует найти проблемы, которые могут случиться в будущем и предложит Вам методы их решения. Главное - не промахнитесь!)</small>";
}

/**
* Самотестирование
*/
function magicPage() {

	$path = llmServer::getPPAth();
	$check_dirs = array("data","data/images","tmp","tmp/logs");
	
	echo "<h2 id=home>Волшебная страница</h2><h3>Проверка директорий на запись</h3><ul>";
	
	foreach($check_dirs as $di) {
	
		$dpath = $path.$di.'/'.md5(rand().rand().rand().rand()).'.test';
		
		$f = @fopen($dpath,"w");
		$status = $f ? "<font color='green'>OK</font>" : "<font color='red'>Недоступна для записи</font>";
		if ($f) {fclose($f);unlink($dpath);}
		
		echo "<li><b>{$di}</b> - {$status}</li>";
	}
	
	echo "</ul><h3>Нужные расширения PHP</h3><ul>";
	$e = get_loaded_extensions();
	$good = array('zlib','curl','gd');
	foreach($good as $g) {
	
		$status = in_array($g,$e) ? "<font color='green'>OK</font>" : "<font color='red'>Недоступно</font>";
		echo "<li><b>{$g}</b> - {$status}</li>";
	}
	
	echo "</ul>";
	echo "</ul><h3>Прочие опции</h3><ul>";

		$c = curl_init();
		$r = @curl_setopt($c, CURLOPT_FOLLOWLOCATION,1);

		echo "<li>Директива <b>CURLOPT_FOLLOWLOCATION</b> - ".($r ? "<font color='green'>разрешена</font>" : "<font color='red'>запрещена</font> (может плохо сказаться на индексаторе сайтов)")."</li>";
		
		$max_time = ini_get("max_execution_time");
		$r = @ini_set("max_execution_time",0);
		echo "<li><b>MAX_EXECUTION_TIME</b> = {$max_time}, при помощи ini_set - ".($r===false ? "<font color='red'>изменить нельзя</font>" : "<font color='green'>изменить можно</font>")."</li>";
		
		
		$parent_dir = dirname(llmServer::getPPath());
		$dirs = glob($parent_dir.'/*',GLOB_ONLYDIR);
		echo "<li><b>Возможность прочитать корневую дирекорию - </b> ".(sizeof($dirs)==0 ? "<font color='red'>Нет </font>(Важно!!! Дайте права на чтение папки `{$parent_dir}`)" : "<font color='green'>Да</font>")."</li>";
		
		//echo "<li></li>";
	echo "</ul>";
	
	echo "</ul><h3>Просмотр логов</h3><ul>";
	
	$logs = @glob(llmServer::getPPAth().'tmp/logs/*');
	
	//условие тут странное, но на некоторых хостингах может тупить и делать не интересные варнинги
	if ( $logs && $logs!=false && sizeof($logs)>0 ) {
		
		foreach($logs as $log) {
			
			$bn = basename($log);
			$wa = is_writeable($log) ? "" : "<font color='red'>недоступен для записи</font>";
			$stat = stat($log);
			echo "<li><a target='_blank' href='".llmServer::getWPath()."tmp/logs/{$bn}?rand=".rand()."'><b>{$bn}</b> - ".date("d/m/Y H:i:s",$stat['mtime']).", ~".ceil($stat['size']/1024)." Кб</a> (<a href='".llmServer::GetHPath("hello",'clearlog',"log={$bn}")."'>попытаться очистить</a>, <a href='".llmServer::GetHPath("hello",'deletelog',"log={$bn}")."'>попытаться удалить</a>) $wa</li>";
		}
	}
	echo "</ul>";
	
	echo "<h3>Ссылка на конфиг</h3><li><a href='".llmServer::getWPath()."conf/main.ini' target='_blank'>main.ini</a></li>";
}

/**
* Очистка заданного файла лога
*/
function clearLog() {

	$log = getParam('log');
	$log = preg_replace("|[^a-z0-9\.]|Umsi","",$log);
	
	$file = llmServer::getPPAth().'tmp/logs/'.$log;
	
	if (file_exists($file)) {
	
		$f = @fopen($file,"w");
		
		if($f) {fclose($f);$r = true;}
			else {$r = false;}
	}
	else {$r = false;}
	
	llmServer::redirect(llmServer::getHPath('hello','special_page'),"Очистка файла лога: ".($r ? "успешна" : "ошибка"));
}
function deleteLog() {

	$log = getParam('log');
	$log = preg_replace("|[^a-z0-9\.]|Umsi","",$log);
	
	$file = llmServer::getPPAth().'tmp/logs/'.$log;
	
	if (file_exists($file)) {
	
		$r = @unlink($file);		
	}
	else {$r = false;}
	
	llmServer::redirect(llmServer::getHPath('hello','special_page'),"Удаление файла лога: ".($r ? "успешно" : "ошибка"));
}
?>