<?php

namespace Translatable\Fixture\Issue;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Student
 * @ORM\Entity
 */
class Student extends Person
{
    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="course", type="string", length=128)
     */
    protected $course;

    /**
     * @return string
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param string $course
     *
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }


}