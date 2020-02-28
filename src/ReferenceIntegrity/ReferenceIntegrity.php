<?php

namespace Gedmo\ReferenceIntegrity;

/**
 * This interface is not necessary but can be implemented for
 * Entities which in some cases needs to be identified te have
 * ReferenceIntegrity checks
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface ReferenceIntegrity
{
    /**
     * ReferenceIntegrity expects certain settings to be required
     * in combination with an association
     */

    /**
     * example
     * @ODM\ReferenceOne(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("nullify")
     * @var Article
     */

    /**
     * example
     * @ODM\ReferenceOne(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("restrict")
     * @var Article
     */

    /**
     * example
     * @ODM\ReferenceMany(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("nullify")
     * @var Doctrine\Common\Collections\ArrayCollection
     */

    /**
     * example
     * @ODM\ReferenceMany(targetDocument="Article", nullable="true", mappedBy="type")
     * @Gedmo\ReferenceIntegrity("restrict")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
}
