<html>
	<head>
		<title>Login</title>
		<style>
{literal}
#login_form {
	margin-left: 30%;
	border: 1px solid;
	width: 40%;
	background-color: #eeeeee;
}
#login_form h1 {
	text-align: center;
}
#login_form table {
	text-align: center;
}
#login_form .message {
	text-align: center;
}
{/literal}
		</style>
	</head>

	<body>

<div id="login_form">
	<h1>Login required</h1>
	<p class="message">{$message}</p>
	<form action="{$login_url}" method="post">
		{if $back_url }
		<input type="hidden" name="back_url" value="{$back_url}" />
		{/if}
		<table width="100%">
			<tr>
				<td>User (mail):</td>
				<td><input type="text" name="user" /></td>
			</tr>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="pass" /></td>
			</tr>
			<tr>
				<td colspan="2" class="submit"><input type="submit" /></td>
			</tr>
		</table>
	</form>
</div>

	</body>

</html>
