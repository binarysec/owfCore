<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>{$html_title}</title>
{if $html_css}
{foreach $html_css as $v}
<link rel="stylesheet" type="text/css" href="{$v}" />
{/foreach}
{/if}
{if $html_js}
{foreach $html_js as $v}
<script type="text/javascript" src="{$v}"></script>
{/foreach}
{/if}
{$html_meta}
</head>

<body{$html_body_attribs}>

{$html_body}

{if $html_managed_body}
{$html_managed_body}
{/if}
</body>
</html>
