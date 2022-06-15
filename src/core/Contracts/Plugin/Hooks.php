<?php

namespace Ravand\Core\Interfaces\Plugin;

/**
 * Plugin Hooks
 */
interface Hooks
{
    /**
     * This function will be called on plugin activation
     * 
     * @return void
     */
    public function activate();

    /**
     * This function will be called on plugin deactivation
     * 
     * @return void
     */
    public function deactivate();

    /**
     * This function will be called on plugin uninstall
     * 
     * @return void
     */
    public function uninstall();
}