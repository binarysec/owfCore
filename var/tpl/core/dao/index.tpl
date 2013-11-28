<!-- If there is no map, we do not need the javascript -->
%{if(count($maps) > 0)}%
<script type='text/javascript'>
	
	/* size popup */
	function resize_popup() {
		var	w = $(window).width(), h = $(window).height();
		
		if(w <= 480 || h <= 320)
			$('.owf-dao-map-iframe').width(w - 10).height(h - 10);
	};
	
	/* proc it */
	$(document).bind('pageinit', resize_popup);
	$(document).bind('orientationchange', resize_popup);
	
	/* on updates */
	function owf_dao_update_map(mapname) {
		var	mapid = "#owf-dao-map-form-data-" + mapname + "-latitude",
			curdata = "#owf-dao-map-cur-data-latitude-" + mapname;
		
		$(mapid).val($(curdata).val());
		
		mapid = "#owf-dao-map-form-data-" + mapname + "-longitude";
		curdata = "#owf-dao-map-cur-data-longitude-" + mapname;
		
		$(mapid).val($(curdata).val());
	}
	
	$(function() {
		$(".owf-dao-map-input").change(function(data) {
			var	data = $(this).attr('id').split('-'),
				mapname = data[data.length - 1];
			
			owf_dao_update_map(mapname);
			var framename = 'owf-dao-map-iframe-' + mapname;
			document.getElementById(framename).contentWindow.update_map_marker();
		});
	});
	
</script>
%{/if}%

<!-- Create popups and iframes for potential google maps -->
%{foreach $maps as $name => $map}%
<div id="owf-dao-map-popup-%{$name}%" data-role="popup" data-overlay-theme="a" data-theme="a" data-corners="false" data-tolerance="15,15">
	<a href="#" data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">%{@ "Close"}%</a>
	<iframe id="owf-dao-map-iframe-%{$name}%" class="owf-dao-map-iframe" src="%{link '/dao/gmap'}%?name=%{$name}%&text=%{$map['text']}%&lat=%{$map['latitude']}%&long=%{$map['longitude']}%" height="320px" width="480px"></iframe>
	
	<div class="ui-grid-a" style="margin-left: 3%; margin-top: 3%;">
		<div class="ui-block-a"><label for="latitude">%{@ "Latitude"}% :</label></div>
		<div class="ui-block-b"><input style="width: 95%;" type="text" id="owf-dao-map-cur-data-latitude-%{$name}%" class="owf-dao-map-input" value="%{$map['latitude']}%"  /></div>
	</div>
	<div class="ui-grid-a" style="margin-left: 2.5%;" >
		<div class="ui-block-a"><label for="longitude">%{@ "Longitude"}% :</label></div>
		<div class="ui-block-b"><input style="width: 95%;" type="text" id="owf-dao-map-cur-data-longitude-%{$name}%" class="owf-dao-map-input" value="%{$map['longitude']}%"  /></div>
	</div>
	<div class="ui-grid-solo">
		<div class="ui-block-a"><a href="#" type="v" data-role="button" data-rel="back" data-theme="b">%{@ "Valider"}%</a></div>
	</div>
</div>
%{/foreach}%

%{if array_key_exists("msgs", $error) && count($error["msgs"]) > 0}%
<ul data-role="listview" data-inset="true" data-theme="c" data-dividertheme="e" class='adapt-width-style'>
	<li data-role="list-divider">%{@ 'There are some problems into your form'}%</li>
	%{foreach $error["msgs"] as $v}%
	<li>%{$v|entities}%</li>
	%{/foreach}%
</ul>
%{elseif strlen($body) > 0}%
<p>%{$body}%</p>
%{/if}%

<script type="text/javascript">
function adaptToDevice() {
	if( ! /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
		$(".adapt-width-style").attr("style","width: 65%; margin: auto;")
	}
};
$(document).ready(adaptToDevice);
</script>

%{$forms}%
