<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\MsiInventorySampleDataDemo\Model;


use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalog\Model\BulkInventoryTransfer;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Indexer\Model\IndexerFactory as Reindex;

class RevertDemoInventory
{

    /** @var BulkInventoryTransfer  */
    protected $bulkInventoryTransfer;

    /** @var SourceRepositoryInterface  */
    protected $sourceRepository;

    /** @var SourceItemRepositoryInterface  */
    protected $sourceItemRepository;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteria;

    /** @var StockRepositoryInterface  */
    protected $stockRepository;

    /** @var SalesChannelInterfaceFactory  */
    protected $salesChannelInterface;

    /** @var Reindex  */
    protected $reindex;

    /**
     * RevertDemoInventory constructor.
     * @param BulkInventoryTransfer $bulkInventoryTransfer
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilder $searchCriteria
     * @param StockRepositoryInterface $stockRepository
     * @param SalesChannelInterfaceFactory $salesChannelInterface
     * @param Reindex $reindex
     */
    public function __construct(
        BulkInventoryTransfer $bulkInventoryTransfer,
        SourceRepositoryInterface $sourceRepository,
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $searchCriteria,
        StockRepositoryInterface $stockRepository,
        SalesChannelInterfaceFactory $salesChannelInterface,
        Reindex $reindex)
    {
        $this->bulkInventoryTransfer = $bulkInventoryTransfer;
        $this->sourceRepository = $sourceRepository;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteria = $searchCriteria;
        $this->stockRepository = $stockRepository;
        $this->salesChannelInterface = $salesChannelInterface;
        $this->reindex = $reindex;
    }

    public function apply()
    {
        //get sources except for default
        $allSources = $this->getSources();

        foreach($allSources as $source){
            $sourceCode = $source->getSourceCode();

            $sourceProducts = $this->getSourceItems($sourceCode);

            if(!empty($sourceProducts)) {
                $products = [];
                foreach ($sourceProducts as $product) {
                    $products[] = $product->getSku();
                }

                //move products to default source
                $this->bulkInventoryTransfer->execute($products, $sourceCode, 'default', true);

                //disable the source
                $source->setEnabled(false);
                $this->sourceRepository->save($source);
            }
        }
        //assign default stock to website
        $this->setDefaultStockSalesChannel();
        //reindex stock
        $indexer = $this->reindex->create()->load('cataloginventory_stock');
        $indexer->reindexAll();
        echo "fin";
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\SourceInterface[]
     */
    public function getSources()
    {
        $search = $this->searchCriteria
            ->addFilter(SourceInterface::SOURCE_CODE, 'default', 'neq')->create();
        $allSources = $this->sourceRepository->getList($search)->getItems();
        return($allSources);
    }

    /**
     * @param $sourceCode
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function getSourceItems($sourceCode)
    {
        $search = $this->searchCriteria
            ->addFilter(SourceInterface::SOURCE_CODE, $sourceCode, 'eq')->create();
        //get products that have inventory in that source
        $sourceProducts = $this->sourceItemRepository->getList($search)->getItems();
        return $sourceProducts;
    }

    public function setDefaultStockSalesChannel()
    {

        $stock = $this->stockRepository->get(1);
        $extensionAttributes = $stock->getExtensionAttributes();
        $salesChannel = $this->salesChannelInterface->create();
        $salesChannel->setCode('base');
        $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
        $salesChannels[] = $salesChannel;
        $extensionAttributes->setSalesChannels($salesChannels);
        $this->stockRepository->save($stock);
    }

}