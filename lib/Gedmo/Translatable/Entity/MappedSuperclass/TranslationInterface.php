<?php

namespace Gedmo\Translatable\Entity\MappedSuperclass;


interface TranslationInterface
{
    public function getField();
    public function getContent();
}