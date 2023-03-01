<?php

namespace Opencad\App\Helpers\Config;

use Opencad\App\Helpers\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class ConfigHandler
{
    private $configs = array(); // an array to store all configurations
    private $cache = array(); // an array to store the cached values

    public function __construct($cacheDir = __DIR__ . "/../../../bin/cache/config")
    {
        $this->loadJsonConfigs(); // load JSON configurations
        $this->loadYamlConfigs(); // load YAML configurations

        if ($cacheDir !== null) {
            $this->cache = $this->loadCache($cacheDir); // load cached values if cache directory is provided
        }
    }

    // Load JSON configurations
    private function loadJsonConfigs()
    {
        $configFiles = glob(__DIR__ . '/../../../config/json/*.json');

        foreach ($configFiles as $configFile) {
            $configName = basename($configFile, '.json'); // get the configuration name from the file name
            $configJson = file_get_contents($configFile); // read the contents of the JSON file
            $config = json_decode($configJson, true); // decode the JSON string into an associative array
            $this->configs[$configName] = $config; // store the configuration in the $configs array
        }
    }

    // Load YAML configurations
    private function loadYamlConfigs()
    {
        $configFiles = glob(__DIR__ . '/../../../config/yml/*.yml');

        foreach ($configFiles as $configFile) {
            $configName = basename($configFile, '.yml'); // get the configuration name from the file name
            $configYml = file_get_contents($configFile); // read the contents of the YAML file
            $config = Yaml::parse($configYml); // parse the YAML string into an associative array
            $this->configs[$configName] = $config; // store the configuration in the $configs array
        }
    }

    // Get a configuration value from the cache or from the $configs array
    public function get(string $configName, string $key)
    {
        if (isset($this->cache[$configName][$key])) { // check if the value is already in the cache
            return $this->cache[$configName][$key]; // return the cached value
        }

        $value = $this->configs[$configName]; // get the configuration value from the $configs array

        foreach (explode('.', $key) as $k) { // loop through the nested keys
            if (!isset($value[$k])) { // if the key doesn't exist, set the value to null and break out of the loop
                $value = null;
                break;
            }
            $value = $value[$k]; // otherwise, set the value to the nested value
        }

        $this->cache[$configName][$key] = $value; // store the value in the cache
        return $value; // return the value
    }

    // Get a configuration value as an array
    public function getArray($configName, $key)
    {
        $value = $this->get($configName, $key);

        if (is_array($value)) { // check if the value is an array
            return $value; // return the array
        }

        return null; // otherwise, return null
    }
    // Resolve options based on the configuration options
    public function resolveOptions($configName, array $options)
    {
        $config = $this->get($configName, 'options'); // get the options configuration for the specified configuration name

        if (!is_array($config)) { // check if the options configuration is not an array
            throw new \InvalidArgumentException(sprintf('Configuration options for "%s" not found', $configName)); // throw an exception if the options configuration is not found
        }

        $resolver = new OptionsResolver($config); // create a new instance of OptionsResolver using the options configuration

        foreach ($options as $name => $value) { // loop through the options array
            $resolver->addOption($name, $value); // add each option to the resolver
        }

        return $resolver->getOptions(); // resolve the options and return them
    }

    // Save the cache to a PHP file
    public function saveCache($cacheDir)
    {
        $cacheFile = $cacheDir . '/config-cache.php'; // set the path to the cache file
        file_put_contents($cacheFile, "<?php\n\nreturn " . var_export($this->cache, true) . ";", FILE_APPEND); // write the cache to the cache file
    }

    // Load the cache from a PHP file
    private function loadCache($cacheDir)
    {
        $cacheFile = $cacheDir . '/config-cache.php'; // set the path to the cache file

        if (!file_exists($cacheFile)) { // check if the cache file doesn't exist
            $this->cache = array(); // set the cache to an empty array
            $this->saveCache($cacheDir); // save the empty cache to the cache file
        } else {
            $cache = require $cacheFile; // load the cache from the cache file

            if (is_array($cache)) { // check if the cache is an array
                return $cache; // return the cache
            }
        }

        return array(); // otherwise, return an empty array
    }

}
