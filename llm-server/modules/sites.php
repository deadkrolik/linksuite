<?PHP
defined('LLM_STARTED') or die("o_0");
llmTitler::put("���������� �������");

switch($task) {

	case 'delete_static_code':
		deleteSCode();
		break;
		
	case 'save_code':
		saveSCode();
		break;
		
	case 'save_positions':
		savePositions();
		break;
		
	case 'manage_positions':
		managePositions();
		break;
		
	case 'edit_static_code':
		editSCode((int)getParam('id',0),"�������������� ����");
		break;
		
	case 'add_static_code':
		editSCode(0,"���������� ����");
		break;
		
	case 'static_code':
		showStaticCodeList();
		break;
		
	case 'export_pages':
		exportPages();
		break;
		
	case 'check_ftp':
		checkFTP();
		break;
		
	case 'get_links_on_page':
		getLinksOnPage();
		break;
		
	case 'linkactivation':
		linkActivation();
		break;
		
	case 'update_main_pr':
		updatePRCY((int)getParam('site_id'));
		break;
		
	case 'delete_site':
		deleteSite((int)getParam('site_id'));
		break;
		
	case 'rebuild_null_pr':
		clearPagesNULLPR();
		break;
		
	case 'rebuild_pr':
		clearPagesPR();
		break;
		
	case 'newlink_html':
		updateLinkHtml();
		break;

	case 'dellink':
		deleteLink();
		break;

	case 'show_our_links_on_page':
		showLinksOnPage();
		break;

	case 'manage_pages':
		managePages();
		break;

	case 'clear_pages':
		clearSitePages();
		break;

	case 'off_site_index':
		changeSiteIndexingState(0,(int)getParam('site_id'));
		break;

	case 'on_site_index':
		changeSiteIndexingState(1,(int)getParam('site_id'));
		break;

	case 'show_pages':
		showPages();
		break;

	case "save_site":
		saveSite();
		break;

	case "edit_site":
		editSite((int)getParam('id',0),"�������������� �����");
		break;

	case "add_site":
		editSite(0,"�������� ����� ����");
		break;

	case '':
		listSites();
		break;
}

/**
* ���������� ����� � ���������
*/
function savePositions() {

	$positions = loadPositions();
	
	$c = stripslashes(getParam("content"));
	
	$pfile = llmServer::getPPath().'/data/positions.txt';
	
	$f = fopen($pfile,"w");fwrite($f,$c);fclose($f);

	llmServer::redirect(llmServer::getHPath('sites','manage_positions'),"���� � ��������� ��������");
}
/**
* ����������� �������� �������
*/
function managePositions() {

	$positions = loadPositions();
	$p = implode("\n",$positions);
	
	echo "
	<h2 id='newsite'>���������� ���������</h2>
	<form action='".llmServer::getWPath()."' method='post' id='siteform'>
	<table align='center' width='100%'>
	
	<tr> 
	
	<td width=25%>������ ������� ".showHelp("������� �� ������ � ���������. ��� ��� �������� � �������� ����� /data/positions.txt<br>����� ����� ������ �� ����� �� ���������� �����, ����� footer, header, left � �.�.<br>��� ������ ������� ���������� � ����� ������, ��������� ����. ��������")." <br><br><b>��������:</b><br>footer<br>header<br>left<br>right</td><td><textarea style='width:100%' rows=10 name='content'>{$p}</textarea></td> 
	
	</tr>
	";
	
	echo llmHTML::formBottom('save_positions','���������',2,"button-add");
	
	echo "
	</table></form>";
}

/**
* ��������� ��������� �������
*/
function loadPositions() {

	$pos = array();
	
	$pfile = llmServer::getPPath().'data/positions.txt';
	
	if(!file_exists($pfile)) {
	
		$f = fopen($pfile,"w");
		if (!$f) {
		
			llmServer::showError("�� ���� ������� ���� {$pfile}");
		}
		fclose($f);
	}
	
	$t = file($pfile);
	
	foreach($t as $k=>$v) {
	
		if (!trim($v)) continue;
		
		$pos[] = trim($v);
	}
	
	return $pos;
}

function deleteSCode() {

	$database = llmServer::getDBO();
	
	$id = (int)getParam('id');
	
	$database->setQuery("DELETE FROM static_code WHERE id='{$id}'");
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('sites','static_code'),"��� ������");
}
/**
* ���������� ���������� ����
*/
function saveSCode() {

	$database = llmServer::getDBO();
	
	$id            = (int)getParam('id');
	$title         = getParam('title');
	$position_name = getParam('position_name');
	$content       = getParam('content');
	$is_published  = getParam('is_published');
	
	$s = $_POST['sites'];$sarr = array();
	foreach($s as $site_id) {
	
		$site_id = (int)$site_id;
		if ($site_id < 0) continue;
		if (in_array($site_id,$sarr)) continue;
		
		$sarr[] = $site_id;
	}
	
	//����� ���� - ��� �����, � ������ ������������ ������� ��� ���-�� ���� ���� ��� ��� ���� ������
	if (in_array(0,$sarr)) $show_on_sites = "0";
		else $show_on_sites = implode(",",$sarr);
	
	if ($id) {
	
		$query = "UPDATE static_code SET title='$title', position_name='$position_name', content='$content', is_published='$is_published', show_on_sites='$show_on_sites' WHERE id = '$id'";
	}
	else {
	
		$query = "INSERT INTO static_code (title,position_name,content,is_published,show_on_sites) VALUES ('$title','$position_name','$content','$is_published','$show_on_sites')";
	}
	
	$database->setQuery($query);
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('sites','static_code'),"��� ������� ��������");
}

