<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<link rel="stylesheet" type="text/css" href="%{link '/data/admin/css/jqm-docs.css'}%" />
<link rel="stylesheet" type="text/css" href="%{link '/data/admin/css/admin.css'}%" />
<link rel="stylesheet" type="text/css" href="%{link '/data/css/jquery.mobile.min.css'}%" />
<link rel="stylesheet" type="text/css" href="%{link '/data/css/jqm.simpledialog.css'}%" />
<script type="text/javascript" src="%{link '/data/js/jquery-1.7.js'}%"></script>
<script type="text/javascript" src="%{link '/data/js/jquery.mobile.min.js'}%"></script>
<script type="text/javascript" src="%{link '/data/js/jqm.simpledialog2.js'}%"></script>
<meta http-equiv="Content-Language" content="fr"/>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Redirection</title>
</head>

<body>
	<script type="text/javascript">
		document.location.href="%{$url}%";
	</script>

	<div data-role="content">
		<a href="%{$url|html}%" data-role="button">Cliquez ici si rien ne se passe</a>
	</div>
</body>
</html>





