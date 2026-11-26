<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Fixture;

use Gedmo\Translatable\TranslatableListener;

/**
 * Records the translatable listener's skipOnLoad state at the time
 * a (possibly deferred) postLoad event is dispatched.
 */
final class PostLoadSkipOnLoadSpy
{
    /**
     * @var list<bool>
     */
    public array $skipOnLoadStates = [];

    public ?\Throwable $exception = null;

    private TranslatableListener $listener;

    public function __construct(TranslatableListener $listener)
    {
        $this->listener = $listener;
    }

    public function postLoad(): void
    {
        $this->skipOnLoadStates[] = $this->listener->isSkipOnLoad();

        if (null !== $this->exception) {
            throw $this->exception;
        }
    }
}
