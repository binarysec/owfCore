%{js '/data/js/jquery-1.5.js'}%
%{js '/data/js/jquery-ui-1.8.js'}%
%{css '/data/bsf/waf/screen.css'}%
%{js '/data/js/dao-form.js'}%


 
<script type="text/javascript">


</script>

<div data-role="page"> 

	<div data-role="header" data-theme="a" data-position="fixed">
		<h1>%{@ 'System SMTP'}%</h1>
		<a href="../../" data-icon="home" data-iconpos="notext" data-direction="reverse">Home</a>

		<a href="index.html" data-icon="gear" class="ui-btn-right">Options</a>
	</div>

	<div data-role="content" data-theme="b"> 
		<a href="%{$dao_link_add}%" data-role="button" data-inline="true" data-transition="slidedown">Add website</a>

		<p>
		%{$dataset}%
		</p>

	</div>

	<div data-role="footer" class="footer-docs" data-theme="c">
		<p>&copy; 2012 <a href="http://binarysec.com" target="_blank">BinarySEC</a> 2006-2012</p>
	</div>
</div><!-- /page -->