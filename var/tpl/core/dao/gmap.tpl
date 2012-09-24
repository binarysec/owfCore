<html>
<head>
	<style>
		html { height: 100%; }
		body { margin: 0; padding: 0; height: 100%; }
		#map-canvas { height: 100%; }
	</style>
	
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>
	<script type="text/javascript" src="%{link '/data/js/jquery-1.7.js'}%"></script>
	<script>
		var marker;
		var map_loaded = false;
		var timeout = 4;
		
		function update_map_marker() {
			var latlng = new google.maps.LatLng(
				$("#owf-dao-map-cur-data-latitude-%{$name}%", window.parent.document).val(),
				$("#owf-dao-map-cur-data-longitude-%{$name}%", window.parent.document).val()
			);
			marker.setPosition(latlng);
		}
		
		function on_load() {
			if(typeof window.google == 'undefined') {
				timeout -= 1;
				if(timeout > 0)
					setTimeout(on_load, 1000);
				else
					document.getElementById('map-canvas').innerHTML = "An error occured while loading google map.<br/>Please check the javascript is not blocked.";
			}
			else if(!map_loaded) {
				map_loaded = true;
				
				var latlng = new google.maps.LatLng(%{$lat}%, %{$long}%);
				marker = new google.maps.Marker({
					position: latlng,
					title: "%{$text}%",
					draggable: true,
				});
				
				google.maps.event.addListener(marker , 'click', function() {
					map.setCenter(this.position);
					map.setZoom(map.getZoom() + 2);
				});
				
				google.maps.event.addListener(marker, 'dragend', function(event) {
					$("#owf-dao-map-cur-data-latitude-%{$name}%", window.parent.document).val(event.latLng.lat().toFixed(4));
					$("#owf-dao-map-cur-data-longitude-%{$name}%", window.parent.document).val(event.latLng.lng().toFixed(4));
					parent.owf_dao_update_map("%{$name}%");
				});
				
				var myOptions  = {
					zoom: 1,
					center: latlng,
					mapTypeId: google.maps.MapTypeId.HYBRID
				};
				
				var map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
				marker.setMap(map);
			}
		}
		
		window.onload = on_load;
	</script>
</head>
<body>
	<div id="map-canvas" style="color: white; text-align: center;"></div>
</body>
</html>
