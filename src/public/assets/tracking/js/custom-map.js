    function initMap() {
    // Styles a map in night mode.
    var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 13,
      center: {
        lat: -33.855414, // customer location lattitude
        lng: 151.055151  // customer location longitude
      },
      scrollwheel: false,
      scaleControl: false,
      draggable: false,
      mapTypeControl: false,
      getMap: true,
      styles: [{
          elementType: 'labels.text.fill',
          stylers: [{
            color: '#1E98BB'
          }]
        },
        {
          featureType: 'road',
          elementType: 'geometry',
          stylers: [{
            color: '#DCC5FE'
          }]
        },
        {
          featureType: 'road',
          elementType: 'geometry.stroke',
          stylers: [{
            color: '#9BD1E1'
          }]
        },
        {
          featureType: 'road',
          elementType: 'labels.text.fill',
          stylers: [{
            color: '#9BD1E1'
          }]
        },
        {
          featureType: 'road.highway',
          elementType: 'geometry',
          stylers: [{
            color: '#9BD1E1'
          }]
        },
        {
          featureType: 'road.highway',
          elementType: 'geometry.stroke',
          stylers: [{
            color: '#1E98BB'
          }]
        },
        {
          featureType: 'road.highway',
          elementType: 'labels.text.fill',
          stylers: [{
            color: '#9BD1E1'
          }]
        }
      ]
    });
    var infowindow = new google.maps.InfoWindow({
      content: document.getElementById("cont").innerHTML
    });
    var marker = new google.maps.Marker({
      position: map.getCenter(),
      icon: "images/map.png",
      map: map
    });
    infowindow.open(map, marker);
  }  