<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">


    <link rel="stylesheet" href="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/css/ol.css" type="text/css">
    <script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/build/ol.js"></script>

    <title>Hello, world!</title>
</head>

<body>
    <div id="app">
        FROM <input v-model="from"><br>
        TO <input v-model="to"><br>
        <button @click="go">Go</button>
        <br>
        <br>
        <div id="map" style="width: 600px; height:800px"></div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

    <script>
        const {
            createApp
        } = Vue

        createApp({
            data() {
                return {
                    map: null,
                    from: '100.3296591, 5.3974707',
                    to: '100.3298096759949, 5.41457675',

                    setMode: 1,
                }
            },
            methods: {
                go() {
                    if (this.from == null || this.to == null) {
                        alert('from and to required')
                        return;
                    }
                    this.setRoute();
                },
                async initMap() {
                    this.map = new ol.Map({
                        controls: ol.control.defaults({
                            attribution: false
                        }),
                        layers: [
                            new ol.layer.Tile({
                                source: new ol.source.OSM({
                                    url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                                    attributions: [ol.source.OSM.ATTRIBUTION],
                                    maxZoom: 20
                                })
                            })
                        ],
                        target: 'map',
                        view: new ol.View({
                            center: ol.proj.fromLonLat([100.3296591, 5.3974707]), // Center of Malaysia
                            minZoom: 12,
                            maxZoom: 18,
                            zoom: 15 // Adjust the zoom level to fit the map on the screen
                        })
                    });
                    await new Promise(resolve => this.map.once('rendercomplete', resolve));

                    // Add a click event listener to the map
                    var vue = this
                    this.map.on('click', function(evt) {
                        // Get the map coordinate from the click event's pixel coordinate
                        var coordinate = ol.proj.toLonLat(evt.coordinate);
                        coordinate = coordinate.join(',')
                        console.log(coordinate)
                        vue.from = coordinate
                        vue.setRoute()
                        // if (vue.setMode == 1) {
                        //     vue.from = coordinate
                        //     vue.setMode = 2
                        // } else {
                        //     vue.to = coordinate
                        //     vue.setRoute()
                        //     vue.setMode = 1
                        // }
                    });


                },
                getLayerById(id) {
                    var layers = this.map.getLayers().getArray();
                    for (var i = 0; i < layers.length; i++) {
                        if (layers[i].get('id') === id) return layers[i];
                    }
                    return null;
                },
                convertLongLatObj(str) {
                    var rs = str.split(',')
                    return {
                        longitude: Number(rs[0]),
                        latitude: Number(rs[1]),
                    };
                },
                setRoute() {
                    var map = this.map
                    var vue = this;
                    // Define the two points as longitude/latitude pairs
                    var startPoint = this.from;
                    var endPoint = this.to;

                    // Define the routing service endpoint and API key
                    var routingUrl = 'http://localhost:8080/ors/v2/directions/driving-car';
                    var apiKey = '5b3ce3597851110001cf624845c6054beffa4c2d814663f7704df28e'; // replace with your API key

                    // Define the request parameters for the routing service
                    var params = {
                        api_key: apiKey,
                        start: startPoint,
                        end: endPoint,
                        units: 'm',
                        language: 'en',
                    };

                    // Send a GET request to the routing service with the request parameters
                    var full_url = routingUrl + '?' + new URLSearchParams(params)

                    //CLEAR LAYERS
                    var routeLayer = this.getLayerById('routeLayer');
                    if (routeLayer) map.removeLayer(routeLayer);

                    var vectorLayerFrom = this.getLayerById('vectorLayerFrom');
                    if (vectorLayerFrom) map.removeLayer(vectorLayerFrom);

                    var vectorLayerTo = this.getLayerById('vectorLayerTo');
                    if (vectorLayerTo) map.removeLayer(vectorLayerTo);

                    console.log(full_url)
                    fetch(full_url)
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(data) {
                            if (data.error) {
                                alert(data.error.message)
                                return;
                            }

                            // Parse the response to get the route coordinates
                            var routeCoords = data.features[0].geometry.coordinates;

                            // Convert the route coordinates to OpenLayers format
                            var routePoints = routeCoords.map(function(coord) {
                                return ol.proj.fromLonLat(coord);
                            });

                            // Create a new OpenLayers vector layer to display the route
                            var routeLayer = new ol.layer.Vector({
                                id: 'routeLayer',
                                source: new ol.source.Vector({
                                    features: [new ol.Feature({
                                        geometry: new ol.geom.LineString(routePoints)
                                    })]
                                }),
                                style: new ol.style.Style({
                                    stroke: new ol.style.Stroke({
                                        color: '#01641A',
                                        width: 8
                                    })
                                })
                            });

                            // Add the route layer to the map
                            map.addLayer(routeLayer);

                            var pinFrom = vue.convertLongLatObj(vue.from);
                            var pinTo = vue.convertLongLatObj(vue.to);

                            var pointFrom = ol.proj.fromLonLat([pinFrom.longitude, pinFrom.latitude])
                            var pointTo = ol.proj.fromLonLat([pinTo.longitude, pinTo.latitude])

                            var vectorLayer = new ol.layer.Vector({
                                id: 'vectorLayerFrom',
                                source: new ol.source.Vector({
                                    features: [
                                        new ol.Feature({
                                            geometry: new ol.geom.Point(pointFrom)
                                        }),
                                    ]
                                }),
                                style: new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1], // anchor point of the icon
                                        src: '/driver_icon.png', // image source
                                        scale: 0.2 // scale factor to adjust size of the icon
                                    })
                                })
                            });
                            map.addLayer(vectorLayer);

                            var vectorLayer = new ol.layer.Vector({
                                id: 'vectorLayerTo',
                                source: new ol.source.Vector({
                                    features: [
                                        new ol.Feature({
                                            geometry: new ol.geom.Point(pointTo)
                                        })
                                    ]
                                }),
                                style: new ol.style.Style({
                                    image: new ol.style.Icon({
                                        anchor: [0.5, 1], // anchor point of the icon
                                        src: 'https://icon-library.com/images/location-pin-icon-png/location-pin-icon-png-26.jpg', // image source
                                        scale: 0.15 // scale factor to adjust size of the icon
                                    })
                                })
                            });
                            map.addLayer(vectorLayer);

                            const points = [pointFrom, pointTo];

                            // create an OpenLayers geometry for the extent of the two points
                            const extent = ol.extent.boundingExtent(points);

                            // set the map view to the extent of the two points
                            map.getView().fit(extent, {
                                padding: [10, 10, 10, 10]
                            });
                        });
                }
            },
            mounted() {
                this.initMap()
                // this.setRoute()
            }
        }).mount('#app')
    </script>
</body>

</html>