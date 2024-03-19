<?php

declare(strict_types=1);

class Texture
{
    public string $filename;
    public int $width;
    public int $height;
    public array $data;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->load();
    }

    private function load(): void
    {
        $image = imagecreatefromgif($this->filename);
        $this->width  = imagesx($image);
        $this->height = imagesy($image);

        $this->data = [];
        for ($y = 0; $y < $this->height; $y++) {
            $this->data[$y] = [];
            for ($x = 0; $x < $this->width; $x++) {
                $rvb = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $this->data[$y][$x] = new Color(
                    (float) $rvb['red'],
                    (float) $rvb['green'],
                    (float) $rvb['blue']
                );
            }
        }

        imagedestroy($image);
    }
}
