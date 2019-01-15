<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\MsiInventorySampleDataDemo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\MsiInventorySampleData\Model\InstallInventoryData as SampleData;
use Magento\MsiInventorySampleData\Model\MoveInventoryFromDefault as MoveInventory;
use MagentoEse\MsiInventorySampleDataDemo\Setup\SetSession;


class InstallDemoInventory implements DataPatchInterface
{

    /** @var ModuleDataSetupInterface  */
    protected $moduleDataSetup;

    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /** @var SampleData  */
    protected $sampleData;

    /** @var MoveInventory  */
    protected $moveInventory;

    /**
     * InstallDemoInventory constructor.
     * @param SampleDataContext $sampleDataContext
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SampleData $sampleData
     * @param MoveInventory $moveInventoryFromDefault
     * @param SetSession $setSession
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        ModuleDataSetupInterface $moduleDataSetup,
        SampleData $sampleData,
        MoveInventory $moveInventoryFromDefault,
        SetSession $setSession
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->sampleData = $sampleData;
        $this->moveInventory = $moveInventoryFromDefault;
    }


    public function apply()
    {
        $this->sampleData->addInventory(['MagentoEse_MsiInventorySampleDataDemo::fixtures/luma_msi_inventory.csv']);
        $this->sampleData->addInventory(['MagentoEse_MsiInventorySampleDataDemo::fixtures/venia_msi_inventory.csv']);
        $this->moveInventory->transfer('us_warehouse');
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [InstallDemoInventory::class];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [//SetSession::class

        ];
    }

}