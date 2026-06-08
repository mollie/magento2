<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\TerminalInterface;

class Terminal implements TerminalInterface
{
    public function __construct(
        private string $id,
        private string $brand,
        private string $model,
        private ?string $serialNumber,
        private string $description
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
