<?PHP
$basepath_server = dirname(dirname(__FILE__)).'/';

@set_time_limit(0);
ini_set('max_execution_time',0);
echo str_repeat(" ",300);flush();
require_once($basepath_server."includes/llm_server.php");
require_once($basepath_server."includes/llm_http.php");
require_once($basepath_server."opensource/_.php");
$database = llmServer::getDBO();

?>