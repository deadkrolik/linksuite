<?PHP
defined('LLM_STARTED') or die("o_0");

$menu_items = array(

	'�����'            => llmServer::getWPath(),
	'���������'        => llmServer::getHPath("categories",''),
	'�����'            => llmServer::getHPath("sites",''),
	'�������'          => llmServer::getHPath("projects",''),
	'����� �� �������' => llmServer::getHPath("builder",''),
	'������������'     => llmServer::getHPath("docs",''),
	'������������'     => llmServer::getHPath("users",''),
	'�����'            => llmServer::getWPath()."index.php?logout=1"
);

$menu = array();$delim = " &nbsp;&nbsp;&nbsp;&nbsp;&middot;&nbsp;&nbsp;&nbsp;&nbsp; ";global $mod;
foreach($menu_items as $item => $url) {

	$is_active = strpos($url,"mod=".$mod)!==false ? "class='menu-active-item'" : '';
	if ($mod == 'hello' && $item=='�����') $is_active = "class='menu-active-item'";
	$menu[] = "<a href='{$url}' $is_active>{$item}</a>";
}

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".implode($delim,$menu)."&nbsp;&nbsp;<a href='".llmServer::getHPath('hello','special_page')."'><img src='".llmServer::getWPath()."template/icons/settings.gif' width=16 height=16 border=0 /></a>";
?>