/**
* ��������� ���������� ��������� ����
*/
function editSCode($id,$title) {

	$database = llmServer::getDBO();
	
	if ($id) {
	
		$database->setQuery("SELECT * FROM static_code WHERE id = '{$id}'");
		$code = $database->loadObjectList();
		
		if (sizeof($code) == 0) {
		
			llmServer::showError("��� `$id` �� ���������",false);
		}
		$code = $code[0];
	}
	else {
	
		$code = new stdClass();
		$code->id = 0;
		$code->title = '����� ���, �� �������� ��� ����� ���������������� � ������';
		$code->position_name = '';
		$code->content = '����� js- ��� html-���';
		$code->is_published = true;
		$code->show_on_sites = "";
	}
	
	$positions = loadPositions();
	
	if (sizeof($positions) == 0) $pos_html = "������� �� �������, ����� ����� <a href='".llmServer::getHPath("sites",'manage_positions')."'>��� �������</a>";
		else {
		
			$pos_html = "<select name='position_name'>";
			foreach($positions as $pos_name) {
			
				if ($code->position_name == $pos_name) $sel = "selected='selected'";
					else $sel = "";
				$pos_html .= "<option value='$pos_name' $sel>$pos_name</option>";
			}
			$pos_html .= "</select> (<a href='".llmServer::getHPath("sites",'manage_positions')."'>������� ���� ��� ���������� �������</a>)";
		}
	
	
	echo "
	<h2 id='newsite'>{$title}</h2>
	<form action='".llmServer::getWPath()."' method='post' id='siteform'>
	<table align='center' width='100%'>
	
	<tr> 
	
	<td width=25%>��� ���� ".showHelp("������ ����� ����� ��������, <br>��� �� ���� ���-�� �������� ���� ����� �����")." </td><td><input type=text name=title value='{$code->title}' style='width:100%'></td> 
	</tr>
	
	<tr>
	
	<td width=25%>��� ������� </td><td>{$pos_html}</td> 
	
	</tr>
	
	<tr>
	<td width=25%>���������� ".showHelp("��� ����������, ���������, �������� ������ � ������")." </td><td><textarea name='content' style='width:100%' rows=10>{$code->content}</textarea></td>
	
	</tr>";
	
	$sii = "<select name='is_published'>";
	$si = array(1 => "��", 0 => "���");
	foreach($si as $k => $v) {
	
		if ($code->is_published == $k) $sel = "selected='selected'";
			else $sel = "";
		$sii .= "<option value='$k' $sel>$v</option>";
	}
	$sii .= "</select>";
	
	echo "<tr> 
	
	<td width=25%>������������</td><td>{$sii}</td> 
	
	</tr>
	";
	
	$show_on_sites = explode(",",$code->show_on_sites);
	$c = in_array(0,$show_on_sites) ? "selected='selected'" : "";
	$site_sel = "<select name='sites[]' multiple='miltiple' size=16 style='width:100%'><option value='0' {$c}>�� ���� ������</option>";
	$database->setQuery("SELECT * FROM sites ORDER BY id");
	$sites = $database->loadObjectList();
	foreach($sites as $site) {
	
		$c = in_array($site->id,$show_on_sites) ? "selected='selected'" : "";
		$site_sel .= "<option value='{$site->id}' {$c}>{$site->url}</option>";
	}
	$site_sel .= "</select>";
	
	echo "<tr> 
	
	<td width=25%>����������� �� ������ ".showHelp("������ �������� ����� �� ����� � ����� ����� � ����� ������� �����������<br>����� ����� ����� ������� �� ����� ������ ���� ��� ��������� ����������.<br>����� ���� �������, ���� ���� �������� ��� ��������� �� �����-�� ���������� �����, <br>� �� ��������� �������� ��� ���������")."<br>(����� Ctrl)</td><td>{$site_sel}</td> 
	
	</tr>
	";
	
	echo llmHTML::formBottom('save_code','��������� ���',2,"button-add");
	
	echo "
	</table>
	<input type='hidden' name='id' value='{$code->id}'>
	</form>";
}

/**
* ������� ������ ���������� ����
*/
function showStaticCodeList() {

	$database = llmServer::getDBO();
	
	$database->setQuery("SELECT static_code.* FROM static_code ORDER BY id");
	$list = $database->loadObjectList();
	
	if (sizeof($list) == 0) {
		
		echo "<div class=error>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;��������� ����� �� ����������<br><br></div>";
	}
	else {
		
		echo "<table align='center' width='100%'>";
		echo "<tr>
		<td class='sites-theader' width='5'> <b>ID</b> </td>
		<td class='sites-theader' width='40%'><b>��������</b></td>
		<td class='sites-theader' width='20%'><b>�������</b></td>
		<td class='sites-theader' width='10%'><b>�����������</b></td>
		<td class='sites-theader' width='29%'><b>��������</b></td>
		</tr>";
		
		foreach($list as $code) {
		
			$del_url = llmServer::getHPath("sites",'delete_static_code',"id={$code->id}");
			
			echo "<tr>
			<td>{$code->id}</td>
			<td><a href='".llmServer::getHPath("sites",'edit_static_code',"id={$code->id}")."'>{$code->title}</a></td>
			<td>{$code->position_name}</td>
			<td>".($code->is_published ? "��" : "���")."</td>
			<td><a href=\"javascript: if (confirm('�����?')) { window.location.href='{$del_url}' } else { void('') };\" class='delete'> �������?</a></td>
			</tr>";
		}
		
		echo "</table>";
	}
}

function exportPages() {

	//---------------------------- �������� �� ������� showPages
	$database = llmServer::getDBO();
	$site_id = (int)getParam('site_id');

	$database->setQuery("SELECT * FROM sites WHERE id = '{$site_id}'");
	$sites = $database->loadObjectList();$site = $sites[0];
		
	//���������� �������
	$filter_url      = getParam('filter_url');
	$filter_pr       = getParam('filter_pr');
	$filter_nesting  = getParam('filter_nesting');
	$filter_yindex   = getParam('filter_yindex',-1);
	
	//������ WHERE
	$where = array();
	if ($filter_url) {
	
		$where[] = " url LIKE '%$filter_url%' ";
	}
	if ($filter_pr!='') {
	
		$where[] = " pr = '{$filter_pr}' ";
	}
	if ($filter_nesting!='') {
	
		$where[] = " nesting = '{$filter_nesting}' ";
	}
	if ($filter_yindex==1) {
	
		$where[] = " is_in_yandex_index > 0 ";
	}
	if ($filter_yindex==0) {
	
		$where[] = " is_in_yandex_index = 0 ";
	}
	if ($where) $where_str = "AND ( ".implode(" AND ",$where)." )";
		else $where_str = "";
	
	//����� �������
	$database->setQuery("SELECT * FROM pages WHERE site_id = '$site_id' {$where_str} ORDER BY id");
	$pages = $database->loadObjectList();
	//---------------------------- �������� �� ������� showPages
	
	$format = getParam('format');
	
	//������� ��������� ����
	$rand = substr(md5(time()),0,5);$path = "tmp/export_{$rand}.txt";
	$f = fopen(llmServer::getPPath().$path,'w');
	
	foreach($pages as $page) {
	
		$URL              = $site->url.($page->url == "/" ? "" : $page->url);
		$SELF_LINKS_COUNT = $page->links_on_page;
		$PR               = $page->pr;
		$YANDEX_INDEX     = $page->is_in_yandex_index;
		
		$line = $format;
		$line = str_replace("{URL}"             ,$URL,$line);
		$line = str_replace("{SELF_LINKS_COUNT}",$SELF_LINKS_COUNT,$line);
		$line = str_replace("{PR}"              ,$PR,$line);
		$line = str_replace("{YANDEX_INDEX}"    ,$YANDEX_INDEX,$line);
		
		fputs($f,$line."\n");
	}
	fclose($f);
	
	echo "<h2>���� ������</h2>";
	echo "<p>��� ����� ������� <a href='".llmServer::getWPath().$path."' target='_blank'>�� ���� ������</a></p>";
	
	//�������� ������ �������
	llmServer::deleteOldFiles("tmp/export_*.txt");
}

