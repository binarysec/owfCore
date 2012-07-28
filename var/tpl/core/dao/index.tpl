%{css '/data/css/bs-docs.css'}%
%{css '/data/css/jquery.mobile.min.css'}%

%{js '/data/js/jquery-1.7.js'}%
%{js '/data/js/bs-docs.js'}%
%{js '/data/js/jquery.mobile.min.js'}%

<script type="text/javascript">
	function draw_form(options, data, id) {
		var ret;
		var form_res = '';
		
		form_res += 
			'<form action="/" method="post" class="ui-body ui-body-a ui-corner-all" >'
		;
		
		if(id != -1) 
			form_res += '<input type="hidden" name="id" value="'+id+'"/>';
		
		var executor = '';
		
			
		$.each(data, function(key, val) {
			/* Input form */
			if(val.kind == 1 || val.kind == 3) {

				
				insert = '<input type="text"'+
					' name="' + key + '"' +
					' id="' + key + '"' +
					' ';
				
				if(val.kind == 3)
					insert += ' readonly="readonly"';
				
				if(typeof(val.value) != 'undefined')
					insert += ' value="' + val.value + '"';
				else
					insert += ' value=""';
					
				if(typeof(val.size) != 'undefined')
					insert += ' size="' + val.size + '"';
					
				insert += ' data-mini="true" />';

				form_res += 
					'<div data-role="fieldcontain">' +
					'<label for="'+key+'">' +
					val.text + ' : ' +
					'</label>' + 
					insert +
					'</div>'+"\n";
				;
			}
// 			/* Select form */
// 			else if(val.kind == 6) {
// 				var select = '';
// 				if(typeof(val.value) == 'undefined')
// 					val.value = '';
// 
// 				if(typeof(val.list) == 'undefined')
// 					alert('Warning: No list defined for select input ' + key);
// 				else {
// 					/* read list */
// 					select += '<select name="'+key+'">';
// 					$.each(val.list, function(lkey, lval) {
// 						if(val.value == lkey) 
// 							select += '<option value="' + lkey + '" selected>'+ lval +'</option>'
// 						else
// 							select += '<option value="' + lkey + '">'+ lval +'</option>'
// 					});
// 					select += '</select>';
// 				}
// 				
// 				form_res += 
// 					'<tr>' +
// 					'<td width="50%" align="right">' + 
// 					val.text + ' : ' +
// 					'<span id="' + 
// 					key + 
// 					'_sp"></span></td>' +
// 					'<td width="50%">' +
// 					select +
// 					'</td>' +
// 					'</tr>'
// 				;
// 			}
// 			else if(val.kind == 7) {
// 				insert = '<input id="' + 
// 					key + 
// 					'_in" name="' + 
// 					key + 
// 					'" type="hidden"';
// 				if(args_tab[key])
// 					insert += ' value="' + args_tab[key] + '"';
// 				else if(typeof(val.value) != 'undefined')
// 					insert += ' value="' + val.value + '"';
// 				
// 				
// 				if(typeof(val.size) != 'undefined')
// 					insert += ' size="' + val.size + '"';
// 				
// 				
// 					
// 				form_res += 
// 					'<tr>' +
// 					'<td>' +
// 					insert +
// 					'</td>' +
// 					'</tr>'
// 				;
// 			
// 			}
// 			/* radio button 8/9 */
// 			else if(val.kind == 8) {
// 				insert = '<input id="' + 
// 					key + 
// 					'_in" name="' + 
// 					key + 
// 					'" value="1" type="checkbox"';
// 				
// 				if(val.kind == 9)
// 					insert += ' readonly="readonly"';
// 				
// 				if(typeof(val.value) != 'undefined' && val.value > 0) {
// 					insert += ' checked>';
// 				}
// 				
// 				form_res += 
// 					'<tr>' +
// 					'<td width="50%" align="right">' +
// 					val.text + ' : ' +
// 					'<span id="' + 
// 					key + 
// 					'_sp"></span></td>' +
// 					'<td width="50%">' +
// 					insert +
// 					'</td>' +
// 					'</tr>'
// 				;
// 			}
// 			/* date picker */
// 			else if(val.kind == 10 || val.kind == 11) {
// 				var idn = key+'_in';
// 				
// 				executor = executor.concat('$( "#'+idn+'" ).datepicker();');
// 				if(val.kind == 11)
// 					executor = executor.concat('$( "#'+idn+'" ).datepicker("disable");');
// 				
// 				insert = '<input id="' + 
// 					idn + 
// 					'" name="' + 
// 					key + 
// 					'" value="1" type="text">';
// 				
// 				form_res += 
// 					'<tr>' +
// 					'<td width="50%" align="right">' +
// 					val.text + ' : ' +
// 					'<span id="' + 
// 					key + 
// 					'_sp"></span></td>' +
// 					'<td width="50%">' +
// 					insert +
// 					'</td>' +
// 					'</tr>'
// 				;
// 			}
// 			/* OWF_DAO_SLIDER */
// 			else if(val.kind == 12) {
// 				var idn = key+'_in';
// 				console.debug(val);
// 				executor = executor.concat(
// 					'$( "#'+idn+'" ).slider({min: ' +
// 					val.startnum + ',' +
// 					(val.step ? 'step:' + val.step + ',' : '') +
// 					'max: ' + val.endnum + '});'
// 				);
// 				
// 				insert = '<div id="' + 
// 					idn +
// 					'" name="' +
// 					key +
// 					'" value=' +
// 					val.startnum +
// 					'></div>';
// 				
// 				form_res += 
// 					'<tr>' +
// 					'<td width="50%" align="right">' +
// 					val.text + ' : ' +
// 					'<span id="' + 
// 					key + 
// 					'_sp"></span></td>' +
// 					'<td width="50%">' +
// 					insert +
// 					'</td>' +
// 					'</tr>'
// 				;
// 			}
		});
		
		form_res += '</form>';
		
// 		$('#bsf-waf-form').html(form_res);
// 		$('#bsf-waf-form').selectmenu('refresh');
// 		$('#bsf-waf-form').page();
// 		console.log(form_res);
		
	}
	


</script>


<div data-role="page"> 

	<div data-role="header" data-theme="a" data-position="fixed">
		<h1>%{$title}%</h1>
		<a href="%{$back}%" data-icon="back" data-iconpos="notext" data-direction="reverse">Back</a>
	</div>
	
	<div data-role="content" data-theme="b" data-mini="true"> 

		%{if array_key_exists("msgs", $error) && count($error["msgs"]) > 0}%
		<p>There are some problems into your form</p>
		<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="f">
			<li data-role="list-divider">%{@ 'Form errors'}%</li>
			%{foreach $error["msgs"] as $v}%
			<li>%{$v}%</li>
			%{/foreach}%
		</ul>
		%{elseif strlen($body) > 0}%
		<p>%{$body}%</p>
		%{/if}%
	
		%{$forms}%
	</div>

	<div data-role="footer" class="footer-docs" data-theme="c">
		<p>&copy; 2012 <a href="http://binarysec.com" target="_blank">BinarySEC</a> 2006-2012</p>
	</div>
</div><!-- /page -->






