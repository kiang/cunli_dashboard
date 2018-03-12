window.app = {};
var sidebar = new ol.control.Sidebar({ element: 'sidebar', position: 'right' });

var projection = ol.proj.get('EPSG:3857');
var projectionExtent = projection.getExtent();
var size = ol.extent.getWidth(projectionExtent) / 256;
var resolutions = new Array(20);
var matrixIds = new Array(20);
var clickedCoordinate, populationLayer, gPopulation;
for (var z = 0; z < 20; ++z) {
    // generate resolutions and matrixIds arrays for this WMTS
    resolutions[z] = size / Math.pow(2, z);
    matrixIds[z] = z;
}
var container = document.getElementById('popup');
var content = document.getElementById('popup-content');
var closer = document.getElementById('popup-closer');
var popup = new ol.Overlay({
  element: container,
  autoPan: true,
  autoPanAnimation: {
    duration: 250
  }
});
var info = {};
var layerPool = {};
var layerYellow = new ol.style.Style({
  stroke: new ol.style.Stroke({
      color: 'rgba(0,0,0,1)',
      width: 1
  }),
  fill: new ol.style.Fill({
      color: 'rgba(255,255,0,0.3)'
  }),
  text: new ol.style.Text({
    font: 'bold 16px "Open Sans", "Arial Unicode MS", "sans-serif"',
    placement: 'point',
    fill: new ol.style.Fill({
      color: 'blue'
    })
  })
});
var layerBlue = new ol.style.Style({
  stroke: new ol.style.Stroke({
      color: 'rgba(0,0,0,1)',
      width: 1
  }),
  fill: new ol.style.Fill({
      color: 'rgba(0,0,255,0.3)'
  }),
  text: new ol.style.Text({
    font: 'bold 16px "Open Sans", "Arial Unicode MS", "sans-serif"',
    placement: 'point',
    fill: new ol.style.Fill({
      color: 'rgba(255,255,0,1)'
    })
  })
});
var layerBlank = new ol.style.Style({
  stroke: new ol.style.Stroke({
      color: 'rgba(0,0,0,1)',
      width: 1
  })
});

closer.onclick = function() {
  popup.setPosition(undefined);
  closer.blur();
  return false;
};

var appView = new ol.View({
  center: ol.proj.fromLonLat([120.301507, 23.124694]),
  zoom: 10
});

var cityLayer = new ol.layer.Vector({
    source: new ol.source.Vector({
        url: 'city.topo.json',
        format: new ol.format.TopoJSON()
    }),
    style: function(f) {
      var fStyle = layerYellow.clone();
      fStyle.getText().setText(f.get('T_Name'));
      return fStyle;
    },
});

var map = new ol.Map({
  layers: [new ol.layer.Tile({source: new ol.source.OSM()}), cityLayer],
  overlays: [popup],
  target: 'map',
  view: appView
});
map.addControl(sidebar);

var geolocation = new ol.Geolocation({
  projection: appView.getProjection()
});

geolocation.setTracking(true);

geolocation.on('error', function(error) {
  console.log(error.message);
});

var positionFeature = new ol.Feature();

positionFeature.setStyle(new ol.style.Style({
  image: new ol.style.Circle({
    radius: 6,
    fill: new ol.style.Fill({
      color: '#3399CC'
    }),
    stroke: new ol.style.Stroke({
      color: '#fff',
      width: 2
    })
  })
}));

geolocation.on('change:position', function() {
  var coordinates = geolocation.getPosition();
  positionFeature.setGeometry(coordinates ?
          new ol.geom.Point(coordinates) : null);
      });

      new ol.layer.Vector({
        map: map,
        source: new ol.source.Vector({
          features: [positionFeature]
        })
      });

