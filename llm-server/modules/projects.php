<?PHP
defined('LLM_STARTED') or die("o_0");
llmTitler::put("Управление проектами");

if (!defined("LLM_NO_EXEC")) {

	switch($task) {
	
		case 'get_project_links':
			getProjectLinks((int)getParam('project_id',0));
			break;
			
		case 'save_urls':
			saveUrls((int)getParam('project_id',0));
			break;
			
		case 'edit_urls':
			editUrls((int)getParam('project_id',0));
			break;
			
		case 'clear_urls':
			clearUrls((int)getParam('project_id',0));
			break;
			
		case 'clean_project_links':
			cleanProjectLinks();
			break;
			
		case 'place_links':
			placeLinks();
			break;
			
		case 'delete_project':
			deleteProject();
			break;
			
		case 'show_links':
			showProjectLinks();
			break;
			
		case 'putlink':
			putLink();
			break;
			
		case 'search_links':
			searchLinks();
			break;
			
		case 'start_setup':
			startSetup((int)getParam('project_id',0));
			break;
			
		case 'edit_project':
			editProject((int)getParam('id',0));
			break;
			
		case 'save_project':
			saveProject();
			break;
			
		case 'add_project':
			editProject(0);
			break;
			
		case '':
			listProjects();
			break;
	}
}
/**
* ==>>> КОПИПАСТ ИЗ sites.php::deleteLink()
*/
function cleanProjectLinks($redir = true) {

	$database = llmServer::getDBO();
	$pid = (int)getParam('project_id');
	
	$database->setQuery("SELECT id FROM links WHERE project_id = '{$pid}'");
	$links_ids = $database->loadResultArray();

	$database->setQuery("SELECT page_id FROM links WHERE project_id = '{$pid}'");
	$pages_ids = $database->loadResultArray();
	
	if (sizeof($links_ids)!=0 && sizeof($pages_ids)!=0) {

		//уменьшае счетчик ссылок
		$database->setQuery("UPDATE pages SET links_on_page = links_on_page - 1 WHERE id IN (".implode(',',$pages_ids).")");
		$database->query();
		
		//удаляем ссылку
		$database->setQuery("DELETE FROM links WHERE id IN (".implode(',',$links_ids).")");
		$database->query();
		
		if ($redir) llmServer::redirect(llmServer::getHPath('projects',''),"Ссылки удалены");
	}
	else {
	
		if ($redir) llmServer::redirect(llmServer::getHPath('projects',''),"Удалять нечего");
	}
}

function deleteProject() {

	$database = llmServer::getDBO();
	$project_id = (int)getParam('project_id');
	
	//удалеяем все ссылки проекта
	cleanProjectLinks(false);
	
	//ну и сам проект
	$database->setQuery("DELETE FROM projects WHERE id = '{$project_id}'");
	$database->query();
	
	//удаляем файл с кодами ссылок
	$pa = getDataFileName($project_id);
	@unlink($pa);
	
	//вроде бы все
	llmServer::redirect(llmServer::getHPath('projects',''),"Проект удален");
}


/**
* Получение всех площадок одним списком
*/
function getProjectLinks($project_id) {

	$database = llmServer::getDBO();
	
	//КОПИПАСТ В Функции showProjectLinks
	$database->setQuery("SELECT links.*,pages.url as pages_url,pages.pr, pages.nesting, pages.external_links_count, pages.links_on_page,pages.id as page_id,pages.url as pages_url,sites.url as sites_url,sites.id as site_id FROM links INNER JOIN pages ON links.page_id = pages.id INNER JOIN sites ON sites.id = pages.site_id WHERE links.project_id = '{$project_id}'");
	$links = $database->loadObjectList();
	//--- КОПИПАСТ
	
	$out = "";
	foreach($links as $link) {
	
		$page_url = $link->sites_url.($link->pages_url=="/" ? '' : $link->pages_url);
		$out .= $page_url."<br>";
	}
	$out = trim($out);
	
	echo $out;
}

