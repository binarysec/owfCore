<table class="list">
	<thead>
		<tr>
			<th class="icon"></th>
			<th class="title">Nom</th>
			<th>Taille</th>
			<th>Chemin</th>
			<th>Type</th>
			<th>Derni&egrave;re modification</th>
		</tr>
	</thead>
	<tbody>
		{if $up_dir}
			<tr class="cat">
				<td class="icon"><img src="{link '/data/icons/16x16/cat_open.png'}" alt="[FILE]" /></td>
					<td class="id title"><a href="{$up_dir}">..</a></td>
					<td>-</td>
					<td>-</td>
					<td>-</td>
					<td>-</td>
			</tr>
		{/if}
		{foreach $files as $file => $data}
			<tr class="cat">
				<td class="icon">
					{if $data['mimetype'] == 'httpd/unix-directory'}
						<img src="{link '/data/icons/16x16/cat_close.png'}" alt="[DIR]" />
					{elseif $data['mimetype'] == 'image/png' || $data['mimetype'] == 'image/gif' || $data['mimetype'] == 'image/jpeg'}
						<img src="{link '/data/icons/16x16/mime_image.png'}" alt="[DIR]" />
					{else}
						<img src="{link '/data/icons/16x16/mime_unknown.png'}" alt="[FILE]" />
					{/if}
				</td>
				<td class="id title"><a href="{$data['link']}">{$file}</a></td>
				<td>{$data['size']}</td>
				<td>{$data['path']}</td>
				<td>{$data['mimetype']}</td>
				<td>{date 'd/m/Y H:i', $data['lastmod']}</td>
			</tr>
		{/foreach}
	</tbody>
	<tfoot>
		<tr>
			<th colspan="6">Total ({$files|count})</th>
		</tr>
	</tfoot>
</table>
