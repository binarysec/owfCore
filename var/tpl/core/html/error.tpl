
<style>
body { 
	font-family: Ubuntu, Tahoma, Verdana, Arial, sans-serif; 
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

h3 { font-size: 8pt;  }

a {
	color: #a10e09; 
	text-decoration: none; 
}
a:hover {
	text-decoration: underline; 
}

.message {
	background-color: #fbfbfb;
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px; 
	padding: 10px; 
	width: 400px;
}
.message h1 { 
	font-size: 8pt; 
}
.message h2 {
	font-size: 10pt;  
}


</style>



<body bgcolor="white" text="black">
<table width="100%" height="100%">
<tr>
<td align="center" valign="middle">
<img src="%{link '/data/logo.png'}%"/></br>

<div class="message">
<h1>%{@ 'Sorry your request could not be delivered'}%</h1>

%{if $code == 404}%
<h2><strong>%{@ '404 - Not found'}%</strong>: %{$message}%</h2>
%{elseif $code == 403}%
<h2><strong>%{@ '403 - Forbidden'}%</strong>: %{$message}%</h2>
%{else}%
<h2>%{$code}% : %{$message}%</h2>
%{/if}%
</div>

<h3><a href="%{link '/'}%">%{@ 'Site root'}%</a> / <a href="%{link '/session/login'}%">%{@ 'Login'}%</a> / <a href="http://www.binarysec.com/">BinarySEC</a> / <a href="http://www.owf.re/">OpenWF</a></h3>

</td>
</tr>
</table>