function showProjectLinks() {

	$database = llmServer::getDBO();
	$project_id = (int)getParam('id');

	//грузим проект
	$database->setQuery("SELECT * FROM projects WHERE id = '{$project_id}' ORDER BY id");
	$projects = $database->loadObjectList();
	
	if (sizeof($projects) == 0) {
	
		echo "Нет такого проекта";
		return;
	}
	$project = $projects[0];
	
	//разбиение на страницы
	$start_from = (int)getParam('start_from');
	$per_page = 50;
	
	//ссылки
	//КОПИПАСТ В Функции getProjectLinks
	$database->setQuery("SELECT links.*,pages.url as pages_url,pages.pr, pages.nesting, pages.external_links_count, pages.links_on_page,pages.id as page_id,pages.url as pages_url,sites.url as sites_url,sites.id as site_id FROM links INNER JOIN pages ON links.page_id = pages.id INNER JOIN sites ON sites.id = pages.site_id WHERE links.project_id = '{$project_id}'");
	$links = $database->loadObjectList();
	//--- КОПИПАСТ
	
	//разбиение на страницы
	$list_sz = sizeof($links);
    $start = $start_from > $list_sz ? 0 : $start_from;
    $end = $start_from + $per_page > $list_sz ? $list_sz : $start_from + $per_page;
	
	echo "<h2>Ссылки на проект {$project->name} (".sizeof($links).")</h2>
	&rarr; <a target='_blank' href='".llmServer::getHPath('projects','get_project_links','no_html=1&project_id='.$project_id)."'>получить все ссылки площадок одним файлом</a>
	<form name='linksform' id='linksform'>
	<table align='center' width=100%>";
	
	$index = 1;
	//foreach($links as $link) {
	for($i=$start;$i<$end;$i++) {
	
		$link = $links[$i];
		$page_url = $link->sites_url.($link->pages_url=="/" ? '' : $link->pages_url);
		
		//активность ссылки
		if ($link->status == LLM_LINK_STATUS_ACTIVE) {
			
			$stat_str = "<font color=green>Активна</font>";
			$span_str = "<font color=red>Выключить</font>";
			$act_status = LLM_LINK_STATUS_UNACTIVE;
		}
		else {
			$stat_str = "<font color=red>Не активна</font>";
			$span_str = "<font color=green>Включить</font>";
			$act_status = LLM_LINK_STATUS_ACTIVE;
		}
		
		//проиндексирована или нет
		if ($link->is_in_index) {
		
			$it = "Присутствует в индексе яндекса. Последняя проверка: ";
		}
		else {
		
			$it = "Не проиндексирована в яндексе. Последняя проверка: ";
		}
		if ($link->last_index_check) $it .= date('d/m/Y H:i:s',$link->last_index_check);
			else $it .= "нет";
		//аналогично гугль
		if ($link->is_in_google_index) {
		
			$it_g = "Присутствует в индексе Google. Последняя проверка: ";
		}
		else {
		
			$it_g = "Не проиндексирована в Google. Последняя проверка: ";
		}
		if ($link->last_index_check) $it_g .= date('d/m/Y H:i:s',$link->last_index_check);
			else $it_g .= "нет";
			
		$aa = $i+1;
		echo "<tr>
		<td rowspan=2 width=20>{$aa}</td>
		<td width='80%'><textarea style='width:100%' rows=1 name='inpp_{$link->id}'>{$link->html}</textarea></td>
		<td>
			<span title='Сохраняет изменения в базе данных, не перегружая страницу' onclick=\"quicksavelink({$link->id},{$link->page_id},'inpp_{$link->id}','status_box2_{$link->id}')\" class=save>Сохранить</span> 
			<span id='status_box2_{$link->id}'></span>
		</td>
		</tr>
		<tr>
			<td>&uarr; &uarr; &uarr; Здесь &rarr; <a href='$page_url'>$page_url</a><br>
			".showHelp($it,$link->is_in_index ? "plus" : "minus")."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".showHelp($it_g,$link->is_in_google_index ? "plus" : "minus")."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; PR = {$link->pr}, УВ = {$link->nesting}, ВС = {$link->external_links_count} ( + наших: {$link->links_on_page}), статус: {$stat_str} <span style='cursor:pointer;cursor:hand;' title='Изменение статуса активности ссылки. Просто инвертирование.' onclick=\"setlink_activation({$link->id},'{$act_status}','status_box3_{$link->id}')\">[ее можно {$span_str}]</span>&nbsp;(<a href='".llmServer::getHPath('sites','show_our_links_on_page',"page_id={$link->page_id}&site_id={$link->site_id}")."'>&rarr;&nbsp;страница</a>) <span id='status_box3_{$link->id}'></span>
			</td>
			<td>
				<span style='cursor:pointer;cursor:hand;color:red' title='Ссылка удаляется из базы ссылок, т.е. на этой странице их станет на одну меньше' onclick=\"dellink({$link->id},{$link->page_id},'status_box_{$link->id}')\" class=delete>Убрать ссылку</span>
				<span id='status_box_{$link->id}'></span>
			</td>
		</tr>";
		
		$index++;
	}
	echo "</table></form>";
	
	//разбиение на страницы
	$page_select = "";
	if ($list_sz < $per_page) {
	
		$page_select = "";
	}
	else {
	
		$page_count = ceil($list_sz/$per_page);
		$links = '';
		for ($i=1;$i<=$page_count;$i++) {
		
			$href = llmServer::getHPath('projects','show_links',"id={$project_id}&start_from=".(($i-1)*$per_page));
			if (($i-1)*$per_page != $start_from) $links .= "&nbsp;<a href='{$href}'>[&nbsp;$i&nbsp;]</a>&nbsp;&nbsp;";
				else $links .= "&nbsp;<span>{$i}</span>&nbsp;&nbsp;";
		}
		$page_select .= $links;
	}
	
	echo "<br><p align='center'>{$page_select}</p>";
}

function placeLinks() {

	$project_id = (int)getParam('project_id');
	
	//размещаем только на конкретных страницах
	$place = isset($_POST['place']) ? $_POST['place'] : array();
	foreach($place as $page_id) {
	
		$page_id       = (int)$page_id;
		$string_number = (int)getParam('anchor_'.$page_id);
		$string_number++;//selectedIndex отличается от нумерации с нуля ровно на 1
		
		$_REQUEST['project_id']    = $project_id;
		$_REQUEST['page_id']       = $page_id;
		$_REQUEST['string_number'] = $string_number;
		
		putLink(false);
	}

	llmServer::redirect(llmServer::getHPath('projects',''),"Ссылки установлены");
}

