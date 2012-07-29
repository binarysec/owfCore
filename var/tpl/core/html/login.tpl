<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<link rel="stylesheet" type="text/css" href="%{link '/data/admin/css/jqm-docs.css'}%" />
<link rel="stylesheet" type="text/css" href="%{link '/data/css/jquery.mobile.min.css'}%" />
<script type="text/javascript" src="%{link '/data/js/jquery-1.7.js'}%"></script>
<script type="text/javascript" src="%{link '/data/js/jquery.mobile.min.js'}%"></script>
<meta http-equiv="Content-Language" content="fr"/>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Please logon</title>
</head>

<body>

<div data-role="page"> 

	<div data-role="content" width="100px">
		<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="f">
			<li data-role="list-divider">%{$message}%</li>
		</ul>
		
		<form action="%{link '/session/login'}%" method="post" class="ui-body ui-body-a ui-corner-all">
			%{if $back_url }%
			<input type="hidden" name="back_url" value="%{$back_url}%" />
			%{/if}%
			
			<fieldset>
				<table width="100%">
					<tr>
						<td><h3>%{@ 'Username'}% :</h3></td>
						<td><input type="text" name="user" /></td>
					</tr>
					<tr>
						<td><h3>%{@ 'Password'}% :</h3></td>
						<td><input type="password" name="pass" /></td>
					</tr>
				</table>
				
				<button type="submit" data-theme="b" name="submit" value="submit-value">Submit</button>
			</fieldset>
		</form>
	</div>

	<div data-role="footer" data-theme="c">
		<p><center>
		%{if isset($via_addr)}%
		%{@ 'Connected from'}% %{$remote_ip}% (%{$remote_addr}%) via %{$via_addr}% (%{$via_addr}%)
		%{else}%
		%{@ 'Connected from'}% %{$remote_ip}% (%{$remote_addr}%)
		%{/if}%
		</center></p>
	</div>
	
</div>
</body>
</html>




