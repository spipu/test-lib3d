<?php

declare(strict_types=1);

class Color
{
    public float $r;
    public float $g;
    public float $b;

    public function __construct(float $r, float $g, float $b)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;

        $this->applyLimit();
    }

    public function applyLimit(): void
    {
        if ($this->r < 0.) {
            $this->r = 0.;
        }
        if ($this->r > 255.) {
            $this->r = 255.;
        }
        if ($this->g < 0) {
            $this->g = 0;
        }
        if ($this->g > 255) {
            $this->g = 255;
        }
        if ($this->b < 0) {
            $this->b = 0;
        }
        if ($this->b > 255) {
            $this->b = 255;
        }
    }

    public function getImageColor(GdImage $image): int
    {
        $this->applyLimit();

        return imagecolorallocate(
            $image,
            (int) $this->r,
            (int) $this->g,
            (int) $this->b
        );
    }
}
