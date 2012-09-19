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
			$arr["������� �� ������ ��������"] = llmServer::getHPath('docs','');
			break;		
	}
	
	showSubMenu($arr);
}

function modUsers() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["�������� ������������"] = llmServer::getHPath('users','add_user');
			break;		
	}
	
	showSubMenu($arr);
}

function modCategories() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["�������� ���������"] = llmServer::getHPath('categories','add_category');
			break;		
	}
	
	showSubMenu($arr);
}

function modProjects() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["�������� ������"] = llmServer::getHPath('projects','add_project');
			break;
		case 'show_links':
			$arr["������� ��� ������"] = "javascript: if (confirm(\"�� ������ ������ �������� ������ �������?\")) { window.location.href=\"".llmServer::getHPath('projects','clean_project_links&project_id='.(int)getParam('id'))."\" } else { void(\"\") };";
			break;
	}
	
	showSubMenu($arr);
}

function modSites() {

	global $task;
	$arr = array();
	
	switch($task) {

		case '':
			$arr["�������� ����"] = llmServer::getHPath('sites','add_site');
			$arr["���������� ��������� �����"] = llmServer::getHPath('sites','static_code');
			break;
		
		case 'show_pages':
			$arr["������� ��� ��������"] = "javascript: if (confirm(\"�����?\")) { window.location.href=\"".llmServer::getHPath('sites','clear_pages','site_id='.(int)getParam('site_id'))."\" } else { void(\"\") };";
			$arr["����������� PR ���� �������"] = "javascript: if (confirm(\"PR ����� ������� � ��� ��������� ������� �������� ��������.\")) { window.location.href=\"".llmServer::getHPath('sites','rebuild_pr','site_id='.(int)getParam('site_id'))."\" } else { void(\"\") };";
			$arr["����������� PR=0"] = "javascript: if (confirm(\"PR ����� ������� � ���������� ��� ���� �������, ��� �� ����� ����. ����� ������ �����, ��� ������ ������� ��������, �� � ��� ����� �����.\")) { window.location.href=\"".llmServer::getHPath('sites','rebuild_null_pr','site_id='.(int)getParam('site_id'))."\" } else { void(\"\") };";
			break;

		case 'show_our_links_on_page':
			$arr["��������� �� ������ ������� ����� �����"] = llmServer::getHPath('sites','show_pages','site_id='.(int)getParam('site_id'));
			break;
			
		case 'static_code':
			$arr["�������� ���"] = llmServer::getHPath('sites','add_static_code');
			break;
			
		case 'edit_static_code':
		case 'manage_positions':
			$arr["��������� � �������� �����"] = llmServer::getHPath('sites','static_code');
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
			$arr["�������� ������"] = llmServer::getHPath('articles','add_article',"site_id=".(int)getParam('site_id'));
			break;
	}
	
	showSubMenu($arr);
}
?>