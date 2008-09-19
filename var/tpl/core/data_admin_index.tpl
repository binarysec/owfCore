{css '/data/core/data_index.css'}
<h1>{@"Index of %s", $dir}</h1>
{if $up_dir}
<div class="parent">
<a href="{$up_dir}">{lang "Parent directory"}</a>
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
