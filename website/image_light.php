<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/init.php';

$va1 = (int) ($_GET['va1'] ?? 0);
$va2 = (int) ($_GET['va2'] ?? 0);

$r = 50.*sin($va1*PI_180);
$va2 = $va2*PI_180;

$image = new Engine3d();
$image->basicInit();
$image->lightAdd(new Vector($r*cos($va2),          $r*sin($va2),          0.), new Color(255.,   0.,   0.), 140.);
$image->lightAdd(new Vector($r*cos($va2+2*M_PI/3), $r*sin($va2+2*M_PI/3), 0.), new Color(  0., 255.,   0.), 140.);
$image->lightAdd(new Vector($r*cos($va2-2*M_PI/3), $r*sin($va2-2*M_PI/3), 0.), new Color(  0.,   0., 255.), 140.);
$image->matrixTranslate(0., 0., 130.);
$image->drawObject(new Object3dPlan());
$image->drawFinish(true);
