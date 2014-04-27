<?php

namespace ride\application\i18n\translator\io;

use ride\library\config\parser\Parser;
use ride\library\config\exception\ConfigException;
use ride\library\config\ConfigHelper;
use ride\library\i18n\exception\I18nException;
use ride\library\i18n\translator\io\AbstractTranslationIO;
use ride\library\system\file\browser\FileBrowser;
use ride\library\system\file\File;

/**
 * Parser implementation of the TranslationIO
 */
class ParserTranslationIO extends AbstractTranslationIO {

    /**
     * Custom directory to write translations
     * @var string
     */
    const DIRECTORY_CUSTOM = 'custom';

    /**
     * Browser of the file system
     * @var \ride\library\system\file\browser\FileBrowser
     */
    protected $fileBrowser;

    /**
     * Parser for the configuration files
     * @var \ride\library\config\parser\Parser
     */
    protected $parser;

    /**
     * Instance of the config helper
     * @var \ride\library\config\ConfigHelper
     */
    protected $configHelper;

    /**
     * Extension for the parser's format
     * @var string
     */
    protected $extension;

    /**
     * Path for the files
     * @var string
     */
    protected $path;

    /**
     * Constructs a new parser translation IO
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser
     * @param \ride\library\config\parser\Parser $parser
     * @param \ride\library\config\ConfigHelper $configHelper
     * @param string $file
     * @param string $path
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, Parser $parser, ConfigHelper $configHelper, $extension, $path = null) {
        $this->fileBrowser = $fileBrowser;
        $this->parser = $parser;
        $this->configHelper = $configHelper;
        $this->extension = $extension;
        $this->path = $path;
    }

    /**
     * Gets all the translations for the provided locale
     * @param string $localeCode code of the locale
     * @return array an associative array with translation key - value pairs
     */
    protected function readTranslations($localeCode) {
        $path = null;
        if ($this->path) {
            $path = $this->path . File::DIRECTORY_SEPARATOR;
        }

        $translationFile = $path . $localeCode . '.' . $this->extension;
        $translationFiles = array_reverse($this->fileBrowser->getFiles($translationFile));

        $translationFile = $this->getCustomTranslationsFile($localeCode);
        if ($translationFile->exists()) {
            $translationFiles[] = $translationFile;
        }

        return $this->getTranslationsFromFiles($translationFiles);
    }

    /**
     * Sets a translation for the provided locale
     * @param string $localeCode Code of the locale
     * @param string $key Key of the translation
     * @param string $translation Translation value
     * @return null
     * @throws \ride\library\i18n\exception\I18nException when one of the
     * provided arguments is empty or invalid
     */
    public function setTranslation($localeCode, $key, $translation = null) {
        if (!is_string($localeCode) || $localeCode == '') {
            throw new I18nException('Could not set the translation: provided locale code is empty or invalid');
        }

        if (!is_string($key) || $key == '') {
            throw new I18nException('Could not set the translation: provided translation key is empty');
        }

        $translationFile = $this->getCustomTranslationsFile($localeCode);

        if ($translationFile->exists()) {
            $translations = $this->getTranslationsFromFiles(array($translationFile));
        } else {
            $translations = array();
        }

        if ($translation === null) {
            if (isset($translations[$key])) {
                unset($translations[$key]);
            }
        } elseif (is_string($translation) && $translation != '') {
            $translations[$key] = $translation;
        } else {
            throw new I18nException('Could not set the translation: provided translation is empty or invalid');
        }

        $this->setTranslationsToFile($translationFile, $translations);
    }

    /**
     * Reads the translations from the provided files
     * @param array $translationFiles Array with File objects of translation
     * files
     * @return array Array with the translation key as array key and the
     * translation as value
     */
    protected function getTranslationsFromFiles($translationFiles) {
        $translations = array();

        foreach ($translationFiles as $translationFile) {
            try {
                $fileTranslations = $this->parser->parseToPhp($translationFile->read());
            } catch (ConfigException $exception) {
                throw new I18nException('Could not read the translations from ' . $translationFile, 0, $exception);
            }

            $fileTranslations = $this->configHelper->flattenConfig($fileTranslations);

            $translations = $fileTranslations + $translations;
        }

        return $translations;
    }

    /**
     * Writes the provided translations to the provided file
     * @param \ride\library\system\file\File $translationFile File to store the
     * translations in
     * @param array $translations Array with the translation key as array key
     * and the translation as value
     * @return null
     */
    protected function setTranslationsToFile(File $translationFile, array $translations) {
        ksort($translations);

        $translationDirectory = $translationFile->getParent();
        $translationDirectory->create();

        $translationFile->write($this->parser->parseFromPhp($translations));
    }

    /**
     * Gets the file for set translations. Keeping them separate for easy
     * synchronisation.
     * @param string $localeCode Code of the locale
     * @return \ride\library\system\file\File
     */
    protected function getCustomTranslationsFile($localeCode) {
        $path = null;
        if ($this->path) {
            $path = $this->path . File::DIRECTORY_SEPARATOR;
        }

        $path .= self::DIRECTORY_CUSTOM . File::DIRECTORY_SEPARATOR . $localeCode . '.' . $this->extension;

        return $this->fileBrowser->getApplicationDirectory()->getChild($path);
    }

}