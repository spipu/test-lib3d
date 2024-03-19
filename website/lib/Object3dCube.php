<?php

declare(strict_types=1);

class Object3dCube extends Object3d
{
    public function __construct()
    {
        parent::__construct('cube');

        $this
            ->pointAdd(-10., 10.,-10.)
            ->pointAdd( 10., 10.,-10.)
            ->pointAdd( 10.,-10.,-10.)
            ->pointAdd(-10.,-10.,-10.)
            ->pointAdd(-10., 10., 10.)
            ->pointAdd( 10., 10., 10.)
            ->pointAdd( 10.,-10., 10.)
            ->pointAdd(-10.,-10., 10.)

            ->textureAdd(new Texture(dirname(__DIR__) . '/resources/image/cadrillage.gif'))

            ->faceAdd(1, 2, 3, new Color(250, 250, 250), 1, [[0, 0], [1, 0], [1, 1]])
            ->faceAdd(1, 3, 4, new Color(250, 250, 250), 1, [[0, 0], [1, 1], [0, 1]])
            ->faceAdd(2, 6, 7, new Color(250, 250, 250), 1, [[0, 0], [1, 0], [1, 1]])
            ->faceAdd(2, 7, 3, new Color(250, 250, 250), 1, [[0, 0], [1, 1], [0, 1]])
            ->faceAdd(6, 5, 8, new Color(250, 250, 250), 1, [[0, 0], [1, 0], [1, 1]])
            ->faceAdd(6, 8, 7, new Color(250, 250, 250), 1, [[0, 0], [1, 1], [0, 1]])
            ->faceAdd(5, 1, 4, new Color(250, 250, 250), 1, [[0, 0], [1, 0], [1, 1]])
            ->faceAdd(5, 4, 8, new Color(250, 250, 250), 1, [[0, 0], [1, 1], [0, 1]])
            ->faceAdd(5, 6, 2, new Color(250, 250, 250), 1, [[0, 0], [1, 0], [1, 1]])
            ->faceAdd(5, 2, 1, new Color(250, 250, 250), 1, [[0, 0], [1, 1], [0, 1]])
            ->faceAdd(4, 3, 7, new Color(250, 250, 250), 1, [[0, 0], [1, 0], [1, 1]])
            ->faceAdd(4, 7, 8, new Color(250, 250, 250), 1, [[0, 0], [1, 1], [0, 1]])
        ;
    }
}
