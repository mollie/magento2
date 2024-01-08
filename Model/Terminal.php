<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\TerminalInterface;

class Terminal implements TerminalInterface
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $brand;
    /**
     * @var string
     */
    private $model;
    /**
     * @var string|null
     */
    private $serialNumber;
    /**
     * @var string
     */
    private $description;

    public function __construct(
        string $id,
        string $brand,
        string $model,
        ?string $serialNumber,
        string $description
    ) {
        $this->id = $id;
        $this->brand = $brand;
        $this->model = $model;
        $this->serialNumber = $serialNumber;
        $this->description = $description;
    }

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
