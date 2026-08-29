<?php

namespace Okay\Modules\AntGemini\GithubDeploy\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\Request;
use Okay\Core\Response;
use Okay\Core\Translit;
use Okay\Modules\AntGemini\GithubDeploy\Helpers\DeployHelper;

class GithubDeployAdmin extends IndexAdmin
{
    public function fetch(DeployHelper $deployHelper)
    {
        $currentBuildKey = $this->settings->get('deploy_build_key');
        if (empty($currentBuildKey)) {
            $this->settings->set('deploy_build_key', md5(microtime()));
        }
        
        $this->design->assign('new_migrations', $deployHelper->getNewMigrations());
        
        $this->response->setContent($this->design->fetch('github_deploy.tpl'));
    }
    
    public function createMigration(DeployHelper $deployHelper)
    {
        $result = false;
        
        if ($migrationName = $this->request->post('migration_name')) {
            $migrationName = str_replace(' ', '_', $migrationName);
            $migrationName = Translit::translit($migrationName);
            $result = $deployHelper->createMigration($migrationName);
        }
        
        $this->response->setContent(json_encode($result), RESPONSE_JSON);
    }
    
    public function saveChannel()
    {
        $this->settings->set('deploy_build_channel', $this->request->post('channel'));
        $this->response->setContent(true, RESPONSE_JSON);
    }

    public function saveSecret()
    {
        $this->settings->set('deploy_github_secret', trim($this->request->post('secret')));
        $this->response->setContent(true, RESPONSE_JSON);
    }
    
    public function executeMigrations(DeployHelper $deployHelper)
    {
        $deployHelper->executeMigrations();
        $this->response->setContent(true, RESPONSE_JSON);
    }
    
    public function updateProject(DeployHelper $deployHelper)
    {
        $channel = $this->settings->get('deploy_build_channel');
        if ($this->request->post('update') && $channel) {
            $deployHelper->updateProject($channel);
        }
        Response::redirectTo(Request::getDomainWithProtocol() . $this->request->url(['controller' => 'AntGemini.GithubDeploy.GithubDeployAdmin']));
    }
}
