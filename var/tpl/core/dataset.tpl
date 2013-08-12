
<!-- define some vars -->
%{set nb_pages, ceil($total_num_rows / $rows_per_page)}%

<!-- HEAD FILTERS FORM -->
%{if($total_num_rows_filterless > $min_rows_per_page)}%
	<form method="get" action="%{$here}%">
		
		<!-- hidden inputs -->
		%{if($search)}%
			<input type="hidden" name="%{$name}%_search" value="%{$search}%" />
		%{/if}%
		<input type="hidden" class="form_page" name="%{$name}%_page" value="%{$page_nb}%" />
		%{foreach $args as $k => $v}%
			<input type="hidden" class="dataset_opt_%{$k}%" name="%{$k|entities}%" value="%{$v|entities}%" />
		%{/foreach}%
		%{if $filters}%
			%{foreach $filters as $col => $filter}%
				%{if $filter['type'] == WF_CORE_DATASET_SELECT}%
					%{if($form_filter[$col])}%
						<input type="hidden" name="%{$name}%_filter[%{$col}%]" value="%{$form_filter[$col]|entities}%" />
					%{/if}%
				%{/if}%
			%{/foreach}%
		%{/if}%
		%{if $orders}%
			%{foreach $orders as $col => $order}%
				%{if($form_order[$col])}%
					<input type="hidden" name="%{$name}%_order[%{$col}%]" value="%{$form_order[$col]|entities}%" />
				%{/if}%
			%{/foreach}%
		%{/if}%
		
		<div data-role="footer" data-theme="a" class="ui-bar ui-corner-all">
			<div class="ui-grid-c">
				<div class="ui-block-a">
					<div data-role="fieldcontain" style="text-align: center;padding-top: 10px;font-size: 0.85em;">
						%{if $display_dataset_select_bar == 1 && $total_num_rows > $rows_per_page}%
							%{$rows_per_page * ($page_nb - 1) + 1}% %{@ '&agrave;'}% %{$rows_per_page * ($page_nb - 1) + count($rows)}%
							%{@ 'sur'}% %{$total_num_rows}%
						%{else}%
							%{@ 'Une seule page'}%
						%{/if}%
					</div>
				</div>
				
				<div class="ui-block-b" style="width: 35%;">
					<div data-role="fieldcontain" style="text-align: center;">
						%{if($total_num_rows > $rows_per_page)}%
							<div data-role="controlgroup" data-type="horizontal">
								
								%{if $nb_pages < 6}%
									<!-- Only 5 pages to display, so display them all -->
									%{for $i = 1; $i <= $nb_pages; $i++}%
										%{if $page_nb != $i}%
											<a href="" data-role="button" data-theme="a"
												onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$i}%);form.submit();return false;">%{$i}%</a>
										%{else}%
											<a href="" data-role="button" data-theme="b" onclick="return false;">%{$i}%</a>
										%{/if}%
									%{/for}%
								%{else}%
									<!-- Otherwise display pagination -->
									%{if $page_nb > 1}%
										<a href="" data-role="button" data-icon="arrow-l" data-iconpos="notext" data-theme="a"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$page_nb - 1}%);form.submit();return false;">&nbsp;</a>
									%{/if}%
									
									%{if $page_nb > 2}%
										<a href="" data-role="button" data-theme="a"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(1);form.submit();return false;">1</a>
									%{/if}%
									
									%{for $i = $page_nb - 1; $i < $page_nb + 2; $i++}%
										%{if $i == $page_nb}%
											<a href="" data-role="button" data-theme="b" onclick="return false;">%{$i}%</a>
										%{elseif $i > 0 && $i < $nb_pages}%
											<a href="" data-role="button" data-theme="a"
												onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$i}%);form.submit();return false;">%{$i}%</a>
										%{/if}%
									%{/for}%
									
									%{if $page_nb < $nb_pages}%
										<a href="" data-role="button" data-theme="a"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$nb_pages}%);form.submit();return false;">%{$nb_pages}%</a>
									%{/if}%
									
									%{if $page_nb < $nb_pages}%
										<a href="" data-role="button" data-iconpos="notext right" data-icon="arrow-r" data-theme="a" data-transition="fade"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$page_nb + 1}%);form.submit();return false;">&nbsp;</a>
									%{/if}%
								%{/if}%
							</div>
						%{/if}%
					</div>
				</div>
					
				<div class="ui-block-c">
					<div data-role="fieldcontain" style="text-align: center;">
						%{if $display_dataset_select_bar == 1}%
							<select name="%{$name}%_rows_per_page" data-native-menu="false" onchange="$(this).closest('form').submit();">
								%{foreach $range_rows_per_page as $v}%
									<option value="%{$v}%"%{if $rows_per_page == $v}% selected="selected"%{/if}%  data-mini="true">%{$v}% %{@ 'r&eacute;sultats'}%</option>
								%{/foreach}%
							</select>
						%{/if}%
					</div>
				</div>
				
				<div class="ui-block-d" style="width: 15%;">
					<div data-role="fieldcontain" style="text-align: center;">
						%{if($filters || $orders)}%
							<a href="#%{$panelid}%" data-role="button" data-icon="gear">%{@ "Filtering"}%</a>
						%{/if}%
					</div>
				</div>
			</div>
		</div>
	</form>