function getLinksOnPage() {

	$database = llmServer::getDBO();
	$page_id  = (int)getParam('page_id');
	
	//������ ���������
	$database->setQuery("SELECT * FROM pages WHERE id = '$page_id'");
	$page = $database->loadObjectList();$page = $page[0];
	//����
	$database->setQuery("SELECT * FROM sites WHERE id = '{$page->site_id}'");
	$site = $database->loadObjectList();$site = $site[0];
	
	$full = $site->url.($page->url=='/' ? "" : $page->url);
	echo "<p>����� ����� ��������� ������:</p>";

	//������� ��� ������� ���� ��� ����������� ��������
	$http = new llmHTTP();
	$content = $http->get($full,$status,$current_url,false);
	$links = $http->getPageLinks($content,$current_url);
	$outher_links = $http->getOutherLinks($site,$links);

	//������ �������
	echo "<pre>";
	foreach($outher_links as $olink) {
	
		echo "$olink\n";
	}
	echo "<pre>";
}
function updatePRCY($site_id) {

	$database = llmServer::getDBO();
	
	$database->setQuery("SELECT url FROM sites WHERE id = '{$site_id}'");
	$url = $database->loadResult();
	
	$cy = seoYandex::getYandexCY($url);
	$google_pr = new googlepr();
	$pr = (int)$google_pr->get($url);
	
	$database->setQuery("UPDATE sites SET pr='{$pr}', cy = '{$cy}' WHERE id = '{$site_id}'");
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('sites',''),"���������� ����� ���������");
}

function deleteSite($site_id) {

	$database = llmServer::getDBO();
	
	//������� ����
	$database->setQuery("DELETE FROM sites WHERE id = '{$site_id}'");
	$database->query();

	//� ��� �������� + ������
	clearSitePages(false);
	
	llmServer::redirect(llmServer::getHPath('sites',''),"���� ������������ ������");
}

function linkActivation() {

	define("ICONV_ME","UTF-8");
	
	$database = llmServer::getDBO();
	
	$link_id    = (int)getParam('link_id');
	$act_status = (int)getParam('act_status');
	
	$database->setQuery("UPDATE links SET status = '{$act_status}' WHERE id = '{$link_id}'");
	$database->query();
	
	echo "<font color=gray>".($act_status == LLM_LINK_STATUS_ACTIVE ? "��������" : "���������")."</font>";
}

function updateLinkHtml() {

	define("ICONV_ME","UTF-8");
	
	$database = llmServer::getDBO();
	$link_id = (int)getParam('link_id');
	$page_id = (int)getParam('page_id');
	
	//� ��� �������� � UTF - ������������
	$html    = iconv("UTF-8","CP1251",getParam('new_html'));
	$html    = trim(str_replace(array("\r","\n"),"",$html));
	
	$database->setQuery("UPDATE links SET html='{$html}' WHERE id = '{$link_id}'");
	$database->query();
	
	echo "<font color=green>���������</font>";
}

/**
* ==>>> �������� �� projects.php::cleanProjectLinks()
*/
function deleteLink() {

	define("ICONV_ME","UTF-8");
	
	$database = llmServer::getDBO();
	$link_id = (int)getParam('link_id');
	$page_id = (int)getParam('page_id');
	
	//�������� ������� ������
	$database->setQuery("UPDATE pages SET links_on_page = links_on_page - 1 WHERE id = '{$page_id}'");
	$database->query();
	
	//������� ������
	$database->setQuery("DELETE FROM links WHERE id = '{$link_id}'");
	$database->query();
	
	echo "<font color=green>�������</font>";
}

function showLinksOnPage() {

	$database = llmServer::getDBO();
	$site_id = (int)getParam('site_id');
	$page_id = (int)getParam('page_id');
	require_once(llmServer::getPPath()."includes/llm_http.php");
	
	//������ ���������
	$database->setQuery("SELECT * FROM pages WHERE id = '$page_id'");
	$page = $database->loadObjectList();$page = $page[0];
	//����
	$database->setQuery("SELECT * FROM sites WHERE id = '$site_id'");
	$site = $database->loadObjectList();$site = $site[0];
	
	$full = $site->url.($page->url=='/' ? "" : $page->url);
	echo "<h2>������� ������ ��������:</h2>";
	echo "<p align='left'>&nbsp;&rarr;&nbsp;<a href='{$full}'>{$full}</a></p><br>";

	//������� ��� ������� ���� ��� ����������� ��������
	$http = new llmHTTP();
	$content = $http->get($full,$status,$current_url,false);
	$links = $http->getPageLinks($content,$current_url);
	$outher_links = $http->getOutherLinks($site,$links);

	//������ �������
	echo "<p align='left'>������� ������ ������� ������ ����� ����� (".sizeof($outher_links).")</p>";
	echo "<table align='center' width='100%'>";
	foreach($outher_links as $olink) {
	
		echo "<tr>
		<td><a href='$olink'>$olink</a></td>
		</tr>";
	}
	echo "</table>";

	//������ ���� ������
	$database->setQuery("SELECT links.*,sites.url as sites_url, pages.url as pages_url FROM links INNER JOIN pages on pages.id = links.page_id INNER JOIN sites ON sites.id = pages.site_id WHERE page_id = '$page_id'");
	$links = $database->loadObjectList();
	
	echo "
	
		<script type='text/javascript'>
		function displayRow(elem) {
		
			var row = document.getElementById(elem);
			if (row.style.display == '')  row.style.display = 'none';
			else row.style.display = '';
		}
		</script>
	";
	
	echo "<br><br><p align='left'>������, ����������� �������� (".sizeof($links).")   ".showHelp("� ���������, ������������� ���� �� ��������������,<br> ��� �������� ����� ����� ������ �������������. <br>����� ���� ������� � ������������ ����� ����� HTML-���� - 255 ��������.")."</p>";
	echo "<form name='linksform' id='linksform'><table align='center' width='100%'>";
	foreach($links as $olink) {
	
		$uri = $olink->sites_url.$olink->pages_url;
		
		if ($olink->is_in_index) {
		
			$it = "������������ � ������� �������. ��������� ��������: ";
		}
		else {
		
			$it = "�� ���������������� ��������. ��������� ��������: ";
		}
		if ($olink->last_index_check) $it .= date('d/m/Y H:i:s',$olink->last_index_check);
			else $it .= "���";
		//google
		if ($olink->is_in_google_index) {
		
			$it_g = "������������ � ������� Google. ��������� ��������: ";
		}
		else {
		
			$it_g = "�� ���������������� Google. ��������� ��������: ";
		}
		if ($olink->last_index_check) $it_g .= date('d/m/Y H:i:s',$olink->last_index_check);
			else $it_g .= "���";
			
			
		
		echo "<tr>
		<td width='70%'>".showHelp($it,$olink->is_in_index ? "plus" : "minus")."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".showHelp($it_g,$olink->is_in_google_index ? "plus" : "minus")."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$olink->html}</td>
		<td width='27%'><span onclick=\"dellink({$olink->id},$page_id,'status_box_{$olink->id}');\" title='������ ��������� � ���� ��������' style='cursor:pointer;cursor:hand;color:red'>������ ������</span> <span id='status_box_{$olink->id}'></span> (����� � ".date("d/m/Y",$olink->time_start).")</td>
		<td width='20'>
		<span style='cursor:hand' onClick=\"displayRow('tr_{$olink->id}')\" title='�������������� ������'><img src='".llmServer::GEtWPath()."template/icons/sm/edit.png' /></span>
		</td>
		</tr>
		<tr id='tr_{$olink->id}' style='display:none'>
		<td><textarea style='width:100%' rows=3 name='inp_{$olink->id}'>{$olink->html}</textarea></td><td colspan=2><span title='���������� ��������� HTML-����' onclick=\"quicksavelink({$olink->id},$page_id,'inp_{$olink->id}','status_box2_{$olink->id}');\" class=save>������� ����������</span> <span id='status_box2_{$olink->id}'></span></td>
		</tr>";
	}
	echo "</table></form>";
}

