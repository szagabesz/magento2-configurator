<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Magento\Framework\App\ObjectManager;
use Bex\Behat\Magento2InitExtension\Fixtures\BaseFixture;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends BaseFixture implements Context
{

    /**
     * @Given I have a yaml file which describes some websites and stores
     */
    public function iHaveAYamlFileWhichDescribesSomeWebsitesAndStores()
    {
        // file prepared in features/bootstrap/Fixtures/master.yaml
    }

    /**
     * @When I run the configurator's cli tool with :component component for :environment environment
     */
    public function iRunTheConfiguratorSCliTool($component, $environment)
    {
        $baseDir = getcwd();
        $command = sprintf(
            'bin/magento configurator:run --env=%s --component=%s',
            escapeshellarg($environment),
            escapeshellarg($component)
        );

        $importerProcess = new Process($command, $baseDir);
        $importerProcess->run();

        if (!$importerProcess->isSuccessful()) {
            throw new \RuntimeException($command . ' failed: ' . $importerProcess->getOutput());
        }
    }

    /**
     * @Then Magento database should have the desired websites and stores
     */
    public function iMagentoDatabaseShouldHaveTheDesiredWebsitesAndStores()
    {
        /** @var Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->createMagentoObject('Magento\Store\Model\StoreManager');

        $expectedWebsites = [
            'hu' => 'HU website',
            'uk' => 'UK website',
            'ch' => 'CH website',
        ];
        foreach ($expectedWebsites as $code => $name) {
            $website = $storeManager->getWebsite($code);
            if ($website->getName() !== $name) {
                throw new \Exception("Website '$name' not found");
            }
        }

        $expectedStoreViews = [
            'hu_hu' => 'HU Store View',
            'en_uk' => 'UK Store View',
            'de_ch' => 'CH Store View German Language',
            'fr_ch' => 'CH Store View French Language',
            'it_ch' => 'CH Store View Italian Language',
        ];
        foreach ($expectedStoreViews as $code => $name) {
            $store = $storeManager->getStore($code);
            if ($store->getName() !== $name) {
                throw new \Exception("Store '$name' not found");
            }
        }
    }

    protected function _getMagentoBaseDir()
    {
        $dir = $this->createMagentoObject('Magento\App\Dir');

        return $dir->getDir();
    }
}
