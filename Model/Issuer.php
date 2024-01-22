<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\IssuerInterface;

class Issuer implements IssuerInterface
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var array
     */
    private $images;

    public function __construct(
        string $id,
        string $name,
        array $images
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->images = $images;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImage(): string
    {
        return $this->images['svg'];
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getImage1x(): string
    {
        return $this->images['size1x'] ?? '';
    }

    public function getImage2x(): string
    {
        return $this->images['size2x'] ?? '';
    }

    public function getImageSvg(): string
    {
        return $this->images['svg'] ?? '';
    }
}
