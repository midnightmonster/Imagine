<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Gd;

use Imagine\Color;
use Imagine\PointInterface;
use Imagine\BoxInterface;
use Imagine\Draw\DrawerInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Exception\RuntimeException;

final class Drawer implements DrawerInterface
{
    private $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * (non-PHPdoc)
     * @see Imagine\Draw.DrawerInterface::arc()
     */
    public function arc(PointInterface $center, BoxInterface $size, $start, $end, Color $color)
    {
        $x      = $center->getX();
        $y      = $center->getY();
        $width  = $size->getWidth();
        $height = $size->getHeight();

        if (false === imagearc(
            $this->resource, $x, $y, $size->getWidth(), $size->getHeight(),
            $start, $end, $this->getColor($color)
        )) {
            throw new RuntimeException('Draw arc operation failed');
        }

        return $this;
    }

    /**
     * This function doesn't work properly because of a bug in GD
     *
     * (non-PHPdoc)
     * @see Imagine\Draw.DrawerInterface::chord()
     */
    public function chord(PointInterface $center, BoxInterface $size, $start, $end, Color $color, $fill = false)
    {
        $x      = $center->getX();
        $y      = $center->getY();
        $width  = $size->getWidth();
        $height = $size->getHeight();

        if ($fill) {
            $style = IMG_ARC_CHORD;
        } else {
            $style = IMG_ARC_CHORD | IMG_ARC_NOFILL;
        }

        if (false === imagefilledarc(
            $this->resource, $x, $y, $width, $height, $start, $end,
            $this->getColor($color), $style
        )) {
            throw new RuntimeException('Draw chord operation failed');
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Imagine\Draw.DrawerInterface::ellipse()
     */
    public function ellipse(PointInterface $center, BoxInterface $size, Color $color, $fill = false)
    {
        $color  = $this->getColor($color);
        $x      = $center->getX();
        $y      = $center->getY();
        $width  = $size->getWidth();
        $height = $size->getHeight();

        if ($fill) {
            if (false === imagefilledellipse(
                $this->resource, $x, $y, $width, $height, $color
            )) {
                throw new RuntimeException('Draw ellipse operation failed');
            }
        } else {
            if (false === imageellipse(
                $this->resource, $x, $y, $width, $height, $color
            )) {
                throw new RuntimeException('Draw ellipse operation failed');
            }
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Imagine\Draw.DrawerInterface::line()
     */
    public function line(PointInterface $start, PointInterface $end, Color $color)
    {
        $x1 = $start->getX();
        $y1 = $start->getY();
        $x2 = $end->getX();
        $y2 = $end->getY();

        if (false === imageline(
            $this->resource, $x1, $y1, $x2, $y2, $this->getColor($color)
        )) {
            throw new RuntimeException('Draw line operation failed');
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Imagine\Draw.DrawerInterface::pieSlice()
     */
    public function pieSlice(PointInterface $center, BoxInterface $size, $start, $end, Color $color, $fill = false)
    {
        $x      = $center->getX();
        $y      = $center->getY();
        $width  = $size->getWidth();
        $height = $size->getHeight();

        if ($fill) {
            $style = IMG_ARC_EDGED;
        } else {
            $style = IMG_ARC_EDGED | IMG_ARC_NOFILL;
        }

        if (false === imagefilledarc(
            $this->resource, $x, $y, $width, $height, $start, $end,
            $this->getColor($color), $style
        )) {
            throw new RuntimeException('Draw chord operation failed');
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Imagine\Draw.DrawerInterface::dot()
     */
    public function dot(PointInterface $position, Color $color)
    {
        $x = $position->getX();
        $y = $position->getY();

        if ($x < 0 || $y < 0) {
            throw new OutOfBoundsException(
                'PointInterfaces or the target pixel must start at '.
                'minimum 0, 0 position from top left corner'
            );
        }

        if (false === imagesetpixel(
            $this->resource, $x, $y, $this->getColor($color)
        )) {
            throw new RuntimeException('Draw point operation failed');
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Imagine\Draw.DrawerInterface::polygon()
     */
    public function polygon(array $coordinates, Color $color, $fill = false)
    {
        if (count($coordinates) < 3) {
            throw new InvalidArgumentException(sprintf(
                'A polygon must consist of at least 3 points, %d given',
                count($coordinates)
            ));
        }

        $points = array();

        foreach ($coordinates as $coordinate) {
            if (!$coordinate instanceof PointInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Each entry in coordinates array must be an array of '.
                    'only two values - x, y, %s given', var_export($coordinate)
                ));
            }

            $x = $coordinate->getX();
            $y = $coordinate->getY();

            $points[] = $x;
            $points[] = $y;
        }

        $color = $this->getColor($color);

        if ($fill) {
            if (false === imagefilledpolygon(
                $this->resource, $points, count($coordinates), $color
            )) {
                throw new RuntimeException('Draw polygon operation failed');
            }
        } else {
            if (false === imagepolygon(
                $this->resource, $points, count($coordinates), $color
            )) {
                throw new RuntimeException('Draw polygon operation failed');
            }
        }

        return $this;
    }
    /**
     * Internal
     *
     * Generates a GD color from Color instance
     *
     * @param  Color $color
     *
     * @throws RuntimeException
     *
     * @return resource
     */
    private function getColor(Color $color)
    {
        $color = imagecolorallocatealpha(
            $this->resource,
            $color->getRed(), $color->getGreen(), $color->getBlue(),
            round(127 * $color->getAlpha() / 100)
        );
        if (false === $color) {
            throw new RuntimeException(sprintf(
                'Unable to allocate color "RGB(%s, %s, %s)" with '.
                'transparency of %d percent', $color->getRed(),
                $color->getGreen(), $color->getBlue(), $color->getAlpha()
            ));
        }

        return $color;
    }
}
