<?php

namespace Okay\Modules\AntGemini\GithubDeploy;

return [
    'AntGemini_GithubDeploy_build' => [
        'slug' => '/github_deploy/{$channel}/{$buildKey}',
        'patterns' => [
            '{$buildKey}' => '([a-f0-9]{32})',
        ],
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\BuildController',
            'method' => 'build',
        ],
        'always_active' => true,
    ],
];
