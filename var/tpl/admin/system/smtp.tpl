<div class="content-secondary">
	<div id="jqm-homeheader">
		<h1 id="jqm-logo"><img src="%{link '/data/admin/images/title_smtp.png'}%" alt="%{@ 'OWF SMTP'}%" /></h1>
		<p>%{@ 'Configuration des relais SMTP'}%</p>
	</div>

	<p class="intro">%{@ 'Pour envoyer des mails, <strong>OWF</strong> utilise des serveurs de relais SMTP externes. C\'est ici que vous pourrez les configurer.'}%</p>
	
	<a href="%{$dao_link_add|html}%" data-role="button" data-transition="slidedown">%{@ 'Add SMTP relay'}%</a>
</div>

<div class="content-primary">
	%{$dataset}%
</div>


