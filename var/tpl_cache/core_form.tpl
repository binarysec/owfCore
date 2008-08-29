<?php 
function tpl_func_js($t, $file) { echo $t->wf->core_js()->linker($file); }
function tpl_func_css($t, $file) { echo $t->wf->core_css()->linker($file); }
function tpl_func_img($t, $file) { echo $t->wf->core_img()->linker($file); }
function tpl_func_p_js($t, $file) { echo $t->wf->core_js()->pass_linker($file); }
function tpl_func_p_css($t, $file) { echo $t->wf->core_css()->pass_linker($file); }
function tpl_func_p_img($t, $file) { echo $t->wf->core_img()->pass_linker($file); }

?>
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

Linker JS : <?php tpl_func_js($t, "/file.js"); ?>
<br />
Linker CSS : <?php tpl_func_css($t, "/file.css"); ?>
<br />
Linker IMG : <?php tpl_func_img($t, "/file.png"); ?>
<br />
Linker PASS JS : <?php tpl_func_p_js($t, "/file.js"); ?>
<br />
Linker PASS CSS : <?php tpl_func_p_css($t, "/file.css"); ?>
<br />
Linker PASS IMG : <?php tpl_func_p_img($t, "/file.png"); ?>