function putLink($output = true,$force_html = null) {

	static $cached_arr = null;
	static $cached_aurl = array();
	
	if ($output) define("ICONV_ME","UTF-8");

	$database      = llmServer::getDBO();
	$project_id    = (int)getParam('project_id');
	$page_id       = (int)getParam('page_id');
	$string_number = (int)getParam('string_number');
	
	if (!isset($cached_arr[$project_id])) {
		
		$arr = getDataFileUrls($project_id);
		$cached_arr[$project_id] = $arr;
	}
	else {
	
		$arr = $cached_arr[$project_id];
	}

	if ($string_number == 0) {//случайный выбор ссылки
	
		$sz = sizeof($arr);
		$index = rand(0,$sz-1);
		$html = isset($arr[$index]) ? $arr[$index] : NULL;
	}
	else {
		
		$string_number--;//т.к. у нас в списке добавлен один элемент в самое начало
		$html = isset($arr[$string_number]) ? $arr[$string_number] : NULL;
	}

	if ($force_html) $html = $force_html;
	
	if ($html === NULL) {
	
		echo "<font color=red>BAD html index</font> ($page_id,$project_id,$string_number)";
		return "<font color=red>BAD html index</font> ($page_id,$project_id,$string_number)";
	}

	//проверяем есть ли уже такая ссылка, может дважды нажали
	$database->setQuery("SELECT count(*) FROM links WHERE project_id = '{$project_id}' AND page_id = '{$page_id}'");
	
	$cnt = $database->loadResult();
	
	if ($cnt > 0) {
	
		echo "<font color=red>Ссылка этого же проекта тут есть</font>";
		return "<font color=red>Ссылка этого же проекта тут есть</font> ($page_id,$project_id,$string_number)";
	}
	else {
	
		//проверка страницы на активность
		$database->setQuery("SELECT * FROM pages WHERE id = '{$page_id}'");
		$page_obj = $database->loadObjectList();$page_obj = $page_obj[0];
		$page_status = $page_obj->status;
		
		//на всякий случай проверку делаем
		if ($page_status == LLM_PAGE_STATUS_UNACTIVE) {
		
			echo "<font color=red>Страница не активна, ссылки на ставим</font>";
			return "<font color=red>Страница не активна, ссылки на ставим</font>";
		}
		$site_id = $page_obj->site_id;
		
		//грузим настройки сайта
		$database->setQuery("SELECT * FROM sites WHERE id = '$site_id'");
		$sites = $database->loadObjectList();$site = $sites[0];
		
		//максимальное количество ссылок для данного типа страниц этого сайта
		if ($page_obj->nesting == 0) $max_links = $site->links_on_main;
			else $max_links = $site->links_on_other;
		
		if ($page_obj->links_on_page >= $max_links) {
		
			echo "<font color=red>Ссылок слишком много: {$page_obj->links_on_page}, разрешено {$max_links}</font>";
			return "<font color=red>Ссылок слишком много: {$page_obj->links_on_page}, разрешено {$max_links}</font> ($page_id,$project_id,$string_number)";
		}
		
		//заменяем саповские штуки #a#......#/a#
		if (!isset($cached_aurl[$project_id])) {
			
			//грузим проект, оттуда будем выдирать html-код ссылки
			$database->setQuery("SELECT aurl FROM projects WHERE id = '{$project_id}'");
			$aurl = $database->loadResult();
			
			$cached_aurl[$project_id] = $aurl;
		}
		else {
		
			$aurl = $cached_aurl[$project_id];
		}
		
		if ($aurl) {
		
			$html = str_replace("#/a#","</a>",$html);
			$html = str_replace("#a#","<a href='{$aurl}'>",$html);
		}
		
		$html = addslashes(trim($html));
		$database->setQuery("INSERT INTO links (html,time_start,status,project_id,page_id) VALUES ('$html','".time()."','".LLM_LINK_STATUS_ACTIVE."','{$project_id}','{$page_id}')");
		$database->query();
		
		$database->setQuery("UPDATE pages SET links_on_page = links_on_page + 1 WHERE id = '{$page_id}'");
		$database->query();
		
		echo "<font color=green>Ссылка установлена</font>";
		return true;
	}
}

