<script type="text/javascript">

	var data_responder = '%{$form_responder}%';
	
	function form_submit() {
		if(data_responder != '')
			f = document.getElementById(data_responder);
		else
			f = document.getElementById('form_%{$name}%');
		f.method = 'GET';
		f.action = '';	
		f.submit();
// 		$.mobile.changePage( "", {
// 			allowSamePageTransition: true,
// 			type: "get",
// 			data: $("form#form_%{$name}%").serialize()
// 		});

	}
	
	function dataset_set_order(col, order) {
		%{foreach $cols as $id => $col}%
		%{if $col['orderable']}%
			document.getElementById('form_%{$name}%_order_%{$id}%').value = '';
		%{/if}%
		%{/foreach}%
		document.getElementById('form_%{$name}%_order_' + col).value = order;
		form_submit();
	}
	
	function dataset_set_page(nb) {
		$('#form_%{$name}%_page').val(nb);
		console.log($('#form_%{$name}%_page'));
		form_submit();
	}
	
	function dataset_set_rows_per_page(nb) {
		document.getElementById('form_%{$name}%_rows_per_page').value = nb;
		form_submit();
	}
	
	function dataset_set_option(name, value) {
		document.getElementById('dataset_opt_' + name).value = value;
		form_submit();
	}
	
	function dataset_set_search(value) {
		document.getElementById('form_%{$name}%_search').value = value;
		form_submit();
	}	
	
</script>

<form method="get" id="form_%{$name}%" action="">
	
	<div class="dataset_filters">
		
		%{if $filters}%
		<table>
			%{foreach $filters as $col => $filter}%
			%{if $filter['type'] == WF_CORE_DATASET_SELECT}%
			<tr>
				<td class="label">
					%{$filter['label']}%&nbsp;:
				</td>
				<td class="filter">
					<select name="%{$name}%_filter[%{$col}%]" onchange="javascript: document.getElementById('form_%{$name}%').submit();">
						<option value=""></option>
						%{foreach $filter['options'] as $key => $value}%
						<option value="%{$key}%"%{if $form_filter[$col] == $key}% selected="selected"%{/if}%>%{$value}%</option>
						%{/foreach}%
					</select>
				</td>
			</tr>
			%{/if}%
			%{/foreach}%
		</table>
		%{/if}%
		
		<input type="hidden" id="form_%{$name}%_page" name="%{$name}%_page" value="%{$page_nb}%" />
		<input type="hidden" id="form_%{$name}%_rows_per_page" name="%{$name}%_rows_per_page" value="%{$rows_per_page}%" />
		%{foreach $cols as $id => $col}%
			%{if $col['orderable']}%
			<input type="hidden" id="form_%{$name}%_order_%{$id}%" name="%{$name}%_order[%{$id}%]" value="%{$form_order[$id]}%" />
			%{/if}%
		%{/foreach}%
		
		%{foreach $args as $k => $v}%
		<input type="hidden" id="dataset_opt_%{$k}%" name="%{$k}%" value="%{$v}%" />
		%{/foreach}%
		
	</div>
	
	%{set nb_pages, ceil($total_num_rows / $rows_per_page)}%
	
	%{if($total_num_rows_filterless > $min_rows_per_page)}%
	
	<div data-role="footer" data-theme="a" class="ui-bar ui-corner-all">
		<table>
			<tr>
				%{if $display_dataset_select_bar == 1 && count($rows) > 0}%
				<td width="30%">
					%{$rows_per_page * ($page_nb - 1) + 1}% %{@ '&agrave;'}% %{$rows_per_page * ($page_nb - 1) + count($rows)}%
					%{@ 'sur'}% %{$total_num_rows}%
				</td>
				%{/if}%
				
				<td width="60%">
					<div data-role="controlgroup" data-type="horizontal">
						
						%{if $page_nb > 1}%
							<a href="javascript: dataset_set_page('%{$page_nb - 1}%');" data-role="button" data-icon="arrow-l" data-theme="a">&nbsp;</a>
						%{/if}%
						
						%{if $nb_pages<5}%
							%{for $i = 1; $i <= $nb_pages; $i++}%
								%{if $page_nb != $i}%
									<a href="javascript: dataset_set_page('%{$i}%');" data-role="button" data-theme="a">%{$i}%</a>
								%{else}%
									<a href="javascript: void(0);" data-role="button" data-theme="b">%{$i}%</a>
								%{/if}%
							%{/for}%
						%{else}%
							%{if $page_nb<4}%
								%{for $i = 1; $i <= $page_nb; $i++}%
									%{if $page_nb != $i}%
										<a href="javascript: dataset_set_page('%{$i}%');" data-role="button" data-theme="a">%{$i}%</a>
									%{else}%
										<a href="javascript: void(0);" data-role="button" data-theme="b">%{$i}%</a>
									%{/if}%
								%{/for}%
							%{else}%
								<a href="javascript: dataset_set_page('1');" data-role="button" data-theme="a">1</a>
								<a href="javascript: dataset_set_page('%{$page_nb - 1}%');" data-role="button" data-theme="a">%{$page_nb - 1}%</a>
								<a href="javascript: dataset_set_page('%{$page_nb - 1}%');" data-role="button" data-theme="b">%{$page_nb}%</a>
							%{/if}%
							
							%{if $page_nb>$nb_pages-3}%
								%{for $i =$page_nb+1; $i <= $nb_pages; $i++}%
										<a href="javascript: dataset_set_page('%{$i}%');" data-role="button" data-theme="a">%{$i}%</a>
								%{/for}%
							%{else}%
								<a href="javascript: dataset_set_page('%{$page_nb + 1}%');" data-role="button" data-theme="a">%{$page_nb + 1}%</a>
								<a href="javascript: dataset_set_page('%{$nb_pages}%');" data-role="button" data-theme="a">%{$nb_pages}%</a> 
							%{/if}%
						%{/if}%
						
						%{if $page_nb < $nb_pages}%
							<a href="javascript: dataset_set_page('%{$page_nb + 1}%');" data-iconpos="right" data-theme="a" data-role="button" data-icon="arrow-r" data-transition="fade">&nbsp;</a>
						%{/if}%
					</div>
				</td>
				
				%{if $display_dataset_select_bar == 1}%
				<td width="10%">
					<select onchange="javascript: dataset_set_rows_per_page(this.value);" data-native-menu="false">
						%{foreach $range_rows_per_page as $v}%
							<option value="%{$v}%"%{if $rows_per_page == $v}% selected="selected"%{/if}%  data-mini="true">%{$v}% %{@ 'r&eacute;sultats'}%</option>
						%{/foreach}%
					</select>
				</td>
				%{/if}%
			</tr>
		</table>
	</div><!-- /footer -->
	%{/if}%

	<ul data-role="listview" data-inset="true" data-theme="d" data-divider-theme="d" data-mini="true">
	%{if $searchi > 0}%
		<li>
			<input type="search" name="%{$name}%_search" id="form_%{$name}%_search" value="%{$search}%" data-mini="true" placeholder="Type here to search" onchange="javascript: dataset_set_search(this.value);"/>
		</li>
	%{/if}%

	%{if $rows}%
		%{foreach $rows as $row}%
			%{$row}%
		%{/foreach}%
	%{else}%
		<li data-theme="c">
		%{@ 'La recherche n\'a retourn&eacute; aucun r&eacute;sultat pour ces crit&egrave;res.'}%
		</li>
	%{/if}%
	</ul>

</form>