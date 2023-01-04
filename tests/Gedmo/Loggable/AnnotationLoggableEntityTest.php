<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Loggable\LoggableEntityTest;

/**
 * These are tests for loggable behavior with an annotation reader (created by the listener by default)
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class AnnotationLoggableEntityTest extends LoggableEntityTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $loggableListener = new LoggableListener();
        $loggableListener->setUsername('jules');
        $evm->addEventSubscriber($loggableListener);

        $this->em = $this->getDefaultMockSqliteEntityManager($evm);
    }
}
