<?php
namespace Gedmo\Translatable;

/**
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LocaleResolver implements LocaleResolverInterface
{
    /**
     * Default locale, this changes behavior
     * to not update the original record field if locale
     * which is used for updating is not default. This
     * will load the default translation in other locales
     * if record is not translated yet
     *
     * @var string
     */
    private $defaultLocale = 'en_US';

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocale($object = null, $meta = null)
    {
        return $this->defaultLocale;
    }

    /**
     * Set the default locale.
     *
     * @param $locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }
}
