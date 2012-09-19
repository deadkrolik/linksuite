<?PHP
include(dirname(__FILE__).'/_common.php');

$f = fopen(llmServer::getPPath()."tmp/logs/test.txt","a");

fwrite($f,"\nСтарт тестовой крон-задачи ".date("d/m/Y H:i:s",time()).", усыпаем на 10 секунд\n");

sleep(10);

fwrite($f,"Завершаем задачу ".date("d/m/Y H:i:s",time())."\n");

fclose($f);

?>