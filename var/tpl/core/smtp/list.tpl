%{js '/data/js/jquery-1.5.js'}%
%{js '/data/js/jquery-ui-1.8.js'}%
%{js '/data/js/dao-form.js'}%

<script type="text/javascript">
	$(function() {
		$('#%{$dao_name}%%{$dao_id}%').dao('form', {
			dao: {
				name: '%{$dao_name}%',
				id: '%{$dao_id}%',
				linker: '%{link "/dao/form"}%'
			},
			add: {
				width: 500,
				text_send: '%{@ "Add"}%',
				text_cancel: '%{@ "Cancel"}%',
				loading: '%{@ "Loading data"}%',
				icon: 'ui-icon-plus',
			},
			
			del: {
				width: 300,
				text_send: '%{@ "Delete"}%',
				text_cancel: '%{@ "Cancel"}%',
				text: '%{@ "Are you sure ?"}%',
				icon: 'ui-icon-close',
			},
			mod: {
				width: 500,
				text_send: '%{@ "Modify"}%',
				text_cancel: '%{@ "Cancel"}%',
				loading: '%{@ "Loading data"}%',
				icon: 'ui-icon-close',
			},
		});
	});
</script>


<h1><img src="%{link '/data/core/title_smtp.png'}%" alt="%{@ 'SMTP Servers configuration'}%" title="%{@ 'SMTP Servers configuration'}%" />%{@ 'SMTP Servers configuration'}%</h1>

<div id="%{$dao_name}%%{$dao_id}%">
%{$dao_dialog}%
%{$dao_button_add}%<br><br>

%{$dataset}%
</div>



