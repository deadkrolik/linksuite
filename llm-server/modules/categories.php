<?PHP
defined('LLM_STARTED') or die("o_0");

llmTitler::put("Управление категориями");
switch($task) {

	case 'delete_category':
		deleteCategory();
		break;
		
	case 'save_category':
		saveCategory();
		break;
		
	case 'edit_category':
		editCategory((int)getParam('id',0),"Редактирование категории");
		break;
		
	case 'add_category':
		editCategory(0,"Добавить новую категорию");
		break;
		
	case '':
		listCategories();
		break;
}

function deleteCategory() {

	$database = llmServer::getDBO();
	$category_id = (int)getParam('category_id');

	//обнуляем категорию у сайтов
	$database->setQuery("UPDATE sites SET category_id = '0' WHERE category_id='{$category_id}'");
	$database->query();
	
	//и сама категория
	$database->setQuery("DELETE FROM categories WHERE id = '{$category_id}'");
	$database->query();
	
	//вроде бы все
	llmServer::redirect(llmServer::getHPath('categories',''),"Категория удалена");
}

function saveCategory() {

	$database = llmServer::getDBO();
	
	$id   = (int)getParam('id');
	$name = getParam('name');
		
	if (!$name) {
	
		echo "Имя не может быть пустым";
		return;
	}
	
	if ($id) {
	
		$query = "UPDATE categories SET name='$name' WHERE id = '$id'";
	}
	else {
	
		$query = "INSERT INTO categories (name) VALUES ('$name')";
	}
	
	$database->setQuery($query);
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('categories',''),"Категория сохранена");
}

function editCategory($id,$title) {

	$database = llmServer::getDBO();
	$database->setQuery("SELECT * FROM categories ORDER BY id");
	$categories = $database->loadObjectList('id');
	
	if ($id) {
	
		$category = $categories[$id];
	}
	else {
	
		$category = new stdClass();
		$category->id = 0;
		$category->name = '';
	}
	
	echo "<h2 id=newcat>{$title}</h2>";
	
	echo "<form action='".llmServer::getWPath()."index.php?mod=categories&task=save_category' method='post'>
	<table align='center' width='100%'>
	<tr> <td width=25%>Имя</td>  <td><input type=text name='name' value='{$category->name}' style='width:100%'></td> <td width='15%'>&nbsp;</td> </tr>";
	
	echo llmHTML::formBottom('save_category','Сохранить категорию',3,"button-add");
	
	echo "
	</table>
	<input type='hidden' name='id' value='{$category->id}'>
	</form>";	
}

function listCategories() {

	$database = llmServer::getDBO();
	$database->setQuery("SELECT * FROM categories ORDER BY id");
	$categories = $database->loadObjectList('id');
	
	echo "<h2 id='catlist'>Список ваших категорий</h2>";
	if (sizeof($categories) == 0) {
		
		echo "<div class=error>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Категорий не обнаружено<br><br></div>";
	}
	else {
			
		echo "<table align='center' width='100%'>";
		foreach($categories as $category) {
		
			$del_url = llmServer::getHPath('categories','delete_category','category_id='.$category->id);
			
			echo "<tr>
			<td width='85%'><a href='".llmServer::getHPath('categories','edit_category','id='.$category->id)."'>{$category->name}</a>
			</td>
			<td width='15%' nowrap='nowrap'><a href=\"javascript: if (confirm('Вы точно хотите удалить. Восстановление будет не возможно и все такое. Лучше такими кнопочками не играться. Срочно нажимайте Отменить, пока не стало слишком поздно.')) { window.location.href='{$del_url}' } else { void('') };\" class=delete>удалить?</a></td>
			</tr>";
		}
		echo "</table>";
	}
	
	editCategory(0,"Добавить новую категорию");
}

?>