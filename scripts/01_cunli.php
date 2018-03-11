<?php
include_once(__DIR__ . '/vendor/autoload.php');

$fh = fopen(__DIR__ . '/villages.csv', 'r');
fgetcsv($fh, 2048);
$ref = array();
while($line = fgetcsv($fh, 2048)) {
  $parts = explode('-', $line[1]);
  $ref[$line[4] . $line[3] . $parts[1]] = array(
    'name' => $line[2],
    'town' => $parts[0],
    'code' => $line[1],
  );
}

$json = json_decode(file_get_contents('/home/kiang/public_html/taiwan_basecode/cunli/geo/20150401.json'), true);
$pool = array();
foreach($json['features'] AS $f) {
  $parts = explode('-', $f['properties']['VILLAGE_ID']);
  $key = $f['properties']['C_Name'] . $f['properties']['T_Name'] . $parts[1];
  if(isset($ref[$key])) {
    $f['properties']['TOWN_ID'] = $ref[$key]['town'];
    $f['properties']['VILLAGE_ID'] = $ref[$key]['code'];
    $f['properties']['V_Name'] = $ref[$key]['name'];
  }
  if(!isset($pool[$f['properties']['TOWN_ID']])) {
    $pool[$f['properties']['TOWN_ID']] = array(
      'type' => 'FeatureCollection',
      'features' => array(),
    );
  }
  $pool[$f['properties']['TOWN_ID']]['features'][] = $f;
}

$path = dirname(__DIR__) . '/cunli';
if(!file_exists($path)) {
  mkdir($path, 0777);
}
$cityFc = array(
  'type' => 'FeatureCollection',
  'features' => array(),
);
foreach($pool AS $townId => $fc) {
  file_put_contents($path . '/' . $townId . '.json', json_encode($fc));
  $city = false;
  foreach($fc['features'] AS $f) {
    $j = geoPHP::load(json_encode($f), 'json');
    if(false !== $city) {
      $city = $city->union($j);
    } else {
      $city = $j;
      $cityProperties = array(
        'COUNTY_ID' => $f['properties']['COUNTY_ID'],
        'TOWN_ID' => $f['properties']['TOWN_ID'],
        'C_Name' => $f['properties']['C_Name'],
        'T_Name' => $f['properties']['T_Name'],
        'Shape_Leng' => $f['properties']['Shape_Leng'],
        'Shape_Area' => $f['properties']['Shape_Area'],
      );
    }
  }
  $cityFeature = array(
    'type' => 'Feature',
    'properties' => $cityProperties,
    'geometry' => json_decode($city->out('json')),
  );
  $cityFc['features'][] = $cityFeature;
}

file_put_contents(dirname(__DIR__) . '/city.json', json_encode($cityFc));
exec('/usr/local/bin/mapshaper -i city.json -o format=topojson city.topo.json');
