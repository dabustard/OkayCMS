<?php

namespace Okay\Modules\AntGemini\GithubDeploy\Controllers;

use Okay\Core\Response;
use Okay\Core\Settings;
use Okay\Modules\AntGemini\GithubDeploy\Helpers\DeployHelper;

class BuildController
{
    public function build(DeployHelper $deployHelper, Settings $settings, Response $response, $channel, $buildKey)
    {
        $settings->set('deploy_last_status_text', "");
        
        $currentBuildKey = $settings->get('deploy_build_key');
        if (empty($currentBuildKey)) {
            $settings->set('deploy_last_status_text', "Empty deploy build key. Deploy is stopped!");
            return false;
        } elseif ($buildKey != $currentBuildKey) {
            $settings->set('deploy_last_status_text', "Build key \"{$buildKey}\" is wrong. Deploy is stopped!");
            return false;
        }

        $deployHelper->executeHook($channel);
        
        $response->setContent('OK', RESPONSE_TEXT);
    }
}
