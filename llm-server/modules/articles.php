<?PHP
defined('LLM_STARTED') or die("o_0");

switch($task) {

	case 'deleteimage':
		deleteImage();
		break;
		
	case 'create_archive':
		createArchive();
		break;
		
	case 'delete_article':
		deleteArticle();
		break;
		
	case 'save_article':
		saveArticle();
		break;
		
	case 'add_article':
		llmTitler::put("�������� ������");
		addArticleForm();
		break;
		
	case 'save_template':
		saveTemplate();
		break;
		
	case 'savefolder':
		saveSiteFolder();
		break;
		
	case 'create_template':
		llmTitler::put("�������� �������");
		preCreateTemplate();
		break;

	case 'view_site_articles':
		llmTitler::put("���������� �������� �����");
		viewSiteArticles();
		break;
}

/**
* �������� ���������� ���������� ������
*/
function saveSiteFolder() {

	define("ICONV_ME","UTF-8");
	
	$database = llmServer::getDBO();
	
	$site_id    = (int)getParam('site_id');
	$folder     = getParam('folder');
	$folder     = preg_replace("|[^a-z0-9]|Umsi","",$folder);
	
	$database->setQuery("UPDATE sites SET articles_folder = '{$folder}' WHERE id = '{$site_id}'");
	$database->query();
	
	echo "<font color=green>����� ��� ���������� `{$folder}` ���������.</font>";
}

/**
* ������� ������ �� ����
*/
function deleteArticle() {

	$database = llmServer::getDBO();
	
	$site_id    = (int)getParam('site_id');
	$id         = (int)getParam('id');
	
	$database->setQuery("SELECT * FROM articles WHERE id='{$id}'");
	$art = $database->loadObjectList();
	
	if (!isset($art[0])) {
	
		echo "����� ������ �� ���������� `$id`";
		return;
	}
	$art = $art[0];
	
	if (isset($art->image) && $art->image){
	
		$f_dst = llmServer::getPPath().'data/images/'.$art->image;

		if (file_exists($f_dst)) {

			$ret = unlink($f_dst);
			if (!$ret) {

				echo "�� ���� ������� �������� ������ {$f_dst}";
				return;
			}
		}
	}
	
	$database->setQuery("DELETE FROM articles WHERE id = '{$id}'");
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('articles','view_site_articles',"site_id={$site_id}"),"������ �������");
}
/**
* ��������� ������ � ����
*/
function saveArticle() {

	$database   = llmServer::getDBO();
	$site_id    = (int)getParam('site_id');

	$id         = (int)getParam('id');
	$title      = getParam('title');
	$content    = getParam('content');
	$description= getParam('description');
	$keywords   = getParam('keywords');
	
	$upload = new shFileUpload("art_image");
	if ($upload->isFileUploaded()) {
	
		//��������� ��� ����
		if ($upload->getError()) {
		
			echo $upload->getError();
			return;
		}
		
		if (!$upload->isImage()) {
		
			echo $upload->getError();
			return;
		}
		
		$size_w = getParam('size_w');
		$size_h = getParam('size_h');
		
		//��������� ���������� ���
		$fname = $upload->getUploadedFileName();
		$fname = str_replace("..","",str_replace(".".$upload->getNormalExtension(),"",strtolower(preg_replace("|[^a-z0-9\_\-\.]|","",$fname))));

		$f_name_db = basename($fname).".".$upload->getNormalExtension();
		
		//����� ������������ ������� ������ ��������, ���� ��� ����
		if ($id) {
			
			$database->setQuery("SELECT * FROM articles WHERE id='$id'");
			$art = $database->loadObjectList();$art = $art[0];
			
			if ($art->image) {
			
				$f_dst = llmServer::getPPath().'data/images/'.$art->image;
				
				if (file_exists($f_dst)) {
				
					$ret = unlink($f_dst);
					if (!$ret) {
					
						echo "�� ���� ������� ������ �������� {$f_dst}";
						return;
					}
				}
			}
		}
		
		//��������� ������� �������� � ����� �� ������, ���� ��� ���� - ������ �������� ����
		$iter = 0;
		while(true) {
		
			$test_name = llmServer::getPPath().'data/images/'.($iter!=0 ? rand()."-" : "").$f_name_db;
			$iter++;
			
			if ($iter > 40) break;
			if (!file_exists($test_name)) break;
		}
		
		//����� �� ��� ��������� ���
		$new_image_name = $test_name;		
		
		//��������
		$res = $upload->copyResized($new_image_name,$size_w,$size_h);
		
		if (!$res) {
		
			echo $upload->getError();
			return;
		}
		
		//� ������� � ��
		$NEW_IMAGE_PATH = basename($new_image_name);
	}
	else {
		
		$database->setQuery("SELECT * FROM articles WHERE id='$id'");
		$art = $database->loadObjectList();
		
		$NEW_IMAGE_PATH = isset($art[0]) ? $art[0]->image : "";
	}
	
	if (getParam('new_category')) $category = getParam('new_category');
		else $category = getParam('category');
	$category = trim($category);
	
	if (!$category) {
	
		echo "��������� �� ����� ���� ������";
		return;
	}
	
	$time_end  = getParam('time_end_str');
	if($time_end) {
	
		list($y,$m,$d) = explode('.',$time_end);
		$te = mktime(0,0,0,$m,$d,$y);
	}
	else {
	
		$te = "";
	}

	$database->setQuery("SELECT * FROM sites WHERE id='{$site_id}'");
	$site = $database->loadObjectList();

	if (!$site_id || sizeof($site)==0) {
	
		llmServer::showError("Bad site_id",false);
		return;
	}
	$site = $site[0];
	
	if ($id) {
	
		$query = "UPDATE articles SET image='{$NEW_IMAGE_PATH}', title='{$title}', content='{$content}', keywords='{$keywords}', description='{$description}', category='{$category}' ".($te ? ", time_end='{$te}'" : ", time_end='0'")." WHERE id='{$id}'";
	}
	else {
	
		$query = "INSERT INTO articles (image,keywords,description,title,content,category".($te ? ",time_end" : "").",time_add,site_id) VALUES ('{$NEW_IMAGE_PATH}','{$keywords}', '{$description}','{$title}','{$content}','{$category}'".($te ? ",'{$te}'" : "").",".time().",'{$site->id}')";
	}
	
	$database->setQuery($query);
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('articles','view_site_articles',"site_id={$site->id}"),"������ ���������");
}

