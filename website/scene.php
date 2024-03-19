<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/init.php';

$objet = new Object3d('objet ASE');
$objet->loadASE(__DIR__ . '/resources/object/voiture.ASE', 0.08);

$ratio = 16./9.;
$width = 1920;
$viewPort = 32;

$image = new Engine3d();
$image->setScreen($width, (int) ((float) $width / $ratio));
$image->setBackground(new Color(20., 20., 20.));
$image->drawInit();
$image->zBufferInit(1, 150);
$image->setOpening(45.0);
$image->setView(-$viewPort, $viewPort, -(int) ((float) $viewPort / $ratio), (int) ((float) $viewPort / $ratio));
$image->lightAmbient(new Color(50., 50., 50.));
$image->matrixIdentity();
$image->lightAdd(new Vector(  0., 0., 0.), new Color(255., 255., 255.), null);
$image->lightAdd(new Vector(-40., 0., 0.), new Color(250.,   0.,   0.), 50.);
$image->lightAdd(new Vector( 40., 0., 0.), new Color(  0.,   0., 250.), 50.);
$image->matrixTranslate(3., -4., 40.);
$image->matrixRotateX(120.);
$image->matrixRotateZ(40.);
$image->drawObject($objet);
$image->matrixRotateZ(40.);
$image->drawFinish(true, 100);
