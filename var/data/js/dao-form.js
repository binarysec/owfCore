(function($, undefined) {
	
	var methods = { };
	
	methods.form = function(opts) {
		var options = opts;
		var form_name_id = options.dao.name + options.dao.id;
		
		var form_name = {
			base: form_name_id,
			add: "dao-button-add-" + form_name_id,
			modify: "dao-button-mod-" + form_name_id,
			remove: "dao-button-rm-" + form_name_id,
			dialog: "dao-dialog-" + form_name_id,
			form: "dao-form-" + form_name_id,
		};
		
		/* adding */
		if(options.add) {
			$('.'+form_name.add).button({ 
				icons: {
					primary: options.add.icon
				}
			});
			
			$('.'+form_name.add).click(function() {
				var gd = $('.'+form_name.dialog);
				
				gd.html(options.add.loading);
				
				/* Prevent enter */
				gd.bind("keypress", function(e) {
					if (e.keyCode == 13) {
						e.preventDefault(); 
						$('#' + form_name.form).submit();
						return(false);
					}
				});
				
				var buttons = {};

				buttons[options.add.text_send] = function() {
					$('#' + form_name.form).submit();
				}
				buttons[options.add.text_cancel] = function() {
					$(this).dialog("close");
				}
				
				gd.dialog({
					modal: true,
					width: options.add.width,
					resizable: false,
					buttons: buttons
				});
				
				url = options.dao.linker + '/add/' + options.dao.name + '/' + options.dao.id;
				
				$.getJSON(
					url,
					function(data) {
						methods.drawform(form_name, options, data, -1);
					}
				);
		
				return(false);
			});
			
		}
		
		/* deleting */
		if(options.del) {
			$('.'+form_name.remove).button({ 
				icons: {
					primary: options.del.icon
				}
			});
			
			$('.'+form_name.remove).click(function() {
				var agg =  $(this).attr('data-agg');
				var aggid =  $(this).attr('data-aggid');
				var id = $(this).attr('data-id');

				var form_name_id = agg + aggid;
				
				var form_name = {
					base: form_name_id,
					add: "dao-button-add-" + form_name_id,
					modify: "dao-button-mod-" + form_name_id,
					remove: "dao-button-rm-" + form_name_id,
					dialog: "dao-dialog-" + form_name_id,
					form: "dao-form-" + form_name_id,
				};
				
				var gd = $('.'+form_name.dialog);
				
				gd.html(options.del.text);
				
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
				
				gd.dialog({
					modal: true,
					width: options.del.width,
					resizable: false,
					buttons: buttons
				});

				return(false);
			});
		}

		/* modification */
		if(options.mod) {
			$('.'+form_name.modify).button({ 
				icons: {
					primary: options.mod.icon
				}
			});

			$('.'+form_name.modify).click(function() {
				var agg =  $(this).attr('data-agg');
				var aggid =  $(this).attr('data-aggid');
				var id = $(this).attr('data-id');

				var form_name_id = agg + aggid;
				
				var form_name = {
					base: form_name_id,
					add: "dao-button-add-" + form_name_id,
					modify: "dao-button-mod-" + form_name_id,
					remove: "dao-button-rm-" + form_name_id,
					dialog: "dao-dialog-" + form_name_id,
					form: "dao-form-" + form_name_id,
				};
				
				var gd = $('.'+form_name.dialog);
				
				gd.html(options.mod.loading);
				
				/* Prevent enter */
				gd.bind("keypress", function(e) {
					if (e.keyCode == 13) {
						e.preventDefault(); 
						$('#' + form_name.form).submit();
						return(false);
					}
				});
				
				var buttons = {};

				buttons[options.mod.text_send] = function() {
					$('#' + form_name.form).submit();
				}
				buttons[options.mod.text_cancel] = function() {
					$(this).dialog("close");
				}
				
				gd.dialog({
					modal: true,
					width: options.mod.width,
					resizable: false,
					buttons: buttons
				});
				
				url = options.dao.linker + '/mod/' + options.dao.name + '/' + options.dao.id;

				$.getJSON(
					url + '?id=' + id,
					function(data) {
						methods.drawform(form_name, options, data, id);
					}
				);

				return(false);
			});
		}
	
	};

	methods.drawform = function(form_name, options, data, id) {
		var gd = $('.'+form_name.dialog);
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
			'<form id="'+form_name.form+'" action="/">' +
			'<table width="100%">'
		;
// 		06 77 79 21 
		
		if(id != -1) 
			form_res += '<input type="hidden" name="id" value="'+id+'"/>';
		
		var executor = '';
		
		$.each(data, function(key, val) {
			/* Input form */
			if(val.kind == 1 || val.kind == 3) {

				insert = '<input id="' + 
					key + 
					'_in" name="' + 
					key + 
					'" type="text"';
				
				if(val.kind == 3)
					insert += ' readonly="readonly"';
				
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
			else if(val.kind == 6) {
				var select = '';
				if(typeof(val.value) == 'undefined')
					val.value = '';

				if(typeof(val.list) == 'undefined')
					alert('Warning: No list defined for select input ' + key);
				else {
					/* read list */
					select += '<select name="'+key+'">';
					$.each(val.list, function(lkey, lval) {
						if(val.value == lkey) 
							select += '<option value="' + lkey + '" selected>'+ lval +'</option>'
						else
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
			else if(val.kind == 7) {
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
			/* radio button 8/9 */
			else if(val.kind == 8) {
				insert = '<input id="' + 
					key + 
					'_in" name="' + 
					key + 
					'" value="1" type="checkbox"';
				
				if(val.kind == 9)
					insert += ' readonly="readonly"';
				
				if(typeof(val.value) != 'undefined' && val.value > 0) {
					insert += ' checked>';
				}
				
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
			/* date picker */
			else if(val.kind == 10 || val.kind == 11) {
				var idn = key+'_in';
				
				executor = executor.concat('$( "#'+idn+'" ).datepicker();');
				if(val.kind == 11)
					executor = executor.concat('$( "#'+idn+'" ).datepicker("disable");');
				
				insert = '<input id="' + 
					idn + 
					'" name="' + 
					key + 
					'" value="1" type="text">';
				
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
			/* OWF_DAO_SLIDER */
			else if(val.kind == 12) {
				var idn = key+'_in';
				console.debug(val);
				executor = executor.concat(
					'$( "#'+idn+'" ).slider({min: ' +
					val.startnum + ',' +
					(val.step ? 'step:' + val.step + ',' : '') +
					'max: ' + val.endnum + '});'
				);
				
				insert = '<div id="' + 
					idn +
					'" name="' +
					key +
					'" value=' +
					val.startnum +
					'></div>';
				
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
		});
		form_res += '</table></form>'
	
		$('.'+form_name.dialog).html(form_res);
		
		/* post handler */
		eval(executor);
		
		$('#'+form_name.form).submit(function(event) {
			event.preventDefault(); 

			if(id == -1)
				url = options.dao.linker + '/postadd/' + options.dao.name + '/' + options.dao.id;
			else
				url = options.dao.linker + '/postmod/' + options.dao.name + '/' + options.dao.id;
		
			$.post(
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
				},
				"json"
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