/**
* ����� ���������� ������ � ������ �� ���������
*/
function addArticleForm() {

	$database   = llmServer::getDBO();
	$site_id    = (int)getParam('site_id');

	$id         = (int)getParam('id');
	
	$database->setQuery("SELECT * FROM sites WHERE id='{$site_id}'");
	$site = $database->loadObjectList();

	if (!$site_id || sizeof($site)==0) {
	
		llmServer::showError("Bad site_id",false);
		return;
	}
	$site = $site[0];
	
	$database->setQuery("SELECT * FROM articles WHERE id='{$id}'");
	$art = $database->loadObjectList();
	if (sizeof($art) > 0) {
	
		$art = $art[0];
	}
	else {
	
		$art = new stdClass();
		
		$art->id       = 0;
		$art->site_id  = $site->id;
		$art->category = "";
		$art->title    = "";
		$art->content  = "";
		$art->time_end = "";
		$art->description = "";
		$art->keywords = "";
		$art->image    = "";
	}
	
	echo "<link rel='stylesheet' href='".llmServer::getWPath()."template/calendar/dhtmlgoodies_calendar.css' type='text/css'>
	<script type='text/javascript' src='".llmServer::getWPath()."template/calendar/dhtmlgoodies_calendar.js'></script>
	";
	
	echo "<h2 id='newsite'>�������� ������ ��� ����� {$site->url}</h2>
	
	<form action='".llmServer::getWPath()."' method='post' id='articlesconfform' enctype='multipart/form-data'>
	<table align='center' width='100%'>
	";
	
	//���������
	echo "<tr><td width=25%>��������� ������</td><td> <input type='text' name='title' value='{$art->title}' style='width:100%'></td></tr>";
	
	//����������
	echo "<tr><td width=25%>����������<br><br><br><small>��� ��������� �����-������ �� �������� ����� ������ �������� ����� ���� ��� <b>&lt;cut&gt;</b><br><br>��� ������� ������������ ����������� �������� <b>{image}</b></small></td><td><textarea name='content' style='width:100%' rows=25>".htmlspecialchars($art->content)."</textarea></td></tr>";
	
	//
	echo "<tr><td width=25%>Meta Desciprtion<br><br><br></td><td><textarea name='description' style='width:100%' rows=5>".htmlspecialchars($art->description)."</textarea></td></tr>";
	
	//
	echo "<tr><td width=25%>Meta Keywords<br><br><br></td><td><textarea name='keywords' style='width:100%' rows=5>".htmlspecialchars($art->keywords)."</textarea></td></tr>";
	
	//���������
	$database->setQuery("SELECT DISTINCT category FROM articles WHERE site_id='{$site_id}'");
	$cats = $database->loadResultArray();
	$is_sel = $art->category == "" ? "selected='selected'" : "";
	$cats_sel = "<select name='category'><option value='' {$is_sel}>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>";
	foreach($cats as $cat) {
	
		if (!trim($cat)) continue;
		$is_sel = $art->category == $cat ? "selected='selected'" : "";
		$cats_sel .= "<option value='{$cat}' {$is_sel}>{$cat}</option>";
	}
	$cats_sel .= "</select>";
	
	echo "<tr><td width=25%>��������� (<font color=red>�����������</font>)</td><td> $cats_sel ��� ������� �����: <input type='text' name='new_category' value=''></td></tr>";
	
	//������ ��������
	echo "<tr><td width=25%>������ ������������ ��</td><td> <input type='text' name='time_end_str' value='".($art->time_end ? date("Y.m.d",$art->time_end) : "")."' size=10 > <input type='button' value='...' onclick=\"displayCalendar(document.forms[0].time_end_str,'yyyy.mm.dd',this)\"> <small>(����.��.�� ��� �������� ������ ���� ������ ��������)</small></td></tr>";

	//����������
	if ($art->image) {
	
		$existed_image = "<img src='".llmServer::getWPath()."data/images/{$art->image}?rand=".rand()."' > &rarr; ��� �� ������� <span style='cursor:pointer;cursor:hand;' title='' onclick=\"delete_art_image({$art->id})\">[ ������� ���� ]</span><br>";
	}
	else {
	
		$existed_image = "<i>��� �� ���������</i>";
	}
	echo "<tr><td width=25%>����������� � ������ ".showHelp("�������� ����� ��������� � ���������� /data/images ��� ����� ������<br>� ������� ����� �������� ��� �������, �����: ���� ����������� ��������, ���� � ������� �������������<br>���� �������� � ����� ������ ��� ���������� - �������� ����� ����� ��������.")."</td><td> <span id='ex_image'>$existed_image</span> <br><input type='file' name='art_image' style='width:100%'><br>
	�������� ������� �������� ��: <input type=text name=size_w size=4 value='400'>px �� ������ � <input type=text name=size_h size=4 value='400'>px �� ������
	
	</td></tr>";

	
	echo llmHTML::formBottom('save_article','���������',2,"button-add");
	
	echo "</table>
	<input type='hidden' name='site_id' value='{$site->id}'>
	<input type='hidden' name='id' value='{$id}'>
	</form>";
}

