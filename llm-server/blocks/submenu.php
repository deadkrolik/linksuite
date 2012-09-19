<?PHP
defined('LLM_STARTED') or die("o_0");
global $mod,$task;

switch($mod) {

	case 'articles':
		modArticles();
		break;
		
	case 'docs':
		modDocs();
		break;
		
	case 'users':
		modUsers();
		break;
		
	case 'categories':
		modCategories();
		break;
		
	case 'projects':
		modProjects();
		break;
		
	case 'sites':
		modSites();
		break;
		
	case "hello":
		modHello();
		break;
}

function modDocs() {

	global $task;
	$arr = array();

	switch($task) {

		case 'showsection':
			$arr["Обратно на список разделов"] = llmServer::getHPath('docs','');
			break;		
	}
	
	showSubMenu($arr);
}

function modUsers() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["Добавить пользователя"] = llmServer::getHPath('users','add_user');
			break;		
	}
	
	showSubMenu($arr);
}

function modCategories() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["Добавить категорию"] = llmServer::getHPath('categories','add_category');
			break;		
	}
	
	showSubMenu($arr);
}

function modProjects() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["Добавить проект"] = llmServer::getHPath('projects','add_project');
			break;
		case 'show_links':
			$arr["Удалить все ссылки"] = "javascript: if (confirm(\"Вы правда хотите очистить ссылки проекта?\")) { window.location.href=\"".llmServer::getHPath('projects','clean_project_links&project_id='.(int)getParam('id'))."\" } else { void(\"\") };";
			break;
	}
	
	showSubMenu($arr);
}

function modSites() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["Добавить сайт"] = llmServer::getHPath('sites','add_site');
			$arr["Управление статичным кодом"] = llmServer::getHPath('sites','static_code');
			break;
		
		case 'show_pages':
			$arr["Удалить все страницы"] = "javascript: if (confirm(\"Точна?\")) { window.location.href=\"".llmServer::getHPath('sites','clear_pages','site_id='.(int)getParam('site_id'))."\" } else { void(\"\") };";
			$arr["Пересчитать PR всех страниц"] = "javascript: if (confirm(\"PR будет сброшен и при ближайшем запуске считалки обновлен.\")) { window.location.href=\"".llmServer::getHPath('sites','rebuild_pr','site_id='.(int)getParam('site_id'))."\" } else { void(\"\") };";
			$arr["Пересчитать PR=0"] = "javascript: if (confirm(\"PR будет сброшен и пересчитан для всех страниц, где он равен нулю. Такое бывает нужно, ибо иногда сервисы проверки, да и сам гугль тупят.\")) { window.location.href=\"".llmServer::getHPath('sites','rebuild_null_pr','site_id='.(int)getParam('site_id'))."\" } else { void(\"\") };";
			break;

		case 'show_our_links_on_page':
			$arr["Вернуться на список страниц этого сайта"] = llmServer::getHPath('sites','show_pages','site_id='.(int)getParam('site_id'));
			break;
			
		case 'static_code':
			$arr["Добавить код"] = llmServer::getHPath('sites','add_static_code');
			break;
			
		case 'edit_static_code':
		case 'manage_positions':
			$arr["Вернуться к листингу кодов"] = llmServer::getHPath('sites','static_code');
			break;
	}
	
	showSubMenu($arr);
}

function modHello() {

	showSubMenu(array());
}

function showSubMenu($elements) {

	echo "&rarr;&nbsp;&nbsp;&nbsp;";
	if (sizeof($elements) > 0) {
	
		$arr = array();
		foreach ($elements as $elem => $url) {
		
			$arr[] = "<a href='{$url}'>{$elem}</a>";
		}
		echo implode(" &nbsp;&nbsp;&nbsp;&nbsp;&middot;&nbsp;&nbsp;&nbsp;&nbsp; ",$arr);
	}
	
}

function modArticles() {

	global $task;
	$arr = array();
	
	switch($task) {

		case 'view_site_articles':
			$arr["Добавить статью"] = llmServer::getHPath('articles','add_article',"site_id=".(int)getParam('site_id'));
			break;
	}
	
	showSubMenu($arr);
}
?>