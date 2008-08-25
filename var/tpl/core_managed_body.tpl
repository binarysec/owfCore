<div class="managed_body" style="background-color: #efefef; border-bottom: 1px solid; margin-bottom: 20px; padding-bottom: 15px;">
	<p style="background-color: #dddddd;">&Eacute;dition du template <strong>{$title}</strong> (<em>{$path}</em>)&nbsp;:</p>
	<table width="100%">
		<tr>
			<td width="60%">
				<textarea style="width: 100%; height: 200px;">{$data}</textarea>
				<input type="submit" value="Enregistrer" />
			</td>
			<td valign="top">
				<p>Variables&nbsp;:</p>
				<ul>
				{foreach $vars as $name => $value}
					<li>
						{literal}{{/literal}${$name}{literal}}{/literal}<br />
						<em>ex: {$value}</em>
					</li>
				{/foreach}
				</ul>
			</td>
		</tr>
	</table>
</div>
