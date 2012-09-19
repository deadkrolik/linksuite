<?PHP
defined('LLM_STARTED') or die("o_0");

$statusmsg = getParam('statusmsg');
if ($statusmsg) {

	echo "<div class=msg>".htmlspecialchars($statusmsg)."</div>";
}
?>