<?PHP
defined('LLM_STARTED') or die("o_0");

llmTitler::put("Управление пользователями");
switch($task) {
		
	case 'delete_user':
		deleteUser();
		break;
		
	case 'save_user':
		saveUser();
		break;
		
	case 'edit_user':
		editUser((int)getParam('id',0),"Редактирование пользователя");
		break;
		
	case 'add_user':
		editUser(0,"Добавить нового пользователя");
		break;
		
	case '':
		listUsers();
		break;
}

function deleteUser() {

	$database = llmServer::getDBO();
	$id = (int)getParam('user_id');

	$user = llmAuth::getUser();
	
	if ($user->id == $id) {
	
		llmServer::showError("Самого себя незачем удалять",false);
		return;
	}

	$database->setQuery("DELETE FROM users WHERE id = '{$id}'");
	$database->query();
	
	//вроде бы все
	llmServer::redirect(llmServer::getHPath('users',''),"Пользователь удален");
}

function saveUser() {

	$database = llmServer::getDBO();
	
	$id         = (int)getParam('id');
	$username   = getParam('username');
	$password1  = getParam('password1');
	$password2  = getParam('password2');
	$user_group = getParam('user_group');
		
	$groups = getGroupList();
	if (!in_array($user_group,$groups)) {
	
		llmServer::showError("Bad user group",false);
		return;
	}
	
	if (!$username) {
	
		llmServer::showError("Пустое имя пользователя",false);
		return;
	}
	
	if ($password1!=$password2 || (!$id && ($password1=='' || $password2==''))) {
	
		llmServer::showError("Пароли не совпадают",false);
		return;
	}

	//а есть ли юзер с таким же именем уже
	$database->setQuery("SELECT COUNT(*) FROM users WHERE username='{$username}'");
	$cnt = $database->loadResult();
	if (!$id && $cnt) {
	
		llmServer::showError("Пользователь с таким именем уже существует в системе",false);
		return;
	}
	
	if ($id) {
	
		$key = substr(md5(rand().rand().rand()),0,10);
		if ($password1 && $password2) $pass_upd = ",password = '".($key.':'.md5($key.$password1))."'";
			else $pass_upd = "";
		
		$query = "UPDATE users SET username='$username',user_group='$user_group' {$pass_upd} WHERE id = '$id'";
	}
	else {
	
		$key = substr(md5(rand().rand().rand()),0,10);
		$query = "INSERT INTO users (username,password,user_group) VALUES ('$username','".($key.':'.md5($key.$password1))."','$user_group')";
	}
	$database->setQuery($query);
	$database->query();
	
	llmServer::redirect(llmServer::getHPath('users',''),"Пользователь сохранен");
}

function editUser($id,$title) {

	$database = llmServer::getDBO();
	$database->setQuery("SELECT * FROM users ORDER BY id");
	$users = $database->loadObjectList('id');
	
	if ($id) {
	
		$user = $users[$id];
	}
	else {
	
		$user = new stdClass();
		$user->id         = 0;
		$user->username   = '';
		$user->user_group = LLM_GROUP_GUEST;
	}	

	//формируем список групп из констант
	$groups = getGroupList();
	$gsellist = "<select name='user_group'>";
	foreach($groups as $group) {
	
		$is_sel    = $user->user_group == $group ? "selected='selected'" : '';
		$gsellist .= "<option value='{$group}' {$is_sel}>{$group}</option>";
	}
	$gsellist .= "</select>";
	
	echo "<h2 id=adduser>{$title}</h2>";
	
	echo "<form action='".llmServer::getWPath()."index.php?mod=users&task=save_user' method='post'>
	<table align='center' width='100%'>
	
	<tr> <td width=25%>Имя</td>  <td><input type=text name='username' value='{$user->username}' style='width:100%'></td> </tr>
	<tr> <td width=25%>Группа ".showHelp("Гости - специальная группа, которая может только смотреть<br>но не может ничего изменить или удалить")."</td>  <td>$gsellist</td> </tr>
	<tr> <td width=25%>Пароль</td>  <td><input type=password name='password1'  style='width:100%'></td> </tr>
	<tr> <td width=25%>Пароль (повтор)</td>  <td><input type=password name='password2' style='width:100%'></td> </tr>";
	
	echo llmHTML::formBottom('save_user','Сохранить пользователя',2,"button-add");
	echo "</table><input type='hidden' name='id' value='{$user->id}'></form>";	
}

function getGroupList() {

	$constants = get_defined_constants();$ret = array();
	
	foreach($constants as $k => $co) {
		$pos = strpos($k,"LLM_GROUP_");
		if ($pos!==false) $ret[$k] = $co;
	}
	return $ret;
}

function listUsers() {

	$database = llmServer::getDBO();
	$database->setQuery("SELECT * FROM users ORDER BY id");
	$users = $database->loadObjectList('id');
	
	echo "<h2 id=users>Список пользователей</h2>";
	
	echo "<table align='center' width='100%'>";
	foreach($users as $user) {
	
		$del_url = llmServer::getHPath('users','delete_user','user_id='.$user->id);
		
		echo "<tr>
		<td><a href='".llmServer::getHPath('users','edit_user','id='.$user->id)."'>{$user->username}</a></td>
		<td>{$user->user_group}</td>
		<td width='10%' nowrap='nowrap'><a href=\"javascript: if (confirm('Вы точно хотите удалить. Восстановление будет не возможно и все такое. Лучше такими кнопочками не играться. Срочно нажимайте Отменить, пока не стало слишком поздно.')) { window.location.href='{$del_url}' } else { void('') };\" class=delete> удалить?</a></td>
		</tr>";
	}
	echo "</table>";
	
	editUser(0,"Добавить нового пользователя");
}
?>