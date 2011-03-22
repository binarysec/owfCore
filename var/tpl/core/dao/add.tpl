
<script type="text/javascript">
(function($, undefined) {
	
	var methods = { };
	
	methods.form = function(options) {
		var gen_dialog = '#' + options.dao.name + options.dao.id + '_dialog';

		$(gen_dialog).hide();
		
		$("a", ".dao_button_add").button({ 
			icons: {
				primary: options.buttons.add
			}
		});
		
		$("a", ".dao_button_del").button({ 
			icons: {
				primary: options.buttons.del
			}
		});
		
		$("a", ".dao_button_add").click(function() {
			
			$(gen_dialog).html(options.add.loading);
			
			/* Prevent enter */
			$(gen_dialog).bind("keypress", function(e) {				
				if (e.keyCode == 13) {
					e.preventDefault(); 
					var form_name = '#' + options.dao.name + options.dao.id + '_form';
					$(form_name).submit();
					return(false);
				}
			});
			
			var buttons = {};

			buttons[options.add.text_send] = function() {
				var form_name = options.dao.name + options.dao.id + '_form';
				$('#' + form_name).submit();
			}
			buttons[options.add.text_cancel] = function() {
				$(this).dialog("close");
			}
			
			$(gen_dialog).dialog({
				modal: true,
				width: options.add.width,
				resizable: false,
				buttons: buttons
			});
			
			$.getJSON(
				options.add.form_data,
				function(data) {
					methods.drawform(gen_dialog, options, data);		
				}
			);
	
			return(false);
		});
	
		
		$("a", ".dao_button_del").click(function() {

			$(gen_dialog).html(options.del.text);
			
			var buttons = {};

			buttons[options.del.text_send] = function() {
// 				var form_name = options.dao.name + options.dao.id + '_form';
// 				$('#' + form_name).submit();
			}
			buttons[options.del.text_cancel] = function() {
				$(this).dialog("close");
			}
			
			$(gen_dialog).dialog({
				modal: true,
				width: options.del.width,
				resizable: false,
				buttons: buttons
			});
			
			return(false);
		});
	};


	methods.drawform = function(form, options, data) {
		var gen_dialog = '#' + options.dao.name + options.dao.id + '_dialog';
		var form_name = options.dao.name + options.dao.id + '_form';
		var form_res = '';
		
		form_res += 
			'<form id="'+form_name+'" action="/">' +
			'<table width="100%">'
		;
		
		$.each(data, function(key, val) {
			
			/* Input form */
			if(val.kind == 1) {

				insert = '<input id="' + 
					key + 
					'_in" name="' + 
					key + 
					'" type="text"';
				
				if(typeof(val.value) != 'undefined')
					insert += ' value="' + val.value + '"';
				
				if(typeof(val.size) != 'undefined')
					insert += ' size="' + val.size + '"';
					
				form_res += 
					'<tr>' +
					'<td width="50%" align="right">' +
					val.text + ' : ' +
					'<span id="' + 
					key + 
					'_sp"></span></td>' +
					'<td width="50%">' +
					insert +
					'</td>' +
					'</tr>'
				;
			
			}
		
			/* Select form */
			else if(val.kind == 3) {
				var select = '';
				if(typeof(val.value) == 'undefined')
					val.value = '';
				
				/* read list */
				select += '<select name="'+key+'">';
				$.each(val.list, function(lkey, lval) {
					select += '<option value="' + lkey + '">'+ lval +'</option>'
				});
				select += '</select>';
				
				form_res += 
					'<tr>' +
					'<td width="50%" align="right">' + 
					val.text + ' : ' +
					'<span id="' + 
					key + 
					'_sp"></span></td>' +
					'<td width="50%">' +
					select +
					'</td>' +
					'</tr>'
				;
			}
		});
		form_res += '</table></form>'
		
		$(form).html(form_res);
		
		$('#' + form_name).submit(function(event) {
			event.preventDefault(); 

			$.getJSON(
				options.add.post_data,
				$(this).serializeArray(),
				function(data) {
					/* Reception OK */
					if(data == true) {
						var gen_confirm = '#' + options.dao.name + options.dao.id + '_confirm';
						$(gen_dialog).dialog('close');
						location.reload();
					}
					/* errors detected */
					else {
						/* forward errors */
						$.each(data, function(key, val) {
							id = '#' + key + '_sp';
							if(val == false)
								$(id).hide("slow");
							else {
								$(id).html(
									'<div width="100%" class="ui-state-error ui-corner-all" '+
									'style="padding: 0 .7em;"> ' +
									'<p><span class="ui-icon ui-icon-alert" '+
									'style="float: left; margin-right: .3em;"></span> ' +
									val +'</p>' +
									'</div>'
								);
								$(id).show();
							}
						});
					}
				}
			);	
		});	
	};
	
	$.fn.dao = function(method) {
		if (methods[method]) {
			return(methods[method].apply(this, Array.prototype.slice.call(arguments, 1)));
		} 
		else if(typeof(method) === 'object' || !method) {
			return(methods.init.apply(this, arguments));
		} 
		else {
			$.error( 
				'Method ' +  method + ' does not exist' );
		}
	};

})(jQuery);

	$(function() {
		$('#%{$id}%_add').dao('form', {
			dao: {
				name: '%{$dao_name}%',
				id: '%{$dao_id}%',
			},
			add: {
				width: 550,
				text_send: '%{@ "Add"}%',
				text_cancel: '%{@ "Cancel"}%',
				loading: '%{@ "Loading data"}%',
				
				form_data: '%{link "/"}%dao/form/add/%{$dao_name}%/%{$dao_id}%',
				post_data: '%{link "/"}%dao/form/postadd/%{$dao_name}%/%{$dao_id}%',
			},
			
			del: {
				width: 550,
				text_send: '%{@ "Delete"}%',
				text_cancel: '%{@ "Cancel"}%',
				text: '%{@ "Are you sure ?"}%',
				
				post_data: '%{link "/"}%dao/form/postadd/%{$dao_name}%/%{$dao_id}%',
			},
			
			
			buttons: {
				add:'ui-icon-plus',
				del:'ui-icon-close'
			}
		});
		
	});
	
</script>




<span class="dao_button_add">
<a href="">%{$button_name}%</a>
<div id="%{$dao_name}%%{$dao_id}%_dialog" class="dao_dialog">
</div>
</span>




