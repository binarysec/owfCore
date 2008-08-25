<div class="managed_body" style="background-color: #efefef; border-bottom: 1px solid; margin-bottom: 20px; padding-bottom: 15px;">
	<p style="background-color: #dddddd;">&Eacute;dition du template <strong><?php echo $t->vars['title']; ?></strong> (<em><?php echo $t->vars['path']; ?></em>)&nbsp;:</p>
	<table width="100%">
		<tr>
			<td width="60%">
				<textarea style="width: 100%; height: 200px;"><?php echo $t->vars['data']; ?></textarea>
				<input type="submit" value="Enregistrer" />
			</td>
			<td valign="top">
				<p>Variables&nbsp;:</p>
				<ul>
				<?php foreach($t->vars['vars'] as $t->vars['name'] => $t->vars['value']):?>
					<li>
						{$<?php echo $t->vars['name'];  ?>}<br />
						<em>ex: <?php echo $t->vars['value']; ?></em>
					</li>
				<?php endforeach;?>
				</ul>
			</td>
		</tr>
	</table>
</div>
