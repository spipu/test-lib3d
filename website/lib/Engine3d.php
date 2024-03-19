<?php

declare(strict_types=1);

class Engine3d
{
    protected float $timer;
    private   int   $timerLimit = 3;

    protected Color   $background;
    protected Matrix  $matrixView;
    public    GdImage $img;
    public    bool    $displayFast = false;
    private   float   $screenFactor = 1.5;

    public    int   $realWidth;
    public    int   $realHeight;
    public    int   $scrWidth;
    public    int   $scrHeight;
    public    float $opening;
    public    float $viewMinX;
    public    float $viewMaxX;
    public    float $viewMinY;
    public    float $viewMaxY;

    /**
     * @var Light[]
     */
    public    array $lightList;
    public    Color $lightAmbient;

    /**
     * @var int[][]
     */
    public    array $zBuffer;
    public    int   $zNear;
    public    int   $zFar;
    public    int   $zDef;

    public function __construct()
    {
        set_time_limit($this->timerLimit + 1);
        $this->setTimer();

        $this->background = new Color(0., 0., 0.);
        $this->lightAmbient = new Color(0., 0., 0.);
        $this->lightList = [];

        $this->matrixView = new Matrix();
        $this->matrixIdentity();
    }

    public function basicInit(): void
    {
        $ratio = 16./9.;
        $width = 800;
        $viewPort = 32;

        $this->setScreen($width, (int) ((float) $width / $ratio));
        $this->setBackground(new Color(20., 20., 20.));
        $this->drawInit();
        $this->zBufferInit(1, 150);
        $this->setOpening(45.0);
        $this->setView(-$viewPort, $viewPort, -(int) ((float) $viewPort / $ratio), (int) ((float) $viewPort / $ratio));
        $this->lightAmbient(new Color(50., 50., 50.));
        $this->matrixIdentity();
    }

    public function setScreen(int $width, int $height): self
    {
        $this->scrWidth   = (int) floor($width * $this->screenFactor);
        $this->scrHeight  = (int) floor($height * $this->screenFactor);
        $this->realWidth  = $width;
        $this->realHeight = $height;

        return $this;
    }

    public function setBackground(Color $color): self
    {
        $this->background = $color;
        return $this;
    }

    public function lightAdd(Vector $position, Color $color, ?float $length): self
    {
        $this->lightList[] = new Light(
            $this->matrixView->multiplyVector($position),
            $color,
            $length
        );

        return $this;
    }

    public function lightAmbient(Color $color): self
    {
        $this->lightAmbient = $color;
        return $this;
    }

    public function setOpening(float $opening): self
    {
        $this->opening = PI_180 * $opening;
        return $this;
    }

    public function setView(float $xMin, float $xMax, float $yMin, float $yMax): self
    {
        $this->viewMinX = $xMin;
        $this->viewMaxX = $xMax;
        $this->viewMinY = $yMin;
        $this->viewMaxY = $yMax;
        return $this;
    }

    public function matrixIdentity(): self
    {
        $this->matrixView->identity();
        return $this;
    }

    public function matrixPush(): self
    {
        $this->matrixView->push();
        return $this;
    }

    public function matrixPop(): self
    {
        $this->matrixView->pop();
        return $this;
    }

    public function matrixTranslate(float $vx, float $vy, float $vz): self
    {
        $this->matrixView->multiply((new Matrix())->translate($vx, $vy, $vz));
        return $this;
    }

    public function matrixRotateX(float $rx): self
    {
        $this->matrixView->multiply((new Matrix())->rotateX(PI_180*$rx));
        return $this;
    }

    public function matrixRotateY(float $ry): self
    {
        $this->matrixView->multiply((new Matrix())->rotateY(PI_180*$ry));
        return $this;
    }

    public function matrixRotateZ(float $rz): self
    {
        $this->matrixView->multiply((new Matrix())->rotateZ(PI_180*$rz));
        return $this;
    }

    public function matrixScale(float $sx, float $sy, float $sz): self
    {
        $this->matrixView->multiply((new Matrix())->scale($sx, $sy, $sz));
        return $this;
    }

    public function zBufferInit(int $near = 1, int $far = 80): self
    {
        $this->zNear = $near;
        $this->zFar  = $far;

        $this->zBuffer = array_fill(
            0,
            $this->scrWidth,
            array_fill(
                0,
                $this->scrHeight,
                $this->zFar
            )
        );
        return $this;
    }

    public function zBufferSet(float $x, float $y, float $z): bool
    {
        $x = (int) $x;
        $y = (int) $y;

        if ($x < 0 || $y < 0 || $x > $this->scrWidth - 1 || $y > $this->scrHeight - 1) {
            return false;
        }

        if ($z < $this->zNear || $z > $this->zFar || $this->zBuffer[$x][$y] < $z) {
            return false;
        }

        $this->zBuffer[$x][$y]=$z;

        return true;
    }

    public function drawInit(): self
    {
        $this->img = imagecreatetruecolor($this->scrWidth, $this->scrHeight);
        $background = $this->background->getImageColor($this->img);
        imagefilledrectangle($this->img, 0, 0, $this->scrWidth, $this->scrHeight, $background);
        return $this;
    }

    public function drawObject(Object3d $obj): self
    {
        $obj->ptTransform($this->matrixView);
        $obj->ptProjection($this);
        $obj->facePrepare();
        $obj->faceDraw($this);
        return $this;
    }

    public function drawFinish(bool $generate = false, int $quality = 95): void
    {
        $tmp = imagecreatetruecolor($this->realWidth, $this->realHeight);
        imagecopyresampled($tmp, $this->img, 0, 0, 0, 0, $this->realWidth, $this->realHeight, $this->scrWidth, $this->scrHeight);

        if ($generate) {
            $fontSize = 2;
            $txt = 'generate in : '.number_format($this->getTimer()*1000, 1, '.', '').' ms';
            $white = (new Color(255., 255., 255.))->getImageColor($tmp);
            $x = $this->realWidth-imagefontwidth($fontSize)*strlen($txt)-2;
            $y = $this->realHeight-imagefontheight($fontSize)-2;
            imagestring($tmp, $fontSize, $x, $y, $txt, $white);
        }

        header('Content-type: image/jpg');
        imagejpeg($tmp, null, $quality);
        imagedestroy($this->img);
        imagedestroy($tmp);
    }

    protected function setTimer(): void
    {
        $this->timer = microtime(true);
    }

    protected function getTimer(): float
    {
        return microtime(true) - $this->timer;
    }
}
