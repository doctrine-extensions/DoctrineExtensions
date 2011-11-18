<?php

namespace Translator\Fixture;

use Gedmo\Translator\TranslationProxy;

class CustomProxy extends TranslationProxy
{
    public function setName($name)
    {
        return $this->setTranslatedValue('name', $name);
    }

    public function getName()
    {
        return $this->getTranslatedValue('name');
    }
}
