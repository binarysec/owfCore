<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="%{link '/data/admin/css/jqm-docs.css'}%" />
	<link rel="stylesheet" type="text/css" href="%{link '/data/admin/css/admin.css'}%" />
	<link rel="stylesheet" type="text/css" href="%{link '/data/css/jquery.mobile.min.css'}%" />
	
	<!-- JS -->
	<script type="text/javascript" src="%{link '/data/js/jquery-1.7.js'}%"></script>
	<script type="text/javascript" src="%{link '/data/js/jquery.mobile.min.js'}%"></script>
	
	<!-- Meta -->
	<meta http-equiv="Content-Language" content="fr"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	
	<!-- Others -->
	<title>%{$title}%</title>
</head>

<body>
	<div data-role="dialog" data-theme="b" class="owf-dialog"> 
		<div data-role="header">
			<h1>%{$header}%</h1>
		</div>
		
		<div data-role="content">
			<center>
				%{$message}%
			</center>
		</div>
		
		<div data-role="footer" class="owf-footer">
			<h3><a href="%{link '/'}%">%{@ 'Site root'}%</a> / <a href="%{link '/session/login'}%">%{@ 'Login'}%</a> / <a href="http://www.binarysec.com/">BinarySEC</a> / <a href="http://www.owf.re/">OpenWF</a></h3>
		</div>
	</div>
</body>

</html>