/**
* ������� ���� ������
*/
function deleteImage() {

	define("ICONV_ME","UTF-8");
	
	$database = llmServer::getDBO();
	
	$article_id = (int)getParam('article_id');
	
	$database->setQuery("SELECT image FROM articles WHERE id='{$article_id}'");
	$img = $database->loadResult();
	
	if ($img) {
	
		$fe = llmServer::getPPath().'data/images/'.$img;
		if (!file_exists($fe)) {
		
			echo "<font color=red>�������� �� ������� � ����� /data/images</font>";
		}
		else {
		
			$ret = unlink($fe);
			if ($ret) {
			
				//� �� ���� �������
				$database->setQuery("UPDATE articles SET image='' WHERE id='$article_id'");
				$database->query();
				
				echo "<font color=green>�������� ������� �������</font>";
			}
			else {
			
				echo "<font color=red>�� �������� ������� �������� `{$img}` �� ���������� /data/images</font>";
			}
		}
	}
	else {
	
		echo "<font color=red>�������� �� ������� � ��</font>";
	}
}

/**
* ����� ����� �������
*/
function preCreateTemplate() {

	$database   = llmServer::getDBO();
	$site_id    = (int)getParam('site_id');

	$database->setQuery("SELECT * FROM sites WHERE id='{$site_id}'");
	$site = $database->loadObjectList();

	if (!$site_id || sizeof($site)==0) {
	
		llmServer::showError("Bad site_id",false);
		return;
	}
	$site = $site[0];

	$ht = new llmHTTP();
	$page = $ht->get($site->url,$st,$cu,false);
	
	if ($site->articles_folder == "") {
	
		llmServer::showError("�� ��������� ���� `��� ����������`. ��� ���� ������������ ������� �� ��������!",false);
		return;
	}
	
	$file = llmServer::getPPath().'data/'.$site->domain_key.'.html';
	if (file_exists($file)) {
	
		$obj = unserialize(file_get_contents($file));
	}
	else {
	
		$obj = new stdClass();
		$obj->tpl_page = iconv($site->charset,"CP1251",$page);
		$obj->tpl_page = preg_replace("|<textarea.*>.*</textarea>|Umsi","",$obj->tpl_page);
		$obj->tpl_articles_listing = "";
		$obj->tpl_articles_listing_element = "";
		$obj->tpl_articles_full = "";
		$obj->tpl_categories_list = "";
		$obj->tpl_categories_element = "";
		$obj->tpl_articles_per_page = "";
		$obj->tpl_title_main = "������ ����� {$site->url}";
	}

	echo "<h2 id='newsite'>������ ������ ������ ����� {$site->url}</h2>
	
	<form action='".llmServer::getWPath()."' method='post' id='articlesconfform'>
	<table align='center' width='100%'>
	
	<tr><td width='100%' colspan=2>������ ���������� �������������� �������� ������� �������� ����� � �������� �� � ������ ���� - ����� ������, ��� �� �� ������ ������� ������ ����������� ����������������. ������ ����� ��������� ����������� � ����� ��������� �����. ��� ����������� ������� �������� �� ���������� �������� ��������� ������ � ������, ����������� ������. �� ���� ����-������� {ARTICLES_AREA} ������ ���� � ��� ����� ����� ��� � ��� � ��������� ����� '��������' �����. ��� ���� ������� ������ �������� �� ����� - ������� �� ����� ����� ���� ��������� ������.
	<br><br>
	�� ����� ��������� �������� ���������� ������: `<b>{$site->articles_folder}</b>`. �� ���� ���� ���������� �� �������� ����� �������� �� ������: `<b>{$site->url}{$site->articles_folder}/</b>`. ��� ���������� ���� ���� CSS � JS ������ �������� � ������������ � ���, ��� ���� ����� ������� ������ ������������ ���� ����������.
	<br><br>
	����� ����, ��� �� ������� ������ ��������� ������ �������� � ���������� <b>/DATA</b> ���� �������, ������� ������������ ����� ���������������. ��� ������ ��� �������� ������ ��� ��������.
	</td></tr>
	<tr> 
	<td width='25%' valign='top'><font color='green'>�������� �������</font><br><small>&lt;html&gt;&lt;head&gt;<br>&lt;title&gt;{PAGE_TITLE}&lt;/title&gt;<br>&lt;/head&gt;<br>&lt;body&gt;<br>��������� �����<br>{CATEGORIES_AREA}<br>�����-�� �����<br>{ARTICLES_AREA}<br>� ��� �����<br>&lt;/body&gt;&lt;/html&gt;<br><br>�� �������� ��� meta-���� {PAGE_DESCRIPTION} � {PAGE_KEYWORDS}</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=30 name=tpl_page>{$obj->tpl_page}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>������� ������ (ARTICLES_AREA)</font><br><small><br>&lt;h1&gt;������ ������ �����&lt;/h1&gt;<br>{ARTICLES_ELEMENTS}<br>&lt;p&gt;{PAGESWITCH}&lt;/p&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_articles_listing>{$obj->tpl_articles_listing}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>������ � �������� (ARTICLE_ELEMENT)</font><br><small><br>&lt;h2&gt;{ARTICLE_LINK}&lt;/h2&gt;<br>&lt;p&gt;{ARTICLE_CONTENT}&lt;/p&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_articles_listing_element>{$obj->tpl_articles_listing_element}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>������ ������ (ARTICLES_AREA)</font><br><small><br>&lt;h2&gt;{ARTICLE_TITLE}&lt;/h2&gt;<br>&lt;p&gt;{ARTICLE_CONTENT}&lt;/p&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_articles_full>{$obj->tpl_articles_full}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>������� ��������� (CATEGORIES_AREA)</font><br><small><br>&lt;ul&gt;{CATEGORIES_LIST}&lt;/ul&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_categories_list>{$obj->tpl_categories_list}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>���� ��������� (CATEGORIES_LIST)</font><br><small><br>&lt;li&gt;{CATEGORY_LINK}&lt;/li&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_categories_element>{$obj->tpl_categories_element}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>����� ������ �� ��������</font> ".showHelp("����� ������ ����")."</td>
	<td width='75%'><input type='text' name='tpl_articles_per_page' value='{$obj->tpl_articles_per_page}' size=5></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>��������� ��������� �����".showHelp("�� ����� �����, ������� ����� ��������� � title ��������<br> ��� ��������� �������� ��������� ������ � ���������<br>��������: �������� ������ ������ �����")."</font></td>
	<td width='75%'><input type='text' name='tpl_title_main' value='{$obj->tpl_title_main}' style='width:100%'5></td>
	</tr>
	";
	
	echo llmHTML::formBottom('save_template','���������',2,"button-add");
	
	echo "</table>
	<input type='hidden' name='site_id' value='{$site->id}'>
	</form>";
}