function managePages() {

	$database = llmServer::getDBO();
	$site_id = (int)getParam('site_id');
	
	$is_executed = false;
	$action = getParam('action');
	switch($action) {
	
		case 'unpublish':
			$set = ' status = '.LLM_PAGE_STATUS_UNACTIVE;
			break;
		case 'publish':
			$set = ' status = '.LLM_PAGE_STATUS_ACTIVE;
			break;
		case 'not_get_pr':
			$set = ' pr = -2 ';
			break;
		case 'get_pr':
			$set = ' pr = -1 ';
			break;
		case 'not_get_index':
			$set = ' is_in_yandex_index = -2, is_in_google_index = -2 ';
			break;
		case 'get_index':
			$set = ' is_in_yandex_index = -1, is_in_google_index = -1 ';
			break;
		case 'delete':
			{
				$ids = $_POST['urls'];$arr = array();
				foreach($ids as $k => $ii) $arr[] = (int)$k;

				if ($arr) {
					
					$database->setQuery("DELETE FROM pages WHERE id IN (".implode(',',$arr).")");
					$database->query();
					
					$database->setQuery("DELETE FROM links WHERE page_id IN (".implode(',',$arr).")");
					$database->query();
				}
				
				$is_executed = true;
			}
			break;
		default:
			llmServer::showError("Unknown command '{$action}'",false);
			return;
			break;
	}
	
	if (!$is_executed) {
		
		$ids = $_POST['urls'];$arr = array();
		foreach($ids as $k => $ii) $arr[] = (int)$k;
		
		if ($arr) {
			
			$database->setQuery("UPDATE pages SET {$set} WHERE id IN (".implode(',',$arr).")");
			$database->query();
		}
	}
	
	//��������� ���������� �������
	$filter_url      = getParam('filter_url');
	$filter_pr       = getParam('filter_pr');
	$filter_nesting  = getParam('filter_nesting');
	$filter_yindex   = getParam('filter_yindex',-1);
	
	llmServer::redirect(llmServer::getHPath('sites','show_pages','site_id='.$site_id.'&filter_url='.urlencode($filter_url).'&filter_pr='.$filter_pr.'&filter_nesting='.$filter_nesting.'&filter_yindex='.$filter_yindex),"�������� �����������");
}

function clearPagesNULLPR() {

	$database = llmServer::getDBO();
	$site_id = (int)getParam('site_id');
	
	$database->setQuery("UPDATE pages SET pr=-1 WHERE site_id = '{$site_id}' AND pr=0");
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('sites','show_pages','site_id='.$site_id),"PR �������");
}

function clearPagesPR() {

	$database = llmServer::getDBO();
	$site_id = (int)getParam('site_id');
	
	$database->setQuery("UPDATE pages SET pr=-1 WHERE site_id = '{$site_id}'");
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('sites','show_pages','site_id='.$site_id),"PR ���� ������� ����� �������");
}

function clearSitePages($external_func = true) {

	$database = llmServer::getDBO();
	$site_id = (int)getParam('site_id');
	
	//��� ��������� �����
	$database->setQuery("SELECT id FROM pages WHERE site_id = '{$site_id}'");
	$pages_ids = $database->loadResultArray();

	if (sizeof($pages_ids) > 0) {
		
		//������� ��������� �� ���������� ������
		$database->setQuery("DELETE FROM links WHERE page_id IN (".implode(',',$pages_ids).")");
		$database->query();
	}
	
	//� ���� ��������	
	$database->setQuery("DELETE FROM pages WHERE site_id = '{$site_id}'");
	$database->query();
	
	if ($external_func) llmServer::redirect(llmServer::getHPath('sites',''),"��� �������� ����� ������� �� �������");
}

function changeSiteIndexingState($state,$site_id) {

	$database = llmServer::getDBO();
	
	$database->setQuery("UPDATE sites SET cron_index = '$state' WHERE id = '$site_id'");
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('sites',''),"������� �� ���������� ����� ����������");
}

