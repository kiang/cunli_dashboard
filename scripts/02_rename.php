<?php
$path = dirname(__DIR__);
$fh = fopen(__DIR__ . '/villages.csv', 'r');
fgetcsv($fh, 2048);
$ref = array();
while($line = fgetcsv($fh, 2048)) {
  $line[1] = substr($line[1], 0, 7) . substr($line[1], 8);
  $ref[$line[1]] = $line[2];
}

foreach(glob($path . '/cunli/*.json') AS $jsonFile) {
  $json = json_decode(file_get_contents($jsonFile), true);
  foreach($json['features'] AS $k => $f) {
    if(isset($ref[$f['properties']['VILLAGE_ID']]) && ($f['properties']['V_Name'] !== $ref[$f['properties']['VILLAGE_ID']])) {
      $json['features'][$k]['properties']['V_Name'] = $ref[$f['properties']['VILLAGE_ID']];
    }
  }
  file_put_contents($jsonFile, json_encode($json));
}
