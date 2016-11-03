<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
use Symfony\Component\Yaml\Yaml;

class Config extends ComponentAbstract
{

    protected $alias = 'config';
    protected $name = 'Configuration';
    protected $description = 'Component to set the store/system configuration values';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configResource;

    /**
     * @var ConfigInterface
     */
    protected $scopeConfig;

    public function __construct(LoggingInterface $log, ObjectManagerInterface $objectManager)
    {
        parent::__construct($log, $objectManager);

        $this->configResource = $this->objectManager->create(\Magento\Config\Model\ResourceModel\Config::class);
        $this->scopeConfig = $this->objectManager->create(\Magento\Framework\App\Config::class);
    }

    protected function canParseAndProcess()
    {
        $path = BP . '/' . $this->source;
        if (!file_exists($path)) {
            throw new ComponentException(
                sprintf("Could not find file in path %s", $path)
            );
        }
        return true;
    }

    protected function parseData($source = null)
    {

        try {
            if ($source == null) {
                throw new ComponentException(
                    sprintf('The %s component requires to have a file source definition.', $this->alias)
                );
            }

            $parser = new Yaml();
            return $parser->parse(file_get_contents($source));
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    protected function processData($data = null)
    {
        try {
            $validScopes = array('global', 'websites', 'stores');
            foreach ($data as $scope => $configurations) {

                if (!in_array($scope, $validScopes)) {
                    throw new ComponentException(sprintf("This is not a valid scope '%s' in your config.", $scope));
                }

                if ($scope == "global") {
                    foreach ($configurations as $configuration) {
                        $this->setGlobalConfig($configuration['path'], $configuration['value']);
                    }
                }

                if ($scope == "websites") {
                    foreach ($configurations as $code => $websiteConfigurations) {
                        foreach ($websiteConfigurations as $configuration) {
                            $this->setWebsiteConfig($configuration['path'], $configuration['value'], $code);
                        }
                    }
                }

                if ($scope == "stores") {
                    foreach ($configurations as $code => $storeConfigurations) {
                        foreach ($storeConfigurations as $configuration) {
                            $this->setStoreConfig($configuration['path'], $configuration['value'], $code);
                        }
                    }
                }
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function setGlobalConfig($path, $value, $encrypted = 0)
    {
        try {

            // Encrypted not supported at the moment
            if ($encrypted) {
                throw new ComponentException("There is no encryption support just yet");
            }

            // Check existing value, skip if the same
            $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

            // Save the config
            $this->configResource->saveConfig($path, $value, $scope, 0);
            $this->log->logInfo(sprintf("Global Config: %s = %s", $path, $value));

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function setWebsiteConfig($path, $value, $code, $encrypted = 0)
    {
        try {

            if ($encrypted) {
                throw new ComponentException("There is no encryption support just yet");
            }

            $logNest = 1;
            $scope = 'websites';

            // Prepare Website ID
            $websiteFactory = new WebsiteFactory($this->objectManager, \Magento\Store\Model\Website::class);
            $website = $websiteFactory->create();
            $website->load($code, 'code');
            if (!$website->getId()) {
                throw new ComponentException(sprintf("There is no website with the code '%s'", $code));
            }

            // Save the config
            $this->configResource->saveConfig($path, $value, $scope, $website->getId());
            $this->log->logInfo(sprintf("Website '%s' Config: %s = %s", $code, $path, $value), $logNest);

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function setStoreConfig($path, $value, $code, $encrypted = 0)
    {
        try {

            if ($encrypted) {
                throw new ComponentException("There is no encryption support just yet");
            }

            $logNest = 2;
            $scope = 'stores';

            $storeFactory = new StoreFactory($this->objectManager, \Magento\Store\Model\Store::class);
            $storeView = $storeFactory->create();
            $storeView->load($code, 'code');
            if (!$storeView->getId()) {
                throw new ComponentException(sprintf("There is no store view with the code '%s'", $code));
            }

            $this->configResource->saveConfig($path, $value, $scope, $storeView->getId());
            $this->log->logInfo(sprintf("Store '%s' Config: %s = %s", $code, $path, $value), $logNest);

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }

    }
}