function showPages() {

	//---------------------------- �������� ���� � ������� exportPages
	$database = llmServer::getDBO();
	$site_id = (int)getParam('site_id');

	$database->setQuery("SELECT * FROM sites WHERE id = '{$site_id}'");
	$sites = $database->loadObjectList();$site = $sites[0];
		
	//���������� �������
	$filter_url      = getParam('filter_url');
	$filter_pr       = getParam('filter_pr');
	$filter_nesting  = getParam('filter_nesting');
	$filter_yindex   = getParam('filter_yindex',-1);
	
	$sii = "<select name='filter_yindex'>";
	$si = array(-1 => "�� �����", 1 => "��", 0 => "���");
	foreach($si as $k => $v) {
	
		if ($filter_yindex == $k) $sel = "selected='selected'";
			else $sel = "";
		$sii .= "<option value='$k' $sel>$v</option>";
	}
	$sii .= "</select>";
	
	echo "<form action='index.php?mod=sites&task=show_pages&site_id={$site_id}' method='post'><h2>����� ������� �� ����������</h2>";
	echo "<p>
	��������� � URL: <input name='filter_url' value='{$filter_url}' size='20'>
	PR: <input name='filter_pr' value='{$filter_pr}' size='5'>
	��: <input name='filter_nesting' value='{$filter_nesting}' size='5'>
	� �������: {$sii}
	&rarr;&nbsp;<input type='submit' style='border: 1px solid black' value=' �������� '>
	��� <a href='index.php?mod=sites&task=show_pages&site_id={$site_id}'>������ ������</a>
	</p>";
	
	echo "</form><br>";

	//������ WHERE
	$where = array();
	if ($filter_url) {
	
		$where[] = " url LIKE '%$filter_url%' ";
	}
	if ($filter_pr!='') {
	
		$where[] = " pr = '{$filter_pr}' ";
	}
	if ($filter_nesting!='') {
	
		$where[] = " nesting = '{$filter_nesting}' ";
	}
	if ($filter_yindex==1) {
	
		$where[] = " is_in_yandex_index > 0 ";
	}
	if ($filter_yindex==0) {
	
		$where[] = " is_in_yandex_index = 0 ";
	}
	if ($where) $where_str = "AND ( ".implode(" AND ",$where)." )";
		else $where_str = "";
	
	//����� �������
	$database->setQuery("SELECT * FROM pages WHERE site_id = '$site_id' {$where_str} ORDER BY id");
	$pages = $database->loadObjectList();
	//---------------------------- �������� ���� � ������� exportPages
	
	//������������� �������
	$start_from = (int)getParam('start_from');
	$per_page = llmConfig::get("PAGES_ON_PAGE",100);
	
	echo "<h2 id='pageslist'>������ ������� ����� <span>{$site->url}</span> (".sizeof($pages).")</h2>";
	$pages_size = sizeof($pages);
	if ($pages_size == 0) {
		
		echo "������� �� ����������, �������� ���� ��� �� ���������������";
		return;
	}

    $start = $start_from > $pages_size ? 0 : $start_from;
    $end = $start_from + $per_page > $pages_size ? $pages_size : $start_from + $per_page;
    
    //������ ��������������� ������� ��� ����������
	for($jsarr=array(),$i=$start;$i<$end;$i++) $jsarr[] = $pages[$i]->id;
		
	//���� �������� ������ ����, �� ����� ������ ��������� new Array(123)
	//������ ��� ������������� ������� ������ � ������������ 123, � �� ������ 123 � ������� ��������
	if (sizeof($jsarr) > 1) {
		
		$js_on_click = "checkAll(new Array(".implode(',',$jsarr)."))";
	}
	else {
	
		$js_on_click = "checkAll(new Array(\"".$jsarr[0]."\"))";
	}
	
	echo "<form action='index.php?mod=sites&task=manage_pages' method='post'>
	<table align='center' width='100%'>";
	echo "<tr>
	
	<td width=10>�</td>
	<td width='50%'>URL</td>
	<td width='10%' align='center'>� ������� Yandex/Google</td>
	<td width='10%'>PR</td>
	<td width='15'>��</td>
	<td width='15' align='center'>��</td>
	<td width='25' align='center'>�� ����</td>
	<td width='10%'>�������</td>
	<td width=10><input type='checkbox' name='mainchk' onClick='{$js_on_click}'></td>
	</tr>";

	require_once(llmServer::getPPath().'/opensource/_.php');
	
	for($index=1,$i=$start;$i<$end;$i++) {
	
		$page = $pages[$i];
		//��� �����
		$href = $site->url.($page->url == "/" ? "" : $page->url);
		$anchor = llmHTML::trimURL($page->url);

		//���� �� � �������
		if (1) {
			
			//������� ��������� �� ������ seoYandex::getYInIndex 
			$yandex_path = YANDEX_MASK;
			$href_no_http = str_replace("http://","",$href);
			
			$pu = parse_url("http://".$href_no_http);
			$host = $pu['host'];
			$pos = strpos($host,"www.");
			//���� ��� ��������� www. �� ��� �� ������ �����
			if ($pos === false) {
			
				$yandex_path = str_replace("{URL}",$href_no_http,$yandex_path);
				$full_url = str_replace($host,"www.".$host,$href_no_http);
				$yandex_path = str_replace("{WWW_URL}",$full_url,$yandex_path);
			}
			else {
			
				//����� ��� ���� - ���� ��������
				$yandex_path = str_replace("{WWW_URL}",$href_no_http,$yandex_path);
				$tmp = "http://".$href_no_http;
				$short_url = str_replace("http://www.","http://",$tmp);
				$tmp = str_replace("http://","",$short_url);
				$yandex_path = str_replace("{URL}",$tmp,$yandex_path);
			}
			//����� ��������� (���� �������� �������)
			
			$google_path = str_replace("{URL}",$href,GOOGLE_MASK);
		}
		
		$index_state = ($page->is_in_yandex_index==1 ? "<font color=green><a href='$yandex_path' target='_blank'>��</a></font>" : ($page->is_in_yandex_index == 0 ? "<font color=red>���</font>" : ($page->is_in_yandex_index == -2 ? '���' : -1 )))." / ".($page->is_in_google_index==1 ? "<font color=green><a href='{$google_path}' target='_blank'>��</a></b></font>" : ($page->is_in_google_index == 0 ? "<font color=red>���</font>" : ($page->is_in_google_index == -2 ? '���' : -1 )));
		
		$aa = $i+1;
		$cl = @$cl==0 ? 1 : 0;
		$cl_class = " class='row{$cl}' ";
		echo "<tr{$cl_class}><td>{$aa}</td>
		
		<td><a href='{$href}'>{$anchor}</a></td>
		<td width='10%'>{$index_state}</td>
		<td>".($page->pr == -2 ? '��������' : "<b>".$page->pr."</b>")."</td>
		<td>{$page->nesting}</td>
		<td align='center'><a href='".llmServer::getHPath('sites','get_links_on_page','page_id='.$page->id.'&no_html=1')."' rel='ibox&width=500&height=150'>{$page->external_links_count}</a></td>
		<td align='center'>".($page->links_on_page ? "<a href='".llmServer::getHPath('sites','show_our_links_on_page','page_id='.$page->id.'&site_id='.$site_id)."'>[&nbsp;{$page->links_on_page}&nbsp;]</a>" : "&nbsp;")."</td>
		<td>".($page->status == LLM_PAGE_STATUS_ACTIVE ? '<font color=green>��</font>' : '<font color=red>���</font>')."</td>
		<td width=10><input type='checkbox' name='urls[{$page->id}]'></td>
		</tr>";
		
		$index++;
	}
	
	//��������� �� ��������
	$page_select = "";
	if ($pages_size <= $per_page) {
	
		$page_select = "";
	}
	else {
	
		$page_count = ceil($pages_size/$per_page);
		$links = '';
		for ($i=1;$i<=$page_count;$i++) {
		
			$href = llmServer::getHPath('sites','show_pages',"site_id={$site_id}&filter_url=".urlencode($filter_url)."&filter_pr={$filter_pr}&filter_nesting={$filter_nesting}&filter_yindex={$filter_yindex}&start_from=".(($i-1)*$per_page));
			if (($i-1)*$per_page != $start_from) $links .= "&nbsp;<a href='{$href}'>[&nbsp;$i&nbsp;]</a>&nbsp;&nbsp;";
				else $links .= "&nbsp;<span>{$i}</span>&nbsp;&nbsp;";
		}
		$page_select .= $links;
	}
	
	echo "</table>
	<br>
	<p align='center'>{$page_select}</p>
	<br>
	<p align='center'>
	<select name='action'>
		<option value='unpublish'>��������� ��������</option>
		<option value='publish'>������������ ��������</option>
		<option value='delete'>������� �������� �� �������</option>
		<option value='not_get_pr'>�� ������� PR � ������ �������</option>
		<option value='get_pr'>����������� PR � ������ �������</option>
		<option value='not_get_index'>�������� �������� ��������������������</option>
		<option value='get_index'>������������� ��������������������</option>
	</select>
	&nbsp;
	<input type='submit' name='���������' style='border: 1px solid black'>
	<input type='hidden' name='site_id' value='$site_id'>
	<input type='hidden' name='filter_url' value='$filter_url'>
	<input type='hidden' name='filter_pr' value='$filter_pr'>
	<input type='hidden' name='filter_nesting' value='$filter_nesting'>
	<input type='hidden' name='filter_yindex' value='$filter_yindex'>
	</p>
	</form>";
	
	echo "<form action='index.php?mod=sites&task=export_pages&site_id={$site_id}' method='post'><h2>������� ������� ����� � ����</h2>";
	echo "<p>
	������ ������".showHelp("������ ���������� �������� �������� ������� ������������ � ����<br>{URL} - ����� ��������<br>{SELF_LINKS_COUNT} - ����� ����� ������<br>{PR} - �� Google PR<br>{YANDEX_INDEX} - ���� �� � ������� Yandex: 1 ��� 0")." &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <input name='format' value='{URL}' style='width:50%'>
	<input type='submit' style='border: 1px solid black' value=' ������� '>
	<input type='hidden' name='filter_url' value='$filter_url'>
	<input type='hidden' name='filter_pr' value='$filter_pr'>
	<input type='hidden' name='filter_nesting' value='$filter_nesting'>
	<input type='hidden' name='filter_yindex' value='$filter_yindex'>
	</p>";
	
	echo "</form><br>";
}

