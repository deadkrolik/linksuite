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
		llmTitler::put("Создание статьи");
		addArticleForm();
		break;
		
	case 'save_template':
		saveTemplate();
		break;
		
	case 'savefolder':
		saveSiteFolder();
		break;
		
	case 'create_template':
		llmTitler::put("Создание шаблона");
		preCreateTemplate();
		break;

	case 'view_site_articles':
		llmTitler::put("Управление статьями сайта");
		viewSiteArticles();
		break;
}

/**
* Аяксовое сохранение директории статей
*/
function saveSiteFolder() {

	define("ICONV_ME","UTF-8");
	
	$database = llmServer::getDBO();
	
	$site_id    = (int)getParam('site_id');
	$folder     = getParam('folder');
	$folder     = preg_replace("|[^a-z0-9]|Umsi","",$folder);
	
	$database->setQuery("UPDATE sites SET articles_folder = '{$folder}' WHERE id = '{$site_id}'");
	$database->query();
	
	echo "<font color=green>Новое имя директории `{$folder}` сохранено.</font>";
}

/**
* Удаляем статью из базы
*/
function deleteArticle() {

	$database = llmServer::getDBO();
	
	$site_id    = (int)getParam('site_id');
	$id         = (int)getParam('id');
	
	$database->setQuery("SELECT * FROM articles WHERE id='{$id}'");
	$art = $database->loadObjectList();
	
	if (!isset($art[0])) {
	
		echo "Такой статьи не существует `$id`";
		return;
	}
	$art = $art[0];
	
	if (isset($art->image) && $art->image){
	
		$f_dst = llmServer::getPPath().'data/images/'.$art->image;

		if (file_exists($f_dst)) {

			$ret = unlink($f_dst);
			if (!$ret) {

				echo "Не могу удалить картинку статьи {$f_dst}";
				return;
			}
		}
	}
	
	$database->setQuery("DELETE FROM articles WHERE id = '{$id}'");
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('articles','view_site_articles',"site_id={$site_id}"),"Статья удалена");
}
/**
* Сохраняем статью в базу
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
	
		//проверяем все дела
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
		
		//формируем правильное имя
		$fname = $upload->getUploadedFileName();
		$fname = str_replace("..","",str_replace(".".$upload->getNormalExtension(),"",strtolower(preg_replace("|[^a-z0-9\_\-\.]|","",$fname))));

		$f_name_db = basename($fname).".".$upload->getNormalExtension();
		
		//перед копированием удаляем старую картинку, если она есть
		if ($id) {
			
			$database->setQuery("SELECT * FROM articles WHERE id='$id'");
			$art = $database->loadObjectList();$art = $art[0];
			
			if ($art->image) {
			
				$f_dst = llmServer::getPPath().'data/images/'.$art->image;
				
				if (file_exists($f_dst)) {
				
					$ret = unlink($f_dst);
					if (!$ret) {
					
						echo "Не могу удалить старую картинку {$f_dst}";
						return;
					}
				}
			}
		}
		
		//проверяем наличие картинки с таким же именем, если она есть - меняем название этой
		$iter = 0;
		while(true) {
		
			$test_name = llmServer::getPPath().'data/images/'.($iter!=0 ? rand()."-" : "").$f_name_db;
			$iter++;
			
			if ($iter > 40) break;
			if (!file_exists($test_name)) break;
		}
		
		//вроде бы как присвоили имя
		$new_image_name = $test_name;		
		
		//копируем
		$res = $upload->copyResized($new_image_name,$size_w,$size_h);
		
		if (!$res) {
		
			echo $upload->getError();
			return;
		}
		
		//и заносим в БД
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
	
		echo "Категория не может быть пустой";
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
	
	llmServer::redirect(llmServer::getHPath('articles','view_site_articles',"site_id={$site->id}"),"Статья сохранена");
}

/**
* Форма добавления статьи и выбора ее категории
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
	
	echo "<h2 id='newsite'>Создание статьи для сайта {$site->url}</h2>
	
	<form action='".llmServer::getWPath()."' method='post' id='articlesconfform' enctype='multipart/form-data'>
	<table align='center' width='100%'>
	";
	
	//заголовок
	echo "<tr><td width=25%>Заголовок статьи</td><td> <input type='text' name='title' value='{$art->title}' style='width:100%'></td></tr>";
	
	//содержимое
	echo "<tr><td width=25%>Содержимое<br><br><br><small>Для отделения интро-текста от основной части статьи вставьте между ними тэг <b>&lt;cut&gt;</b><br><br>Для вставки загруженного изображения напишите <b>{image}</b></small></td><td><textarea name='content' style='width:100%' rows=25>".htmlspecialchars($art->content)."</textarea></td></tr>";
	
	//
	echo "<tr><td width=25%>Meta Desciprtion<br><br><br></td><td><textarea name='description' style='width:100%' rows=5>".htmlspecialchars($art->description)."</textarea></td></tr>";
	
	//
	echo "<tr><td width=25%>Meta Keywords<br><br><br></td><td><textarea name='keywords' style='width:100%' rows=5>".htmlspecialchars($art->keywords)."</textarea></td></tr>";
	
	//категория
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
	
	echo "<tr><td width=25%>Категория (<font color=red>обязательна</font>)</td><td> $cats_sel или введите новую: <input type='text' name='new_category' value=''></td></tr>";
	
	//период действия
	echo "<tr><td width=25%>Статья опубликована до</td><td> <input type='text' name='time_end_str' value='".($art->time_end ? date("Y.m.d",$art->time_end) : "")."' size=10 > <input type='button' value='...' onclick=\"displayCalendar(document.forms[0].time_end_str,'yyyy.mm.dd',this)\"> <small>(ГГГГ.ММ.ДД или оставьте пустым если статья навсегда)</small></td></tr>";

	//миниатюрка
	if ($art->image) {
	
		$existed_image = "<img src='".llmServer::getWPath()."data/images/{$art->image}?rand=".rand()."' > &rarr; что бы удалить <span style='cursor:pointer;cursor:hand;' title='' onclick=\"delete_art_image({$art->id})\">[ нажмите сюда ]</span><br>";
	}
	else {
	
		$existed_image = "<i>еще не загружено</i>";
	}
	echo "<tr><td width=25%>Изображение к статье ".showHelp("Картинка будет сохранена в директорию /data/images под своим именем<br>в котором будут вырезаны все символы, кроме: букв английского алфавита, цифр и символа подчеркивания<br>Если картинка с таким именем уже существует - название новой будет изменено.")."</td><td> <span id='ex_image'>$existed_image</span> <br><input type='file' name='art_image' style='width:100%'><br>
	Изменить размеры картинки до: <input type=text name=size_w size=4 value='400'>px по ширине и <input type=text name=size_h size=4 value='400'>px по высоте
	
	</td></tr>";

	
	echo llmHTML::formBottom('save_article','Сохранить',2,"button-add");
	
	echo "</table>
	<input type='hidden' name='site_id' value='{$site->id}'>
	<input type='hidden' name='id' value='{$id}'>
	</form>";
}

/**
* Удаляет одну иконку
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
		
			echo "<font color=red>Картинка не найдена в папке /data/images</font>";
		}
		else {
		
			$ret = unlink($fe);
			if ($ret) {
			
				//и из базы грохаем
				$database->setQuery("UPDATE articles SET image='' WHERE id='$article_id'");
				$database->query();
				
				echo "<font color=green>Картинка успешно удалена</font>";
			}
			else {
			
				echo "<font color=red>Не возможно удалить картинку `{$img}` из директории /data/images</font>";
			}
		}
	}
	else {
	
		echo "<font color=red>Картинка не найдена в БД</font>";
	}
}

/**
* Форма ввода шаблона
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
	
		llmServer::showError("Не заполнено поле `Имя директории`. Без него формирование шаблона не возможно!",false);
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
		$obj->tpl_title_main = "Статьи сайта {$site->url}";
	}

	echo "<h2 id='newsite'>Шаблон вывода статей сайта {$site->url}</h2>
	
	<form action='".llmServer::getWPath()."' method='post' id='articlesconfform'>
	<table align='center' width='100%'>
	
	<tr><td width='100%' colspan=2>Скрипт попытается самостоятельно получить главную страницу сайта и вставить ее в первое поле - общий шаблон, что бы вы смогли сделать только необходимые макроподстановки. Статья дожна выглядеть естественно в общем окружении сайта. Все последующие шаблоны отвечают за конкретные элементы отрисовки статей и других, необходимых частей. То есть мета-вставка {ARTICLES_AREA} должна быть в том самом месте где у вас и находится самое 'читаемое' место. Без этой вставки ничего работать не будет - система не будет знать куда вставлять статьи.
	<br><br>
	Вы ввели следующее название директории ссылок: `<b>{$site->articles_folder}</b>`. То есть сама директория со статьями будет доступна по адресу: `<b>{$site->url}{$site->articles_folder}/</b>`. Вам необходимо пути всех CSS и JS файлов привести в соответствие с тем, что пути будут браться именно относительно этой директории.
	<br><br>
	После того, как вы нажмете кнопку Сохранить скрипт сохранит в директорию <b>/DATA</b> файл шаблона, который впоследствии можно отредактировать. Это первый шаг создания архива для хостинга.
	</td></tr>
	<tr> 
	<td width='25%' valign='top'><font color='green'>Страница целиком</font><br><small>&lt;html&gt;&lt;head&gt;<br>&lt;title&gt;{PAGE_TITLE}&lt;/title&gt;<br>&lt;/head&gt;<br>&lt;body&gt;<br>начальный текст<br>{CATEGORIES_AREA}<br>какой-то текст<br>{ARTICLES_AREA}<br>и еще текст<br>&lt;/body&gt;&lt;/html&gt;<br><br>Не забудьте про meta-тэги {PAGE_DESCRIPTION} и {PAGE_KEYWORDS}</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=30 name=tpl_page>{$obj->tpl_page}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>Листинг статей (ARTICLES_AREA)</font><br><small><br>&lt;h1&gt;Статьи нашего сайта&lt;/h1&gt;<br>{ARTICLES_ELEMENTS}<br>&lt;p&gt;{PAGESWITCH}&lt;/p&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_articles_listing>{$obj->tpl_articles_listing}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>Статья в листинге (ARTICLE_ELEMENT)</font><br><small><br>&lt;h2&gt;{ARTICLE_LINK}&lt;/h2&gt;<br>&lt;p&gt;{ARTICLE_CONTENT}&lt;/p&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_articles_listing_element>{$obj->tpl_articles_listing_element}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>Полная статья (ARTICLES_AREA)</font><br><small><br>&lt;h2&gt;{ARTICLE_TITLE}&lt;/h2&gt;<br>&lt;p&gt;{ARTICLE_CONTENT}&lt;/p&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_articles_full>{$obj->tpl_articles_full}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>Область категорий (CATEGORIES_AREA)</font><br><small><br>&lt;ul&gt;{CATEGORIES_LIST}&lt;/ul&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_categories_list>{$obj->tpl_categories_list}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>Одна категория (CATEGORIES_LIST)</font><br><small><br>&lt;li&gt;{CATEGORY_LINK}&lt;/li&gt;</small>
	
	</td>
	<td width='75%'><textarea style='width:100%' rows=14 name=tpl_categories_element>{$obj->tpl_categories_element}</textarea></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>Число статей на страницу</font> ".showHelp("Число больше нуля")."</td>
	<td width='75%'><input type='text' name='tpl_articles_per_page' value='{$obj->tpl_articles_per_page}' size=5></td>
	</tr>
	
	<tr>
	<td width='25%' valign='top'><font color='green'>Заголовок стартовой папки".showHelp("Те самые слова, которые будут вставлены в title страницы<br> при просмотре листинга последних статей и категорий<br>Например: Полезные статьи нашего сайта")."</font></td>
	<td width='75%'><input type='text' name='tpl_title_main' value='{$obj->tpl_title_main}' style='width:100%'5></td>
	</tr>
	";
	
	echo llmHTML::formBottom('save_template','Сохранить',2,"button-add");
	
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
	
		llmServer::showError("Число статей на страницу не должно быть меньше или равно нулю",false);
		return;
	}
	
	$pos = strpos($e->tpl_page,"{ARTICLES_AREA}");
	if ($pos === false) {
	
		llmServer::showError("Мета-подстановка {ARTICLES_AREA} должна присутствовать в глобальном шаблоне, иначе ничего работать не будет",false);
		return;
	}
	
	$file = llmServer::getPPath().'data/'.$site->domain_key.'.html';
	$f = fopen($file,"w");
	if (!$f) {
	
		llmServer::showError("Файл {$file} недоступен на запись",false);
		return;
	}
	
	//итак, мы выяснили, что serialize иногда тупит, поэтому просто делаем в цикле эту штуку
	//пока обратное преобразование не станет корректным
	$count = 0;$is_good = false;
	while($count <= 10) {
	
		$tmp = serialize($e);
		if (false == @unserialize($tmp)) {
		
			//все плохо
		}
		else {
		
			$is_good = true;
			$str = $tmp;
			break;
		}
		
		$count++;
	}
	
	if (!$is_good) {
	
		llmServer::showError("Наступила та самая странная ошибка serialize, которую нельзя избежать. Попробуйте сохранить форму заново.",false);
		return;
	}

	fwrite($f,$str);
	fclose($f);
	
	llmServer::redirect(llmServer::getHPath('articles','view_site_articles',"site_id={$site->id}"),"Файл шаблона обновлен");
}

/**
* Просмотр листинга статей заданного сайта
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
		
		echo "<div class=error>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Статей не обнаружено<br><br></div>";
	}
	else {
		
		echo "<table align='center' width='100%'>";$sindex = 1;
		echo "<tr>
		<td class='sites-theader' width='5'> <b>ID</b> </td>
		<td class='sites-theader' width='55%'><b>Заголовок</b></td>
		<td class='sites-theader' width='15%'><b>Категория</b></td>
		<td class='sites-theader' width='15%'><b>Опублик. до</b></td>
		<td class='sites-theader' width='15%'><b>Удалить?</b></td>
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
			<td nowrap='nowrap'> <a href=\"javascript: if (confirm('Точна?')) { window.location.href='{$del_url}' } else { void('') };\" class='delete'> удалить?</a></td>
			</tr>";
	
			$sindex++;
		}
		echo "</table>";
	}
	
	
	$file = llmServer::getPPath().'data/'.$site->domain_key.'.html';
	if (file_exists($file)) {
	
		$tf = "<small style='color:green'>(Файл шаблона найден)</small>";
	}
	else $tf = "";
	
	$step2_link = ($tf ? "2. <a href='".llmServer::getHPath("builder",'',"site_id={$site->id}&type=articles")."'>Генератор клиентского кода</a>" : "2. Генератор клиентского кода (<font color=red>шаг 1 не выполнен</font>)")." ".showHelp("Ссылка на модуль создания архива для хостинга");
	
	//настройки статейного раздела и опции
	echo "<h2 id='newsite'>Настройки сайта {$site->url}</h2>
	<form action='' method='post' id='articlesconfform'>
	<table align='center' width='100%'>
	
	<tr> 
	
	
	<td width=25%>Имя директории ".showHelp("Просто английские буквы и цифры, без слэшей, http и других символов<br>Полный путь будет выглядеть так: http://ваш_сайт.ru/ДИРЕКТОРИЯ/<br>Вам нужно ввести только ДИРЕКТОРИЮ и ничего больше<br><br>Поле обязательно к заполнению<br><br>После генерации файлов на хостинг - это поле лучше не менять")." </td>  <td width='75%'><input type=text name='url' id='folder' value='{$site->articles_folder}' size=28> <span style='cursor:pointer;cursor:hand;' onclick=\"foldersave()\"> [Сохранить]</span> <span id='foldersavespan'></span></td>
	
	</tr>
	
	<tr> 
	
	<td width=25%>Инструменты ".showHelp("Перед публикацией статей нужно пройти все шаги, перечисленные справа")."</td><td>
	1. <a href='".llmServer::getHPath("articles",'create_template',"site_id={$site->id}")."'>Генератор шаблона</a> {$tf} ".showHelp("Большая форма с макро-подстановками")."
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