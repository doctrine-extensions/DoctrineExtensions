<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

/**
 * @internal
 */
interface TrackingAwareAnnotationInterface extends Annotation
{
    public const EVENT_CHANGE = 'change';
    public const EVENT_CREATE = 'create';
    public const EVENT_UPDATE = 'update';

    public const EVENTS = [
        self::EVENT_CHANGE,
        self::EVENT_CREATE,
        self::EVENT_UPDATE,
    ];
}
