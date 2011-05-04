(function($, undefined) {
	
	var methods = { };
	
	methods.form = function(options) {
		var div_title =  "#" + options.dao.name + options.dao.id;
		var gen_dialog = div_title + '_dialog';
	
		$(gen_dialog).hide();
	
		$("a", div_title + " .dao_button_add").button({ 
			icons: {
				primary: options.add.icon
			}
		});
		
		$("a", div_title + " .dao_button_del").button({ 
			icons: {
				primary: options.del.icon
			}
		});

		$("a", div_title + " .dao_button_mod").button({ 
			icons: {
				primary: options.mod.icon
			}
		});
		$("a", div_title + " .dao_button_add").click(function() {
			
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
			
			url = options.dao.linker + '/add/' + options.dao.name + '/' + options.dao.id;
			
			$.getJSON(
				url,
				function(data) {
					methods.drawform(gen_dialog, options, data, -1);		
				}
			);
	
			return(false);
		});
	
		$("a", div_title + " .dao_button_del").click(function() {
			var id = $(this).attr("name");
			
			$(gen_dialog).html(options.del.text);
			
			var buttons = {};

			buttons[options.del.text_send] = function() {
			
				url = options.dao.linker + '/del/' + options.dao.name + '/' + options.dao.id;

				$.getJSON(
					url + '?id=' + id,
					function(data) {
						location.reload();
					}
				);
			
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
		
		
		$("a", div_title + " .dao_button_mod").click(function() {
			var id = $(this).attr("name");

			$(gen_dialog).html(options.mod.loading);
			
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

			buttons[options.mod.text_send] = function() {
				var form_name = options.dao.name + options.dao.id + '_form';
				$('#' + form_name).submit();
			}
			buttons[options.mod.text_cancel] = function() {
				$(this).dialog("close");
			}
			
			$(gen_dialog).dialog({
				modal: true,
				width: options.mod.width,
				resizable: false,
				buttons: buttons
			});
			
			url = options.dao.linker + '/mod/' + options.dao.name + '/' + options.dao.id;
			
			$.getJSON(
				url + '?id=' + id,
				function(data) {
					methods.drawform(gen_dialog, options, data, id);		
				}
			);
			
			return(false);
		});
	};


	methods.drawform = function(form, options, data, id) {
		var gen_dialog = '#' + options.dao.name + options.dao.id + '_dialog';
		var form_name = options.dao.name + options.dao.id + '_form';
		var form_res = '';
		
		if(options.dao.args){
			var t = options.dao.args.split(",");
			var args_tab = new Array();
			$.each(t,function(key, val) {
				var rep = val.split(":");
				args_tab[rep[0]] = rep[1];
			});
		}
		
		form_res += 
			'<form id="'+form_name+'" action="/">' +
			'<table width="100%">'
		;
		
		if(id != -1) 
			form_res += '<input type="hidden" name="id" value="'+id+'"/>';
		
		
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
			else if(val.kind == 5) {
				var select = '';
				if(typeof(val.value) == 'undefined')
					val.value = '';

				if(typeof(val.list) == 'undefined')
					alert('Warning: No list defined for select input ' + key);
				else {
					/* read list */
					select += '<select name="'+key+'">';
					$.each(val.list, function(lkey, lval) {
						
						select += '<option value="' + lkey + '">'+ lval +'</option>'
					});
					select += '</select>';
				}
				
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
			else if(val.kind == 6) {	
				insert = '<input id="' + 
					key + 
					'_in" name="' + 
					key + 
					'" type="hidden"';
				if(args_tab[key])
					insert += ' value="' + args_tab[key] + '"';
				else if(typeof(val.value) != 'undefined')
					insert += ' value="' + val.value + '"';
				
				
				if(typeof(val.size) != 'undefined')
					insert += ' size="' + val.size + '"';
				
				
					
				form_res += 
					'<tr>' +
					'<td>' +
					insert +
					'</td>' +
					'</tr>'
				;
			
			}

		});
		form_res += '</table></form>'
	
		$(form).html(form_res);
		
		$('#' + form_name).submit(function(event) {
			event.preventDefault(); 

			if(id == -1)
				url = options.dao.linker + '/postadd/' + options.dao.name + '/' + options.dao.id;
			else
				url = options.dao.linker + '/postmod/' + options.dao.name + '/' + options.dao.id;
			
			$.getJSON(
				url,
				$(this).serializeArray(),
				function(data) {
					/* Reception OK */
					if(data == true)
						location.reload();
					/* errors detected */
					else {
						/* forward errors */
						$.each(data, function(key, val) {
							spid = '#' + key + '_sp';
							if(val == false)
								$(spid).hide("slow");
							else {
								$(spid).html(
									'<div width="100%" class="ui-state-error ui-corner-all" '+
									'style="padding: 0 .7em;"> ' +
									'<p><span class="ui-icon ui-icon-alert" '+
									'style="float: left; margin-right: .3em;"></span> ' +
									val +'</p>' +
									'</div>'
								);
								$(spid).show();
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