function searchLinks() {

	$database   = llmServer::getDBO();
	$project_id = (int)getParam('project_id');
	$links_on_page = (int)getParam('links_on_page');
	
	$sql_where  = array();
	//вложенность
	$nesting = isset($_POST['nesting']) ? $_POST['nesting'] : array();
	$sql_where = array();
	if (sizeof($nesting)>0) {
		
		$sql_where[] = " pages.nesting IN (".implode(',',array_keys($nesting)).") ";
	}
	
	//количество наших ВС
	if ($links_on_page!=0) {
	
		$sql_where[] = " pages.links_on_page <= {$links_on_page}";
	}
	
	//начальный PR
	$pr_start = getParam('pr_start');
	if ($pr_start!=="") {
	
		$pr_start = (int)$pr_start;
		$sql_where[] = " pages.pr >= {$pr_start}";
	}
	
	//конечный PR
	$pr_end = getParam('pr_end');
	if ($pr_end!=="") {
	
		$pr_end = (int)$pr_end;
		$sql_where[] = " pages.pr <= {$pr_end}";
	}
	
	$page_title = getParam('page_title');
	if ($page_title) {
	
		$sql_where[] = " pages.title LIKE '%{$page_title}%'";
	}
	
	//на заданных сайтах
	$sites = isset($_POST['sites']) ? $_POST['sites'] : array();
	if (sizeof($sites) == 1 && $sites[0] == 0) {
	
		//если на любых сайтах, то фильтра нет
	}
	else {
	
		//иначе фильтр
		$sql_where[] = " pages.site_id IN (".implode(',',$sites).") ";
	}
	
	//максимальное количество внешних ссылок
	$maxel = getParam('maxel');
	if ($maxel!=="") {
	
		$maxel = (int)$maxel;
		$sql_where[] = " pages.external_links_count <= {$maxel}";
	}
	
	//учет наличия в индексе
	$is_in_yandex_index = (int)getParam('is_in_yandex_index');
	if ($is_in_yandex_index) {
	
		$sql_where[] = " pages.is_in_yandex_index = 1";
	}
	//и гугль
	$is_in_google_index = (int)getParam("is_in_google_index");
	if ($is_in_google_index) {
	
		$sql_where[] = " pages.is_in_google_index = 1";
	}
	
	//категория
	$category_id = (int)getParam('category_id',0);
	if ($category_id) {
	
		$sql_where[] = " sites.category_id = '{$category_id}'";
	}
	
	if (sizeof($sql_where)==0) {
	
		llmServer::showError("No conditions to search",false);
		return;
	}
	else {
	
		$where = implode(" AND ",$sql_where);
	}
	
	$where .= " AND pages.status = ".LLM_PAGE_STATUS_ACTIVE." ";

	$query = "SELECT pages.id, pages.url, pages.pr, pages.nesting, pages.external_links_count,sites.url as sites_url,pages.links_on_page FROM pages INNER JOIN sites ON sites.id = pages.site_id WHERE $where AND pages.links_on_page < if(pages.nesting=0,sites.links_on_main,sites.links_on_other) ORDER BY sites_url ASC, pages.external_links_count ASC, nesting ASC, PR desc ";
	
	$database->setQuery($query);
	$pages = $database->loadObjectList('id');

	//далее нужно отобрать все ссылки стоящие на этих страницах и исключить проекты этих ссылок
	//для этого выбираем идентификаторы страниц из нашего массива
	$pages_ids = array_keys($pages);
	//смотрим все идентификаторы страниц на которых расположены ссылки принадлежащие ЭТОМУ проекту
	$database->setQuery("SELECT links.page_id FROM links INNER JOIN projects ON projects.id = links.project_id WHERE projects.id = '{$project_id}'");
	$all_placed_pages = $database->loadResultArray();
	//а далее тупо исключаем из общего списка страниц те, на которых уже есть ссылки этого проекта
	foreach($all_placed_pages as $page_id) unset($pages[$page_id]);
	//конец фильтрации страниц, на которых уже есть ссылки из ЭТОГО проекта
	
	//фильтрация по количеству ссылок на один сайт в выдаче
	$pages_per_site = (int)getParam('pages_per_site');
	if ($pages_per_site) {
		
		$filtered_domains = array();
		foreach($pages as $id => $page) {
		
			$domain = $page->sites_url;
			if (!isset($filtered_domains[$domain])) {
			
				$filtered_domains[$domain] = 1;
				continue;
			}
			else {
	
				$size = $filtered_domains[$domain];
				if ($size >= $pages_per_site) unset($pages[$id]);
				$filtered_domains[$domain]++;
			}
		}
		unset($filtered_domains);
	}

	echo "<h2>Нашлось: ".sizeof($pages)."</h2>";
	//если ничего не найдено
	if (sizeof($pages) == 0) {
		
		echo "К сожалению совпадений нет";
		return;
	}
	
	$only_list = getParam('only_list');
	if ($only_list) {
	
		llmServer::deleteOldFiles('tmp/preview_*.txt');
		
		$rand = substr(md5(time()),0,5);$fname = "preview_{$rand}.txt";
		$f = fopen(llmServer::getPPath()."tmp/{$fname}",'w');$arr = array();
		foreach($pages as $page) {
		
			$full_url = $page->sites_url.($page->url == "/" ? '' : $page->url);
			$arr[] = $full_url;
		}
		fwrite($f,implode("\n",$arr));fclose($f);
		
		echo "<p>Сгенерирован файл с адресами найденных страниц, его можно скачать, <a href='".llmServer::getWPath()."tmp/{$fname}' target='_blank'>пройдя по этой ссылке</a></p>";
		
		return;
	}
	
	//массовое добавление
	$mass_check = getParam('mass_check');
	if ($mass_check) {
	
		@set_time_limit(0);
		ini_set('max_execution_time',0);
		
		$arr = getDataFileUrls($project_id);
		$arr_index = 0;$arr_size = sizeof($arr);

		//пишем cron-файл с заданием на расстановку ссылок
		$rand = substr(md5(time()),0,5);$delimiter = "||||||||||";$fname = "masslink_{$rand}.cron";
		$f = fopen(llmServer::getPPath()."tmp/{$fname}",'w');
		foreach($pages as $page) {
		
			$html = trim($arr[$arr_index]);
			
			if (!$html) {
				
				$html = trim($arr[$arr_index]);
				while($html=='') {
					
					$arr_index++;
					if ($arr_index == $arr_size) $arr_index = 0;
					$html = trim($arr[$arr_index]);
				}
			}

			$pieces = array($project_id,$page->id,$html);
			fputs($f,implode($delimiter,$pieces)."\n");fflush($f);
			
			$arr_index++;
			if ($arr_index == $arr_size) $arr_index = 0;
		}
		fclose($f);
		
		llmServer::redirect(llmServer::getHPath('projects',''),"Файл крон-задачи создан ({$fname}) и в ближайшее время будет обработан");
		return;
	}

	$only_random = getParam('only_random');

	//возможные якоря для этого проекта
	$anchor_select = "<select name='{{{NAME}}}' id='{{{NAME}}}'><option value=-1>Случайный выбор</option>";
	$arr = getDataFileUrls($project_id);

	if (!$only_random) {
		
		foreach($arr as $index => $line) {
		
			$anchor_select .= "<option value='{$index}'>".htmlspecialchars(trim($line))."</option>";
		}
	}
	$anchor_select .= "</select>";
	
	
	echo "<table align='center' width='90%'>
	<form action='".llmServer::getWPath('')."index.php?mod=projects&task=place_links' name='bform' id='bform' method='post'>
	";
	echo "<tr>
		<td width='50%'>URL</td>
		<td width='7%'>PR</td>
		<td width='7%'>УВ</td>
		<td width='7%'>ВС</td>
		<td width='7%'>ВС наши</td>
		</tr>";

	foreach($pages as $page) {
	
		$full_url = $page->sites_url.($page->url == "/" ? '' : $page->url);
		$view_url = llmHTML::trimURL($full_url);
		echo "<tr>
		<td width='50%'><a href='{$full_url}' target='_blank'>{$view_url}</a></td>
		<td width='7%'>{$page->pr}</td>
		<td width='7%'>{$page->nesting}</td>
		<td width='7%'><a href='".llmServer::getHPath('sites','get_links_on_page','page_id='.$page->id.'&no_html=1')."' rel='ibox&width=500&height=150'>{$page->external_links_count}</a></td>
		<td width='7%'>{$page->links_on_page}</td>
		</tr>";
		
		$tmp_anchor = $anchor_select;
		$tmp_anchor = str_replace("{{{NAME}}}","anchor_{$page->id}",$tmp_anchor);
		echo "<tr>
		<td width='50%' align='left' colspan=5>
			<span style='cursor:pointer;cursor:hand;' title='Одно нажатие, и на этой странице окажется ссылка из вашего проекта' onclick=\"putlink({$page->id},{$project_id})\">Разместить здесь &uarr; &uarr; &uarr;</span>
			&nbsp;{$tmp_anchor}&nbsp;<input type='checkbox' name='place[]' value='{$page->id}'>
			&nbsp;<span id='status_box_{$page->id}'></span>
		</td>
		</tr>";
	}
	echo "</table><br><br>
	<p style='text-align:center'><input type='submit' value=' Разместить ссылки на выбранных страницах '></p>
	<input type='hidden' name='project_id' value='$project_id'>
	</form>";
}

