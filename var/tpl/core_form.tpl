<form id="{$id}" name="{$name}" class="{$class}" action="{$action}" method="{$method}">
	<table style="width: 250px; border: 1px solid;">
		<tr>
			<th colspan="2">FORMULAIRE</th>
		</tr>
		{foreach $elements as $id => $element}
		<tr>
			<td><label for="{$id}">{$element->label}</label></td>
			<td>{$element->render()}</td>
		</tr>
		{/foreach}
	</table>
</form>
