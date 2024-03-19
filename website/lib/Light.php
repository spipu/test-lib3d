<?php

declare(strict_types=1);

class Light
{
    protected Vector $position;
    protected Color $color;
    protected ?float $length;

    public function __construct(Vector $position, Color $color, ?float $length)
    {
        if ($length <= 0.) {
            $length = null;
        }

        $this->position = $position;
        $this->color = $color;
        $this->length = $length;
    }

    public function getColor(Vector $point, Vector $normal): Color
    {
        $delta = new Vector(
            $this->position->x - $point->x,
            $this->position->y - $point->y,
            $this->position->z - $point->z
        );
        $deltaLength = $delta->getLength();
        $delta->normalize();

        $f = ($normal->x*$delta->x + $normal->y*$delta->y +$normal->z*$delta->z);
        if ($f<0) {
            $f=0.;
        }

        $d = 1.;
        if (!is_null($this->length)) {
            $d = (1. - $deltaLength/$this->length);
            $d = ($d > 0.) ? sqrt($d) : 0.;
        }

        return new Color(
            $this->color->r * $d * $f,
            $this->color->g * $d * $f,
            $this->color->b * $d * $f
        );
    }
}
