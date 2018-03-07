<?php
$path = dirname(__DIR__);
$fh = fopen(__DIR__ . '/villages.csv', 'r');
fgetcsv($fh, 2048);
$ref = array();
while($line = fgetcsv($fh, 2048)) {
  $parts = explode('-', $line[1]);
  $ref[$line[4] . $line[3] . $parts[1]] = $line[2];
}

foreach(glob($path . '/cunli/*.json') AS $jsonFile) {
  $json = json_decode(file_get_contents($jsonFile), true);
  foreach($json['features'] AS $k => $f) {
    $parts = explode('-', $f['properties']['VILLAGE_ID']);
    $key = $f['properties']['C_Name'] . $f['properties']['T_Name'] . $parts[1];
    if(isset($ref[$key]) && ($f['properties']['V_Name'] !== $ref[$key])) {
      $json['features'][$k]['properties']['V_Name'] = $ref[$key];
    }
  }
  file_put_contents($jsonFile, json_encode($json));
}
