<form id="<?php echo $t->vars['id']; ?>" name="<?php echo $t->vars['name']; ?>" class="<?php echo $t->vars['class']; ?>" action="<?php echo $t->vars['action']; ?>" method="<?php echo $t->vars['method']; ?>">
	<table style="width: 250px; border: 1px solid;">
		<tr>
			<th colspan="2">FORMULAIRE</th>
		</tr>
		<?php foreach($t->vars['elements'] as $t->vars['id'] => $t->vars['element']):?>
		<tr>
			<td><label for="<?php echo $t->vars['id']; ?>"><?php echo $t->vars['element']->label; ?></label></td>
			<td><?php echo $t->vars['element']->render(); ?></td>
		</tr>
		<?php endforeach;?>
	</table>
</form>
