<?php

namespace Pluguin\Database;

use \Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;

class Database extends Capsule
{
    public function __construct()
    {
        parent::__construct();

        $this->bootEloquent();

        $this->setAsGlobal();

        $this->addWordpressDatabaseConnection();

        $this->container->instance(
            MigrationRepositoryInterface::class, 
            new DatabaseMigrationRepository($this->getDatabaseManager(), 'pluguin_migrations')
        );

        $this->container->instance(
            ConnectionResolverInterface::class, 
            $this->getDatabaseManager()
        );

        $this->createMigrationTable();

    }

    public function setAsGlobal()
    {
        if (!isset(static::$instance) || !(static::$instance instanceof (static::class))) {
            parent::setAsGlobal();
        }
    }

    protected function addWordpressDatabaseConnection()
    {
        global $wpdb;
        $this->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'collation' => empty(DB_COLLATE) ? 'utf8_unicode_ci' : DB_COLLATE,
            'prefix' => $this->getWordpressTablePrefix(),
        ]);
    }

    protected function getWordpressTablePrefix()
    {
        global $wpdb;
        return $wpdb->prefix;
    }

    protected function createMigrationTable()
    {
        $repository = $this->container->make(MigrationRepositoryInterface::class);

        if(!$repository->repositoryExists())
        {
            $repository->createRepository();
        }
    }

    public function migrator()
    {
        return $this->container->make(Migrator::class);
    }
}