function saveSite() {

	$database = llmServer::getDBO();
	
	$id              = (int)getParam('id');
	$url             = getParam('url');
	$url             = str_replace(array("'",'"'),"",$url);//������� ���� ��� �����
	$links_on_main   = (int)getParam('links_on_main');
	$links_on_other  = (int)getParam('links_on_other');
	$links_delimiter = getParam('links_delimiter');
	$links_delimiter = str_replace(" ","&nbsp;",$links_delimiter);//MySQL ����� �������� �������
	$exclude_urls    = getParam('exclude_urls');
	$css_class       = getParam('css_class');
	$category_id     = (int)getParam('category_id');
	$new_cat         = getParam('new_cat');
	$charset         = getParam('charset');
	
	$is_ftp          = getParam('is_ftp');
	$ftp_host        = getParam('ftp_host');
	$ftp_user        = getParam('ftp_user');
	$ftp_password    = getParam('ftp_password');
	$ftp_dir         = getParam('ftp_dir');

	$url             = trim(strtolower($url));//��� ����� ��� ���� ������, ����� ���� ������ ��� ������, ���� http://Super-Site.Ru, ������ ����, ������
	
	//�������� ���� �� ����������
	$pu     = @parse_url($url);
	$is_bad = false;
	if ($pu === false) $is_bad = true;
	if (!isset($pu['path']) || $pu['path']!='/') $is_bad = true;
	$pos = strpos($url,".");
	if ($pos === false) $is_bad = true;
	if (!isset($pu['scheme']) || !in_array(strtoupper($pu['scheme']),array('HTTP','HTTPS'))) $is_bad = true;

	if ($is_bad && !$id) {
	
		echo "����� ����� ������ �� � ���������� ������� (<b>HTTP://����.��/</b>)";
		return;
	}

	if (!$id) {
		
		//�������� ��������� � www � ��� ����, ��������� �� redsoft.ru
		//� ������ ������������� ����� � ��������� ��� ������
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);//�������� ������
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Opera/9.00 (Windows NT 5.1; U; ru)');
		curl_setopt($curl, CURLOPT_REFERER, $url);
		
		$header    = curl_exec($curl);
		$errno  = curl_errno($curl);
		if ($errno > 0) {

			$status = curl_error($curl);
			echo "������ ���������� � ������: {$status}";
			return;
		}
		
		curl_close($curl);
	
		$pos = strpos($header,"301 Moved Permanently");
		if ($pos!==false) {
		
			preg_match("|Location:(.*)|",$header,$mt);
			$loc = trim($mt[1]);
			
			echo "��������� ���� ����� `$url` �������������� ������ �� ����� `$loc`. �������� ��� ����� ����������������� URL ������ ������ � ���������� �����.";
			return;
		}
	}
		
	if (!$charset && !$id) {
	
		echo "�������� ���������� ��������� �����";
		return;
	}
	
	//���� ���� ������� ���������
	if ($new_cat) {
	
		//��������� ������� �����
		$database->setQuery("SELECT * FROM categories WHERE name='{$new_cat}'");
		$nn = $database->loadObjectList();
		if (sizeof($nn) > 0) {
		
			//���������� ��� ������
			$old_cat = $nn[0];
			$category_id = $old_cat->id;
		}
		else {
		
			//������� �����
			$database->setQuery("INSERT INTO categories (name) VALUES ('{$new_cat}')");
			$database->query();
			
			$category_id = $database->insertid();
		}
	}
	
	if ($id) {
	
		$query = "UPDATE sites SET links_on_main='$links_on_main', links_on_other='$links_on_other', links_delimiter='$links_delimiter', exclude_urls = '$exclude_urls',css_class = '$css_class', category_id = '$category_id',is_ftp = '$is_ftp',ftp_host = '$ftp_host', ftp_user='$ftp_user', ftp_password='$ftp_password', ftp_dir='$ftp_dir' WHERE id = '$id'";
	}
	else {
	
		//���� ��������� ���� ������, ����� ��������� �� ���������� ���-������
		$domain_key = md5(rand().rand().$url.$links_on_main);
		
		//����� �� ������� ����������
		$cy = seoYandex::getYandexCY($url);
		$google_pr = new googlepr();
		$pr = (int)$google_pr->get($url);
		
		$query = "INSERT INTO sites (url,links_on_main,links_on_other,links_delimiter,pr,cy,domain_key,exclude_urls,css_class,category_id,charset,is_ftp,ftp_host,ftp_user,ftp_password) VALUES ('$url','$links_on_main','$links_on_other','$links_delimiter','{$pr}','{$cy}','{$domain_key}','{$exclude_urls}','{$css_class}','{$category_id}','{$charset}','{$is_ftp}','{$ftp_host}','{$ftp_user}','{$ftp_password}')";
	}
	
	$database->setQuery($query);
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('sites',''),"���� ������� ��������");
}

