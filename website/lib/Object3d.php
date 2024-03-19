<?php

declare(strict_types=1);

class Object3d
{
    public string $name;

    /**
     * @var Vector[]
     */
    public array $ptOriginal;

    /**
     * @var Vector[]
     */
    public array $pt3d;

    /**
     * @var Vector[]
     */
    public array $pt2d;
    public int   $ptCount;

    public array $faceList;
    public array $faceInfo;
    public int   $faceCount;

    public array $textureList;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->ptOriginal = array();
        $this->pt3d = array();
        $this->pt2d = array();
        $this->ptCount = 0;
        $this->faceList = array();
        $this->faceInfo = array();
        $this->faceCount = 0;
        $this->textureList = array();
    }

    public function textureAdd(Texture $texture): self
    {
        $this->textureList[] = $texture;
        return $this;
    }

    public function pointAdd(float $x, float $y, float $z): self
    {
        $point = new Vector($x, $y, $z, 1.);
        $this->ptOriginal[] = $point;
        $this->pt3d[] = clone $point;
        $this->ptCount++;
        return $this;
    }

    public function faceAdd(int $pt1, int $pt2, int $pt3, ?Color $color = null, ?int $texture = null, ?array $map = null): self
    {
        if ($color === null) {
            $color = new Color(255, 255, 255);
        }
        if ($map === null) {
            $map = [
                [null, null],
                [null, null],
                [null, null]
            ];
        }
        $this->faceList[] = [
            $pt1 - 1,
            $pt2 - 1,
            $pt3 - 1,
            $color,
            $texture ? $texture - 1 : null,
            $map
        ];
        $this->faceInfo[] = [null, null];
        $this->faceCount++;
        return $this;
    }

    public function loadASE(string $filename, float $scale = 1.): self
    {
        $content = file_get_contents($filename);
        $content = preg_replace('/[\s]+/', ' ', $content);
        $content = explode('GEOMOBJECT', $content);
        unset($content[0]);
        $nbPoints = 0;
        foreach ($content as $object) {
            preg_match_all('/\*MESH_VERTEX ([\d]+) ([\d\.\-]+) ([\d\.\-]+) ([\d\.\-]+) /', $object, $match);
            $nb = count($match[0]);
            for ($k = 0; $k < count($match[0]); $k++) {
                $this->pointAdd(
                    ((float) $match[2][$k]) * $scale,
                    ((float) $match[3][$k]) * $scale,
                    ((float) $match[4][$k]) * $scale
                );
            }


            preg_match_all('/\*MESH_FACE ([\d]+): A: ([\d]+) B: ([\d]+) C: ([\d]+) /', $object, $match);
            for ($k = 0; $k < count($match[0]); $k++) {
                $this->faceAdd(
                    $nbPoints + (int) $match[2][$k] + 1,
                    $nbPoints + (int) $match[3][$k] + 1,
                    $nbPoints + (int) $match[4][$k] + 1
                );
            }

            $nbPoints += $nb;
        }

        return $this;
    }

    public function ptTransform(Matrix $m): self
    {
        for ($ptKey = 0; $ptKey < $this->ptCount; $ptKey++) {
            $this->pt3d[$ptKey] = $m->multiplyVector($this->ptOriginal[$ptKey]);
        }

        return $this;
    }

    public function ptProjection(Engine3d $vue): self
    {
        $sx = $vue->scrWidth / ($vue->viewMaxX - $vue->viewMinX);
        $sy = $vue->scrHeight / ($vue->viewMaxY - $vue->viewMinY);

        $factor_x = ($vue->viewMaxX - $vue->viewMinX) / (2. * tan($vue->opening));
        $factor_y = ($vue->viewMaxX - $vue->viewMinX) / (2. * tan($vue->opening));


        $this->pt2d = [];
        for ($ptKey = 0; $ptKey < $this->ptCount; $ptKey++) {
            $this->pt2d[$ptKey] = new Vector(
                $sx * ($factor_x * $this->pt3d[$ptKey]->x / $this->pt3d[$ptKey]->z - $vue->viewMinX),
                $sy * ($factor_y * $this->pt3d[$ptKey]->y / $this->pt3d[$ptKey]->z - $vue->viewMinY),
                $this->pt3d[$ptKey]->z
            );
        }

        return $this;
    }

    private function faceNormalize2d(Vector $pt1, Vector $pt2, Vector $pt3): float
    {
        $v1 = [$pt2->x - $pt1->x, $pt2->y - $pt1->y];
        $v2 = [$pt3->x - $pt1->x, $pt3->y - $pt1->y];
        return $v1[0] * $v2[1] - $v1[1] * $v2[0];
    }

    private function faceNormal(Vector $pt1, Vector $pt2, Vector $pt3): Vector
    {
        $v1 = new Vector($pt2->x - $pt1->x, $pt2->y - $pt1->y, $pt2->z - $pt1->z);
        $v2 = new Vector($pt3->x - $pt1->x, $pt3->y - $pt1->y, $pt3->z - $pt1->z);

        $v = new Vector(
            $v1->y * $v2->z - $v1->z * $v2->y,
            $v1->z * $v2->x - $v1->x * $v2->z,
            $v1->x * $v2->y - $v1->y * $v2->x,
        );

        $v->normalize();

        return $v;
    }

    public function facePrepare(): void
    {
        for ($faceKey = 0; $faceKey < $this->faceCount; $faceKey++) {
            $fc = &$this->faceList[$faceKey];
            $this->faceInfo[$faceKey][0] = $this->faceNormal($this->pt3d[$fc[0]], $this->pt3d[$fc[1]], $this->pt3d[$fc[2]]);
            $this->faceInfo[$faceKey][1] = $this->faceNormalize2d($this->pt2d[$fc[0]], $this->pt2d[$fc[1]], $this->pt2d[$fc[2]]);
        }
    }

    public function ptColor(Engine3d $vue, Color $color, Vector $pt, Vector $normal): Color
    {
        $col = clone $vue->lightAmbient;

        foreach ($vue->lightList as $light) {
            $temp = $light->getColor($pt, $normal);
            $col->r += $temp->r;
            $col->g += $temp->g;
            $col->b += $temp->b;
        }
        $col->applyLimit();

        $col->r = $color->r * $col->r / 255.;
        $col->g = $color->g * $col->g / 255.;
        $col->b = $color->b * $col->b / 255.;

        return $col;
    }

    public function faceDraw(Engine3d $vue): void
    {
        foreach ($this->faceInfo as $faceKey => &$faceInfo) {
            $pt1 = [];
            $pt2 = [];
            $pt3 = [];

            if (!$this->fcMappingPrepare($vue, $this->faceList[$faceKey], $faceInfo, $pt1, $pt2, $pt3)) {
                continue;
            }

            if ($vue->displayFast) {
                $this->fcMappingFast($vue, $pt1, $pt2, $pt3);
            } elseif ($this->faceList[$faceKey][4] === null) {
                $this->fcMappingWithoutTexture($vue, $pt1, $pt2, $pt3);
            } else {
                $this->fcMappingWithTexture($vue, $pt1, $pt2, $pt3, $this->textureList[$this->faceList[$faceKey][4]]);
            }
        }
    }

    public function fcMappingPrepare(Engine3d $vue, array $face, array $faceInfo, array &$pt1, array &$pt2, array &$pt3): bool
    {
        if ($faceInfo[1] > 0) {
            return false;
        }

        $col = $this->ptColor($vue, $face[3], $this->pt3d[$face[0]], $faceInfo[0]);
        $pt1[IX] = $this->pt2d[$face[0]]->x;
        $pt1[IY] = $this->pt2d[$face[0]]->y;
        $pt1[IZ] = $this->pt2d[$face[0]]->z;
        $pt1[IR] = $col->r;
        $pt1[IG] = $col->g;
        $pt1[IB] = $col->b;
        $pt1[IU] = $face[5][0][U];
        $pt1[IV] = $face[5][0][V];

        $col = $this->ptColor($vue, $face[3], $this->pt3d[$face[1]], $faceInfo[0]);
        $pt2[IX] = $this->pt2d[$face[1]]->x;
        $pt2[IY] = $this->pt2d[$face[1]]->y;
        $pt2[IZ] = $this->pt2d[$face[1]]->z;
        $pt2[IR] = $col->r;
        $pt2[IG] = $col->g;
        $pt2[IB] = $col->b;
        $pt2[IU] = $face[5][1][U];
        $pt2[IV] = $face[5][1][V];

        $col = $this->ptColor($vue, $face[3], $this->pt3d[$face[2]], $faceInfo[0]);
        $pt3[IX] = $this->pt2d[$face[2]]->x;
        $pt3[IY] = $this->pt2d[$face[2]]->y;
        $pt3[IZ] = $this->pt2d[$face[2]]->z;
        $pt3[IR] = $col->r;
        $pt3[IG] = $col->g;
        $pt3[IB] = $col->b;
        $pt3[IU] = $face[5][2][U];
        $pt3[IV] = $face[5][2][V];

        if ($pt1[IZ] < 1. && $pt2[IZ] < 1. && $pt3[IZ] < 1.) {
            return false;
        }

        if (
            ($pt1[IY] < $pt2[IY] || ($pt1[IY] == $pt2[IY] && $pt1[IX] < $pt2[IX])) &&
            ($pt1[IY] < $pt3[IY] || ($pt1[IY] == $pt3[IY] && $pt1[IX] < $pt3[IX]))
        ) {
            if ($pt2[IX] > $pt3[IX]) {
                $t = $pt2;
                $pt2 = $pt3;
                $pt3 = $t;
                unset($t);
            }
        } else if (
            ($pt2[IY] < $pt3[IY] || ($pt2[IY] == $pt3[IY] && $pt2[IX] < $pt3[IX])) &&
            ($pt2[IY] < $pt1[IY] || ($pt2[IY] == $pt1[IY] && $pt2[IX] < $pt1[IX]))
        ) {
            $t = $pt1;
            $pt1 = $pt2;
            $pt2 = $t;
            unset($t);
            if ($pt2[IX] > $pt3[IX]) {
                $t = $pt2;
                $pt2 = $pt3;
                $pt3 = $t;
                unset($t);
            }
        } else {
            $t = $pt1;
            $pt1 = $pt3;
            $pt3 = $t;
            unset($t);
            if ($pt2[IX] >= $pt3[IX]) {
                $t = $pt2;
                $pt2 = $pt3;
                $pt3 = $t;
                unset($t);
            }
        }

        return true;
    }

    public function fcMappingWithTexture(Engine3d $vue, array &$pt1, array &$pt2, array &$pt3, Texture $texture): void
    {
        $pt1[IR] /= 255.;
        $pt1[IG] /= 255.;
        $pt1[IB] /= 255.;
        $pt1[IV] = 1. - $pt1[IV];
        $pt1[IU] /= $pt1[IZ];
        $pt1[IV] /= $pt1[IZ];

        $pt2[IR] /= 255.;
        $pt2[IG] /= 255.;
        $pt2[IB] /= 255.;
        $pt2[IV] = 1. - $pt2[IV];
        $pt2[IU] /= $pt2[IZ];
        $pt2[IV] /= $pt2[IZ];

        $pt3[IR] /= 255.;
        $pt3[IG] /= 255.;
        $pt3[IB] /= 255.;
        $pt3[IV] = 1. - $pt3[IV];
        $pt3[IU] /= $pt3[IZ];
        $pt3[IV] /= $pt3[IZ];

        $yMin = $pt1[IY];
        $yMax = max($pt2[IY], $pt3[IY]);

        $dt12 = array();
        $dt12[IX] = $pt2[IX]-$pt1[IX];
        $dt12[IY] = $pt2[IY]-$pt1[IY];
        $dt12[IR] = $pt2[IR]-$pt1[IR];
        $dt12[IG] = $pt2[IG]-$pt1[IG];
        $dt12[IB] = $pt2[IB]-$pt1[IB];
        $dt12[IU] = $pt2[IU]-$pt1[IU];
        $dt12[IV] = $pt2[IV]-$pt1[IV];

        $dt23 = array();
        $dt23[IX] = $pt3[IX]-$pt2[IX];
        $dt23[IY] = $pt3[IY]-$pt2[IY];
        $dt23[IR] = $pt3[IR]-$pt2[IR];
        $dt23[IG] = $pt3[IG]-$pt2[IG];
        $dt23[IB] = $pt3[IB]-$pt2[IB];
        $dt23[IU] = $pt3[IU]-$pt2[IU];
        $dt23[IV] = $pt3[IV]-$pt2[IV];

        $dt13 = array();
        $dt13[IX] = $pt3[IX]-$pt1[IX];
        $dt13[IY] = $pt3[IY]-$pt1[IY];
        $dt13[IR] = $pt3[IR]-$pt1[IR];
        $dt13[IG] = $pt3[IG]-$pt1[IG];
        $dt13[IB] = $pt3[IB]-$pt1[IB];
        $dt13[IU] = $pt3[IU]-$pt1[IU];
        $dt13[IV] = $pt3[IV]-$pt1[IV];

        for ($ly = $yMin; $ly <= $yMax; $ly++) {
            $lt0 = array();
            if ($ly <= $pt2[IY]) {
                $al = ($dt12[IY]) ? ($ly - $pt1[IY]) / $dt12[IY] : 0.;

                $lt0[IY] = $ly;
                $lt0[IZ] = 1. / ((1. - $al) / $pt1[IZ] + $al / $pt2[IZ]);
                $lt0[IX] = $pt1[IX] + $dt12[IX] * $al;
                $lt0[IR] = $pt1[IR] + $dt12[IR] * $al;
                $lt0[IG] = $pt1[IG] + $dt12[IG] * $al;
                $lt0[IB] = $pt1[IB] + $dt12[IB] * $al;
                $lt0[IU] = ($pt1[IU] + $dt12[IU] * $al) * $lt0[IZ];
                $lt0[IV] = ($pt1[IV] + $dt12[IV] * $al) * $lt0[IZ];
            } else {
                $al = ($dt23[IY]) ? ($ly - $pt2[IY]) / $dt23[IY] : 0.;

                $lt0[IY] = $ly;
                $lt0[IZ] = 1. / ((1. - $al) / $pt2[IZ] + $al / $pt3[IZ]);
                $lt0[IX] = $pt2[IX] + $dt23[IX] * $al;
                $lt0[IR] = $pt2[IR] + $dt23[IR] * $al;
                $lt0[IG] = $pt2[IG] + $dt23[IG] * $al;
                $lt0[IB] = $pt2[IB] + $dt23[IB] * $al;
                $lt0[IU] = ($pt2[IU] + $dt23[IU] * $al) * $lt0[IZ];
                $lt0[IV] = ($pt2[IV] + $dt23[IV] * $al) * $lt0[IZ];
            }

            if ($ly < $pt3[IY]) {
                $al = ($dt13[IY]) ? ($ly - $pt1[IY]) / $dt13[IY] : 0.;

                $lt1[IY] = $ly;
                $lt1[IZ] = 1. / ((1. - $al) / $pt1[IZ] + $al / $pt3[IZ]);
                $lt1[IX] = $pt1[IX] + $dt13[IX] * $al;
                $lt1[IR] = $pt1[IR] + $dt13[IR] * $al;
                $lt1[IG] = $pt1[IG] + $dt13[IG] * $al;
                $lt1[IB] = $pt1[IB] + $dt13[IB] * $al;
                $lt1[IU] = ($pt1[IU] + $dt13[IU] * $al) * $lt1[IZ];
                $lt1[IV] = ($pt1[IV] + $dt13[IV] * $al) * $lt1[IZ];
            } else {
                $al = ($dt23[IY]) ? ($pt3[IY] - $ly) / $dt23[IY] : 0.;

                $lt1[IY] = $ly;
                $lt1[IZ] = 1. / ((1. - $al) / $pt3[IZ] + $al / $pt2[IZ]);
                $lt1[IX] = $pt3[IX] - $dt23[IX] * $al;
                $lt1[IR] = $pt3[IR] - $dt23[IR] * $al;
                $lt1[IG] = $pt3[IG] - $dt23[IG] * $al;
                $lt1[IB] = $pt3[IB] - $dt23[IB] * $al;
                $lt1[IU] = ($pt3[IU] - $dt23[IU] * $al) * $lt1[IZ];
                $lt1[IV] = ($pt3[IV] - $dt23[IV] * $al) * $lt1[IZ];
            }

            if ($lt0[IX] == $lt1[IX]) {
                continue;
            }

            if ($lt0[IX] > $lt1[IX]) {
                $t = $lt0;
                $lt0 = $lt1;
                $lt1 = $t;
                unset($t);
            }

            $xMin = floor($lt0[IX]);
            $xMax = floor($lt1[IX]);

            $lt0[IU] = $lt0[IU] * $texture->width / $lt0[IZ];
            $lt1[IU] = $lt1[IU] * $texture->width / $lt1[IZ];
            $lt0[IV] = $lt0[IV] * $texture->height / $lt0[IZ];
            $lt1[IV] = $lt1[IV] * $texture->height / $lt1[IZ];

            $dt = array();
            $dt[IR] = $lt1[IR] - $lt0[IR];
            $dt[IG] = $lt1[IG] - $lt0[IG];
            $dt[IB] = $lt1[IB] - $lt0[IB];
            $dt[IU] = $lt1[IU] - $lt0[IU];
            $dt[IV] = $lt1[IV] - $lt0[IV];

            for ($lx = $xMin; $lx <= $xMax; $lx++) {
                $al = ($xMin < $xMax) ? ($lx - $xMin) / ($xMax - $xMin) : 0.;
                $lz = (1. / ((1. - $al) / $lt0[IZ] + $al / $lt1[IZ]));

                if ($vue->zBufferSet($lx, $ly, $lz)) {
                    $xt = (int) floor($lz * ($lt0[IU] + $dt[IU] * $al));
                    $xt = $xt % $texture->width;
                    if ($xt < 0.) {
                        $xt += $texture->width;
                    }

                    $yt = (int) floor($lz * ($lt0[IV] + $dt[IV] * $al));
                    $yt = $yt % $texture->height;
                    if ($yt < 0.) {
                        $yt += $texture->height;
                    }

                    $textureColor = $texture->data[$yt][$xt];
                    $col = new Color(
                        (int) (($lt0[IR] + $dt[IR] * $al) * $textureColor->r),
                        (int) (($lt0[IG] + $dt[IG] * $al) * $textureColor->g),
                        (int) (($lt0[IB] + $dt[IB] * $al) * $textureColor->b)
                    );

                    $ly = (int) $ly;

                    imagesetpixel(
                        $vue->img,
                        (int) $lx,
                        (int) $ly,
                        $col->getImageColor($vue->img)
                    );
                }
            }
        }
    }

    public function fcMappingWithoutTexture(Engine3d $vue, array &$pt1, array &$pt2, array &$pt3): void
    {
        $yMin = $pt1[IY];
        $yMax = max($pt2[IY], $pt3[IY]);

        $dt12 = [];
        $dt12[IX] = $pt2[IX] - $pt1[IX];
        $dt12[IY] = $pt2[IY] - $pt1[IY];
        $dt12[IR] = $pt2[IR] - $pt1[IR];
        $dt12[IG] = $pt2[IG] - $pt1[IG];
        $dt12[IB] = $pt2[IB] - $pt1[IB];

        $dt23 = [];
        $dt23[IX] = $pt3[IX] - $pt2[IX];
        $dt23[IY] = $pt3[IY] - $pt2[IY];
        $dt23[IR] = $pt3[IR] - $pt2[IR];
        $dt23[IG] = $pt3[IG] - $pt2[IG];
        $dt23[IB] = $pt3[IB] - $pt2[IB];

        $dt13 = [];
        $dt13[IX] = $pt3[IX] - $pt1[IX];
        $dt13[IY] = $pt3[IY] - $pt1[IY];
        $dt13[IR] = $pt3[IR] - $pt1[IR];
        $dt13[IG] = $pt3[IG] - $pt1[IG];
        $dt13[IB] = $pt3[IB] - $pt1[IB];

        for ($ly = $yMin; $ly <= $yMax; $ly++) {
            $lt0 = array();
            if ($ly <= $pt2[IY]) {
                $al = ($dt12[IY]) ? ($ly - $pt1[IY]) / $dt12[IY] : 0.;

                $lt0[IY] = $ly;
                $lt0[IZ] = 1. / ((1. - $al) / $pt1[IZ] + $al / $pt2[IZ]);
                $lt0[IX] = $pt1[IX] + $dt12[IX] * $al;
                $lt0[IR] = $pt1[IR] + $dt12[IR] * $al;
                $lt0[IG] = $pt1[IG] + $dt12[IG] * $al;
                $lt0[IB] = $pt1[IB] + $dt12[IB] * $al;
            } else {
                $al = ($dt23[IY]) ? ($ly - $pt2[IY]) / $dt23[IY] : 0.;

                $lt0[IY] = $ly;
                $lt0[IZ] = 1. / ((1. - $al) / $pt2[IZ] + $al / $pt3[IZ]);
                $lt0[IX] = $pt2[IX] + $dt23[IX] * $al;
                $lt0[IR] = $pt2[IR] + $dt23[IR] * $al;
                $lt0[IG] = $pt2[IG] + $dt23[IG] * $al;
                $lt0[IB] = $pt2[IB] + $dt23[IB] * $al;
            }

            if ($ly < $pt3[IY]) {
                $al = ($dt13[IY]) ? ($ly - $pt1[IY]) / $dt13[IY] : 0.;

                $lt1[IY] = $ly;
                $lt1[IZ] = 1. / ((1. - $al) / $pt1[IZ] + $al / $pt3[IZ]);
                $lt1[IX] = $pt1[IX] + $dt13[IX] * $al;
                $lt1[IR] = $pt1[IR] + $dt13[IR] * $al;
                $lt1[IG] = $pt1[IG] + $dt13[IG] * $al;
                $lt1[IB] = $pt1[IB] + $dt13[IB] * $al;
            } else {
                $al = ($dt23[IY]) ? ($pt3[IY] - $ly) / $dt23[IY] : 0.;

                $lt1[IY] = $ly;
                $lt1[IZ] = 1. / ((1. - $al) / $pt3[IZ] + $al / $pt2[IZ]);
                $lt1[IX] = $pt3[IX] - $dt23[IX] * $al;
                $lt1[IR] = $pt3[IR] - $dt23[IR] * $al;
                $lt1[IG] = $pt3[IG] - $dt23[IG] * $al;
                $lt1[IB] = $pt3[IB] - $dt23[IB] * $al;
            }

            if ($lt0[IX] == $lt1[IX]) {
                continue;
            }

            if ($lt0[IX] > $lt1[IX]) {
                $t = $lt0;
                $lt0 = $lt1;
                $lt1 = $t;
                unset($t);
            }

            $xMin = floor($lt0[IX]);
            $xMax = floor($lt1[IX]);

            $dt = array();
            $dt[IR] = $lt1[IR] - $lt0[IR];
            $dt[IG] = $lt1[IG] - $lt0[IG];
            $dt[IB] = $lt1[IB] - $lt0[IB];

            for ($lx = $xMin; $lx <= $xMax; $lx++) {
                $al = ($xMin < $xMax) ? ($lx - $xMin) / ($xMax - $xMin) : 0.;
                $lz = 1. / ((1. - $al) / $lt0[IZ] + $al / $lt1[IZ]);

                if ($vue->zBufferSet($lx, $ly, $lz)) {
                    $col = new Color(
                        $lt0[IR] + $dt[IR] * $al,
                        $lt0[IG] + $dt[IG] * $al,
                        $lt0[IB] + $dt[IB] * $al
                    );

                    $ly = (int) $ly;

                    imagesetpixel(
                        $vue->img,
                        (int) $lx,
                        (int) $ly,
                        $col->getImageColor($vue->img)
                    );
                }
            }
        }
    }

    public function fcMappingFast(Engine3d $vue, array &$pt1, array &$pt2, array &$pt3): void
    {
        $col = new Color(
            (int) (($pt1[IR] + $pt1[IR] + $pt1[IR]) / 3.),
            (int) (($pt1[IG] + $pt1[IG] + $pt1[IG]) / 3.),
            (int) (($pt1[IB] + $pt1[IB] + $pt1[IB]) / 3.)
        );
        $col->applyLimit();

        $color = $col->getImageColor($vue->img);

        imageline($vue->img, (int) $pt1[IX], (int) $pt1[IY], (int) $pt2[IX], (int) $pt2[IY], $color);
        imageline($vue->img, (int) $pt2[IX], (int) $pt2[IY], (int) $pt3[IX], (int) $pt3[IY], $color);
        imageline($vue->img, (int) $pt3[IX], (int) $pt3[IY], (int) $pt1[IX], (int) $pt1[IY], $color);
    }
}
