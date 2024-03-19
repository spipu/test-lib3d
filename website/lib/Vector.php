<?php

declare(strict_types=1);

class Vector
{
    public float $x;
    public float $y;
    public float $z;
    public float $t;

    public function __construct(float $x = 0., float $y = 0., float $z = 0., float $t = 1.)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->t = $t;
    }

    public function getLength(): float
    {
        return sqrt($this->x*$this->x + $this->y*$this->y + $this->z*$this->z);
    }

    public function normalize(): self
    {
        $length = $this->getLength();

        if ($length > 0.) {
            $this->x /= $length;
            $this->y /= $length;
            $this->z /= $length;
        }
        $this->t = 1.;

        return $this;
    }
}
