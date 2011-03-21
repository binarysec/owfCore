%{js '/data/js/jquery.js'}%
%{js '/data/js/jquery-ui.js'}%
%{js '/data/js/core/dao_form.js'}%

<script>
	$(function() {
		$('#%{$id}%_add').dao('form', {
			dao: {
				name: '%{$dao_name}%',
				id: '%{$dao_id}%',
				text_send: '%{@ "Add"}%',
				text_cancel: '%{@ "Cancel"}%',
				loading: '%{@ "Loading data"}%',
				
				form_data: '%{link "/"}%dao/form/add/%{$dao_name}%/%{$dao_id}%',
				post_data: '%{link "/"}%dao/form/postadd/%{$dao_name}%/%{$dao_id}%',
			},
			
			button_icons: {
				primary:'ui-icon-plus'
			}
		});
		
	});
</script>

<span id="%{$id}%_add">
<a href="">%{$button_name}%</a>
<div id="%{$dao_name}%%{$dao_id}%_dialog" class="dao_form_dialog" title="%{$title}%">
</div>

<div id="%{$dao_name}%%{$dao_id}%_confirm" title="%{$title}%">

<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
	<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
	<strong>Hey!</strong> Sample ui-state-highlight style.</p>
</div>


</div>

</span>