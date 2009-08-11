
<script type="text/javascript">
	
	var data_responder = '{$form_responder}';
	
	{literal}
	
	function dataset_set_order(col, order) {
		{/literal}
		{foreach $cols as $id => $col}
		{if $col['orderable']}
			document.getElementById('form_{$name}_order_{$id}').value = '';
		{/if}
		{/foreach}
		{literal}
		document.getElementById('form_{/literal}{$name}{literal}_order_' + col).value = order;
		
		if(data_responder != '')
			f = document.getElementById(data_responder);
		else
			f = document.getElementById('form_{/literal}{$name}{literal}');
		f.method = 'GET';
		f.action = '';	
		f.submit();
	}
	
	function dataset_set_page(nb) {
		document.getElementById('form_{/literal}{$name}{literal}_page').value = nb;
		if(data_responder != '')
			f = document.getElementById(data_responder);
		else
			f = document.getElementById('form_{/literal}{$name}{literal}');
		f.method = 'GET';
		f.action = '';	
		f.submit();
	}
	
	function dataset_set_rows_per_page(nb) {
		document.getElementById('form_{/literal}{$name}{literal}_rows_per_page').value = nb;
		if(data_responder != '')
			f = document.getElementById(data_responder);
		else
			f = document.getElementById('form_{/literal}{$name}{literal}');
		f.method = 'GET';
		f.action = '';	
		f.submit();
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
		
		
		{foreach $args as $k => $v}
		<input type="hidden" name="{$k}" value="{$v}" />
		{/foreach}
		
	</form>
</div>

<div class="dataset_header dataset_header_color">
<table width="100%">
<tr>
<td>
	R&eacute;sultats
	{$rows_per_page * ($page_nb - 1) + 1} &agrave; {$rows_per_page * ($page_nb - 1) + count($rows)}
	sur {$total_num_rows}
</td>

{if $rows_per_page}
<td align="center">
<div class="dataset_pager">
	{set nb_pages, ceil($total_num_rows / $rows_per_page)}
	{if $page_nb > 1}
		<a
			href="javascript: void(0);"
			onclick="javascript: dataset_set_page('{$page_nb - 1}');"
			><img src="{link '/data/icons/16x16/agt_back.png'}" title="Page pr&ecaute;c&eacute;dente" alt="Page pr&eacute;c&eacute;dente" /></a>
	{/if}

	{for $i = 1; $i <= $nb_pages; $i++}
		{if $page_nb != $i}
		<a href="javascript: void(0);" onclick="javascript: dataset_set_page('{$i}');">{$i}</a>
		{else}
		[{$i}]
		{/if}
	{/for}
	{if $page_nb < $nb_pages}
		<a
			href="javascript: void(0);"
			onclick="javascript: dataset_set_page('{$page_nb + 1}');"
			><img src="{link '/data/icons/16x16/agt_forward.png'}" title="Page suivante" alt="Page suivante" /></a>
	{/if}
</div>
</td>
{/if}

<td align="right">
	R&eacute;sultats par page : 
	
	<select onchange="javascript: dataset_set_rows_per_page(this.value);">
		<option value="0"{if $rows_per_page == 25} selected="selected"{/if}>25 r&eacute;sultat</option>
		<option value="1"{if $rows_per_page == 50} selected="selected"{/if}>50 r&eacute;sultats</option>
		<option value="2"{if $rows_per_page == 100} selected="selected"{/if}>100 r&eacute;sultats</option>
	</select>
</td>
</tr>
</table>
</div>

<div class="dataset_data">
	<table class="dataset_data_table">
		<thead class="dataset_data_head">
			<tr>
				{foreach $cols as $id => $col}
				<th>
					{if $col['orderable']}<a href="javascript: void(0);" onclick="javascript: dataset_set_order('{$id}', '{if $form_order[$id] == 'A'}D{elseif !$form_order[$id]}A{else}{/if}');">{/if}{$col['name']}{if $col['orderable']}</a>{/if}
					{if $form_order[$id]}
					{if $form_order[$id] == 'D'}<img src="{link '/data/yui/build/assets/skins/sam/dt-arrow-dn.png'}" alt="[DESC]" title="Tri d&eacute;croissant" />
					{else}<img src="{link '/data/yui/build/assets/skins/sam/dt-arrow-up.png'}" alt="[ASC]" title="Tri croissant" />
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

<div class="dataset_footer dataset_footer_color">
<table width="100%">
<tr>
<td>
	R&eacute;sultats
	{$rows_per_page * ($page_nb - 1) + 1} &agrave; {$rows_per_page * ($page_nb - 1) + count($rows)}
	sur {$total_num_rows}
</td>

{if $rows_per_page}
<td align="center">
<div class="dataset_pager">
	{set nb_pages, ceil($total_num_rows / $rows_per_page)}
	{if $page_nb > 1}
		<a
			href="javascript: void(0);"
			onclick="javascript: dataset_set_page('{$page_nb - 1}');"
			><img src="{link '/data/icons/16x16/agt_back.png'}" title="Page pr&ecaute;c&eacute;dente" alt="Page pr&eacute;c&eacute;dente" /></a>
	{/if}

	{for $i = 1; $i <= $nb_pages; $i++}
		{if $page_nb != $i}
		<a href="javascript: void(0);" onclick="javascript: dataset_set_page('{$i}');">{$i}</a>
		{else}
		[{$i}]
		{/if}
	{/for}
	{if $page_nb < $nb_pages}
		<a
			href="javascript: void(0);"
			onclick="javascript: dataset_set_page('{$page_nb + 1}');"
			><img src="{link '/data/icons/16x16/agt_forward.png'}" title="Page suivante" alt="Page suivante" /></a>
	{/if}
</div>
</td>
{/if}

<td align="right">
	R&eacute;sultats par page : 
	
	<select onchange="javascript: dataset_set_rows_per_page(this.value);">
		<option value="0"{if $rows_per_page == 25} selected="selected"{/if}>25 r&eacute;sultat</option>
		<option value="1"{if $rows_per_page == 50} selected="selected"{/if}>50 r&eacute;sultats</option>
		<option value="2"{if $rows_per_page == 100} selected="selected"{/if}>100 r&eacute;sultats</option>
	</select>
</td>
</tr>
</table>

</div>
