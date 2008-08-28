<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>{$html_title}</title>
{$html_meta}

{if $html_css}
{foreach $html_css as $k => $v} 
<link rel="stylesheet" type="text/css" href="{$v[1]}"/>
{/foreach}

{/if}
{if $html_js}
{foreach $html_js as $k => $v} 
<script type="text/javascript" src="{$v[1]}"></script>
{/foreach}
{/if}

</head>

<body{$body_attribs}>

{$html_body}

{if $html_managed_body}
{$html_managed_body}
{/if}
</body>
</html>
