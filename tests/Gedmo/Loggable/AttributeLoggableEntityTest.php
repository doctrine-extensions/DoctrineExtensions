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
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Tests\Loggable\LoggableEntityTest;

/**
 * These are tests for loggable behavior with an attribute reader
 *
 * @requires PHP >= 8.0
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class AttributeLoggableEntityTest extends LoggableEntityTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $loggableListener = new LoggableListener();
        $loggableListener->setAnnotationReader(new AttributeReader());
        $loggableListener->setUsername('jules');
        $evm->addEventSubscriber($loggableListener);

        $this->em = $this->getDefaultMockSqliteEntityManager($evm);
    }
}
