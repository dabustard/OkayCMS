<?php

namespace Okay\Modules\AntGemini\GithubDeploy\Init;

use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Entities\TranslationsEntity;
use Okay\Modules\AntGemini\GithubDeploy\Entities\MigrationsEntity;
use Okay\Modules\AntGemini\GithubDeploy\Extenders\BackendExtender;

class Init extends AbstractInit
{
    const PERMISSION = 'github_deploy';
    
    /**
     * @inheritDoc
     */
    public function install()
    {
        $this->setBackendMainController('GithubDeployAdmin');
        $this->migrateEntityTable(MigrationsEntity::class, [
            (new EntityField('id'))->setTypeInt(11)->setAutoIncrement(),
            (new EntityField('name'))->setTypeVarchar(1024),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->registerBackendController('GithubDeployAdmin');
        
        $this->addBackendControllerPermission('GithubDeployAdmin', self::PERMISSION);
        
        $this->registerChainExtension(
            [TranslationsEntity::class, 'getWriteLangFile'],
            [BackendExtender::class, 'getWriteLangFile']
        );

        $this->registerChainExtension(
            [TranslationsEntity::class, 'getWriteModuleLangFile'],
            [BackendExtender::class, 'getWriteLangFile']
        );

        $this->registerQueueExtension(
            [TranslationsEntity::class, 'writeThemeTranslations'],
            [BackendExtender::class, 'writeThemeTranslations']
        );

        $this->registerQueueExtension(
            [TranslationsEntity::class, 'writeModuleTranslation'],
            [BackendExtender::class, 'writeModuleTranslation']
        );
        
        $this->registerChainExtension(
            [TranslationsEntity::class, 'initOneTranslation'],
            [BackendExtender::class, 'initOneTranslation']
        );

        $this->registerChainExtension(
            [TranslationsEntity::class, 'get'],
            [BackendExtender::class, 'get']
        );
        
        $this->addBackendBlock('translation_custom_block', 'translation_custom_block.tpl');
        $this->addBackendBlock('translations_custom_block', 'translations_custom_block.tpl');
    }
}
