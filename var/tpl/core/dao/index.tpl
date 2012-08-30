%{if array_key_exists("msgs", $error) && count($error["msgs"]) > 0}%
<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="f">
	<li data-role="list-divider">%{@ 'There are some problems into your form'}%</li>
	%{foreach $error["msgs"] as $v}%
	<li>%{$v}%</li>
	%{/foreach}%
</ul>
%{elseif strlen($body) > 0}%
<p>%{$body}%</p>
%{/if}%

%{$forms}%
