<?php

namespace Okay\Modules\AntGemini\GithubDeploy\Helpers;

use Okay\Core\Database;
use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Request;
use Okay\Core\ServiceLocator;
use Okay\Core\Settings;
use Okay\Modules\AntGemini\GithubDeploy\Entities\MigrationsEntity;

class DeployHelper
{
    private $request;
    private $settings;
    private $database;
    private $entityFactory;
    
    private $migrationDir;
    
    public function __construct(Request $request, Settings $settings, Database $database, EntityFactory $entityFactory)
    {
        $this->request = $request;
        $this->settings = $settings;
        $this->database = $database;
        $this->entityFactory = $entityFactory;
        
        $this->migrationDir = dirname(__DIR__) . '/migrations/';
    }

    public function executeHook($channel): bool
    {
        $rawPost = $this->request->post();
        $requestBody = json_decode($rawPost);
        
        $branch = $this->getBranch($channel);

        $currentChannel = $this->settings->get('deploy_build_channel');
        if ($channel != $currentChannel) {
            return false;
        }

        // Проверка подписи GitHub Webhook Secret
        $secret = $this->settings->get('deploy_github_secret');
        if (!empty($secret)) {
            if (!$this->verifyGithubSignature($rawPost, $secret)) {
                $this->settings->set('deploy_last_status_text', date("d.m.Y H:i:s") . PHP_EOL . 'GitHub webhook signature verification failed.');
                return false;
            }
        }
        
        if ($requestBody === null) {
            $this->settings->set('deploy_last_status_text', date("d.m.Y H:i:s") . PHP_EOL . 'Empty request from GitHub');
            return false;
        }

        // Поддержка пинг-запроса от GitHub при сохранении вебхука
        if (isset($requestBody->zen)) {
            $this->settings->set('deploy_last_status_text', date("d.m.Y H:i:s") . PHP_EOL . 'Ping event from GitHub: ' . $requestBody->zen);
            return true;
        }

        $ref = $requestBody->ref ?? '';
        if (empty($ref) || strpos($ref, 'refs/heads/') !== 0) {
            return false;
        }

        $pushBranch = str_replace('refs/heads/', '', $ref);

        // Если прилетел хук, но пушили не в ветку, которую мы "слушаем", phing запускать не нужно
        if ($pushBranch != $branch) {
            return false;
        }
        
        $this->updateProject($branch);
        return true;
    }

    private function verifyGithubSignature($rawPost, $secret): bool
    {
        $signatureHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        if (empty($signatureHeader)) {
            return false;
        }

        list($algo, $hash) = explode('=', $signatureHeader, 2) + [null, null];
        if ($algo !== 'sha256' || empty($hash)) {
            return false;
        }

        $calculatedHash = hash_hmac('sha256', $rawPost, $secret);
        return hash_equals($hash, $calculatedHash);
    }
    
    public function updateProject($branch)
    {
        if (!$pathToPhp = $this->settings->get('path_to_php')) {
            $constants = get_defined_constants();
            if (isset($constants['PHP_BINDIR'])) {
                $pathToPhp = rtrim($constants['PHP_BINDIR'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }
        }
        
        $dir = dirname(__DIR__);
        exec("{$pathToPhp}php {$dir}/bin/phing.phar -f {$dir}/build.xml -Dbranch=\"{$branch}\" -Dphp_path=\"{$pathToPhp}\"", $output);
        $deployLog = date("d.m.Y H:i:s")
            . PHP_EOL
            . implode(PHP_EOL, $output);
        $this->settings->set('deploy_last_status_text', $deployLog);
    }

    /**
     * Метод выполняет все новые миграции
     * @throws \Exception
     */
    public function executeMigrations()
    {
        /** @var MigrationsEntity $migrationsEntity */
        $migrationsEntity = $this->entityFactory->get(MigrationsEntity::class);
        
        if ($newMigrations = $this->getNewMigrations()) {
            foreach ($newMigrations as $migration) {
                $this->database->restore($migration['full_path']);
                $migrationsEntity->add(['name' => $migration['name']]);
            }
        }
    }
    
    public function getNewMigrations()
    {
        $newMigrations = [];
        /** @var MigrationsEntity $migrationsEntity */
        $migrationsEntity = $this->entityFactory->get(MigrationsEntity::class);

        $migrationsCount = $migrationsEntity->count();
        
        $alreadyExecuted = $migrationsEntity->cols(['name'])->find([
            'limit' => $migrationsCount,
        ]);
        
        if (is_dir($this->migrationDir)) {
            foreach (glob($this->migrationDir . "*.up.sql") as $path) {
                $file = pathinfo($path, PATHINFO_BASENAME);
                if (!in_array($file, $alreadyExecuted)) {
                    $newMigrations[] = [
                        'full_path' => $path,
                        'name' => $file,
                    ];
                }
            }
        }
        return ExtenderFacade::execute(__METHOD__, $newMigrations, func_get_args());
    }
    
    public function createMigration($name)
    {
        if (!is_dir($this->migrationDir)) {
            mkdir($this->migrationDir, 0755, true);
        }
        $migrationName = date("YmdHis") . (empty($name) ? '' : '_'.$name) . ".up.sql";
        fclose(fopen($this->migrationDir . $migrationName, "w"));
        return ExtenderFacade::execute(__METHOD__, $migrationName, func_get_args());
    }
    
    public function getBranch($channel)
    {
        $branch = null;
        switch ($channel) {
            case 'dev':
                $branch = 'dev';
                break;
            case 'production':
                $branch = 'production';
                break;
        }
        return ExtenderFacade::execute(__METHOD__, $branch, func_get_args());
    }

    public function updateModules() : bool
    {
        $SL = ServiceLocator::getInstance();

        $entityFactory = $SL->getService('Okay\Core\EntityFactory');
        $modulesEntity = $entityFactory->get('Okay\Entities\ModulesEntity');
        $installer = $SL->getService('Okay\Core\Modules\Installer');

        if (!$modules = $modulesEntity->find()) {
            return false;
        }
        foreach ($modules as $module) {
            $installer->update((int)$module->id);
        }

        return true;
    }
}
