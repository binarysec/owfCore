{literal}
	<script type="text/javascript">
		
		function dataset_set_order(col, order) {
			{/literal}
			{foreach $cols as $id => $col}
			{if $col['orderable']}
				document.getElementById('form_{$name}_order_{$id}').value = '';
			{/if}
			{/foreach}
			{literal}
			document.getElementById('form_{/literal}{$name}{literal}_order_' + col).value = order;
			document.getElementById('form_{/literal}{$name}{literal}').submit();
		}
		
		function dataset_set_page(nb) {
			document.getElementById('form_{/literal}{$name}{literal}_page').value = nb;
			document.getElementById('form_{/literal}{$name}{literal}').submit();
		}
		
		function dataset_set_rows_per_page(nb) {
			document.getElementById('form_{/literal}{$name}{literal}_rows_per_page').value = nb;
			document.getElementById('form_{/literal}{$name}{literal}').submit();
		}

	</script>
{/literal}

{$scripts}

<div class="dataset_filters">
	<form method="GET" id="form_{$name}">
		{if $filters}
		<table>
			{foreach $filters as $col => $filter}
			{if $filter['type'] == WF_CORE_DATASET_SELECT}
			<tr>
				<td class="label">
					{$filter['label']}&nbsp;:
				</td>
				<td class="filter">
					<select name="{$name}_filter[{$col}]" onchange="javascript: document.getElementById('form_{$name}').submit();">
						<option value=""></option>
						{foreach $filter['options'] as $key => $value}
						<option value="{$key}"{if $form_filter[$col] == $key} selected="selected"{/if}>{$value}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			{/if}
			{/foreach}
		</table>
		{/if}
		
		<input type="hidden" id="form_{$name}_page" name="{$name}_page" value="{$page_nb}" />
		<input type="hidden" id="form_{$name}_rows_per_page" name="{$name}_rows_per_page" value="{$rows_per_page}" />
		{foreach $cols as $id => $col}
		{if $col['orderable']}
		<input type="hidden" id="form_{$name}_order_{$id}" name="{$name}_order[{$id}]" value="{$form_order[$id]}" />
		{/if}
		{/foreach}
	</form>
</div>

<div class="dataset_header">
	R&eacute;sultats
	{$rows_per_page * ($page_nb - 1) + 1} &agrave; {$rows_per_page * ($page_nb - 1) + count($rows)}
	sur {$total_num_rows}
</div>

<div class="dataset_data">
	<table class="dataset_data_table">
		<thead class="dataset_data_head">
			<tr>
				{foreach $cols as $id => $col}
				<th>
					{if $col['orderable']}<a href="javascript: void(0);" onclick="javascript: dataset_set_order('{$id}', '{if $form_order[$id] == 'A'}D{elseif !$form_order[$id]}A{else}{/if}');">{/if}{$col['name']}{if $col['orderable']}</a>{/if}
					{if $form_order[$id]}
					{if $form_order[$id] == 'D'}<img src="{link '/data/devtest/dataset/img/downarrow.png'}" alt="[DESC]" title="Tri d&eacute;croissant" />
					{else}<img src="{link '/data/devtest/dataset/img/uparrow.png'}" alt="[ASC]" title="Tri croissant" />
					{/if}
					{/if}
				</th>
				{/foreach}
			</tr>
		</thead>
		<tbody class="dataset_data_body">
			{if $rows}
			{foreach $rows as $row}
			<tr class="{alt 'alt'}">
			{foreach $row as $col => $val}
				<td>{$val}</td>
			{/foreach}
			</tr>
			{/foreach}
			{else}
			<tr class="noresult">
				<td colspan="{$cols|count}">La recherche n'a retourn&eacute; aucun r&eacute;sultat pour ces crit&egrave;res.</td>
			</tr>
			{/if}
		</tbody>
		
		
	</table>
</div>

{if $rows_per_page}
<div class="dataset_pager">
	{set nb_pages, ceil($total_num_rows / $rows_per_page)}
	{if $page_nb > 1}
		<a
			href="javascript: void(0);"
			onclick="javascript: dataset_set_page('{$page_nb - 1}');"
			><img src="{link '/data/devtest/dataset/img/leftarrow.png'}" title="Page pr&ecaute;c&eacute;dente" alt="Page pr&eacute;c&eacute;dente" /></a>
	{else}
		<img src="{link '/data/devtest/dataset/img/leftarrow_disabled.png'}" title="Page pr&ecaute;c&eacute;dente" alt="Page pr&eacute;c&eacute;dente" />
	{/if}
	-
	{for $i = 1; $i <= $nb_pages; $i++}
		{if $page_nb != $i}<a href="javascript: void(0);" onclick="javascript: dataset_set_page('{$i}');">{/if}{$i}{if $page_nb != $i}</a>{/if} -
	{/for}
	{if $page_nb < $nb_pages}
		<a
			href="javascript: void(0);"
			onclick="javascript: dataset_set_page('{$page_nb + 1}');"
			><img src="{link '/data/devtest/dataset/img/rightarrow.png'}" title="Page suivante" alt="Page suivante" /></a>
	{else}
		<img src="{link '/data/devtest/dataset/img/rightarrow_disabled.png'}" title="Page suivante" alt="Page suivante" />
	{/if}
</div>
{/if}

<div class="dataset_footer">
	R&eacute;sultats
	{$rows_per_page * ($page_nb - 1) + 1} &agrave; {$rows_per_page * ($page_nb - 1) + count($rows)}
	sur {$total_num_rows} / 
	R&eacute;sultats par page : 
	
	<select name="{$name}_rows_per_page" onchange="javascript: dataset_set_rows_per_page(this.value);">
		<option value=""{if !$rows_per_page} selected="selected"{/if}>tous les r&eacute;sultats</option>
		<option value="1"{if $rows_per_page == 1} selected="selected"{/if}>1 r&eacute;sultat</option>
		<option value="2"{if $rows_per_page == 2} selected="selected"{/if}>2 r&eacute;sultats</option>
		<option value="3"{if $rows_per_page == 3} selected="selected"{/if}>3 r&eacute;sultats</option>
	</select>
</div>
