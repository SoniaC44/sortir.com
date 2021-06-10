window.addEventListener("DOMContentLoaded", function () {
    initMap();
})

function initMap(){
    mapboxgl.accessToken = 'pk.eyJ1IjoiYmFsYnV6YXJkIiwiYSI6ImNrcGR2eHBwMTFyc3MybnA3NWpkaGVjZWIifQ.68J9NxbIMvEWHFgRXma62g';
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        zoom: 9,
        minZoom: 1,
        center: [ $long, $lat ]
    });

    let marker = new mapboxgl.Marker({
        color: "#FF5555",
        draggable: true
    }).setLngLat([ $long, $lat]).addTo(map);

    function onDragEnd() {
        var lngLat = marker.getLngLat();

        document.getElementById('sortie_nouveauLieu_longitude').value=lngLat.lng;
        document.getElementById('sortie_nouveauLieu_latitude').value=lngLat.lat;
    }

    marker.on('dragend', onDragEnd);


}