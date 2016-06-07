<?php
/**
 * @license See the file LICENSE for copying permission
 */

namespace Sluggable\Fixture\Issue1605;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType(value="JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *      "page" = "Page",
 *      "article" = "Article"
 * })
 */
abstract class ParentEntity
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
