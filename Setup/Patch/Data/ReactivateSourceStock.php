<?php
/**
 * This is necessary to undo the RemoteDemoInventory changes in the reversedata branch.
 * This patch can be removed after all branches that have RemoteDataInventory have been updated
 */

namespace MagentoEse\MsiInventorySampleDataDemo\Setup\Patch\Data;


use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\MsiInventorySampleData\Model\InstallInventoryData as SampleData;
use Magento\MsiInventorySampleData\Model\MoveInventoryFromDefault as MoveInventory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use MagentoEse\MsiInventorySampleDataDemo\Model\Session;


class ReactivateSourceStock implements DataPatchInterface
{

    protected $sourcesToActivate =['default','us_store','us_warehouse'];

    protected $stockToEnable = 'North America';


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

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;

    /** @var SourceRepositoryInterface  */
    protected $sourceRepository;

    /** @var StockRepositoryInterface  */
    protected $stockRepository;

    /** @var SalesChannelInterfaceFactory  */
    protected $salesChannelInterface;

    /**
     * ReactivateSourceStock constructor.
     * @param SampleDataContext $sampleDataContext
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SampleData $sampleData
     * @param MoveInventory $moveInventoryFromDefault
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        ModuleDataSetupInterface $moduleDataSetup,
        SampleData $sampleData,
        MoveInventory $moveInventoryFromDefault,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceRepositoryInterface $sourceRepository,
        StockRepositoryInterface   $stockRepository,
        SalesChannelInterfaceFactory $salesChannelInterface,
        Session $session
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->sampleData = $sampleData;
        $this->moveInventory = $moveInventoryFromDefault;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceRepository = $sourceRepository;
        $this->stockRepository = $stockRepository;
        $this->salesChannelInterface = $salesChannelInterface;
    }


    public function apply()
    {
        //reactivate sources
        $this->reactivateSources();
        //set sales channel
        //add inventory
        $this->sampleData->addInventory(['MagentoEse_MsiInventorySampleDataDemo::fixtures/luma_msi_inventory.csv']);
        $this->sampleData->addInventory(['MagentoEse_MsiInventorySampleDataDemo::fixtures/venia_msi_inventory.csv']);
        $this->moveInventory->transfer('us_warehouse');

    }

    private function reactivateSources(){
        $this->searchCriteriaBuilder->addFilter('enabled', 2, 'neq');
        $search = $this->searchCriteriaBuilder->create();
        $sourcelist = $this->sourceRepository->getList($search)->getItems();
        /** @var \Magento\InventoryApi\Api\Data\SourceInterface $source */
        foreach ($sourcelist as $source){
            if (in_array($source->getSourceCode(),$this->sourcesToActivate)) {
                $source->setEnabled(1);
            }else{
                $source->setEnabled(0);
            }
                $this->sourceRepository->save($source);
        }
    }

    private function setDefaultSalesChannel(){
        $this->searchCriteriaBuilder->addFilter('name', $this->stockToEnable, 'eq');
        $search = $this->searchCriteriaBuilder->create();
        $stockList = $this->stockRepository->getList($search)->getItems();
        /** @var \Magento\InventoryApi\Api\Data\StockInterface $stock */
        $stock = $stockList[0];
        $extensionAttributes = $stock->getExtensionAttributes();
        $salesChannel = $this->salesChannelInterface->create();
        $salesChannel->setCode('base');
        $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
        $salesChannels[] = $salesChannel;
        $extensionAttributes->setSalesChannels($salesChannels);
        $this->stockRepository->save($stock);
    }


    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [InstallRefInventory::class

        ];
    }

}