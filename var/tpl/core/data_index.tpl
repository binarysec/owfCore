<h1><img src="%{link '/data/core/index/title.png'}%" alt="%{@ "Index directory"}%"/>%{@ "Index directory of"}% %{$dir}%</h1>



<table class="dataset_data_table" width="100%">
	<thead>
		<tr>
			<th class="icon"></th>
			<th class="title">%{@ 'Nom'}%</th>
			<th>%{@ 'Taille'}%</th>
			<th>%{@ 'Type'}%</th>
			<th>%{@ 'Derni&egrave;re modification'}%</th>
		</tr>
	</thead>

	<tbody>
		%{if $up_dir}%
			<tr class="alt">
				<td><img src="%{link '/data/icons/16x16/cat_open.png'}%" alt="[FILE]" /></td>
					<td class="id title"><a href="%{$up_dir}%">..</a></td>
					<td>-</td>
					<td>-</td>
					<td>-</td>
			</tr>
		%{/if}%
		%{foreach $files as $file => $data}%
			<tr class="alt">
				<td class="icon">
					<img src="%{link '/data/icons/'}%%{$data['mimetype']}%.png" alt="?" />
				</td>
				<td width="25%"><a href="%{$data['link']}%">%{$file}%</a></td>
				<td>%{$data['size']}%</td>
				<td>%{$data['mimetype']}%</td>
				<td>%{date 'd/m/Y H:i', $data['lastmod']}%</td>
			</tr>
		%{/foreach}%
	</tbody>
	
	<tfoot>
		<tr>
			<th colspan="5">Total (%{$files|count}%)</th>
		</tr>
	</tfoot>
</table>