function saveTemplate() {

	$database   = llmServer::getDBO();
	$site_id    = (int)getParam('site_id');
	
	$database->setQuery("SELECT * FROM sites WHERE id='{$site_id}'");
	$site = $database->loadObjectList();

	if (!$site_id || sizeof($site)==0) {
	
		llmServer::showError("Bad site_id",false);
		return;
	}
	$site = $site[0];
	
	$e = new stdClass();
	foreach($_POST as $in => $v) {
	
		$pos = strpos($in,"tpl_");
		if ($pos!==false) {
		
			$e->$in = stripslashes(getParam($in));
		}
	}

	if ( (int)$e->tpl_articles_per_page <= 0) {
	
		llmServer::showError("����� ������ �� �������� �� ������ ���� ������ ��� ����� ����",false);
		return;
	}
	
	$pos = strpos($e->tpl_page,"{ARTICLES_AREA}");
	if ($pos === false) {
	
		llmServer::showError("����-����������� {ARTICLES_AREA} ������ �������������� � ���������� �������, ����� ������ �������� �� �����",false);
		return;
	}
	
	$file = llmServer::getPPath().'data/'.$site->domain_key.'.html';
	$f = fopen($file,"w");
	if (!$f) {
	
		llmServer::showError("���� {$file} ���������� �� ������",false);
		return;
	}
	
	//����, �� ��������, ��� serialize ������ �����, ������� ������ ������ � ����� ��� �����
	//���� �������� �������������� �� ������ ����������
	$count = 0;$is_good = false;
	while($count <= 10) {
	
		$tmp = serialize($e);
		if (false == @unserialize($tmp)) {
		
			//��� �����
		}
		else {
		
			$is_good = true;
			$str = $tmp;
			break;
		}
		
		$count++;
	}
	
	if (!$is_good) {
	
		llmServer::showError("��������� �� ����� �������� ������ serialize, ������� ������ ��������. ���������� ��������� ����� ������.",false);
		return;
	}

	fwrite($f,$str);
	fclose($f);
	
	llmServer::redirect(llmServer::getHPath('articles','view_site_articles',"site_id={$site->id}"),"���� ������� ��������");
}

