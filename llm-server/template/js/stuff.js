function putlink(page_id,project_id) {
	
	ajax = new sack();
	
    var form = document.getElementById('bform');
    var sel  = document.getElementById('anchor_'+page_id);
    var str_num = sel.selectedIndex;

    ajax.setVar("string_number",str_num);
    ajax.setVar("page_id",page_id);
    ajax.setVar("project_id",project_id);
    
    ajax.requestFile = "index.php?mod=projects&task=putlink&no_html=1";
    
    ajax.method = 'POST';
    ajax.element = 'status_box_'+page_id;
    
    ajax.runAJAX();
}

function dellink(link_id,page_id,statusbox) {

	ajax = new sack();
	
	var conf = confirm("Точно удалить?");
	if (!conf) return false;
	
    ajax.setVar("link_id",link_id);
    ajax.setVar("page_id",page_id);
    
    ajax.requestFile = "index.php?mod=sites&task=dellink&no_html=1";
    
    ajax.method = 'POST';
    ajax.element = statusbox;
    
    ajax.runAJAX();
}

function quicksavelink(link_id,page_id,inp_name,statusbox) {

	ajax = new sack();
	
    var form = document.getElementById('linksform');
    ajax.setVar("link_id",link_id);
    ajax.setVar("page_id",page_id);
    ajax.setVar("new_html",form[inp_name].value);
    
    contr_len = form[inp_name].value;
    if (contr_len.length > 255) {
    
    	alert("Максимальная длина HTML-кода составляет 255 символов. Их должно хватить с головой. Надо постараться.");
    	return;
    }
    
    ajax.requestFile = "index.php?mod=sites&task=newlink_html&no_html=1";
    
    ajax.method = 'POST';
    ajax.element = statusbox;
    
    ajax.runAJAX();
}

function checkcharset() {

	ajax = new sack();
	
    var sel = document.getElementById('sitesel');
	var url = sel.value;

    ajax.setVar("url",url);
    
    ajax.requestFile = "index.php?mod=builder&task=testcharset&no_html=1";
    
    ajax.method = 'POST';
    ajax.element = 'charsetbox';
    
    ajax.runAJAX();
}

function setlink_activation(link_id,act_status,statusbox) {

	ajax = new sack();

    ajax.setVar("link_id",link_id);
    ajax.setVar("act_status",act_status);
    
    ajax.requestFile = "index.php?mod=sites&task=linkactivation&no_html=1";
    
    ajax.method = 'POST';
    ajax.element = statusbox;
    
    ajax.runAJAX();
}

function redirSites(url,select,filter) {

	var full = url + "&sort=" + filter + "&cat_id=" + select.options[select.selectedIndex].value;
	document.location.href = full;
}

function checkAll(arra) {

	var f = document.forms[1];
	var c = f.mainchk.checked;

	for (i=0; i < arra.length; i++) {
	
		eval("cb = f['urls["+arra[i]+"]'];");
		cb.checked = c;

	}
}

function checkllmclient() {

	ajax = new sack();
	
    var uuu = document.getElementById('client_url');
	var url = uuu.value;

    ajax.setVar("url",url);
    
    ajax.requestFile = "index.php?mod=builder&task=testllmclient&no_html=1";
    
    ajax.method = 'POST';
    ajax.element = 'llmclientbox';
    
    ajax.runAJAX();
}

function check_ftp_dir() {

	ajax = new sack();
	
    var form = document.getElementById('siteform');
    
    ajax.setVar("id",form['id'].value);
    ajax.setVar("ftp_host",form['ftp_host'].value);
    ajax.setVar("ftp_user",form['ftp_user'].value);
    ajax.setVar("ftp_password",form['ftp_password'].value);
    ajax.setVar("ftp_dir",form['ftp_dir'].value);
    
    ajax.requestFile = "index.php?mod=sites&task=check_ftp&no_html=1";
    
    ajax.method  = 'POST';
    ajax.element = 'ftp_result';
    
    ajax.runAJAX();	
}

function foldersave() {

	ajax = new sack();
	
    var form = document.getElementById('articlesconfform');
    
    ajax.setVar("folder",form['folder'].value);
    ajax.setVar("site_id",form['site_id'].value);

    ajax.requestFile = "index.php?mod=articles&task=savefolder&no_html=1";
    
    ajax.method  = 'POST';
    ajax.element = 'foldersavespan';
    
    ajax.runAJAX();	
}

function delete_art_image(art_id) {

	ajax = new sack();
	
    ajax.setVar("article_id",art_id);

    ajax.requestFile = "index.php?mod=articles&task=deleteimage&no_html=1";
    
    ajax.method  = 'POST';
    ajax.element = 'ex_image';
    
    ajax.runAJAX();	
}