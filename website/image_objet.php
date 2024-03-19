<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/init.php';

$va1 = (int) ($_GET['va1'] ?? 0);
$va2 = (int) ($_GET['va2'] ?? 0);
$code = (string) ($_GET['o'] ?? '');

$objects = [
    'helico'  => 4.,
    'lotus'   => 3.,
    'tank'    => 0.16,
    'tore'    => 0.2,
    'voiture' => 0.08,
];
if (!isset($objects[$code])) {
    exit;
}
$object = __DIR__ . '/resources/object/'.$code.'.ASE';
$scale = $objects[$code];

$image = new Engine3d();
$image->basicInit();
$image->lightAdd(new Vector(  0., 0., 0.), new Color(255., 255., 255.), null);
$image->lightAdd(new Vector(-40., 0., 0.), new Color(250.,   0.,   0.), 50.);
$image->lightAdd(new Vector( 40., 0., 0.), new Color(  0.,   0., 250.), 50.);
$image->matrixTranslate(0., 0., 40.);
$image->matrixRotateX($va1);
$image->matrixRotateZ($va2);
$image->drawObject((new Object3d('objet ASE'))->loadASE($object, $scale));
$image->drawFinish(true);
