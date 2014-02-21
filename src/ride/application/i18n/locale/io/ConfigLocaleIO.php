<?php

namespace ride\application\i18n\locale\io;

use ride\library\config\Config;
use ride\library\config\ConfigHelper;
use ride\library\i18n\locale\io\LocaleIO;
use ride\library\i18n\locale\GenericLocale;

/**
 * Implementation of LocaleIO that reads localization data from the Zibo
 * configuration
 */
class ConfigLocaleIO implements LocaleIO {

    /**
     * Instance of the config
     * @var ride\library\config\Config
     */
    protected $config;

    /**
     * Instance of the config helper
     * @var ride\library\config\ConfigHelper
     */
    protected $configHelper;

    /**
     * Constructs a new config LocaleIO
     * @param ride\library\config\Config $config
     * @param ride\library\config\ConfigHelper $configHelper
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
     * @return ride\library\i18n\locale\Locale
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