%{/if}%

<!-- RESULTS -->
<ul data-role="%{$data_role}%" data-inset="%{$data_inset}%" data-theme="d" data-divider-theme="d" data-mini="%{$data_mini}%">
%{if $searchi > 0 && ($total_num_rows > $rows_per_page || !empty($search))}%
	<li>
		<!-- search form -->
		<form method="get" action="%{$here}%">
			<input type="search" name="%{$name}%_search" value="%{$search}%" data-mini="true" placeholder="%{@ 'Search...'}%" />
			<input type="hidden" class="form_page" name="%{$name}%_page" value="%{$page_nb}%" />
			<input type="hidden" class="form_page" name="%{$name}%_rows_per_page" value="%{$rows_per_page}%" />
			%{if $filters}%
				%{foreach $filters as $col => $filter}%
					%{if $filter['type'] == WF_CORE_DATASET_SELECT}%
						%{if($form_filter[$col])}%
							<input type="hidden" class="form_page" name="%{$name}%_filter[%{$col}%]" value="%{$form_filter[$col]|entities}%" />
						%{/if}%
					%{/if}%
				%{/foreach}%
			%{/if}%
			%{if $orders}%
				%{foreach $orders as $col => $order}%
					%{if($form_order[$col])}%
						<input type="hidden" name="%{$name}%_order[%{$col}%]" value="%{$form_order[$col]|entities}%" />
					%{/if}%
				%{/foreach}%
			%{/if}%
			
			%{foreach $args as $k => $v}%
				<input type="hidden" class="dataset_opt_%{$k}%" name="%{$k|entities}%" value="%{$v|entities}%" />
			%{/foreach}%
		</form>
	</li>
%{/if}%

%{if $rows}%
	%{foreach $rows as $row}%
		%{$row}%
	%{/foreach}%
%{else}%
	<li data-theme="c">%{@ 'La recherche n\'a retourn&eacute; aucun r&eacute;sultat pour ces crit&egrave;res.'}%</li>
%{/if}%
</ul>


