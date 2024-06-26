<?php

namespace ride\application\i18n\translator\io;

use ride\library\i18n\translator\io\TranslationIO;
use ride\library\system\file\File;

/**
 * Cache decorator for another TranslationIO. This I/O will get the
 * translations from the wrapped I/O and generate a PHP script to include. When
 * the generated PHP script exists, this will be used to define the
 * translations. It should be faster since only 1 include is done which
 * contains plain PHP variable initialization.
 */
class CachedTranslationIO implements TranslationIO {

    /**
     * TranslationIO which is cached by this TranslationIO
     * @var \ride\library\i18n\translator\io\TranslationIO
     */
    private $io;

    /**
     * Directory to write the cache to
     * @var \ride\library\system\file\File
     */
    private $directory;

    /**
     * Loaded translations
     * @var array
     */
    private $translations;

    private  $needsWrite;

    /**
     * Constructs a new cached TranslationIO
     * @param TranslationIO $io TranslationIO which needs a cache
     * @param \ride\library\system\file\File $directory Directory for the cache
     * @return null
     */
    public function __construct(TranslationIO $io, File $directory) {
        $this->setDirectory($directory);
        $this->io = $io;
        $this->translations = array();
        $this->needsWrite = array();
    }

    /**
     * Destruction of the cached ConfigIO
     * @return null
     */
    public function __destruct() {
        if ($this->needsWrite) {
            foreach ($this->needsWrite as $locale => $null) {
                $this->warmCache($locale);
            }
        }
    }

    /**
     * Sets the directory for the generated code
     * @param \ride\library\system\file\File $directory The directory to generate
     * the code in
     * @return null
     */
    public function setDirectory(File $directory) {
        $this->directory = $directory;
    }

    /**
     * Gets the directory for the generated code
     * @return \ride\library\system\file\File The directory to generate the code
     * in
     */
    public function getDirectory() {
        return $this->directory;
    }

    /**
     * Gets the cache file for a specific locale
     * @param string $locale Code of the locale
     * @return \ride\library\system\file\File
     */
    public function getFile($locale) {
        return $this->directory->getChild('translations-' . $locale . '.php');
    }

    /**
     * Sets a translation for the provided locale
     * @param string $localeCode Code of the locale
     * @param string $key Key of the translation
     * @param string|null $translation
     * @return null
     */
    public function setTranslation($localeCode, $key, $translation = null) {
        $this->io->setTranslation($localeCode, $key, $translation);

        $this->clearCache($localeCode);
        $this->needsWrite[$localeCode] = true;

        if (isset($this->translations[$localeCode])) {
            unset($this->translations[$localeCode]);
        }
    }

    /**
     * Gets a translation for the provided locale code
     * @param string $localeCode Code of the locale
     * @param string $key Key of the translation
     * @return string|null A string when found, null otherwise
     */
    public function getTranslation($localeCode, $key) {
        if (!isset($this->translations[$localeCode])) {
            $this->getTranslations($localeCode);
        }

        if (!isset($this->translations[$localeCode][$key])) {
            return null;
        }

        return $this->translations[$localeCode][$key];
    }

    /**
     * Gets all the translations for the provided locale
     * @param string $localeCode Code of the locale
     * @return array An associative array with translation key - value pairs
     */
    public function getTranslations($localeCode) {
        if (isset($this->translations[$localeCode])) {
            return $this->translations[$localeCode];
        }

        $file = $this->getFile($localeCode);
        if ($file->exists()) {
            // the generated script exists, include it
            require($file);

            if (isset($translations)) {
                // the script defined translations, return it
                $this->translations[$localeCode] = $translations;

                return $this->translations[$localeCode];
            }
        }

        // we have no translations, use the wrapped I/O to get them
        $this->translations[$localeCode] = $this->io->getTranslations($localeCode);

        // return the translations
        return $this->translations[$localeCode];
    }

    /**
     * Warms the cache of the translator
     * @return array An associative array with translation key - value pairs
     */
    public function warmCache($localeCode) {
        if (!isset($this->translations[$localeCode])) {
            $this->translations[$localeCode] = $this->io->getTranslations($localeCode);
        }

        // generate the PHP code for the obtained translations
        $php = $this->generatePhp($this->translations[$localeCode]);

        // obtain the file for the requested locale
        $file = $this->getFile($localeCode);

        // make sure the parent directory of the script exists
        $parent = $file->getParent();
        $parent->create();

        // write the PHP code to file
        $file->write($php);

        // return the translations
        return $this->translations[$localeCode];
    }

    /**
     * Clears the cache of the dependency container
     * @param string $localeCode Set code to clear the cache of a specific
     * locale
     * @return null
     */
    public function clearCache($localeCode = null) {
        if ($localeCode !== null) {
            // specific locale
            $file = $this->getFile($localeCode);
            if ($file->exists()) {
                $file->delete();
            }
        } elseif ($this->directory->exists()) {
            // all locales
            $this->directory->delete();
        }
    }

    /**
     * Generates a PHP source file for the provided translations
     * @param array $translations Array with the translations
     * @return string
     */
    protected function generatePhp(array $translations) {
        $output = "<?php\n\n";
        $output .= "/*\n";
        $output .= " * This file is generated by ride\application\i18n\CachedTranslationIO.\n";
        $output .= " */\n";
        $output .= "\n";
        $output .= '$translations = ' . var_export($translations, true) . ';';

        return $output;
    }

}