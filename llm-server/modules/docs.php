<?PHP
defined('LLM_STARTED') or die("o_0");
require_once(llmServer::getPPath()."includes/textile.class.php");

switch($task) {

	case 'showsection':
		llmTitler::put("������ ������������");
		showSection();
		break;
		
	case '':
		llmTitler::put("������������");
		helpTOC();
		break;
	
}

function showSection() {

	$section = preg_replace("|[^a-z\.]|","",getParam('section'));
	$path = llmServer::getPPath().'docs/'.$section.'.txt';
	
	if (!file_exists($path)) {
	
		llmServer::showError("File not found: $path",false);
		return;
	}
	llmTitler::put($section);
	
	$textile = new Textile;
	echo $textile->TextileThis(file_get_contents($path));	
}

function helpTOC() {

	$tocs = array(
	'promo'     => "������ ���� ������ ����� �������",
	'start'     => "��������� ��������",
	'install' => "��� ��� ����������",
	'sites'     => "���������� �������",
	'projects'  => "���������� ���������",
	'articles'  => "���������� ������",
	'����� �� �������'   => array(
				'builder.base' => "����� ��������",
				'builder.engines' => "��������� �� ��������� ������"
								),
	'cron'      => "CRON-�������",
	'static'    => "��������� ���",
	'faq'       => "FAQ"
	);
	
	echo "<h2 id=docs>������� ������������</h2><ul>";
	foreach($tocs as $toc => $text) {
	
		if (!is_array($text)) {
			
			echo "<li><a href='".llmServer::getHPath('docs','showsection','section='.$toc)."'>{$text}</a></li>";
		}
		else {
		
			echo "<li>{$toc}<ul>";
			foreach ($text as $k => $v) {
			
				echo "<li><a href='".llmServer::getHPath('docs','showsection','section='.$k)."'>{$v}</a></li>";
			}
			echo "</ul></li>";
		}
	}
	echo "</ul>";
}



?>