Parfait l'édition fonctionne<br>
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
