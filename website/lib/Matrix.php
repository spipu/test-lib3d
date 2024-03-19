<?php

declare(strict_types=1);

class Matrix
{
    public array $v;
    private array $stack;

    public function __construct()
    {
        $this->reset();
        $this->stack = [];
    }

    public function reset(): self
    {
        $this->v = array_fill(0, 4, array_fill(0, 4, 0.));
        return $this;
    }

    public function identity(): self
    {
        $this->reset();
        $this->v[0][0]=1.;
        $this->v[1][1]=1.;
        $this->v[2][2]=1.;
        $this->v[3][3]=1.;
        return $this;
    }

    public function translate(float $tx, float $ty, float $tz): self
    {
        $this->identity();
        $this->v[3][0]=$tx;
        $this->v[3][1]=$ty;
        $this->v[3][2]=$tz;
        return $this;
    }

    public function scale(float $sx, float $sy, float $sz): self
    {
        $this->identity();
        $this->v[0][0]=$sx;
        $this->v[1][1]=$sy;
        $this->v[2][2]=$sz;
        return $this;
    }

    public function rotateX(float $rx): self
    {
        $this->identity();
        $this->v[1][1] = cos($rx); $this->v[2][1] =-sin($rx);
        $this->v[1][2] = sin($rx); $this->v[2][2] = cos($rx);
        return $this;
    }

    public function rotateY(float $ry): self
    {
        $this->identity();
        $this->v[0][0] = cos($ry); $this->v[2][0] = sin($ry);
        $this->v[0][2] =-sin($ry); $this->v[2][2] = cos($ry);
        return $this;
    }

    public function rotateZ(float $rz): self
    {
        $this->identity();
        $this->v[0][0] = cos($rz); $this->v[1][0] = sin($rz);
        $this->v[0][1] =-sin($rz); $this->v[1][1] = cos($rz);
        return $this;
    }

    public function multiply(Matrix $m): self
    {
        $a = $this->v;
        $b = $m->v;
        $this->reset();

        for($x=0; $x<4; $x++) {
            for ($y = 0; $y < 4; $y++) {
                $this->v[$x][$y] = $a[0][$y] * $b[$x][0] + $a[1][$y] * $b[$x][1] + $a[2][$y] * $b[$x][2] + $a[3][$y] * $b[$x][3];
            }
        }

        return $this;
    }

    public function multiplyVector(Vector $vector): Vector
    {
        $vector->t = 1.;

        return new Vector(
            $this->v[0][0]*$vector->x + $this->v[1][0]*$vector->y + $this->v[2][0]*$vector->z + $this->v[3][0]*$vector->t,
            $this->v[0][1]*$vector->x + $this->v[1][1]*$vector->y + $this->v[2][1]*$vector->z + $this->v[3][1]*$vector->t,
            $this->v[0][2]*$vector->x + $this->v[1][2]*$vector->y + $this->v[2][2]*$vector->z + $this->v[3][2]*$vector->t,
            $this->v[0][3]*$vector->x + $this->v[1][3]*$vector->y + $this->v[2][3]*$vector->z + $this->v[3][3]*$vector->t
        );
    }

    public function push(): self
    {
        $this->stack[] = $this->v;
        return $this;
    }

    public function pop(): self
    {
        $this->v = array_pop($this->stack);
        return $this;
    }

    public function draw(): void
    {
        echo '----------------------'."\n";
        for($y=0; $y<4; $y++) {
            echo '[';
            for($x=0; $x<4; $x++) {
                echo number_format($this->v[$x][$y], 2).' ';
            }
            echo ']'."\n";
        }
        echo '----------------------'."\n";
    }
}