function startSetup($project_id) {

	$database = llmServer::getDBO();

	//рисуем число страниц сразу же
	$database->setQuery("SELECT site_id,count(*) as count FROM pages GROUP BY site_id");
	$pcount = $database->loadObjectList('site_id');
	
	$arrx = getDataFileUrls($project_id);
	if (sizeof($arrx) == 0) {
	
		echo "В проекте не обнаружено HTML-кодов ссылок. Отбор страниц невозможен.";
		return;
	}
	
	$site_sel = "<select name='sites[]' multiple='miltiple' size=16 style='width:100%'><option value='0' selected='yes'>На всех сайтах</option>";
	$database->setQuery("SELECT * FROM sites ORDER BY id");
	$sites = $database->loadObjectList();
	foreach($sites as $site) {
	
		$c = isset($pcount[$site->id]) ? " (".$pcount[$site->id]->count.")" : "";
		$site_sel .= "<option value='{$site->id}'>{$site->url} {$c}</option>";
	}
	$site_sel .= "</select>";
	
	$cat_sel = buildCatSel(0," multiple='miltiple' size=6 style='width:100%'");
	//
	echo "
<h2 id='search'>Поиск ссылок</h2>
<form action='".llmServer::getWPath()."index.php?mod=projects&task=search_links' method='post'>";
	echo "<table slign='center' width='100%'>
	<tr> <td width='30%'>Уровень вложенности</td> <td>
	<input type='checkbox' name='nesting[0]' checked='checked'>&nbsp;Нулевой (главная)<br>
	<input type='checkbox' name='nesting[1]' checked='checked'>&nbsp;Первый <br>
	<input type='checkbox' name='nesting[2]' checked='checked'>&nbsp;Второй<br>
	</td> </tr>
	
	<tr> <td width='30%'>PR</td> <td>от <input type='text' size='4'  style='padding:4px' name='pr_start' value='-2'> до <input type='text' style='padding:4px' value='5' size='4' name='pr_end'> ".showHelp("-2 означает, что расчет PR отменен пользователем<br>-1 означает, что PR не расчитан; <br>0,1,2,... - реальный PR")."</td> </tr>
	
	<tr> <td width='30%'>Сайты (через Ctrl)</td> <td>$site_sel</td> </tr>
	<tr> <td width='30%'>Категории (через Ctrl)</td> <td>$cat_sel</td> </tr>
	
	<tr> <td width='30%'>Максимальное количество ВС</td> <td>до <input type=text value=10 size=4  style='padding:4px'  name=maxel></td> </tr>
	<input type='hidden' name='project_id' value='$project_id'>
	<tr> <td width='30%'>Наличие в индексе</td> <td>
	Yandex: <select name='is_in_yandex_index'>
	<option value='0'>Не важно</option>
	<option value='1'>Обязательно</option>
	</select>
	Google: <select name='is_in_google_index'>
	<option value='0'>Не важно</option>
	<option value='1'>Обязательно</option>
	</select>
	<tr> <td width='30%'>Ограничить число найденных страниц с домена</td> <td>до <input type=text value=0 size=4  style='padding:4px'  name=pages_per_site> ".showHelp("Если 0 - без ограничения")."</td> </tr>
	</td> </tr>
	<tr> <td width='30%'>Пакетное добавление".showHelp("Это значит что последовательно на каждую найденную страницу будет добавлено <br>по одной ссылке из данного проекта, последовательно из всех имеющихся. <br>Если ссылок не будет хватать для найденных страниц, то коды будут перебираться <br>по второму кругу")."</td> <td>
	<input type='checkbox' name='mass_check'>&nbsp;Да
	</td> </tr>
	<tr> <td width='30%'>Только вывод страниц".showHelp("При выборе данной опции будет сгенерирован текстовый файл со всеми найденными страницами,<br> подходящими под ваши условия. Полезно в том, случайно, если перед пакетной расстановкой <br>вам необходимо просмотреть страницы, на которых будут размещены ссылки")."</td> <td>
	<input type='checkbox' name='mass_check'>&nbsp;Да
	</td> </tr>
	<tr> <td width='30%'>Только случайный выбор".showHelp("В списке якорей будет только одна опция - случайный выбор<br>Полезно если якорей много и выводя их в каждом списке мы только утяжеляем страницу")."</td> <td>
	<input type='checkbox' name='only_random'>&nbsp;Да
	</td> </tr>
	<tr> <td width='30%'>Количество наших ВС</td> <td> до <input type=text value=0 size=4  style='padding:4px' name=links_on_page>	 ".showHelp("Число наших внешних ссылок меньше или равно введенному значению<br>0 - параметр не участвует в поиске")."</td> </tr>
	
	<tr> <td width='30%'>Фраза в TITLE ".showHelp("При индексации страниц робот сохраняет в таблицу значение <br>тэга TITLE, а здесь вы можете ввести фразу, которая должна <br>присутствовать в отбираемых страницах")."</td> <td> <input type=text value='' style='width:95%;padding:4px' name=page_title></td> </tr>
	";

	echo llmHTML::formBottom('search_links','Найти ссылки');
	
	echo "</table>";
}

