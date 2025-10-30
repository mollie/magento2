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
    public function __construct(
        private string $id,
        private string $name,
        private array $images
    ) {}

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
