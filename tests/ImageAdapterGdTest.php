<?php

namespace Tests;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Image\Adapter\Gd;
use PHPUnit\Framework\TestCase;

class ImageAdapterGdTest extends TestCase
{
    protected $_originalImage;
    protected $_resultDirectory;

    public function setUp()
    {
        parent::setUp();

        new FactoryDefault();
        $this->_originalImage = __DIR__ . '/Image/original.jpg';
        $this->_resultDirectory = __DIR__ . '/Image/Adapter/Gd/Result';
        if (!@mkdir($this->_resultDirectory, 0755, true) && !is_dir($this->_resultDirectory)) {
            $this->fail('Create directory failed: ' . $this->_resultDirectory);
        }
    }

    public function test_getWidth()
    {
        $image = new Gd($this->_originalImage);
        $this->assertEquals(600, $image->getWidth());
    }

    public function test_getHeight()
    {
        $image = new Gd($this->_originalImage);
        $this->assertEquals(300, $image->getHeight());
    }

    public function test_resize()
    {
        $image = new Gd($this->_originalImage);
        $image->resize(600, 150);
        $resultImageFile = $this->_resultDirectory . '/resize_600x150.jpg';
        $image->save($resultImageFile);

        $image = new Gd($this->_originalImage);
        $image->resize(600, 300);
        $resultImageFile = $this->_resultDirectory . '/resize_600x300.jpg';
        $image->save($resultImageFile);

        $image = new Gd($this->_originalImage);
        $image->resize(600, 600);
        $resultImageFile = $this->_resultDirectory . '/resize_600x600.jpg';
        $image->save($resultImageFile);
    }

    public function test_crop()
    {
        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/crop_100x100_0x0.jpg';
        $image->crop(100, 100, 0, 0)->save($resultImageFile);

        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/crop_100x100_200x200.jpg';
        $image->crop(100, 100, 200, 200)->save($resultImageFile);
    }

    public function test_rotate()
    {
        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate_0.jpg';
        $image->rotate(0)->save($resultImageFile);

        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate_45.jpg';
        $image->rotate(45)->save($resultImageFile);

        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate_90.jpg';
        $image->rotate(90)->save($resultImageFile);

        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/rotate__45.jpg';
        $image->rotate(-45)->save($resultImageFile);
    }

    public function test_watermark()
    {
        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/watermark_with_alpha.jpg';
        $image->watermark(__DIR__ . '/Image/watermark.png', 0, 0, 0.5)->save($resultImageFile);

        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/' . 'watermark_without_alpha.jpg';
        $image->watermark(__DIR__ . '/Image/watermark.jpg', 0, 0, 0.5)->save($resultImageFile);
    }

    public function test_text()
    {
        $image = new Gd($this->_originalImage);
        $resultImageFile = $this->_resultDirectory . '/text.jpg';
        $image->text('http://www.google.com', 0, 0, 0.8, 0xffccdd, 16)->save($resultImageFile);
    }
}