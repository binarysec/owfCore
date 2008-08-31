<div class="god_annonce">Editing : {$tpl_name}</div>

<div class="god_annonce_cadre">
<div class="god_annonce">Content</div>
<div class="god_annonce_cadre">

<form{$form_attribs_string}>
	{foreach $form_hidden_elements as $id => $element}
		{$element->render()}
	{/foreach}
	
	{$form_elements["text"]->render()}<br/>
	{$form_elements["submit"]->render()}
</form>
</div>

<div class="god_annonce">Know values for this template</div>
<div class="god_annonce_cadre">
</div>

<div class="god_annonce">Language translation</div>
<div class="god_annonce_cadre">
</div>

</div>