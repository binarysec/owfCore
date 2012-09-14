<html>
	
<head>
	<style>
		html { height: 100%; }
		body { margin: 0; padding: 0; height: 100%; }
		#map-canvas { height: 100%; }
	</style>
	
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>
	<script>
		var map_loaded = false;
		
		function on_load() {
			if(typeof window.google == 'undefined') {
				setTimeout(on_load, 2000);
				console.debug(window.google);
			}
			else {
				var gmap_data = parent.document.getElementById('owf-dao-map-data');
				
				if(typeof gmap_data != "undefined")
					geomap_init(eval(gmap_data.innerHTML));
			}
		}
		
		function geomap_init(data) {
			if(!map_loaded) {
				map_loaded = true;
				
				var j = 0;
				var marker  = Array();
				var mlatlng = Array();
				var att_pos = data;
				for(var i = 0 ; i < att_pos.length ; i++) {
					if(att_pos[i]) {
						if(att_pos[i]["latitude"] && att_pos[i]["longitude"]) {
							mlatlng[j] = new google.maps.LatLng(att_pos[i]["latitude"], att_pos[i]["longitude"]);
							marker[j]  = new google.maps.Marker({
								position: mlatlng[j],
								title:    att_pos[i]["ip"],
								id:       att_pos[i]["id"]
							});
							
							google.maps.event.addListener(marker[j] , 'click', function() {
								map.setCenter(this.position);
								map.setZoom(12);
							});
							j++;
						}
					}
				}
				
				if(j == 0) {
					mlatlng[0] = new google.maps.LatLng(30, 10);
				}
				var myOptions  = {
					zoom:      1,
					center:    mlatlng[0],
					mapTypeId: google.maps.MapTypeId.HYBRID
				};
				
				var map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
				for(var k = 0 ; k < marker.length ; k++) {
					marker[k].setMap(map);
				}
				if(j == 1) {
					map.setZoom(5);
				}
				if(j > 1) {
					var latlngbounds = new google.maps.LatLngBounds();
					for(var i = 0 ; i < mlatlng.length ; i++)
					{
					  latlngbounds.extend(mlatlng[i]);
					}
					map.fitBounds(latlngbounds);
				}
			}
		}
		
		window.onload = on_load;
	</script>
</head>
<body>
	<div id="map-canvas"></div>
</body>
</html>
