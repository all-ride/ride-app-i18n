<?php

namespace ride\application\cache\control;

use ride\application\i18n\translator\io\CachedTranslationIO;

use ride\library\config\Config;
use ride\library\i18n\translator\io\TranslationIO;

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
     * @var \ride\library\i18n\translator\io\TranslationIO
     */
    private $io;

    /**
     * Instance of the configuration
     * @var \ride\library\config\Config
     */
    private $config;

    /**
     * Constructs a new translation cache control
     * @param \ride\library\i18n\translator\io\TranslationIO $io
     * @param \ride\library\config\Config $config
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
        $io = $this->config->get('system.l10n.io.default');
        if ($io == 'cache') {
            return;
        }

        $this->config->set('system.l10n.io.cache', $io);
        $this->config->set('system.l10n.io.default', 'cache');
    }

    /**
     * Disables this cache
     * @return null
     */
    public function disable() {
        $io = $this->config->get('system.l10n.io.default');
        if ($io != 'cache') {
            return;
        }

        $io = $this->config->get('system.l10n.io.cache');

        $this->config->set('system.l10n.io.default', $io);
        $this->config->set('system.l10n.io.cache', null);
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
