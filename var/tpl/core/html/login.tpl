<!DOCTYPE html> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<link rel="stylesheet" type="text/css" href="%{link '/data/admin/css/jqm-docs.css'}%" />
<link rel="stylesheet" type="text/css" href="%{link '/data/css/jquery.mobile.min.css'}%" />
<script type="text/javascript" src="%{link '/data/js/jquery-1.7.js'}%"></script>
<script type="text/javascript" src="%{link '/data/js/jquery.mobile.min.js'}%"></script>
<meta http-equiv="Content-Language" content="fr"/>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>%{@ 'Please login'}%</title>
<style>
	.ui-dialog .ui-header .ui-btn-icon-notext { display:none;} 
	.ui-footer { font-size: 12px; }
	.owf-links { font-size: 12px; text-align: center; }
	.owf-login-field { text-align: right; padding-right: 5%; }
</style>

</head>

<body>

<div data-role="dialog" data-theme="b"> 
	<div data-role="header">
		<h1>%{@ 'Please login'}%</h1>
	</div>
	<div data-role="content">
		<center>
			<p><strong>%{$message}%</strong></p>
		</center>
		
		<form action="%{link '/session/login'}%" method="post">
			%{if $back_url }%
			<input type="hidden" name="back_url" value="%{$back_url}%" />
			%{/if}%
			
			<div class="owf-login-field" data-role="fieldcontain">
				<label for="user">%{@ 'Username :'}%</label>
				<input type="text" name="user" placeholder="%{@ 'Username'}%" data-mini="true" />
			</div>
			
			<div class="owf-login-field" data-role="fieldcontain">
				<label for="pass">%{@ 'Your password :'}%</label>
				<input type="password" name="pass" placeholder="%{@ 'Password'}%" data-mini="true" />
			</div>
			
			<button type="submit" name="submit" value="submit-value">%{@ 'Login'}%</button>
			<p class="owf-links">
			%{if $allow_account_creation}%
				<a href="%{link '/session/create'}%?back=%{$here_url}%">Create an account</a>
			%{/if}%
			
			%{if $allow_pass_recovering}%
				%{if $allow_account_creation}%-%{/if}%
				<a href="">Did you forget your password?</a>
			%{/if}%
			</p>
			
		</form>
	</div>

	<div data-role="footer">
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