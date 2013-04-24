<div data-role="content">
<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="f">
	<li data-role="list-divider"><h1>Il y a %{$files|count}% fichiers dans %{$dir}%</h1></li>
	%{if count($files)}%
	%{foreach $files as $file => $data}%
		<li>
			<a href="%{$data['link']}%" %{if $data['mimetype'] != 'httpd/unix-directory'}%data-ajax="false"%{/if}%>
				<img src="%{link '/data/icons/'}%%{$data['mimetype']}%.png" alt="?" class="ui-li-icon"/>
				<h1>%{$file}%</h1>
				<p class="ui-li-aside">
				Taille: %{$data['size']}%<br/>
				Type: %{$data['mimetype']}%<br/>
				Modification: %{date 'd/m/Y H:i', $data['lastmod']}%
				</p>
			</a>
		</li>
	%{/foreach}%
	%{/if}%
</ul>
</div>

