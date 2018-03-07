<?php
include_once(__DIR__ . '/vendor/autoload.php');

$json = json_decode(file_get_contents('/home/kiang/public_html/taiwan_basecode/cunli/geo/20150401.json'), true);
$pool = array();
foreach($json['features'] AS $f) {
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
