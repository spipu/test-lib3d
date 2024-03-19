<?php

declare(strict_types=1);

class Object3dPlan extends Object3d
{
    public function __construct()
    {
        parent::__construct('plan');

        $size = 100;
        $step = 10;

        for ($y = -$size; $y <= $size; $y += $step) {
            for ($x = -$size; $x <= $size; $x += $step) {
                $this->pointAdd((float) $x, (float) $y, 0.);
            }
        }

        $v = (2 * $size / $step) + 1;
        for ($l = 0; $l < $v - 1; $l++) {
            for ($k = 0; $k < $v - 1; $k++) {
                $i0 = ($k + 0) + ($l + 0) * ($v) + 1;
                $i1 = ($k + 1) + ($l + 0) * ($v) + 1;
                $i2 = ($k + 1) + ($l + 1) * ($v) + 1;
                $i3 = ($k + 0) + ($l + 1) * ($v) + 1;
                $this->faceAdd($i0, $i2, $i1, new Color(250., 250., 250.));
                $this->faceAdd($i0, $i3, $i2, new Color(250., 250., 250.));
            }
        }
    }
}