function saveProject() {

	$database = llmServer::getDBO();
	
	if (sizeof($_POST) == 0) {
	
		echo "Ошибка обработки запроса (массив _POST пуст)";
		return;
	}
	
	$id   = (int)getParam('id');
	$name = getParam('name');
	$name = str_replace(array("'",'"'),"",$name);//верстку бъют всю нафик
	//$urls = getParam('urls');
	$aurl = getParam('aurl');
	$parent_project_id = (int)getParam('parent_project_id');
	
	$urls = "";
	//аплоад из файла
	if ($_FILES) {
		
		$fil = $_FILES['fil'];
		if ($fil['name'] && $fil['error']==0 && file_exists($fil['tmp_name'])) {
		
			//значит что-то есть
			$add = file_get_contents($fil['tmp_name']);
			$urls = $add;
		}
	}
	//проверяем длину кодов ссылок
	$arr = explode("\n",$urls);$length_ok = true;
	foreach($arr as $link) {
	
		if (strlen(trim($link)) > 255) {
		
			$length_ok = false;
			break;
		}
	}
	if (!$length_ok) {
	
		$link = trim($link);
		echo "Якорь<br>
		<pre>$link</pre><br>
		Имеет слишком большую длину (больше 255 символов).";
		
		return;
	}

	if ($id) {
	
		$query = "UPDATE projects SET name='$name', aurl = '$aurl' WHERE id = '$id'";//, urls='$urls'
	}
	else {
	
		$query = "INSERT INTO projects (name,parent_id,aurl) VALUES ('$name','$parent_project_id','$aurl')";//urls,    '$urls',
	}
	
	$database->setQuery($query);
	$database->query();
	
	if (!$id) $id = $database->insertid();
	
	//сохраняем в файл данные по кодам
	if ($urls) {
	
		$fu = getDataFileUrls($id);
		foreach($fu as $k=>$v) if(trim($v)=="") unset($fu[$k]);
		foreach($urls as $k=>$v) if(trim($v)=="") unset($urls[$k]);
		
		$f  = fopen(getDataFileName($id),"w");
		if ($f) {
			
			fwrite($f,$fu ? implode("",$fu)."\n".$urls : "".$urls);
			fclose($f);
		}
		else {
		
			llmServer::showError("Cannot write to project data file ($id)");
			return;
		}
	}
	
	llmServer::redirect(llmServer::getHPath('projects',''),"Проект успешно сохранен");
}

function getDataFileName($project_id) {

	return $project_id ? llmServer::getPPath().'data/project_urls_'.$project_id.'.txt' : NULL;
}
function getDataFileUrls($project_id) {

	$project_id = (int)$project_id;
	$p = getDataFileName($project_id);
	if ($p) {
	
		if (file_exists($p)) return file($p);
			else {
				//апгрейд на новую версию - файла тупо нет
			
				$database = llmServer::getDBO();
				$database->setQuery("SELECT urls FROM projects WHERE id='{$project_id}'");
				$urls = $database->loadResult();
				
				$f = fopen($p,"w");
				if ($f) {
				
					fwrite($f,$urls);
					fclose($f);
					
					return file($p);
				}
				else {
				
					llmServer::showError("Cannot create file '{$p}', check data directory chmod");
				}
			}
	}
	else {
	
		return array();
	}
}

function formatFileSize($bytes) {
	
	if ($bytes >= 1073741824)
	{
		$bytes = number_format($bytes / 1073741824, 2) . ' GB';
	}
	else
	if ($bytes >= 1048576)
	{
		$bytes = number_format($bytes / 1048576, 2) . ' MB';
	}
	else
	if ($bytes >= 1024)
	{
		$bytes = number_format($bytes / 1024, 2) . ' KB';
	}
	else
	if ($bytes > 1)
	{
		$bytes = $bytes . ' байт(a)';
	}
	else
	if ($bytes == 1)
	{
		$bytes = $bytes . ' байт';
	}
	else
	{
		$bytes = '0 байт';
	}

	return $bytes;

}
function clearUrls($project_id) {

	$fn = getDataFileName($project_id);
	$f = fopen($fn,"w");
	if (!$f) {
	
		echo "Cannot write to $fn";
		return;
	}
	
	fclose($f);
	
	llmServer::redirect(llmServer::getHPath('projects',''),"Ссылки проекта очищены");
}

