<?php

use Zend\Config;

/**
 * ConfigHelper
 *
 * @author mluex
 */
class ConfigHelper {

    protected $configData;

    /**
     * Constructor
     */
    function __construct() {
        $reader = new Zend\Config\Reader\Xml();

        if(file_exists(CONFIG_PATH . '/config.local.xml')) {
            $configPath = CONFIG_PATH . '/config.local.xml';
        } elseif(file_exists(CONFIG_PATH . '/config.xml')) {
            $configPath = CONFIG_PATH . '/config.xml';
        } else {
            $this->configData = false;
            return;
        }

        $this->configData   = $reader->fromFile($configPath);
    }

    /**
     * Checks if config is loaded
     *
     * @return bool True, if config was loaded successfully
     */
    public function configLoaded() {
        return $this->configData !== false;
    }

    /**
     * Returns config value
     *
     * @param String $route         Path to config node, e.g. general/charset
     * @param mixed  $defaultValue  This value will be returned in case of failure
     *
     * @return mixed $defaultValue, if no node was found for the given route. Otherwise returns the config value
     */
    public function getValue($route, $defaultValue = null) {
        if (empty($route)) {
            return $this->configData;
        }

        $targetHop = $this->configData;
        $hops = explode("/", $route);
        foreach ($hops as $hop) {
            if (array_key_exists($hop, $targetHop)) {
                $targetHop = $targetHop[$hop];
            } else {
                return $defaultValue;
            }
        }

        return $targetHop;
    }

}

?>