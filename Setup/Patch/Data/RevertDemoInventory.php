<?php
/**
 * Created by PhpStorm.
 * User: jbritts
 * Date: 12/13/18
 * Time: 3:42 PM
 */

namespace MagentoEse\MsiInventorySampleDataDemo\Setup\Patch\Data;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Inventory\Api\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryApi\Api\StockRepository;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use MagentoEse\MsiInventorySampleDataDemo\Model\Session;
use Magento\Indexer\Model\IndexerFactory as Indexer;

class RevertDemoInventory implements DataPatchInterface
{

    /** @var Session  */
    protected $installSession;

    /** @var ModuleDataSetupInterface  */
    protected $moduleDataSetup;

    /** @var ResourceConnection  */
    protected $resourceConnection;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;

    /** @var StockRepositoryInterface  */
    protected $stockRepository;

    /** @var SalesChannelInterface  */
    protected $salesChannelInterface;

    /** @var SourceRepositoryInterface  */
    protected $sourceRepository;

    /** @var SourceItemRepositoryInterface  */
    protected $sourceItemRepository;

    /** @var $sourceItemInterfaceFactory */
    protected $sourceItemInterface;

    /** @var StockRegistryInterface  */
    protected $stockRegistry;

    /** @var InstallRefInventory  */
    protected $installRefInventory;


    /**
     * RevertDemoInventory constructor.
     * @param Session $session
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConnection $resourceConnection
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StockRepositoryInterface $stockRepository
     * @param SalesChannelInterfaceFactory $salesChannelInterface
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceItemInterfaceFactory $sourceItemInterfaceFactory
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        Session $session,
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resourceConnection,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StockRepositoryInterface $stockRepository,
        SalesChannelInterfaceFactory $salesChannelInterface,
        SourceRepositoryInterface $sourceRepository,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemInterfaceFactory $sourceItemInterfaceFactory,
        StockRegistryInterface $stockRegistry,
        InstallRefInventory $installRefInventory
    )
    {
        $this->installSession = $session;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConnection = $resourceConnection;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stockRepository = $stockRepository;
        $this->salesChannelInterface = $salesChannelInterface;
        $this->sourceRepository = $sourceRepository;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemInterface = $sourceItemInterfaceFactory;
        $this->stockRegistry = $stockRegistry;
        $this->installRefInventory = $installRefInventory;
    }


    public function apply()
    {

        //set the sales channel on default stock
        $this->setDefaultSalesChannel();
        //deactivate other sources
        $this->deactivateSources();
        //set inventory for items with MSI
        if($this->installRefInventory->inventoryUpdate()) {
            $this->setBaseInventory();
        }
        //transfer other inventory back to default

    }

    private function setDefaultSalesChannel(){
        $this->searchCriteriaBuilder->addFilter('name', 'Default Stock', 'eq');
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

    private function deactivateSources(){
        $this->searchCriteriaBuilder->addFilter('enabled', 1, 'eq');
        $search = $this->searchCriteriaBuilder->create();
        $sourcelist = $this->sourceRepository->getList($search)->getItems();
        /** @var \Magento\InventoryApi\Api\Data\SourceInterface $source */
        foreach ($sourcelist as $source){
            if ($source->getSourceCode()!='default') {
                $source->setEnabled(0);
                $this->sourceRepository->save($source);
            }
        }
    }

    private function setBaseInventory(){
        $search = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SOURCE_CODE,'us_store','eq')
            ->create();
        $sourceItemSearch = $this->sourceItemRepository->getList($search)->getItems();

        //now that we have the items, we can delete them
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('inventory_source_item');
        //delete rows that have store inventory
        $sql = "delete from " . $tableName . " where source_code = 'us_store'";
        $connection->query($sql);
        $idx = $this->indexer->create();
        $idx->load('cataloginventory_stock');
        $idx->reindexAll();
        /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItem */
        foreach ($sourceItemSearch as $sourceItem){
            $newSourceItem = $this->sourceItemInterface->create();
            $newSourceItem->setSku($sourceItem->getSku());
            $newSourceItem->setSourceCode('default');
            $newSourceItem->setQuantity(100);
            $newSourceItem->setStatus(1);
            $newSourceItem->save();
            //add traditional inventory
            $sku = $sourceItem->getSku();
            //need to restrict an incorrect sku that was included on original MSI update. The product doesnt exist
            echo $sku."\n";
            if($sku!='VA19-GO-NA2'){
                $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                $stockItem->setQty(100);
                $stockItem->setIsInStock(1);
                $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
            }
            //delete warehouse item
            $sql =  "delete from " . $tableName . " where sku='".$sourceItem->getSku()."' and source_code = 'us_warehouse'";
            $connection->query($sql);
        }
        //transfer rest to default
        $sql = "update " . $tableName . " set source_code = 'default'";
        $connection->query($sql);

    }

    /**
     * {@inheritdoc}
     */
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