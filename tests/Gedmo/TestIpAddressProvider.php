<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests;

use Gedmo\Tool\IpAddressProviderInterface;

final class TestIpAddressProvider implements IpAddressProviderInterface
{
    private ?string $address;

    public function __construct(?string $address)
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }
}
