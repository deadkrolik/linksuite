<?PHP
defined('LLM_STARTED') or die("o_0");

llmTitler::put("���������� ��������������");
switch($task) {
		
	case 'delete_user':
		deleteUser();
		break;
		
	case 'save_user':
		saveUser();
		break;
		
	case 'edit_user':
		editUser((int)getParam('id',0),"�������������� ������������");
		break;
		
	case 'add_user':
		editUser(0,"�������� ������ ������������");
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
	
		llmServer::showError("������ ���� ������� �������",false);
		return;
	}

	$database->setQuery("DELETE FROM users WHERE id = '{$id}'");
	$database->query();
	
	//����� �� ���
	llmServer::redirect(llmServer::getHPath('users',''),"������������ ������");
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
	
		llmServer::showError("������ ��� ������������",false);
		return;
	}
	
	if ($password1!=$password2 || (!$id && ($password1=='' || $password2==''))) {
	
		llmServer::showError("������ �� ���������",false);
		return;
	}

	//� ���� �� ���� � ����� �� ������ ���
	$database->setQuery("SELECT COUNT(*) FROM users WHERE username='{$username}'");
	$cnt = $database->loadResult();
	if (!$id && $cnt) {
	
		llmServer::showError("������������ � ����� ������ ��� ���������� � �������",false);
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
	
	llmServer::redirect(llmServer::getHPath('users',''),"������������ ��������");
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

	//��������� ������ ����� �� ��������
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
	
	<tr> <td width=25%>���</td>  <td><input type=text name='username' value='{$user->username}' style='width:100%'></td> </tr>
	<tr> <td width=25%>������ ".showHelp("����� - ����������� ������, ������� ����� ������ ��������<br>�� �� ����� ������ �������� ��� �������")."</td>  <td>$gsellist</td> </tr>
	<tr> <td width=25%>������</td>  <td><input type=password name='password1'  style='width:100%'></td> </tr>
	<tr> <td width=25%>������ (������)</td>  <td><input type=password name='password2' style='width:100%'></td> </tr>";
	
	echo llmHTML::formBottom('save_user','��������� ������������',2,"button-add");
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
	
	echo "<h2 id=users>������ �������������</h2>";
	
	echo "<table align='center' width='100%'>";
	foreach($users as $user) {
	
		$del_url = llmServer::getHPath('users','delete_user','user_id='.$user->id);
		
		echo "<tr>
		<td><a href='".llmServer::getHPath('users','edit_user','id='.$user->id)."'>{$user->username}</a></td>
		<td>{$user->user_group}</td>
		<td width='10%' nowrap='nowrap'><a href=\"javascript: if (confirm('�� ����� ������ �������. �������������� ����� �� �������� � ��� �����. ����� ������ ���������� �� ��������. ������ ��������� ��������, ���� �� ����� ������� ������.')) { window.location.href='{$del_url}' } else { void('') };\" class=delete> �������?</a></td>
		</tr>";
	}
	echo "</table>";
	
	editUser(0,"�������� ������ ������������");
}
?>