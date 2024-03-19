<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/init.php';

$va1 = (int) ($_GET['va1'] ?? 0);
$va2 = (int) ($_GET['va2'] ?? 0);

$image = new Engine3d();
$image->basicInit();
$image->lightAdd(new Vector(0., 0., 0.), new Color(255., 255., 255.), null);
$image->matrixTranslate(0., 0., 40.);
$image->matrixRotateX($va1);
$image->matrixRotateY($va2);
$image->drawObject(new Object3dCube());
$image->drawFinish(true);