var lastCity = false;
var cunliLayer = false;
var currentTownId = '';
var smallMap = false;
var smallMapView = new ol.View({
  center: ol.proj.fromLonLat([120.301507, 23.124694]),
  zoom: 10
});
var smallMapLayer = false;
var dataPool = {};
map.on('singleclick', function(evt) {
  clickedCoordinate = evt.coordinate;
  map.forEachFeatureAtPixel(evt.pixel, function (feature, layer) {
      var p = feature.getProperties();
      if(currentTownId !== p.TOWN_ID) {
        if(false !== lastCity) {
          var fStyle = layerYellow.clone();
          fStyle.getText().setText(lastCity.get('T_Name'));
          lastCity.setStyle(fStyle);
        }
        if(false !== cunliLayer) {
          map.removeLayer(cunliLayer);
        }
        if(!layerPool[p.TOWN_ID]) {
          layerPool[p.TOWN_ID] = new ol.layer.Vector({
              source: new ol.source.Vector({
                  url: 'cunli/' + p.TOWN_ID + '.json',
                  format: new ol.format.GeoJSON()
              }),
              style: function(f) {
                var fStyle = layerBlue.clone();
                fStyle.getText().setText(f.get('V_Name'));
                return fStyle;
              }
          });
        }
        cunliLayer = layerPool[p.TOWN_ID];
        map.addLayer(cunliLayer);
        map.getView().fit(feature.getGeometry());
        feature.setStyle(layerBlank);
        lastCity = feature;
        sidebar.close();
        currentTownId = p.TOWN_ID;
      } else if(p.VILLAGE_ID) {
        if(!dataPool[p.VILLAGE_ID]) {
          $.getJSON('data/' + p.VILLAGE_ID + '.json', function(d) {
            dataPool[p.VILLAGE_ID] = d;
            showCunliCharts(dataPool[p.VILLAGE_ID]);
          })
        } else {
          showCunliCharts(dataPool[p.VILLAGE_ID]);
        }
        var cunliTitle = p.C_Name + p.T_Name + p.V_Name;
        $('#boardTitle').html(cunliTitle);
        $('#sidebar-title').html(cunliTitle);
        if(!smallMap) {
          smallMap = new ol.Map({
            controls: [],
            interactions: [],
            target: 'smallMap',
            view: smallMapView
          });
        }
        if(false !== smallMapLayer) {
          smallMap.removeLayer(smallMapLayer);
        }
        smallMapLayer = new ol.layer.Vector({
          source: new ol.source.Vector(),
          style: layerBlue
        });
        smallMapLayer.getSource().addFeature(feature);
        sidebar.open('home');
        map.getView().fit(feature.getGeometry());
        smallMap.addLayer(smallMapLayer);
        smallMap.getView().fit(feature.getGeometry());
        smallMap.updateSize();
      }
  });
});

window.chartColors = {
	red: 'rgb(255, 99, 132)',
	orange: 'rgb(255, 159, 64)',
	yellow: 'rgb(255, 205, 86)',
	green: 'rgb(75, 192, 192)',
	blue: 'rgb(54, 162, 235)',
	purple: 'rgb(153, 102, 255)',
	grey: 'rgb(201, 203, 207)'
};

var showCunliCharts = function(d) {
  var chartConfig = {
    type: 'horizontalBar',
    data: {
      labels: [],
      datasets: []
    },
    options: {
      tooltips: {
        mode: 'index',
      },
      scales: {
        xAxes: [{
          stacked: true,
          scaleLabel: {
            display: true,
            labelString: '人數'
          }
        }],
        yAxes: [{
          stacked: true
        }]
      }
    }
  };

  var dataset1 = {
    label: '未滿15歲',
    backgroundColor: window.chartColors.red,
    data: []
  };
  var dataset2 = {
    label: '15-64歲',
    backgroundColor: window.chartColors.green,
    data: []
  };
  var dataset3 = {
    label: '年滿65歲',
    backgroundColor: window.chartColors.purple,
    data: []
  };
  for(k in d.population) {
    chartConfig.data.labels.push(k);
    dataset1.data.push(d.population[k].under15);
    dataset2.data.push(d.population[k].be1564);
    dataset3.data.push(d.population[k].up65);
  }
  chartConfig.data.datasets.push(dataset1);
  chartConfig.data.datasets.push(dataset2);
  chartConfig.data.datasets.push(dataset3);
  var ctx = document.getElementById('chartPopulation').getContext('2d');
  window.myLine = new Chart(ctx, chartConfig);
}
