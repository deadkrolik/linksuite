<?PHP
include(dirname(__FILE__).'/_common.php');

$f = fopen(llmServer::getPPath()."tmp/logs/test.txt","a");

fwrite($f,"\n����� �������� ����-������ ".date("d/m/Y H:i:s",time()).", ������� �� 10 ������\n");

sleep(10);

fwrite($f,"��������� ������ ".date("d/m/Y H:i:s",time())."\n");

fclose($f);

?>