<form<?php echo $t->vars['form_attribs_string']; ?>>
	<?php foreach($t->vars['form_hidden_elements'] as $t->vars['id'] => $t->vars['element']):?>
		<?php echo $t->vars['element']->render(); ?>
	<?php endforeach;?>
	<table style="width: 250px; border: 1px solid;">
		<tr>
			<th colspan="2">FORMULAIRE</th>
		</tr>
		<?php foreach($t->vars['form_elements'] as $t->vars['id'] => $t->vars['element']):?>
		<tr>
			<td><label for="<?php echo $t->vars['id']; ?>"><?php echo $t->vars['element']->label; ?></label></td>
			<td><?php echo $t->vars['element']->render(); ?></td>
		</tr>
		<?php endforeach;?>
	</table>
</form>
