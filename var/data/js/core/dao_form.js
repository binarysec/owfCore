/*
 * (C) Michael Vergoz
 */

(function($, undefined) {
	
	var methods = { };
	
	methods.form = function(options) {
		var gen_dialog = '#' + options.dao.name + options.dao.id + '_dialog';
		var gen_confirm = '#' + options.dao.name + options.dao.id + '_confirm';

		$(gen_confirm).dialog({
			modal: true,
			autoOpen: false,
			resizable: false,
			buttons: { 
				OK: function() {
					$(gen_dialog).dialog("close");
					$(this).dialog("close");
					location.reload();
				}
			}
		});
		
		$("button, input:submit, a", this).button({ 
			icons: options.button_icons
		});
		
		$("a", this).click(function() {
			$(gen_dialog).html(options.dao.loading);
			
			/* Prevent enter */
			$(gen_dialog).bind("keypress", function(e) {				
				if (e.keyCode == 13) {
					e.preventDefault(); 
					var form_name = options.dao.name + options.dao.id + '_form';
					$('#' + form_name).submit();
					return(false);
				}
			});
			
			var buttons = {};

			buttons[options.dao.text_send] = function() {
				var form_name = options.dao.name + options.dao.id + '_form';
				$('#' + form_name).submit();
			}
			buttons[options.dao.text_cancel] = function() {
				$(this).dialog("close");
			}
			
			$(gen_dialog).dialog({
				modal: true,
				width: 550,
				resizable: false,
				buttons: buttons
			});
			
			$.getJSON(
				options.dao.form_data,
				function(data) {
					methods.drawform('.dao_form_dialog', options, data);		
				}
			);
	
			return(false);
		});
	

	};


	methods.drawform = function(form, options, data) {
		var form_name = options.dao.name + options.dao.id + '_form';
		var form_res = '';
		
		form_res += 
			'<form id='+form_name+' action="/">' +
			'<table width="100%">'
		;
		
		$.each(data, function(key, val) {
			
			/* Input form */
			if(val.kind == 1) {
				if(typeof(val.value) == 'undefined')
					val.value = '';
				
				form_res += 
					'<tr>' +
					'<td width="50%" align="right">' +
					val.text + ' : ' +
					'<span id="' + 
					key + 
					'_sp"></span></td>' +
					'<td width="50%"><input id="' + 
					key + 
					'_in" name="' + 
					key + 
					'" type="text" value="' + 
					val.value + '"/></td>' +
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
				options.dao.post_data,
				$(this).serializeArray(),
				function(data) {
					/* Reception OK */
					if(data == true) {
						var gen_confirm = '#' + options.dao.name + options.dao.id + '_confirm';
						$(gen_confirm).dialog('open');
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
