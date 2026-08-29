<?php

namespace Okay\Modules\AntGemini\GithubDeploy\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\Settings;
use Okay\Modules\AntGemini\GithubDeploy\Helpers\TranslationsHelper;

class BackendExtender implements ExtensionInterface
{
    /** @var TranslationsHelper */
    private $translationsHelper;
    
    /** @var Settings */
    private $settings;
    
    public function __construct(
        TranslationsHelper $translationsHelper,
        Settings $settings
    ) {
        $this->translationsHelper = $translationsHelper;
        $this->settings = $settings;
    }

    public function initOneTranslation($translations, $langLabel)
    {
        return $this->translationsHelper->addLocalTranslations($translations, $langLabel);
    }

    public function getWriteLangFile($realFile, $langLabel, $theme)
    {
        if (!($channel = $this->settings->get('deploy_build_channel')) || $channel == 'local') {
            return $realFile;
        }
        return  __DIR__ . '/../tmp/' . $langLabel . '.php';
    }

    public function writeThemeTranslations($null, ...$args)
    {
        $this->translationsHelper->writeThemeTranslations(...$args);
    }

    public function writeModuleTranslation($null, ...$args)
    {
        $this->translationsHelper->writeModuleTranslation(...$args);
    }

    public function get($result, $id)
    {
        if ($newResult = $this->translationsHelper->getLocalVar($id, $result)) {
            $result = $newResult;
        }
        return $result;
    }
}
