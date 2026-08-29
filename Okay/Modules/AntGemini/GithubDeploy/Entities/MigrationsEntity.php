<?php

namespace Okay\Modules\AntGemini\GithubDeploy\Entities;

use Okay\Core\Entity\Entity;

class MigrationsEntity extends Entity
{
    protected static $fields = [
        'id',
        'name',
    ];

    protected static $defaultOrderFields = [
        'id',
    ];

    protected static $table = 'antgemini__github_migrations';
    protected static $tableAlias = 'gmi';
}
