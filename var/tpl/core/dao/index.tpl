<script type='text/javascript'>
	function dao_delete_confirm(href) {
		$('<div>').simpledialog2({
			mode: 'blank',
			headerText: '%{@ 'Delete confirmation'}%',
			headerClose: true,
			dialogAllow: true,
			dialogForce: false,
			width: "400px",
			height: "300px",
			blankContent :
				'<p><center style="padding: 10px;"><form action="' + href + '">' +
					'%{@ 'Are you sure about deleting this item ?'}% <br/>' +
					'<fieldset class="ui-grid-a">' +
						'<div class="ui-block-a"><input type="submit" data-role="button" value="%{@ 'Delete'}%" /></div>' +
						'<div class="ui-block-b"><a rel="close" data-role="button" href="#">%{@ 'Close'}%</a></div>' +
					'</fieldset>' +
				'</form></center></p>'
		});
		return false;
	}
	
	// GMap
	function resize_popup() {
		var w = $(window).width();
		var h = $(window).height();
		
		if(w <= 480 || h <= 320) {
			$('#map-geoloc-iframe').width(w - 10);
			$('#map-geoloc-iframe').height(h - 10);
		}
	};
	$(document).bind('pageinit', resize_popup);
	$(document).bind('orientationchange', resize_popup);
</script>

<div id="map-geoloc" data-role="popup" data-overlay-theme="a" data-theme="a" data-corners="false">
	<a href="#" data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">%{@ "Close"}%</a>
	
	<iframe id="map-geoloc-iframe" src="%{link "/dao/gmap"}%?multi=false&back=%{$back}%" height="320px" width="480px"></iframe>
	
	<div id="gmap-data" style="display: none;"></div>


	<!-- TODO: faire un if pour le GMAP-->
	<!-- TODO: voir pr plusieurs map-->
	<div class="ui-grid-a" style="margin-left: 3%; margin-top: 3%;">
		<div class="ui-block-a"><label for="longitude">Longitude :</label></div>
		<div class="ui-block-b"><label for="latitude">Latitude :</label></div>
	</div>
	<div class="ui-grid-a" style="margin-left: 2.5%;" >
		<div class="ui-block-a"><input style="width: 95%;" type="text" name="longitude" id="longitude" value=""  /></div>
		<div class="ui-block-b"><input style="width: 95%;" type="text" name="latitude" id="latitude" value=""  /></div>
	</div>
	<div class="ui-grid-solo">
		<div class="ui-block-a"><button type="v" data-theme="b">Select !</button></div>
	</div>
</div>

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
