<?php
require_once __DIR__.'/../vendor/autoload.php';

use Burdock\GoogleSpreadsheetsDatastore\GoogleSpreadsheetsDatastore as Datastore;

$ds;
$sheetsId = "1pWLkkFM3v6lQM2cdRvh33ICXL-LIvatlIkW_QdUgxo8";
$sheetName = "TestSheet";

$credentials = __DIR__.'/../credentials.json';
$ds = new Datastore($credentials);
$rows = $ds->getSheetValues($sheetsId, $sheetName);

var_dump($rows);