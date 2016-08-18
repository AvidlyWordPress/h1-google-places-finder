

H1GooglePlacesFinder.map = null;
H1GooglePlacesFinder.markers = [];



var map, places, infoWindow;
var markers = [];
var autocomplete;
// var countryRestrict = {'country': 'fi'};
var MARKER_PATH = 'https://maps.gstatic.com/intl/en_us/mapfiles/marker_green';
var hostnameRegexp = new RegExp('^https?://.+?/');



function h1GoogleMapsAPIPlacesFinder() {
	H1GooglePlacesFinder.initMap();
}


/**
 * Initialize map functionality.
 *
 * - Initialize map element;
 * - Run geolocation;
 * - Initialize autocomplete;
 * - Initialize places.
 */
H1GooglePlacesFinder.initMap = function() {

	H1GooglePlacesFinder.map = new google.maps.Map( document.getElementById("h1-google-places-finder-map"), {
		zoom:              H1GooglePlacesFinder.initialLocation.zoom,
		center:            H1GooglePlacesFinder.initialLocation.center,
		mapTypeControl:    true,
		panControl:        false,
		zoomControl:       true,
		streetViewControl: false,
	});

	// H1GooglePlacesFinder.geolocation( H1GooglePlacesFinder.map );
	H1GooglePlacesFinder.geolocationGoogle( H1GooglePlacesFinder.map );

	H1GooglePlacesFinder.infoWindow = new google.maps.InfoWindow({
		content: document.getElementById("info-window-content")
	});

	// Create the autocomplete object and associate it with the UI input control.
	// Restrict the search to a country, and place type "regions".
	H1GooglePlacesFinder.autocomplete = new google.maps.places.Autocomplete(
		/** @type {!HTMLInputElement} */ (
		document.getElementById("h1-google-places-finder-autocomplete") ), {
			types:                 H1GooglePlacesFinder.autocompleteTypes,
			componentRestrictions: H1GooglePlacesFinder.countryRestrict
		}
	);

	H1GooglePlacesFinder.places = new google.maps.places.PlacesService( H1GooglePlacesFinder.map );

	H1GooglePlacesFinder.autocomplete.addListener( "place_changed", H1GooglePlacesFinder.onPlaceChanged );

};


/**
 * When the user selects a city, get the place details for the city and zoom the map in on the city.
 */
H1GooglePlacesFinder.onPlaceChanged = function() {

	var place = H1GooglePlacesFinder.autocomplete.getPlace();

	if ( place.geometry ) {
		H1GooglePlacesFinder.map.panTo( place.geometry.location );
		H1GooglePlacesFinder.map.setZoom(15);
		H1GooglePlacesFinder.search();
	} else {
		//document.getElementById("h1-google-places-finder-autocomplete").placeholder = "";
	}

};


/**
 * Search for features.
 */
H1GooglePlacesFinder.search = function() {

	// Remove all the markers from the map.
	H1GooglePlacesFinder.clearMarkers();

	var search = {
		// bounds: H1GooglePlacesFinder.map.getBounds(),
		location: H1GooglePlacesFinder.map.getCenter(),
		type: H1GooglePlacesFinder.placesType,
		language: H1GooglePlacesFinder.language,
		radius: H1GooglePlacesFinder.radius,
		rankby: 'distance',
	};

	H1GooglePlacesFinder.places.nearbySearch( search, function( results, status, pagination ) {

		if ( status === google.maps.places.PlacesServiceStatus.OK ) {

			// Create a marker for each hotel found, and
			// assign a letter of the alphabetic to each marker icon.
			for ( var i = 0; i < results.length; i++ ) {

				// var markerLetter = String.fromCharCode( "A".charCodeAt(0) + i );
				var markerIcon = null;

				if ( H1GooglePlacesFinder.markerIcon ) {

					markerIcon = new google.maps.MarkerImage(
						H1GooglePlacesFinder.markerIcon,
						null, null, null,
						new google.maps.Size( 24,37 )
					);

				}

				// Use marker animation to drop the icons incrementally on the map.
				H1GooglePlacesFinder.markers[ i ] = new google.maps.Marker({
					position:  results[ i ].geometry.location,
					animation: google.maps.Animation.DROP,
					icon:      markerIcon
				});

				// If the user clicks a hotel marker, show the details of that hotel
				// in an info window.
				H1GooglePlacesFinder.markers[ i ].placeResult = results[ i ];

				google.maps.event.addListener( H1GooglePlacesFinder.markers[ i ], "click", H1GooglePlacesFinder.showInfoWindow );

				setTimeout( H1GooglePlacesFinder.dropMarker( i ), i * 100 );

			}

		}

		// Get more results if there are any.
		if ( pagination.hasNextPage ) {
			pagination.nextPage();
		}

	});

}


