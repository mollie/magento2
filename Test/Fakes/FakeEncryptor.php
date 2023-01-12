<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Fakes;

use Magento\Framework\Encryption\Encryptor;

class FakeEncryptor extends Encryptor
{
    /**
     * @var array
     */
    private $returnValues = [];

    /**
     * @var bool
     */
    private $disableDecryption = false;

    public function addReturnValue(string $input, string $output): void
    {
        $this->returnValues[$input] = $output;
    }

    public function disableDecryption(): void
    {
        $this->disableDecryption = true;
    }

    public function decrypt($data)
    {
        if ($this->disableDecryption) {
            return $data;
        }

        if (array_key_exists($data, $this->returnValues)) {
            return $this->returnValues[$data];
        }

        return parent::decrypt($data);
    }
}
