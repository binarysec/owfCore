<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>{$html_title}</title>
{$html_meta}
{$css}
{$javascript}
</head>

<body{$body_attribs}>

{$html_body}

{if $html_managed_body}
{$html_managed_body}
{/if}
</body>
</html>