<!-- FOOT FILTERS FORM -->
%{if($total_num_rows_filterless > $min_rows_per_page)}%
	<form method="get" action="%{$here}%">
		
		<!-- hidden inputs -->
		%{if($search)}%
			<input type="hidden" name="%{$name}%_search" value="%{$search}%" />
		%{/if}%
		<input type="hidden" class="form_page" name="%{$name}%_page" value="%{$page_nb}%" />
		%{foreach $args as $k => $v}%
			<input type="hidden" class="dataset_opt_%{$k}%" name="%{$k|entities}%" value="%{$v|entities}%" />
		%{/foreach}%
		%{if $filters}%
			%{foreach $filters as $col => $filter}%
				%{if $filter['type'] == WF_CORE_DATASET_SELECT}%
					%{if($form_filter[$col])}%
						<input type="hidden" name="%{$name}%_filter[%{$col}%]" value="%{$form_filter[$col]|entities}%" />
					%{/if}%
				%{/if}%
			%{/foreach}%
		%{/if}%
		%{if $orders}%
			%{foreach $orders as $col => $order}%
				%{if($form_order[$col])}%
					<input type="hidden" name="%{$name}%_order[%{$col}%]" value="%{$form_order[$col]|entities}%" />
				%{/if}%
			%{/foreach}%
		%{/if}%
		
		<div data-role="footer" data-theme="a" class="ui-bar ui-corner-all">
			<div class="ui-grid-c">
				<div class="ui-block-a">
					<div data-role="fieldcontain" style="text-align: center;padding-top: 10px;font-size: 0.85em;">
						%{if $display_dataset_select_bar == 1 && $total_num_rows > $rows_per_page}%
							%{$rows_per_page * ($page_nb - 1) + 1}% %{@ '&agrave;'}% %{$rows_per_page * ($page_nb - 1) + count($rows)}%
							%{@ 'sur'}% %{$total_num_rows}%
						%{else}%
							%{@ 'Une seule page'}%
						%{/if}%
					</div>
				</div>
				
				<div class="ui-block-b" style="width: 35%;">
					<div data-role="fieldcontain" style="text-align: center;">
						%{if($total_num_rows > $rows_per_page)}%
							<div data-role="controlgroup" data-type="horizontal">
								%{if $nb_pages < 6}%
									<!-- Only 5 pages to display, so display them all -->
									%{for $i = 1; $i <= $nb_pages; $i++}%
										%{if $page_nb != $i}%
											<a href="" data-role="button" data-theme="a"
												onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$i}%);form.submit();return false;">%{$i}%</a>
										%{else}%
											<a href="" data-role="button" data-theme="b" onclick="return false;">%{$i}%</a>
										%{/if}%
									%{/for}%
								%{else}%
									<!-- Otherwise display pagination -->
									%{if $page_nb > 1}%
										<a href="" data-role="button" data-icon="arrow-l" data-iconpos="notext" data-theme="a"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$page_nb - 1}%);form.submit();return false;">&nbsp;</a>
									%{/if}%
									
									%{if $page_nb > 2}%
										<a href="" data-role="button" data-theme="a"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(1);form.submit();return false;">1</a>
									%{/if}%
									
									%{for $i = $page_nb - 1; $i < $page_nb + 2; $i++}%
										%{if $i == $page_nb}%
											<a href="" data-role="button" data-theme="b" onclick="return false;">%{$i}%</a>
										%{elseif $i > 0 && $i < $nb_pages}%
											<a href="" data-role="button" data-theme="a"
												onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$i}%);form.submit();return false;">%{$i}%</a>
										%{/if}%
									%{/for}%
									
									%{if $page_nb < $nb_pages}%
										<a href="" data-role="button" data-theme="a"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$nb_pages}%);form.submit();return false;">%{$nb_pages}%</a>
									%{/if}%
									
									%{if $page_nb < $nb_pages}%
										<a href="" data-role="button" data-iconpos="notext right" data-icon="arrow-r" data-theme="a" data-transition="fade"
											onclick="var form = $(this).closest('form');form.find('.form_page').val(%{$page_nb + 1}%);form.submit();return false;">&nbsp;</a>
									%{/if}%
								%{/if}%
							</div>
						%{/if}%
					</div>
				</div>
					
				<div class="ui-block-c">
					<div data-role="fieldcontain" style="text-align: center;">
						%{if $display_dataset_select_bar == 1}%
							<select name="%{$name}%_rows_per_page" data-native-menu="false" onchange="$(this).closest('form').submit();">
								%{foreach $range_rows_per_page as $v}%
									<option value="%{$v}%"%{if $rows_per_page == $v}% selected="selected"%{/if}%  data-mini="true">%{$v}% %{@ 'r&eacute;sultats'}%</option>
								%{/foreach}%
							</select>
						%{/if}%
					</div>
				</div>
				
				<div class="ui-block-d" style="width: 15%;">
					<div data-role="fieldcontain" style="text-align: center;">
						%{if($filters || $orders)}%
							<a href="#%{$panelid}%" data-role="button" data-icon="gear">%{@ "Filtering"}%</a>
						%{/if}%
					</div>
				</div>
			</div>
		</div>
	</form>
%{/if}%
