<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

interface IssuerInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getImage(): string;

    /**
     * @return string[]
     */
    public function getImages(): array;

    /**
     * @return string
     */
    public function getImage1x(): string;

    /**
     * @return string
     */
    public function getImage2x(): string;

    /**
     * @return string
     */
    public function getImageSvg(): string;
}
