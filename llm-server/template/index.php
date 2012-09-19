<?PHP
defined('LLM_STARTED') or die("o_0");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo llmTitler::get(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<?PHP
echo "<link rel='stylesheet' type='text/css' href='".llmServer::getWPath()."template/style.css' media='screen' />";
echo "<link rel='stylesheet' type='text/css' href='".llmServer::getWPath()."template/tables.css' media='screen' />";
echo "<script type='text/javascript' language='javascript' src='".llmServer::getWPath()."template/js/sack.js'></script>";
echo "<script type='text/javascript' language='javascript' src='".llmServer::getWPath()."template/js/stuff.js'></script>";
echo "<script type='text/javascript' language='javascript' src='".llmServer::getWPath()."template/js/ibox.js'></script>";
?>
<link rel="icon" href="<?PHP echo llmServer::getWPath();?>template/icons/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="<?PHP echo llmServer::getWPath();?>template/icons/favicon.ico" type="image/x-icon">
</head>
<body>
<?php echo "<script type='text/javascript' language='javascript' src='".llmServer::getWPath()."template/js/wz_tooltip.js'></script>"; ?>
<div id="wrap">
<div id="top"></div>
<div id="content">

<div class="header">
<h1>LinkSuite (литл пампкин эдишен)</h1>
</div>

<div class="breadcrumbs">
<?PHP llmServer::insertBlock('menu');?>
</div>
<div class="breadcrumbs2">
<?PHP llmServer::insertBlock('submenu');?>
</div>

<div class="middle">
<?PHP llmServer::insertBlock('statusmsg');?>
<?PHP

echo $executed_module;

?>
</div>
<div id="clear"></div>
</div>
<div id="bottom"></div>
</div>
<div id="footer">
Дизайн: <a href="http://www.redsoft.ru">Lexx</a> | Разработка: <a href="http://dead-krolik.info">Dead Krolik</a><br> 2009 - <?PHP echo date("Y",time());?>
</div>
</body>
</html>