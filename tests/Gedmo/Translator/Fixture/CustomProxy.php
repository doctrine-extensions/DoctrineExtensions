<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translator\Fixture;

use Gedmo\Translator\TranslationProxy;

class CustomProxy extends TranslationProxy
{
    public function setName(?string $name): void
    {
        $this->setTranslatedValue('name', $name);
    }

    public function getName(): ?string
    {
        return $this->getTranslatedValue('name');
    }
}
