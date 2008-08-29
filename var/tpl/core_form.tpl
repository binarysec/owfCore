<form{$form_attribs_string}>
	{foreach $form_hidden_elements as $id => $element}
		{$element->render()}
	{/foreach}
	<table style="width: 250px; border: 1px solid;">
		<tr>
			<th colspan="2">FORMULAIRE</th>
		</tr>
		{foreach $form_elements as $id => $element}
		<tr>
			<td><label for="{$id}">{$element->label}</label></td>
			<td>{$element->render()}</td>
		</tr>
		{/foreach}
	</table>
</form>

Linker JS : {js "/file.js"}
<br />
Linker CSS : {css "/file.css"}
<br />
Linker IMG : {img "/file.png"}
<br />
Linker PASS JS : {p_js "/file.js"}
<br />
Linker PASS CSS : {p_css "/file.css"}
<br />
Linker PASS IMG : {p_img "/file.png"}