/**
 * Clear all the map markers.
 */
H1GooglePlacesFinder.clearMarkers = function() {

	for ( var i = 0; i < H1GooglePlacesFinder.markers.length; i++ ) {

		if ( H1GooglePlacesFinder.markers[ i ] ) {
			H1GooglePlacesFinder.markers[ i ].setMap(null);
		}

	}

	markers = [];

}


/**
 *
 */
H1GooglePlacesFinder.dropMarker = function( i ) {
	return function() {
		H1GooglePlacesFinder.markers[ i ].setMap( H1GooglePlacesFinder.map );
	};
}


// Get the place details for a hotel. Show the information in an info window,
// anchored on the marker for the hotel that the user selected.
H1GooglePlacesFinder.showInfoWindow = function() {

	var marker = this;

	H1GooglePlacesFinder.places.getDetails( { placeId: marker.placeResult.place_id },
		function( place, status ) {

			if ( status !== google.maps.places.PlacesServiceStatus.OK ) {
				return;
			}

			H1GooglePlacesFinder.infoWindow.open( H1GooglePlacesFinder.map, marker );

			H1GooglePlacesFinder.buildIWContent( place );

		}
	);
}


/**
 * Load the place information into the HTML elements used by the info window.
 */
H1GooglePlacesFinder.buildIWContent = function( place ) {

	// document.getElementById("iw-icon").innerHTML = '<img class="hotelIcon" ' + 'src="' + place.icon + '"/>';
	document.getElementById("info-url").innerHTML  = '<b><a href="' + place.url + '">' + place.name + '</a></b>';
	document.getElementById("info-address").textContent = place.vicinity;

	if ( place.formatted_phone_number ) {
		document.getElementById("info-window-container-phone").style.display = '';
		document.getElementById("info-phone").textContent = place.formatted_phone_number;
	} else {
		document.getElementById("info-window-container-phone").style.display = 'none';
	}

	// Assign a five-star rating to the hotel, using a black star ('&#10029;')
	// to indicate the rating the hotel has earned, and a white star ('&#10025;')
	// for the rating points not achieved.
	if ( place.rating ) {
		var ratingHtml = '';

		for ( var i = 0; i < 5; i++ ) {

			if ( place.rating < ( i + 0.5 ) ) {
				ratingHtml += '&#10025;';
			} else {
				ratingHtml += '&#10029;';
			}

			document.getElementById("info-window-container-rating").style.display = '';
			document.getElementById("info-rating").innerHTML = ratingHtml;
		}
	} else {
		document.getElementById("info-window-container-rating").style.display = 'none';
	}

	// The regexp isolates the first part of the URL (domain plus subdomain)
	// to give a short URL for displaying in the info window.
	if ( place.website ) {

		var fullUrl = place.website;
		var website = hostnameRegexp.exec( place.website );

		if ( website === null ) {
			website = 'http://' + place.website + '/';
			fullUrl = website;
		}

		document.getElementById("info-window-container-website").style.display = '';
		// document.getElementById("info-website").textContent = website;
		document.getElementById("info-website").innerHTML = '<a href="' + website + '">' + website + '</a>';
	} else {
		document.getElementById("info-window-container-website").style.display = 'none';
	}
}



/**
 * Get the user location using the Google Geocoding API.
 */
H1GooglePlacesFinder.geolocationGoogle = function() {

	jQuery.post( "https://www.googleapis.com/geolocation/v1/geolocate?key=AIzaSyDRGOMGmIrWuIYNRA-3vSJ6z23vFnbHK28",
		function( result ) {

			var position = {
				coords: {
					latitude:  result.location.lat,
					longitude: result.location.lng,
				}
			};

			H1GooglePlacesFinder.geolocationSuccess( position );

		}).fail( function( error ) {
			H1GooglePlacesFinder.geolocationW3C();
		}
	);

}


/**
 * Get the user location using the W3C Geolocation API.
 */
H1GooglePlacesFinder.geolocationW3C = function() {

	// W3C Geolocation
	if ( navigator.geolocation ) {

		navigator.geolocation.getCurrentPosition(
			function( position ) {
				H1GooglePlacesFinder.geolocationSuccess( position );
			}, function() {
				return false;
			}
		);

	}

}


/**
 * Set the right location to the map and perform a search.
 */
H1GooglePlacesFinder.geolocationSuccess = function( position ) {

	// Set the map to the current location.
	var positionCoords = new google.maps.LatLng( position.coords.latitude, position.coords.longitude );

	H1GooglePlacesFinder.map.setCenter( positionCoords );
	H1GooglePlacesFinder.map.setZoom( 16 );

	// Search places for the current location.
	H1GooglePlacesFinder.search();

}