function editSite($id,$title) {

	$database = llmServer::getDBO();
	
	if ($id) {
	
		$database->setQuery("SELECT * FROM sites WHERE id = '{$id}'");
		$sites = $database->loadObjectList();
		
		if (sizeof($sites) == 0) {
		
			llmServer::showError("���� `$id` �� ���������",false);
		}
		$site = $sites[0];
	}
	else {
	
		$site = new stdClass();
		$site->id = 0;
		$site->url = 'http://';
		$site->links_on_main = 10;
		$site->links_on_other = 10;
		$site->links_delimiter = "; ";
		$site->exclude_urls = "";
		$site->css_class = "";
		$site->category_id = 0;
		$site->charset = "";
		
		$site->is_ftp = 0;
		$site->ftp_host = "";
		$site->ftp_user = "";
		$site->ftp_password = "";
		$site->ftp_dir = "";
		
		$site->domain_key = "";
		$site->last_time_get_links = "";
	}
	
	$csel = buildCatSel($site->category_id);
	
	if (!$site->id) {
		
		//�����������
		$css = explode(',',llmConfig::get('ALLOWED_CHARSETS',"CP1251,UTF-8,KOI8-R"));
		$sites_charset = "<select name='charset'><option value='' ".($site->charset ? "" : "selected='selected'").">[�������� �� ������]</option>";
		foreach ($css as $cs) {
			
			$cs = trim($cs);
			$is_sel = $site->charset == $cs ? "selected='selected'" : "";
			$sites_charset.= "<option value='$cs' {$is_sel}>$cs</option>";
		}
		$sites_charset .= "</select>";
		$sites_charset .= "<span style='cursor:pointer;cursor:hand;' onclick=\"checkcharset()\"> ��������������</span> <span id='charsetbox'></span>";
	}
	else {
	
		$sites_charset = $site->charset;
	}
	
	echo "
	<h2 id='newsite'>{$title}</h2>
	<form action='".llmServer::getWPath()."' method='post' id='siteform'>
	<table align='center' width='100%'>
	
	<tr> 
	
	
	<td width=25%>����� ".showHelp("���� �� ����� � http � ������<br>�� ���� ������: <b>http://�����.RU/</b><br>� <font color=red>�������</font> index.html ��� index.php � �����!")." </td>  <td>".(!$id ? "<input type=text name='url' id='sitesel' value='{$site->url}' size=28>" : $site->url)."</td> 
	
	<td width=25%>��������� �����</td>  <td> {$sites_charset} </td>
	
	
	</tr>
	
	<tr> 
	
	<td width=25%>������ �� ������� ".showHelp("��� ����������� ������ � ��������� �������� ��� ����� ������<br>������� �������� ����������� ������� ������ �������. <br>����� �� ����������� ������� ������, �� ������������� �������.")."</td>  <td><input type=text name='links_on_main' value='{$site->links_on_main}' size=10></td> 
	<td width=25%>������ �� ���������</td>  <td><input type=text name='links_on_other' value='{$site->links_on_other}' size=10></td>
	
	</tr>
	<tr> 
	
	<td width=25%>����������� ������ ".showHelp("��������� ����, ����������� ����� �������� �� ��������")."</td>  <td><input type=text name='links_delimiter' value='{$site->links_delimiter}' size=10></td> 
	<td width=25%>CSS-����� ������ ".showHelp("������� �� ������ � ������� ������. ���� �� ����� ������� HTML-��� ����: <pre>&amp;lt;a href=\'�����.��\'&amp;gt;������&amp;lt;/a&amp;gt;</pre>�� ���������� <b>class=\'xxx\'</b> ����������� ��������������� ����� ������ <b>a</b> � ������ <b>href</b>. <br>���� ��� ��� �� ����������, �� ����� ����������� CSS-������ ����� � ���������� �������")."</td>  <td><input type=text name='css_class' value='{$site->css_class}' size=10>
	
	</tr>
	
	<tr> <td width=25%>��������� ".showHelp("��� ��������� ����� ������� ��� �� ����� ����� � �������<br>���� ����� �� ���� �������������.")."</td>  <td colspan=2>$csel&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ��� ������� ��� ����� ���������&rarr; </td><td><input type=text name='new_cat'></td> </tr>
	
	<tr> <td width=25%>��������� �� ���������� ".showHelp("�� ������ ������ ����������� ����� ���������� ���������, �������� ������� � ������ ��������� ������ ����������<br>����� ��������� �� � �� ����� ������� � ����. �������� ��� ���������� ���� �� �������� ������� �����-���� ����������<br> ��� �������� �� �������������� ��� �� ���� ��������� ����������� ����������� �������� ������, ������������ ������. <br>������ ����������� � ���������, ��������� ���� ��������. ���� ��������� ������ - � �������� �� ����� ����������������.")."</td>  <td colspan=2>
	<textarea name='exclude_urls' style='width:100%' rows=6>{$site->exclude_urls}</textarea></td>
	<td>������:<br><br>
<pre>
/forum/
com_frontpage
index2.php?
/administrator/
/textpattern/
/cpm/
/admin/
</pre><br>
	</td> 
	
	</tr>";
	
	$ftp_sel = "<select name='is_ftp'>";$vals = array("��������","�������");
	for ($i=0;$i<=1;$i++) {
	
		$ftp_sel .= "<option value='$i'>".$vals[$i]."</option>";
	}
	$ftp_sel .= "</select>";
	
	
	echo "<tr> <td width=25%>���-������ ".showHelp("���� �� �������� ��������� ��������� ����������,<br>�� ������������ �������� ���������� ������ �������� ������ �� FTP<br>���� ����� ��������� ������ ����� ���������� ����� � �������� �� ���� ���������� � �������")."</td>  <td>$ftp_sel</td><td>������������: <input type='text' size=10 name='ftp_user' ".getDisabled($site->id)." value='{$site->ftp_user}'></td><td>������: <input type='text' size=10 name='ftp_password' ".getDisabled($site->id)." value='{$site->ftp_password}'></td></tr>
	
	<tr> <td>����: ".showHelp("IP-����� ��� �������� ��� �����, ��� ftp:// ��� http:// � ������")."</td><td><input type='text' size=18 name='ftp_host' ".getDisabled($site->id)." value='{$site->ftp_host}'></td><td>����������: ".showHelp("���������� ������������ ������� ��������� ������ �����<br>�� �������� � �������� ���� ���� ����� ����������. ��������:<br>/httpdocs/<br>/�����/www/<br>/www/�����/<br>/public_html/<br>/domains/�����/html/<br>")."</td><td><input type='text' name='ftp_dir' ".getDisabled($site->id)." value='{$site->ftp_dir}'></td></tr>
	
	<tr> <td width=25%>�������� �������</td><td colspan=3>".($id ? "
	&rarr;&nbsp;&nbsp;<span style='cursor:pointer;cursor:hand;' title='' onclick=\"check_ftp_dir()\">��������� ������</span>&nbsp;&nbsp;&nbsp;<span id='ftp_result'></span>" : "&nbsp;---&nbsp;" )."</td></tr>";

	if ($site->domain_key) echo "<tr> <td width=25%>DomainKey</td><td colspan=3>{$site->domain_key}</td></tr>";

	echo "<tr> <td width=25%>��������� ����� ������� ".showHelp("�����, ����� ������ ��������� � ������� get.php ��� ��������� ����� ������ ��� ������")."</td><td colspan=3>".($site->last_time_get_links == 0 ? "���" : date("d/m/Y H:i:s",$site->last_time_get_links) )."</td></tr>";
	
	echo llmHTML::formBottom('save_site','��������� ����',4,"button-add");
	
	echo "
	</table>
	<input type='hidden' name='id' value='{$site->id}'>
	</form>";
}

function getDisabled($id) {

	return $id ? "" : " disabled='disabled' ";
}

function checkFTP() {

	define("ICONV_ME","UTF-8");
	
	$id   = (int)getParam('id');
	$user = getParam('ftp_user');
	$pass = getParam('ftp_password');
	$host = getParam('ftp_host');
	$dir  = getParam('ftp_dir');
	
	echo "<br>";

	ftpClient::$user = $user;
	ftpClient::$pass = $pass;
	ftpClient::$host = $host;
	ftpClient::$dir  = $dir;
	
	$ftp = ftpClient::getInstance();
	if (!$ftp->_conn) {
	
		echo "<span style='color:red'>�� ������� ������������ � $host</span> (".translateError($ftp->_error).")";
		return;
	}
	
	$dirs = ftpClient::ftp_glob($dir);
	
	echo "<span style='color:green'>������ ����������: </span>".implode($dirs,", ")."";
	
	$database = llmServer::getDBO();
	
	$database->setQuery("SELECT * FROM sites WHERE id = '{$id}'");
	$sites = $database->loadObjectList();
	$site = $sites[0];
	
	$domain_dir = "llm-".$site->domain_key;
	$exists = in_array($domain_dir,$dirs);
	if (!$exists) {
	
		echo "<br><span style='color:red'>���������� {$domain_dir} �� �������, ������ �� FTP �� ��������</span>";
		return;
	}
	else {
	
		echo "<br><span style='color:green'>���������� {$domain_dir} �������, ������ �� FTP ��������</span>";
	}
}