/**
* �������� �������� ������ ��������� �����
*/
function viewSiteArticles() {

	$database   = llmServer::getDBO();
	$site_id    = (int)getParam('site_id');

	$database->setQuery("SELECT * FROM sites WHERE id='{$site_id}'");
	$site = $database->loadObjectList();

	if (!$site_id || sizeof($site)==0) {
	
		llmServer::showError("Bad site_id",false);
		return;
	}
	$site = $site[0];
	
	$database->setQuery("SELECT * FROM articles WHERE site_id = '{$site->id}' ORDER BY id");
	$articles = $database->loadObjectList();
	$list_sz  = sizeof($articles);
	
	if ($list_sz == 0) {
		
		echo "<div class=error>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;������ �� ����������<br><br></div>";
	}
	else {
		
		echo "<table align='center' width='100%'>";$sindex = 1;
		echo "<tr>
		<td class='sites-theader' width='5'> <b>ID</b> </td>
		<td class='sites-theader' width='55%'><b>���������</b></td>
		<td class='sites-theader' width='15%'><b>���������</b></td>
		<td class='sites-theader' width='15%'><b>�������. ��</b></td>
		<td class='sites-theader' width='15%'><b>�������?</b></td>
		</tr>";
		
		$end = sizeof($articles);
		for($i=0;$i<$end;$i++) {
		
			$art = $articles[$i];
			
			$del_url = llmServer::getHPath('articles','delete_article','site_id='.$site->id.'&id='.$art->id);
			
			$short_title = strlen($art->title) > 60 ? substr($art->title,0,60).'...' : $art->title;
			$cl = @$cl==0 ? 1 : 0;
			$cl_class = " class='row{$cl}' ";
			echo "<tr{$cl_class}>
			<td> {$art->id}</td>
			<td nowrap='nowrap'> <a href='".llmServer::getHPath('articles','add_article','id='.$art->id.'&site_id='.$site->id)."'>{$short_title}</a></td>
			<td nowrap='nowrap'> {$art->category} </td>
			<td nowrap='nowrap'> ".($art->time_end ? date("d/m/Y",$art->time_end) : "")." </td>
			<td nowrap='nowrap'> <a href=\"javascript: if (confirm('�����?')) { window.location.href='{$del_url}' } else { void('') };\" class='delete'> �������?</a></td>
			</tr>";
	
			$sindex++;
		}
		echo "</table>";
	}
	
	
	$file = llmServer::getPPath().'data/'.$site->domain_key.'.html';
	if (file_exists($file)) {
	
		$tf = "<small style='color:green'>(���� ������� ������)</small>";
	}
	else $tf = "";
	
	$step2_link = ($tf ? "2. <a href='".llmServer::getHPath("builder",'',"site_id={$site->id}&type=articles")."'>��������� ����������� ����</a>" : "2. ��������� ����������� ���� (<font color=red>��� 1 �� ��������</font>)")." ".showHelp("������ �� ������ �������� ������ ��� ��������");
	
	//��������� ���������� ������� � �����
	echo "<h2 id='newsite'>��������� ����� {$site->url}</h2>
	<form action='' method='post' id='articlesconfform'>
	<table align='center' width='100%'>
	
	<tr> 
	
	
	<td width=25%>��� ���������� ".showHelp("������ ���������� ����� � �����, ��� ������, http � ������ ��������<br>������ ���� ����� ��������� ���: http://���_����.ru/����������/<br>��� ����� ������ ������ ���������� � ������ ������<br><br>���� ����������� � ����������<br><br>����� ��������� ������ �� ������� - ��� ���� ����� �� ������")." </td>  <td width='75%'><input type=text name='url' id='folder' value='{$site->articles_folder}' size=28> <span style='cursor:pointer;cursor:hand;' onclick=\"foldersave()\"> [���������]</span> <span id='foldersavespan'></span></td>
	
	</tr>
	
	<tr> 
	
	<td width=25%>����������� ".showHelp("����� ����������� ������ ����� ������ ��� ����, ������������� ������")."</td><td>
	1. <a href='".llmServer::getHPath("articles",'create_template',"site_id={$site->id}")."'>��������� �������</a> {$tf} ".showHelp("������� ����� � �����-�������������")."
	<br>
	{$step2_link}
	<br>
	
	</td> 
	</tr>";
	
	echo "</table>
	<input type=hidden name='site_id' value='{$site->id}'>
	</form>";
}
?>