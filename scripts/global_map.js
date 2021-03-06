// Id
var mapID = 'curatescape-global-map';

// Map Settings
var default_layer = document.getElementById(mapID).getAttribute('data-default-layer');
var center = document.getElementById(mapID).getAttribute('data-center');
var zoom = document.getElementById(mapID).getAttribute('data-zoom');
var token = document.getElementById(mapID).getAttribute('data-mapbox-token');
var satellite = document.getElementById(mapID).getAttribute('data-mapbox-satellite');
var maki = document.getElementById(mapID).getAttribute('data-maki');
var color = document.getElementById(mapID).getAttribute('data-maki-color');

// Global Map Settings
var clustering = document.getElementById(mapID).getAttribute('data-marker-clustering');

// Helpers
var HttpClient = function() {
    this.get = function(endpoint, successCallback,errorCallback) {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() { 
	        if(xhr.readyState == 4){
	            if (xhr.status == 200){
		            successCallback(xhr.responseText);
	            }else{
		            errorCallback(xhr.statusText); 
	            }		        
	        }
        }
        xhr.open( "GET", endpoint, true );            
        xhr.send( null );
    }
}

var CuratescapeStoriesAPI = function(){
	var domain = window.location.hostname == 'localhost' ? window.location.hostname +':'+ window.location.port : window.location.hostname;
	return window.location.protocol+'//'+domain+'?feed=curatescape_stories_public';
}

// Do Map
var map = L.map(mapID, {
    center: JSON.parse(center),
    zoom: Math.max(parseInt(zoom),0),
});	

map.scrollWheelZoom.disable();

var stamen_terrain = L.tileLayer('//stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}@2x.jpg', {
	attribution: '<a href="http://www.openstreetmap.org/">OpenStreetMap</a> | <a href="http://stamen.com/">Stamen Design</a>'
});

var carto_street_light = L.tileLayer('//{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}@2x.png', {
    attribution: '<a href="https://www.openstreetmap.org/">Open Street Map</a> | <a href="http://cartodb.com/attributions">CartoDB</a>',
});

var osm = L.tileLayer('//{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
});

var mapbox_sattelite_streets = L.tileLayer('https://api.mapbox.com/v4/mapbox.streets-satellite/{z}/{x}/{y}{retina}.png?access_token={accessToken}', {
	attribution: '<a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> | <a href="https://www.mapbox.com/feedback/">Mapbox</a>',
	retina: (L.Browser.retina) ? '@2x' : '',
	accessToken: token,
});	

var toplayer;	
switch(default_layer){
	case 'carto_light':
	toplayer=carto_street_light;
	break;
	case 'stamen_terrain':
	toplayer=stamen_terrain;
	break;
	case 'osm':
	toplayer=osm;
	break;		
	default:
	toplayer=carto_street_light;				
}

toplayer.addTo(map);

// Layer controls
var allLayers={
	"Terrain":stamen_terrain,
	"Street":carto_street_light,
	"Open Street Map":osm,
};
if(token){
	allLayers["Satellite"]=mapbox_sattelite_streets;
}
L.control.layers(allLayers).addTo(map);	

// Maki
if(token && maki){
	function icon(token){ 
	    return L.MakiMarkers.icon({
	    	icon: 'circle', 
			color: color, 
			size: 'm',
			accessToken: token
			});	
	}	
	var markerconfig={ icon: icon(token) };
}else{
	var markerconfig={};
}

// Center marker and popup on open
map.on('popupopen', function(e) {
	// find the pixel location on the map where the popup anchor is
    var px = map.project(e.popup._latlng); 
    // find the height of the popup container, divide by 2, subtract from the Y axis of marker location
    px.y -= e.popup._container.clientHeight/2;
    // pan to new center
    map.panTo(map.unproject(px),{animate: true}); 
});	

// Clustering
if(clustering !== '0'){
	var clusters = L.markerClusterGroup({
		spiderfyOnMaxZoom: false,
		zoomToBoundsOnClick:true,
		polygonOptions: {
			'stroke': false,
			'color': '#000',
			'fillOpacity': .1
		}
	});
}

// Do Markers
var client = new HttpClient();
client.get(CuratescapeStoriesAPI(), function(response) {
    data=JSON.parse(response);
    var markerArray = new Array();
    data.forEach(function(story){
	    if(story.location_coordinates){
		    var featured_img = story.thumb ? story.thumb : null;
		    var coords = JSON.parse(story.location_coordinates);
		    var title = '<strong>'+story.title+'</strong>';
		    var permalink = story.permalink;
			var html ='<a class="curatescape_map_thumb" href="'+permalink+'" style="background-image:url('+featured_img+')"></a>';
			html += '<a class="curatescape_map_title" href="'+permalink+'">'+title+'</a>';
			if(coords){
				var marker = new L.marker(coords,markerconfig);
				marker.on("click", function(e){
					position = marker.getLatLng();
					marker.bindPopup(html);
					e.preventDefault;
				});	
				if(clustering !== '0'){
					clusters.addLayer(marker);
					map.addLayer(clusters);
				}else{
					marker.addTo(map);
				}
				markerArray.push(marker);
			}
			var markerGroup = new L.featureGroup(markerArray);
			if(zoom < 0){ // i.e. if configured to negative one for "fit all" setting
				map.fitBounds(markerGroup.getBounds(),{
					padding:[50,50]
				});
			}
		}  
    });
}, function(error){
	console.error('Curatescape Map -- HTTP Error: '+error);
});