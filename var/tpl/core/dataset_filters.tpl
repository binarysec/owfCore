<form method="get" action="%{$here}%">
	%{if($search)}%
		<input type="hidden" name="%{$name}%_search" value="%{$search}%" />
	%{/if}%
	<input type="hidden" class="form_page" name="%{$name}%_page" value="%{$page_nb}%" />
	<input type="hidden" class="form_page" name="%{$name}%_rows_per_page" value="%{$rows_per_page}%" />
	
	%{foreach $args as $k => $v}%
		<input type="hidden" class="dataset_opt_%{$k}%" name="%{$k|entities}%" value="%{$v|entities}%" />
	%{/foreach}%
	
	%{if $filters}%
		<center><h4>%{@ "Filters"}%</h4></center>
		%{foreach $filters as $col => $filter}%
			%{if $filter['type'] == WF_CORE_DATASET_SELECT}%
				<div data-role="fieldcontain" style="text-align: center;">
					<fieldset data-role="controlgroup" data-mini="true">
						<label for="%{$name}%_head_filter_%{id $col}%">%{$filter['label']}%&nbsp;:</label>
						<select id="%{$name}%_head_filter_%{id $col}%" name="%{$name}%_filter[%{$col}%]" data-native-menu="false">
							%{if(is_numeric($form_filter[$col]) || trim($form_filter[$col]))}%
								<option value=" ">%{@ "Remove filter"}%</option>
							%{else}%
								<option value=" " data-placeholder="true">%{$filter['label']}%</option>
							%{/if}%
							%{foreach $filter['options'] as $key => $value}%
								<option value="%{$key}%"%{if $form_filter[$col] === strval($key)}% selected="selected"%{/if}%>%{$value}%</option>
							%{/foreach}%
						</select>
						<script>
							$(document).on('pagehide', '#%{$name}%_head_filter_%{id $col}%-dialog', function() {
								var panel = $('#owf-panel-%{$panelkey}%');
								if(panel.length > 0)
									panel.panel("open");
							});
						</script>
					</fieldset>
				</div>
			%{/if}%
		%{/foreach}%
	%{/if}%
	
	%{if $orders}%
		%{if $filters}%
		<hr/>
		%{/if}%
		<center><h4>%{@ "Ordering"}%</h4></center>
		%{foreach $orders as $id => $col}%
			<div data-role="fieldcontain" style="text-align: center;">
				<fieldset data-role="controlgroup" data-mini="true">
					<label for="owf-core-dataset-order-%{id $id}%">%{$col["name"]}%</label>
					<select id="owf-core-dataset-order-%{id $id}%" name="%{$name}%_order[%{$id}%]" data-native-menu="false">
						<option value=" " data-placeholder="true">%{$col['name']}%</option>
						%{if(isset($form_order[$id]) && trim($form_order[$id]))}%
							<option value=" ">%{@ "Remove ordering"}%</option>
						%{/if}%
						<option value="A"%{if isset($form_order[$id]) && $form_order[$id] == 'A'}% selected="selected"%{/if}%>%{@ "ASC"}%</option>
						<option value="D"%{if isset($form_order[$id]) && $form_order[$id] == 'D'}% selected="selected"%{/if}%>%{@ "DESC"}%</option>
					</select>
				</fieldset>
			</div>
		%{/foreach}%
	%{/if}%
	
	<!-- validate -->
	<hr/>
	<div data-role="fieldcontain" style="text-align: center;">
		<input type="submit" value="%{@ 'Validate'}%" data-mini="true" data-icon="check" data-theme="b" />
	</div>
</form>

<form method="get" action="%{$here}%">
	%{if($search)}%
		<input type="hidden" name="%{$name}%_search" value="%{$search}%" />
	%{/if}%
	<input type="hidden" class="form_page" name="%{$name}%_page" value="%{$page_nb}%" />
	<input type="hidden" class="form_page" name="%{$name}%_rows_per_page" value="%{$rows_per_page}%" />
	
	%{foreach $args as $k => $v}%
		<input type="hidden" class="dataset_opt_%{$k}%" name="%{$k|entities}%" value="%{$v|entities}%" />
	%{/foreach}%
	
	<!-- clear -->
	<div data-role="fieldcontain" style="text-align: center;">
		<input type="submit" value="%{@ 'Clear all'}%" data-mini="true" data-icon="delete" data-theme="f" />
	</div>
</form>
	