function saveUrls($project_id) {

	$urls = get_magic_quotes_gpc() ? stripslashes($_POST['urls']) : $_POST['urls'];
	$file = getDataFileName($project_id);

	//проверяем длину кодов ссылок
	$arr = explode("\n",$urls);$length_ok = true;
	foreach($arr as $link) {
	
		if (strlen(trim($link)) > 255) {
		
			$length_ok = false;
			break;
		}
	}
	if (!$length_ok) {
	
		$link = trim($link);
		echo "Якорь<br>
		<pre>$link</pre><br>
		Имеет слишком большую длину (больше 255 символов).";
		
		return;
	}

	
	$f = fopen($file,"w");
	if ($f) {
		
		fwrite($f,$urls);
		fclose($f);
	}
	else {
	
		llmServer::showError("Cannot write to project data file ($project_id)");
		return;
	}
	
	llmServer::redirect(llmServer::getHPath('projects',''),"HTML-коды проекта сохранены");
}

function editUrls($project_id) {

	$data = file_get_contents(getDataFileName($project_id));

	echo "<html><head><title>Редактирование html-кодов проекта</title></head>
	<body>
	<p>На каждой строке - один код ссылки. Без заполнения этих кодов вы не сможете начать продвижение этого проекта. Максимально 255 символов в строке.<br>
	Кроме того обратите внимание на еще один штрих. Если вы хотите управлять CSS-классами ссылок на сайте, то HTML-коды должны быть в формате<br>
	<pre>...&lt;a[ОДИН_ИЛИ_НЕСКОЛЬКО_ПРОБЕЛОВ]href...</pre>
	Именно в эти самые пробелы и будет вставлен атрибут класса ссылки. Стоит написать туда какой-нибудь другой атрибут, например:
<br>
	<pre>...&lt;a id='my_link' href...</pre>
	И классы ссылок не сработают.
	<br><br>
	</p>
	<form action='".llmServer::getWPath()."index.php?mod=projects&task=save_urls' method='POST'>
	<textarea name='urls' style='width:100%;height:90%' rows=50>{$data}</textarea>
	<input type='hidden' name='project_id' value='$project_id'>
	<p style='text-align:center'><input type='submit' value='Сохранить'></p>
	</form>
	</body></html>";
}

function editProject($id) {

	$database = llmServer::getDBO();
	$parent_project_id = (int)getParam('parent_project_id');
	
	if ($id) {
	
		$database->setQuery("SELECT * FROM projects WHERE id = '{$id}'");
		$projects = $database->loadObjectList();
		
		if (sizeof($projects) == 0) {
		
			llmServer::showError("Проект `$id` не обнаружен",false);
			return;
		}
		$project = $projects[0];
		$project->urls = htmlspecialchars($project->urls);
	}
	else {
	
		$project = new stdClass();
		$project->id = 0;
		$project->name = 'LinkSuite - лучше всех';
		$project->urls = htmlspecialchars("<a href='http://linksuite.ru'>Скрипт расстановки ссылок</a>\n<a href='http://linksuite.ru'>Скрипт управления ссылками</a>\n<a href='http://linksuite.ru'>Управление ссылками на ваших доменах</a>");
		$project->aurl = '';
	}
	
	$umf = ini_get("upload_max_filesize");
	$pms = ini_get("post_max_size");
	
	//грузим урлы проекта
	$urls = getDataFileUrls($project->id);
	$urls_size = sizeof($urls);
	
	if ($project->id) {

		$a = strlen(file_get_contents(getDataFileName($project->id)));
		if ($a > 0) $sz = " - ".formatFileSize($a);
			else $sz = "";
		
		$clear_url = llmServer::getHPath('projects','clear_urls&project_id='.$project->id);
		
		$links = "- <a target='_blank' href='".str_replace(llmServer::getPPath(),llmServer::getWPath(),getDataFileName($project->id)).'?rand='.rand()."'>[просмотр$sz]</a> | <a href='".llmServer::getHPath('projects','edit_urls&no_html=1&project_id='.$project->id)."'>[редактировать]</a> | <a href=\"javascript: if (confirm('Вы точно хотите удалить все адреса?')) { window.location.href='{$clear_url}' } else { void('') };\"  >[очистить]</a>";
		

	}
	else {
	
		$links = "";
	}
	
	echo "<form action='".llmServer::getWPath()."index.php?mod=projects&task=save_project' method='post' enctype='multipart/form-data'>
	<table align='center' width='100%'>
	<tr> <td width=25%>Имя</td>  <td><input type=text name='name' value='{$project->name}' style='width:100%'></td> </tr>
	<tr> <td width=25%>HTML-коды ссылок ".showHelp("В целях усовершенствования скрипта, хранение адресов было вынесено из<br>базы данных в текстовые файлы, которые в свою очередь находятся в директории /data/")."</td>  <td>
	Всего в файле сохранено $urls_size адрес(ов) {$links}
	
	</td> </tr>
	<tr> <td width=25%>Загрузка html-кодов из файла".showHelp("Все строки из файла дописываются в конец соответствующего файла html-кодов<br>Внизу отображаются настройки PHP, связанные с лимитами на загрузку файлов")."<br>
	<ul>
	<li>Upload Max Filesize = <b>{$umf}</b>
	<li>Post Max Size = <b>{$pms}</b>
	</ul>
	</td>  <td valign='top'><input type=file name='fil' style='width:100%'><br /><b>Внимание: файл должен быть в кодировке CP1251</b></td> </tr>
	<tr> <td width=25%>URL для замены #a#...#/a# ".showHelp("Функция, аналогичная таковой в системе Sape.Ru. <br>#a# заменяется на открывающий HTML-тэг A с href, равным введенному сюда адресу, <br>#/a# - на закрывающий тэг.")."</td>  <td><input type=text name='aurl' value='{$project->aurl}' style='width:100%'></td> </tr>
	";
	
	echo llmHTML::formBottom('save_project','Сохранить проект');
	
	echo "
	</table>
	<input type='hidden' name='id' value='{$project->id}'>
	<input type='hidden' name='parent_project_id' value='{$parent_project_id}'>
	</form>";	
}

