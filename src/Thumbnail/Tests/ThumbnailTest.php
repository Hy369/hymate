<?php
/*
 * This file is part of the Hymate package.
 *
 * (c) Hylin Yin <hylin@iphp8.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hymate\Thumbnail\Tests;

use Hymate\Thumbnail\Thumbnail;

class ThumbnailTest extends \PHPUnit_Framework_TestCase
{
    public function testSetFillColor()
    {
        $thumbnail = new Thumbnail('Tests/Fixtures', 'Tests/Fixtures');
        $r =  new \ReflectionObject($thumbnail);
        $a = $r->getProperty('fillColor');
        $a->setAccessible(true);

        $result = $thumbnail->setFillColor('#FF8000');
        self::assertEquals(array('red' => 255, 'green' => 128, 'blue' => 0, 'alpha' => 0), $a->getValue($thumbnail));
        self::assertInstanceOf('Hymate\Thumbnail\Thumbnail', $result);

        $thumbnail->setFillColor('#FF8000', 75);
        self::assertEquals(array('red' => 255, 'green' => 128, 'blue' => 0, 'alpha' => 75), $a->getValue($thumbnail));

        $thumbnail->setFillColor('#FF8000', -75);
        self::assertEquals(array('red' => 255, 'green' => 128, 'blue' => 0, 'alpha' => 75), $a->getValue($thumbnail));

        $thumbnail->setFillColor('#FF8000', 369);
        self::assertEquals(array('red' => 255, 'green' => 128, 'blue' => 0, 'alpha' => 127), $a->getValue($thumbnail));

        $thumbnail->setFillColor('FF8000', 369);
        self::assertEquals(array('red' => 255, 'green' => 128, 'blue' => 0, 'alpha' => 127), $a->getValue($thumbnail));

        $result = $thumbnail->setFillColor('#HYLIN');
        self::assertFalse($result);

        $thumbnail->setDebug(true);
        try {
            $thumbnail->setFillColor('#HYLIN');
            self::setExpectedException('Exception');
        } catch (\Exception $e) {}
    }

    public function testSetFillColorRgba()
    {
        $thumbnail = new Thumbnail('Tests/Fixtures', 'Tests/Fixtures');
        $r =  new \ReflectionObject($thumbnail);
        $a = $r->getProperty('fillColor');
        $a->setAccessible(true);

        $result = $thumbnail->setFillColorRgba(255, 128, 0);
        self::assertEquals(array('red' => 255, 'green' => 128, 'blue' => 0, 'alpha' => 0), $a->getValue($thumbnail));
        self::assertInstanceOf('Hymate\Thumbnail\Thumbnail', $result);

        $thumbnail->setFillColorRgba(255, 128, 0, 75);
        self::assertEquals(array('red' => 255, 'green' => 128, 'blue' => 0, 'alpha' => 75), $a->getValue($thumbnail));

        $thumbnail->setFillColorRgba(369, 369, 369, 369);
        self::assertEquals(array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127), $a->getValue($thumbnail));

        $thumbnail->setFillColorRgba(-369, -369, -369, -369);
        self::assertEquals(array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127), $a->getValue($thumbnail));
    }

    public function testGetUniformScale()
    {
        $thumbnail = new Thumbnail('Tests/Fixtures', 'Tests/Fixtures');
        $r =  new \ReflectionObject($thumbnail);
        $m = $r->getMethod('getUniformScale');
        $m->setAccessible(true);

        $result = $m->invoke($thumbnail, 200, 300, 500, 600, true);
        self::assertEquals(array(200, 240), $result);

        $result = $m->invoke($thumbnail, 200, 300, 600, 510, true);
        self::assertEquals(array(200, 170), $result);

        $result = $m->invoke($thumbnail, 300, 200, 510, 600, true);
        self::assertEquals(array(170, 200), $result);

        $result = $m->invoke($thumbnail, 300, 200, 600, 500, true);
        self::assertEquals(array(240, 200), $result);

        $result = $m->invoke($thumbnail, 300, 200, 150, 300, true);
        self::assertEquals(array(100, 200), $result);

        $result = $m->invoke($thumbnail, 300, 200, 150, 147, true);
        self::assertEquals(array(150, 147), $result);

        $result = $m->invoke($thumbnail, 300, 200, 120, 100, false);
        self::assertEquals(array(240, 200), $result);
    }

    public function testGetCoordinate()
    {
        $thumbnail = new Thumbnail('Tests/Fixtures', 'Tests/Fixtures');
        $r =  new \ReflectionObject($thumbnail);
        $m = $r->getMethod('getCoordinate');
        $m->setAccessible(true);

        $result = $m->invoke($thumbnail, 100, 200, 30, 40);
        self::assertEquals(array(35, 160), $result);

        $result = $m->invoke($thumbnail, 100, 200, 30, 40, 'foo', 'bar');
        self::assertEquals(array(35, 160), $result);

        $result = $m->invoke($thumbnail, 100, 200, 30, 40, 'left', 'top');
        self::assertEquals(array(0, 0), $result);

        $result = $m->invoke($thumbnail, 100, 200, 30, 40, 'center', 'center');
        self::assertEquals(array(35, 80), $result);

        $result = $m->invoke($thumbnail, 100, 200, 30, 40, 'right', 'bottom');
        self::assertEquals(array(70, 160), $result);
    }
}