function translateError($code) {

	switch($code) {
	
		case 1:
			return "Cannot connect to host";
		case 2:
			return "Bad login info";
	
	}
}

function listSites() {

	$database = llmServer::getDBO();
	
	@list($field,$direction) = explode(':',getParam("sort"));
	if (!in_array($field,array("pr","id","cy"))) $field = "id";
	if (!in_array($direction,array("asc","desc"))) $direction = "asc";
	
	$cat_id = (int)getParam('cat_id',0);
	if ($cat_id) {
	
		$wcat = "WHERE category_id = '{$cat_id}'";
	}
	else $wcat = "";
	
	//������ ������� ������� � ������� �����
	$database->setQuery("SELECT site_id,count(*) as count FROM pages GROUP BY site_id");
	$pcount = $database->loadObjectList('site_id');
	
	//������������� �������
	$start_from = (int)getParam('start_from');
	$per_page = llmConfig::get("SITES_ON_PAGE",20);
	
	$database->setQuery("SELECT sites.*,categories.name as cat_name FROM sites LEFT JOIN categories ON sites.category_id = categories.id {$wcat} ORDER BY {$field} {$direction}");
	$list = $database->loadObjectList();
	$list_sz = sizeof($list);
	
    $start = $start_from > $list_sz ? 0 : $start_from;
    $end = $start_from + $per_page > $list_sz ? $list_sz : $start_from + $per_page;
    
    //���������
	echo "<table class='head' cellpadding=0 cellspacing=0 width='100%'><tr><td nowrap><h2 id=sites>����� (����� {$list_sz})</h2></td><td class=\"filter\"><a href='".llmServer::getHPath('sites','','&sort=pr:asc')."' title=\"������������� �� PR\">&uarr;</a>&nbsp;PR&nbsp;<a href='".llmServer::getHPath('sites','','&sort=pr:desc')."' title=\"������������� �� PR\">&darr;</a> | <a href='".llmServer::getHPath('sites','','&sort=id:asc')."' title=\"������������� �� ID\">&uarr;</a>&nbsp;ID&nbsp;<a href='".llmServer::getHPath('sites','','&sort=id:desc')."' title=\"������������� �� ID\">&darr;</a> | <a href='".llmServer::getHPath('sites','','&sort=cy:asc')."' title=\"������������� �� ���\">&uarr;</a>&nbsp;���&nbsp;<a href='".llmServer::getHPath('sites','','&sort=cy:desc')."' title=\"������������� �� ���\">&darr;</a>  &nbsp;&nbsp;&nbsp;&nbsp;";
	
	//������������ � �������� ���������
	echo " ���������: ".buildCatSel($cat_id,"onChange='redirSites(\"".llmServer::getHPath('sites','')."\",this,\"{$field}:{$direction}\")'")." ��� <a href='".llmServer::getHPath('sites','')."'>������ ������</a> 	</td>	</tr>	</table>";
	if ($list_sz == 0) {
		
		echo "<div class=error>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;������ �� ����������<br><br></div>";
	}
	else {
		
		echo "<table align='center' width='100%'>";$sindex = 1;
		echo "<tr>
		<td class='sites-theader' width='5'> <b>ID</b> </td>
		<td class='sites-theader' width='27%'><b>����� �����</b></td>
		<td class='sites-theader' width='50'><b>PR � ���</b></td>
		<td class='sites-theader' ><b>��������</b></td>
		<td class='sites-theader' align='center'><b>���������</b></td>
		<td class='sites-theader' colspan=2 align='center'><b>��������</b></td>
		</tr>";
		
		for($i=$start;$i<$end;$i++) {
		
			$site = $list[$i];
			//����������
			if ($site->cron_index) {
			
				$cron_index = "��������� �� ���������� <a href='".llmServer::getHPath('sites','off_site_index','site_id='.$site->id)."'>(�����)</a>";
			}
			else {
			
				$cron_index = "<a href='".llmServer::getHPath('sites','on_site_index','site_id='.$site->id)."'> ��������� � ������� ����������</a>";
			}
			
			//��� �� ��������
			$del_url = llmServer::getHPath('sites','delete_site','site_id='.$site->id);
			
			$cl = @$cl==0 ? 1 : 0;
			$cl_class = " class='row{$cl}' ";
			echo "<tr{$cl_class}>
			<td> {$site->id}</td>
			<td nowrap='nowrap'> <a href='".llmServer::getHPath('sites','edit_site','id='.$site->id)."'>{$site->url}</a> [<a href='".llmServer::getHPath('articles','view_site_articles','site_id='.$site->id)."'>������</a>]</td>
			<td nowrap='nowrap'> PR:{$site->pr} ���:{$site->cy} <a href='".llmServer::getHPath('sites','update_main_pr','site_id='.$site->id)."'><img src='".llmServer::getWPath()."template/icons/sm/refresh.png'></a></td>
			<td align='center'><a href='".llmServer::getHPath('sites','show_pages','site_id='.$site->id)."'>".@$pcount[$site->id]->count."</a></td>
			<td align='center'> {$site->cat_name}</td>
			<td nowrap='nowrap'> $cron_index </td> <td nowrap='nowrap'> <a href=\"javascript: if (confirm('�� ����� ������ �������. �������������� ����� �� �������� � ��� �����. ����� ������ ���������� �� ��������. ������ ��������� ��������, ���� �� ����� ������� ������.')) { window.location.href='{$del_url}' } else { void('') };\" class='delete'> �������?</a></td>
			</tr>";
	
			$sindex++;
		}
		echo "</table>";
	
		//��������� �� ��������
		$page_select = "";
		if ($list_sz < $per_page) {
		
			$page_select = "";
		}
		else {
		
			$page_count = ceil($list_sz/$per_page);
			$links = '';
			for ($i=1;$i<=$page_count;$i++) {
			
				$href = llmServer::getHPath('sites','',"sort={$field}:{$direction}&cat_id={$cat_id}&start_from=".(($i-1)*$per_page));
				if (($i-1)*$per_page != $start_from) $links .= "&nbsp;<a href='{$href}'>[&nbsp;$i&nbsp;]</a>&nbsp;&nbsp;";
					else $links .= "&nbsp;<span>{$i}</span>&nbsp;&nbsp;";
			}
			$page_select .= $links;
		}
		
		echo "<br><p align='center'>{$page_select}</p>";
	}
	
	editSite(0,"���������� ������ �����");
}
?>