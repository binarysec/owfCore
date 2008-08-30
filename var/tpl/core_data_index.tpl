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

/* PARENT */
.parent {
	font-size: 8pt;
	font-weight: bold;
/*	text-align: left;
	padding-right: 15px;
	padding-left: 10px;*/
}

/* TITLE */
.title {
	font-size: 9pt;
	font-weight: bold;
	text-align: center;
}

/* FILENAME */
.filename {
	font-size: 8pt;
	font-weight: bold;
	text-align: left;
	padding-right: 15px;
	padding-left: 10px;
}

/* SIZE */
.size {
	font-size: 8pt;
	font-weight: normal;
	text-align: left;
	padding-right: 10px;
	padding-left: 5px;
}

/* LASTMOD */
.lastmod {
	font-size: 8pt;
	font-weight: normal;
	text-align: center;
}

</style>
{/literal}

</head>
<body>
<img src="{$logo_url}"/></br>
<h1>Index of {$dir}</h1>
{if $up_dir}
<div class="parent">
<a href="{$up_dir}">Parent directory</a>
</div>
{/if}

<hr size="1">

<table>

<tr>
<td class="title">Name</td>
<td class="title">Size</td>
<td class="title">Last modification</td>
</tr>

{foreach $files as $file => $data}
<tr>

<td>
<div class="filename">
<a href="{$data[0]}">
{$file}
</a>
</div>
</td>
<td>

<div class="size">{$data[1]}</div>
</td>

<td>
<div class="lastmod">{$data[3]}</div>
</td>

</tr>
{/foreach}

</table>

<hr size="1">
<h3><a href="http://www.binarysec.com/">http://www.binarysec.com/</a></h3>


</body>
</html>