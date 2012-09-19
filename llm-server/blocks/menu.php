<?PHP
defined('LLM_STARTED') or die("o_0");

$menu_items = array(

	'Домой'            => llmServer::getWPath(),
	'Категории'        => llmServer::getHPath("categories",''),
	'Сайты'            => llmServer::getHPath("sites",''),
	'Проекты'          => llmServer::getHPath("projects",''),
	'Файлы на хостинг' => llmServer::getHPath("builder",''),
	'Документация'     => llmServer::getHPath("docs",''),
	'Пользователи'     => llmServer::getHPath("users",''),
	'Выход'            => llmServer::getWPath()."index.php?logout=1"
);

$menu = array();$delim = " &nbsp;&nbsp;&nbsp;&nbsp;&middot;&nbsp;&nbsp;&nbsp;&nbsp; ";global $mod;
foreach($menu_items as $item => $url) {

	$is_active = strpos($url,"mod=".$mod)!==false ? "class='menu-active-item'" : '';
	if ($mod == 'hello' && $item=='Домой') $is_active = "class='menu-active-item'";
	$menu[] = "<a href='{$url}' $is_active>{$item}</a>";
}

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".implode($delim,$menu)."&nbsp;&nbsp;<a href='".llmServer::getHPath('hello','special_page')."'><img src='".llmServer::getWPath()."template/icons/settings.gif' width=16 height=16 border=0 /></a>";
?>