
%{js '/data/js/jquery.js'}%
%{js '/data/js/jquery-ui.js'}%
%{js '/data/js/core/dao.js'}%

<script>
	id = '#%{$id}%_dialog';
	slash = "%{link '/'}%";
	loading = '<p>%{@ 'Data loading'}%</p>';
	
%{literal}%
	$(function() {
%{/literal}%
		new core_dao_add(id, slash, 'waCMS_directory', 0, loading);
%{literal}%
	});
%{/literal}%
	

</script>

<span class="core_dao_button_add">
<a href="">%{$button_name}%</a>

<div id="%{$id}%_dialog" title="%{$title}%">
	<p>%{@ 'Data loading'}%</p>
</div>

</span>