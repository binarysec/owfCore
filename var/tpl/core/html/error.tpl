<html>
<head>
<title>{$message}</title>

{literal}
<style>

body { 
	font-family: Tahoma, Verdana, Arial, sans-serif; 
}

h1, h2, h3 { 
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
h2 { font-size: 8pt; }
h3 { font-size: 6pt; }

a, a:hover {
	color: #a10e09; 
	text-decoration: none; 
}

</style>
{/literal}

</head>
<body bgcolor="white" text="black">
<table width="100%" height="100%">
<tr>
<td align="center" valign="middle">
<img src="{link '/data/logo.png'}"/></br>
<h1>Sorry your request could not be delivered</h1>

{if $code == 404}
<h2>Error HTTP 404 : Page not found</h2>
{else}
<h2>Unknown error {$code} : {$message}</h2>
{/if}<br/>
<h3><a href="http://www.binarysec.com/">http://www.binarysec.com/</a></h3>
</td>
</tr>
</table>
</body>
</html>