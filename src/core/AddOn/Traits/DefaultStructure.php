<?php

namespace Ravand\Core\AddOn\Traits;

trait DefaultStructure
{
    private $root;

    public function getRootDirectory()
    {
        return $this->root ?? ($this->root = getcwd()."/..");
    }

    public function getRoutesDirectory()
    {
        return $this->getRootDirectory() . "/routes";
    }

    public function getAssetsDirectory()
    {
        return $this->getRootDirectory() . "/assets";
    }

    public function getAdminRouter()
    {
        return $this->getRootDirectory() . "/router/admin.php";
    }

    public function getApiRouter()
    {
        return $this->getRootDirectory() . "/router/api.php";
    }

    public function autoloadPackages()
    {
        require_once $this->getRootDirectory() . '/vendor/autoload_packages.php';
    }
}
