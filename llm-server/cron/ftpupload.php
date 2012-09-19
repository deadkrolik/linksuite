<?PHP
include(dirname(__FILE__).'/_common.php');

//������ �����
$hours = llmConfig::get("CRON_FTPUPLOAD_UPDATE_MIN_HOURS",2);
$time_minus_2 = 60*60*$hours;//������� � N �����
$time = time();

$database->setQuery("SELECT * FROM sites WHERE is_ftp > 0 AND (($time - last_ftp_access) > $time_minus_2) ORDER BY last_ftp_access LIMIT 1");
$sites = $database->loadObjectList();

if (sizeof($sites) == 0) exit();
$site = $sites[0];

//���� ���-����� �� �������� ������������ ������
$dir2 = getLLMCLIENTDir();
if (!$dir2) {

	$logger = new llmLogger("ftpupload.txt","a");
	$logger->log(LLM_LOG_ERROR,"Cannot found llm-client directory");
	$logger->save();
	
	//��� �� ������ ��� �� ���������
	updateSiteAccessTime($site->id);
	
	exit();
}

$url = dirname(llmServer::getWPath()).'/'.$dir2.'/get.php?domain_key='.$site->domain_key;

//��������� ������ ��� �����
$page = file_get_contents($url);

$pos = strpos("LERROR",$page);
if ($pos !== false) {

	$logger = new llmLogger("ftpupload.txt","a");
	$logger->log(LLM_LOG_ERROR,"get.php return error [$page]");
	$logger->save();
	
	//��� �� ������ ��� �� ���������
	updateSiteAccessTime($site->id);
	
	exit();
}

//������ ��� ������ �� - �������� ����� ��� �����
$file = base64_decode($page);

ftpClient::$user = $site->ftp_user;
ftpClient::$pass = $site->ftp_password;
ftpClient::$host = $site->ftp_host;
ftpClient::$dir  = $site->ftp_dir;

$ftp = ftpClient::getInstance();
if (!$ftp->_conn) {

	$logger = new llmLogger("ftpupload.txt","a");
	$logger->log(LLM_LOG_ERROR,"Cannot connect to host in '{$site->url}' FTP settings");
	$logger->save();
	
	//��� �� ������ ��� �� ���������
	updateSiteAccessTime($site->id);
	
	exit();
}

//������� ��������� ���� �� ��������
$fname = llmServer::getPPath().'tmp/ftpupload-'.rand().'.txt';
$f = fopen($fname,"w");fwrite($f,$file);fclose($f);

//������
ftpClient::store_file($site->ftp_dir.'llm-'.$site->domain_key,$fname,'llm-links.txt');

//������� ��������� ���� � ����� ������
@unlink($fname);

//��������� ����� ���������� �������
updateSiteAccessTime($site->id);

exit();

function updateSiteAccessTime($id) {

	$database = llmServer::getDBO();
	
	$database->setQuery("UPDATE sites SET last_ftp_access = '".time()."' WHERE id='$id'");
	$database->query();
}
?>