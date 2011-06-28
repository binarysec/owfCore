<html>
<head>
<title>%{@ 'Login'}%</title>

<style>
body { 
	font-family: Ubuntu, Tahoma, Verdana, Arial, sans-serif; 
}

h1, h2, h3, h4 {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px; 
	padding: 0px; 
	text-decoration: none;
	color: #000000;
	font-weight: normal;
}

h1 { font-size: 12pt; }
h2 { 
	font-size: 7pt; 
	color: #a10e09; 
	font-weight: bold;
	margin-top: 10px;
	margin-bottom: 5px;
}
h3 { font-size: 7pt; }
h4 { font-size: 7pt; }

table {
	text-align: center;
}

a, a:hover {
	color: #a10e09; 
	text-decoration: none; 
}
</style>

</head>

<body>

<table width="100%" height="100%">
<tr>
<td align="center" valign="middle">

<h1>%{@ 'Login required'}%</h1>
<h2>%{$message}%</h2>

<form action="%{link '/session/login'}%" method="post">
	%{if $back_url }%
	<input type="hidden" name="back_url" value="%{$back_url}%" />
	%{/if}%
	<table width="300px">
		<tr>
			<td><h3>%{@ 'Username'}% :</h3></td>
			<td><input type="text" name="user" /></td>
		</tr>
		<tr>
			<td><h3>%{@ 'Password'}% :</h3></td>
			<td><input type="password" name="pass" /></td>
		</tr>
		<tr align="center">
			<td colspan="2" class="submit"><input type="submit" /></td>
		</tr>
	</table>
</form>
<h4>
%{if $via_addr}%
%{@ 'from'}% %{$remote_ip}% (%{$remote_addr}%) via %{$via_addr}% (%{$via_addr}%)
%{else}%
%{@ 'from'}% %{$remote_ip}% (%{$remote_addr}%)
%{/if}%

</h4>
<h4><a href="http://www.owf.re/">Open Web Framework</a> / <a href="http://www.binarysec.com/">BinarySEC</a></h4>
</td>
</tr>
</table>

</body>

</html>
