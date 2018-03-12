<?php
$result = array();
foreach(glob('/home/kiang/public_html/tw_population/cunli/*/*.csv') AS $csvFile) {
  $fh = fopen($csvFile, 'r');
  fgetcsv($fh, 2048);
  while($line = fgetcsv($fh, 2048)) {
    if(!isset($result[$line[1]])) {
      $result[$line[1]] = array();
    }
    $result[$line[1]][$line[0]] = array(
      'households' => $line[4],
      'population' => $line[5],
      'male' => $line[6],
      'female' => $line[7],
      'under15' => $line[8],
      'be1564' => $line[9],
      'up65' => $line[10],
      'up20' => $line[11],
      'be1819' => $line[12],
    );
  }
}

$path = dirname(__DIR__) . '/data';
if(!file_exists($path)) {
  mkdir($path, 0777, true);
}

foreach($result AS $code => $data) {
  krsort($data);
  file_put_contents($path . '/' . $code . '.json', json_encode(array(
    'code' => $code,
    'population' => $data,
  )));
}
