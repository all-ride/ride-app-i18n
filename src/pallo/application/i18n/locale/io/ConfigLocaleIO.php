<?php

namespace pallo\application\i18n\locale\io;

use pallo\library\config\Config;
use pallo\library\config\ConfigHelper;
use pallo\library\i18n\locale\io\LocaleIO;
use pallo\library\i18n\locale\GenericLocale;

/**
 * Implementation of LocaleIO that reads localization data from the Zibo
 * configuration
 */
class ConfigLocaleIO implements LocaleIO {

    /**
     * Instance of the config
     * @var pallo\library\config\Config
     */
    protected $config;

    /**
     * Instance of the config helper
     * @var pallo\library\config\ConfigHelper
     */
    protected $configHelper;

    /**
     * Constructs a new config LocaleIO
     * @param pallo\library\config\Config $config
     * @param pallo\library\config\ConfigHelper $configHelper
     * @return null
     */
    public function __construct(Config $config, ConfigHelper $configHelper) {
        $this->config = $config;
        $this->configHelper = $configHelper;
    }

    /**
     * Gets all available locales from the Zibo configuration
     * @return array all Locale objects
     */
    public function getLocales() {
        $locales = array();

        // load the locales
        $localesConfig = $this->config->get('i18n.locale', array());
        foreach ($localesConfig as $code => $options) {
            $options = $this->configHelper->flattenConfig($options);

            $locales[$code] = $this->createLocaleObject($code, $options);
        }

        // load the locale order
        $localesOrder = $this->config->get('i18n.order');
        if (!$localesOrder) {
            // no order, return locales as loaded
            return $locales;
        }

        // order the locales
        $orderedLocales = array();

        $localesOrder = explode(',', $localesOrder);
        foreach ($localesOrder as $code) {
            $code = trim($code);

            if (!isset($locales[$code])) {
                continue;
            }

            $orderedLocales[$code] = $locales[$code];

            unset($locales[$code]);
        }

        foreach ($locales as $code => $locale) {
            $orderedLocales[$code] = $locale;
        }

        return $orderedLocales;
    }

    /**
     * Creates an instance of the Locale class with the given code and options
     * @param string $code
     * @param array $options
     * @return pallo\library\i18n\locale\Locale
     */
    private function createLocaleObject($code, array $options = array()) {
        $name = $code;
        if (isset($options['name'])) {
            $name = $options['name'];

            unset($options['name']);
        }

        return new GenericLocale($code, $name, $options);
    }

}