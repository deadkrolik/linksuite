<?PHP
//���� �������� ��� ������ ����� �������, ����������� � ���� ����������
$base  = dirname(__FILE__);
$files = glob($base."/*.php");

foreach($files as $incl) {

	if ($incl != __FILE__) {
	
		require_once($incl);
	}
}
?>