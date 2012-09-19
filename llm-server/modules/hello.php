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
		llmTitler::put("��������� ��������");
		magicPage();
		break;
		
	case "":
		llmTitler::put("�����������");
		defTask();
		break;
	
}

function defTask() {

	echo "<h2 id=home>����� ����������</h2>
	� ��� ��� �������������� � ������� ������� ������ �������� ��� ����� ����� ������.
	���� �� ������� ��� ���������, ������ �� ����-�� �������� � ����� ����� ��� web � ������
	������ ������� ���� ����� �������, �� ���� ������. � ��� ������ ��� ���������� �������.
	<br><br>
	��������� ��������� �������������� � ������� ����. ����� ���� ��� - ������� � �� �������.
	������ ���������� � ����� ������ ������� � ������ ��� �������������� ��������� ������ ������ ������.
	��� �� �������� ����� ���� - ������������, � ������� ��� ����� ��������� ��� ��������� ���������.
	<br><br>
	� ������ ����.
	<br><br>
	<small>(������, ������� ��, ��������, �������� ������ <img src='".llmServer::getWPath()."template/icons/settings.gif' width=16 height=16 border=0 />, ��� ��� - ����� ������������� ������ �� ���� ���� �� ���� ���. ��������� ��������� ��������, �� ������� ������ ��� ��������� ����� ��������, ������� ����� ��������� � ������� � ��������� ��� ������ �� �������. ������� - �� ������������!)</small>";
}

/**
* ����������������
*/
function magicPage() {

	$path = llmServer::getPPAth();
	$check_dirs = array("data","data/images","tmp","tmp/logs");
	
	echo "<h2 id=home>��������� ��������</h2><h3>�������� ���������� �� ������</h3><ul>";
	
	foreach($check_dirs as $di) {
	
		$dpath = $path.$di.'/'.md5(rand().rand().rand().rand()).'.test';
		
		$f = @fopen($dpath,"w");
		$status = $f ? "<font color='green'>OK</font>" : "<font color='red'>���������� ��� ������</font>";
		if ($f) {fclose($f);unlink($dpath);}
		
		echo "<li><b>{$di}</b> - {$status}</li>";
	}
	
	echo "</ul><h3>������ ���������� PHP</h3><ul>";
	$e = get_loaded_extensions();
	$good = array('zlib','curl','gd');
	foreach($good as $g) {
	
		$status = in_array($g,$e) ? "<font color='green'>OK</font>" : "<font color='red'>����������</font>";
		echo "<li><b>{$g}</b> - {$status}</li>";
	}
	
	echo "</ul>";
	echo "</ul><h3>������ �����</h3><ul>";

		$c = curl_init();
		$r = @curl_setopt($c, CURLOPT_FOLLOWLOCATION,1);

		echo "<li>��������� <b>CURLOPT_FOLLOWLOCATION</b> - ".($r ? "<font color='green'>���������</font>" : "<font color='red'>���������</font> (����� ����� ��������� �� ����������� ������)")."</li>";
		
		$max_time = ini_get("max_execution_time");
		$r = @ini_set("max_execution_time",0);
		echo "<li><b>MAX_EXECUTION_TIME</b> = {$max_time}, ��� ������ ini_set - ".($r===false ? "<font color='red'>�������� ������</font>" : "<font color='green'>�������� �����</font>")."</li>";
		
		
		$parent_dir = dirname(llmServer::getPPath());
		$dirs = glob($parent_dir.'/*',GLOB_ONLYDIR);
		echo "<li><b>����������� ��������� �������� ��������� - </b> ".(sizeof($dirs)==0 ? "<font color='red'>��� </font>(�����!!! ����� ����� �� ������ ����� `{$parent_dir}`)" : "<font color='green'>��</font>")."</li>";
		
		//echo "<li></li>";
	echo "</ul>";
	
	echo "</ul><h3>�������� �����</h3><ul>";
	
	$logs = @glob(llmServer::getPPAth().'tmp/logs/*');
	
	//������� ��� ��������, �� �� ��������� ��������� ����� ������ � ������ �� ���������� ��������
	if ( $logs && $logs!=false && sizeof($logs)>0 ) {
		
		foreach($logs as $log) {
			
			$bn = basename($log);
			$wa = is_writeable($log) ? "" : "<font color='red'>���������� ��� ������</font>";
			$stat = stat($log);
			echo "<li><a target='_blank' href='".llmServer::getWPath()."tmp/logs/{$bn}?rand=".rand()."'><b>{$bn}</b> - ".date("d/m/Y H:i:s",$stat['mtime']).", ~".ceil($stat['size']/1024)." ��</a> (<a href='".llmServer::GetHPath("hello",'clearlog',"log={$bn}")."'>���������� ��������</a>, <a href='".llmServer::GetHPath("hello",'deletelog',"log={$bn}")."'>���������� �������</a>) $wa</li>";
		}
	}
	echo "</ul>";
	
	echo "<h3>������ �� ������</h3><li><a href='".llmServer::getWPath()."conf/main.ini' target='_blank'>main.ini</a></li>";
}

/**
* ������� ��������� ����� ����
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
	
	llmServer::redirect(llmServer::getHPath('hello','special_page'),"������� ����� ����: ".($r ? "�������" : "������"));
}
function deleteLog() {

	$log = getParam('log');
	$log = preg_replace("|[^a-z0-9\.]|Umsi","",$log);
	
	$file = llmServer::getPPAth().'tmp/logs/'.$log;
	
	if (file_exists($file)) {
	
		$r = @unlink($file);		
	}
	else {$r = false;}
	
	llmServer::redirect(llmServer::getHPath('hello','special_page'),"�������� ����� ����: ".($r ? "�������" : "������"));
}
?>