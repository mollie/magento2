<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

interface TerminalInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getBrand(): string;

    /**
     * @return string
     */
    public function getModel(): string;

    /**
     * @return string|null
     */
    public function getSerialNumber(): ?string;

    /**
     * @return string
     */
    public function getDescription(): string;
}
