<?php

namespace pallo\application\cache\control;

use pallo\application\i18n\translator\io\CachedTranslationIO;

use pallo\library\config\Config;
use pallo\library\i18n\translator\io\TranslationIO;

/**
 * Cache control implementation for the translations
 */
class TranslationCacheControl extends AbstractCacheControl {

    /**
     * Name of this control
     * @var string
     */
    const NAME = 'translations';

    /**
     * Instance of the translation I/O
     * @var pallo\library\i18n\translator\io\TranslationIO
     */
    private $io;

    /**
     * Instance of the configuration
     * @var pallo\library\config\Config
     */
    private $config;

    /**
     * Constructs a new translation cache control
     * @param pallo\library\i18n\translation\io\TranslationIO $io
     * @param pallo\library\config\Config $config
     * @return null
     */
    public function __construct(TranslationIO $io, Config $config) {
        $this->io = $io;
        $this->config = $config;
    }

    /**
     * Gets whether this cache can be enabled/disabled
     * @return boolean
     */
    public function canToggle() {
        return true;
    }

    /**
     * Enables this cache
     * @return null
     */
    public function enable() {
        $io = $this->config->get('system.translation.io.cache');
        if ($io) {
            return;
        }

        $io = $this->config->get('system.translation.io.default');

        $this->config->set('system.translation.io.cache', $io);
        $this->config->set('system.translation.io.default', 'cache');
    }

    /**
     * Disables this cache
     * @return null
     */
    public function disable() {
        $io = $this->config->get('system.translation.io.cache');

        $this->config->set('system.translation.io.default', $io);
        $this->config->set('system.translation.io.cache', null);
    }

    /**
     * Gets whether this cache is enabled
     * @return boolean
     */
    public function isEnabled() {
        return $this->io instanceof CachedTranslationIO;
    }

    /**
	 * Clears this cache
	 * @return null
     */
    public function clear() {
        if (!$this->isEnabled()) {
            return;
        }

        $directory = $this->io->getDirectory();
        if ($directory->exists()) {
            $directory->delete();
        }
    }

}