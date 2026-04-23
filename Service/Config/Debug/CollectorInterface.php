<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

interface CollectorInterface
{
    /**
     * @return array<string, string> Map of archive-relative path => file contents
     */
    public function collect(): array;

    /**
     * Returns the description shown in README.txt for this collector's files.
     */
    public function getReadmeDescription(): string;
}
