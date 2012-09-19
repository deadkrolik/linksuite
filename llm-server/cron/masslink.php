<?PHP
include(dirname(__FILE__).'/_common.php');

//����� ����-������
$list = glob(llmServer::getPPath().'tmp/masslink_*.cron');$cron_file = NULL;
if ($list === false || sizeof($list) == 0) exit();

foreach($list as $file) {

	$lock_file = $file.'.lock';
	if (file_exists($lock_file)) {

		//���� ������ ������, ��� 10 ����� �����
		$ftime = file_get_contents($lock_file);
		$diff  = (time() - $ftime)/3600;
		if ($diff > 10) {
		
			//������� �����
			unlink($lock_file);
		}
	}
	else {

		//���� ���� �� ����, �� ����� ���
		$c = file_get_contents($file);
		if (trim($c) == '') unlink($file);
			else {
				
				$cron_file = $file;
				break;
			}
	}
}
if (!$cron_file) exit();

//����������� ���� � ������� lock-����
$f = fopen($lock_file,'w');fwrite($f,time());fclose($f);

$logger = new llmLogger(basename($cron_file).'.txt',"a");

//�������� ������ � �������� X-������
$max_links = llmConfig::get("CRON_MASSLINK_MAX_LINKS",500);
$arr = file($cron_file);
$warr = array_slice($arr,0,$max_links);

//��� ����� ������� putLink()
define("LLM_NO_EXEC",1);
define('LLM_STARTED',1);
require_once(llmServer::getPPath().'modules/projects.php');

foreach($warr as $line) {

	list($project_id,$page_id,$html) = explode("||||||||||",trim($line));

	$_REQUEST['project_id']    = $project_id;
	$_REQUEST['page_id']       = $page_id;
	$ret = putLink(false,$html);

	if ($ret!==true) {
	
		$logger->log(LLM_LOG_ERROR,$ret);
	}
}
$logger->save();

//������� ��� ������������ ������ �� �����
$file_contents = file_get_contents($cron_file);
foreach($warr as $line) {

	$file_contents = str_replace($line,"",$file_contents);
}
$f = fopen($cron_file,'w');fwrite($f,$file_contents);fclose($f);

//������� lock-����
unlink($lock_file);

//���� ����� ������ � ����� ������, ��� ����� �������������� �� ��� - ������ ���� ���������� � ��� ���� �������
if (sizeof($warr) < $max_links) {

	unlink($cron_file);
}

exit();
?>