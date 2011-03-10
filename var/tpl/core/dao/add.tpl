%{js '/data/js/jquery.js'}%
%{js '/data/js/jquery-ui.js'}%

<script>
	$(function() {
		$('#%{$id}%_dialog').dialog({
			modal: true,
			width: 450,
			autoOpen: false,
			resizable: false
		});
		$( "button, input:submit, a", ".core_dao_button_add" ).button({ 
			icons: {
				primary:'ui-icon-plus'
			}
		});
		$( "a", ".core_dao_button_add" ).click(function() { 
			$('#%{$id}%_dialog').dialog({
				buttons: {
					%{@ 'Ajouter'}%: function() {
						$('#%{$id}%_form').submit();
					},
					%{@ 'Annuler'}%: function() {
						$(this).dialog( "close" );
					}
				}
			});
					
			$('#%{$id}%_dialog').html('<p>%{@ 'Data loading'}%</p>');
			$('#%{$id}%_dialog').dialog('open');
			$.ajax({
				url: '%{link "/"}%dao/form/add/%{$dao_name}%/%{$dao_id}%',
				success: function(data) {
					$('#%{$id}%_dialog').html('<p>' + data + '</p>');
					
					$('#%{$id}%_form').submit(function(event) {
						event.preventDefault(); 

						var jqxhr = $.getJSON(
							'%{link "/"}%dao/form/postadd/%{$dao_name}%/%{$dao_id}%',
							$('#%{$id}%_form').serializeArray(),
							function(data) {
								
								if(data == true) {
									$('#%{$id}%_dialog').dialog('close');
									location.reload();
									return;
								}
								else {
									$('#%{$id}%_dialog').html('<p>');
									$.each(data, function(key, val) {
										$('#%{$id}%_dialog').append(val + '<br>');
							
									});
									$('#%{$id}%_dialog').append('</p>');
									
									$('#%{$id}%_dialog').dialog({
										buttons: {
											%{@ 'Recommencer'}%: function() {
												$( "a", ".core_dao_button_add" ).click();
											},
											%{@ 'Annuler'}%: function() {
												$(this).dialog( "close" );
											}
										}
									});
								}
							}
						);
					});
				}
			});
			return false;
		});
	});
</script>

<span class="core_dao_button_add">
<a href="">%{$button_name}%</a>
<div id="%{$id}%_dialog" title="%{$title}%">
</div>
</span>