function listProjects() {

	$database = llmServer::getDBO();
	
	//если заказан просмотр субпроектов конкретного проекта
	$parent_project_id = (int)getParam('parent_project_id');
	
	$database->setQuery("SELECT * FROM projects ".($parent_project_id ? "WHERE parent_id='{$parent_project_id}'" : "WHERE parent_id = 0 ")."ORDER BY id");
	$list = $database->loadObjectList();
	
	//список с делением на количество субпроектов в проекте
	$database->setQuery("SELECT parent_id,count(*) as count FROM projects GROUP BY parent_id");
	$cntx = $database->loadObjectList('parent_id');
	
	//количество ссылок в каждом проекте
	$database->setQuery("SELECT projects.id as pid,count(links.id) as count FROM projects INNER JOIN links ON links.project_id=projects.id GROUP BY projects.id");
	$cnt = $database->loadObjectList('pid');

	echo "<h2 id=projects>Список ваших ".($parent_project_id ? "суб" : "")."проектов ".($parent_project_id ? "<a href='".llmServer::getHPath('projects','')."'>[вверх к главным проектам]</a>" : "")."</h2>";
	if (sizeof($list) == 0) {
		
		echo "Проектов не обнаружено";
		return;
	}
	echo "
	
		<script type='text/javascript'>
		function displayRow(elem) {
		
			var row = document.getElementById(elem);
			if (row.style.display == '')  row.style.display = 'none';
			else row.style.display = '';
		}
		</script>
	";
	
	echo "<table align='center' width='100%'>";
	echo "<tr><td width='20'>&nbsp;</td><td width='25%'>Имя</td><td width='10%' align='center'>Ссылки</td><td width='25%' align='center'>Субпроекты</td><td class=searchContainer width='25%'>Действия</td><td width='15%'>Удаление</td></tr>";
	foreach($list as $project) {
	
		$del_url = llmServer::getHPath('projects','delete_project','project_id='.$project->id);
		
		if ($parent_project_id) {
		
			$subproj_link = '';
		}
		else {
			
			$subproj_link = (isset($cntx[$project->id]) ? "<a href='".llmServer::getHPath('projects','','parent_project_id='.$project->id)."'>[ ".$cntx[$project->id]->count." ]</a>" : "[ нет ]")." <a href='".llmServer::getHPath('projects','add_project','parent_project_id='.$project->id)."'>[ добавить ]</a>";
		}
		
		//таблица субпроектов
		$subprojects = "";
		$database->setQuery("SELECT * FROM projects WHERE parent_id = '{$project->id}'");
		$sub = $database->loadObjectList();
		if ($sub) $icon = "<span style='cursor:hand' onClick=\"displayRow('tr_{$project->id}')\" title='Просмотр субпроектов'><img src='".llmServer::getWPath()."template/icons/sm/edit.png' /></span>";
			else $icon = "";
		
		$cl = @$cl==0 ? 1 : 0;
		$cl_class = " class='row{$cl}' ";
		echo "<tr{$cl_class}>
		<td width='20'>{$icon}</td>
		<td><a href='".llmServer::getHPath('projects','edit_project','id='.$project->id)."'>{$project->name}</a></td>
		<td align='center'>
			".(isset($cnt[$project->id]) ? "<a href='".llmServer::getHPath('projects','show_links','id='.$project->id)."'>[&nbsp;".($cnt[$project->id]->count)."&nbsp;]</a>" : '[ нет ]')."
			
		</td>
		<td align='center'>{$subproj_link} </td>
		<td align='center'><a class=searchlink href='".llmServer::getHPath('projects','start_setup','project_id='.$project->id)."'>Начать поиск площадок</a></td>
		<td><a href=\"javascript: if (confirm('Вы точно хотите удалить. Восстановление будет не возможно и все такое. Лучше такими кнопочками не играться. Срочно нажимайте Отменить, пока не стало слишком поздно.')) { window.location.href='{$del_url}' } else { void('') };\" class='delete'>удалить?</a></td>
		</tr>";
		
		if ($sub) {
		
			$subprojects = "<table align='center' width='100%' cellspacing=0 cellpadding=0>";
			foreach ($sub as $sp) {

				$del_url = llmServer::getHPath('projects','delete_project','project_id='.$sp->id);
				
				$subprojects .= "<tr>
					<td width='20'>&nbsp;&rarr;</td>
					<td width='25%'><a href='".llmServer::getHPath('projects','edit_project','id='.$sp->id)."'>{$sp->name}</a></td>
					<td  width='10%' align='center'>
						".(isset($cnt[$sp->id]) ? "<a href='".llmServer::getHPath('projects','show_links','id='.$sp->id)."'>[&nbsp;".($cnt[$sp->id]->count)."&nbsp;]</a>" : '[ нет ]')."
						
					</td>
					<td  width='25%' align='center'></td>
					<td  width='25%' align='center'><a class=searchlink href='".llmServer::getHPath('projects','start_setup','project_id='.$sp->id)."'>Начать поиск площадок</a></td>
					<td width='15%'><a href=\"javascript: if (confirm('Вы точно хотите удалить. Восстановление будет не возможно и все такое. Лучше такими кнопочками не играться. Срочно нажимайте Отменить, пока не стало слишком поздно.')) { window.location.href='{$del_url}' } else { void('') };\" class='delete'>удалить?</a></td>
					</tr>";
			}
			$subprojects .= "</table>";
			
			
			echo "<tr id='tr_{$project->id}' style='display:none'><td colspan=6>
			{$subprojects}
			</td></tr>";
		}
		

	}
	echo "</table>";
}
?>