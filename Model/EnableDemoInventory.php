<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\MsiInventorySampleDataDemo\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use MagentoEse\MsiInventorySampleDataDemo\Setup\Patch\Data\InstallDemoInventory;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

class EnableDemoInventory
{

    /** @var InstallDemoInventory  */
    protected $installDemoInventory;

    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /** @var SourceRepositoryInterface  */
    protected $sourceRepository;


    /** @var SearchCriteriaBuilder  */
    protected $searchCriteria;

    /** @var SourceInterface  */
    protected $sourceInterface;

    /** @var StockRepositoryInterface  */
    protected $stockRepository;

    /** @var SalesChannelInterfaceFactory  */
    protected $salesChannelInterface;

    public function __construct(
        InstallDemoInventory $installDemoInventory,
        SampleDataContext $sampleDataContext,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteria,
        SourceInterface $sourceInterface,
        StockRepositoryInterface $stockRepository,
        SalesChannelInterfaceFactory $salesChannelInterface
    )
    {
        $this->installDemoInventory = $installDemoInventory;
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteria = $searchCriteria;
        $this->sourceInterface = $sourceInterface;
        $this->stockRepository = $stockRepository;
        $this->salesChannelInterface = $salesChannelInterface;
    }

    //Take data from the original install MsiSourceStockSampleData
    public function apply(){
        //enable stocks
        $this->enableSources(['Magento_MsiSourceStockSampleData::fixtures/sources.csv']);

        //set sales channel
        $this->setSalesChannel(['Magento_MsiSourceStockSampleData::fixtures/stock.csv']);

        //reload inventory
        $this->installDemoInventory->apply();
    }


    public function enableSources(array $fixtures){
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            //get sources except for default
            $allSources = $this->getSources();

            foreach($allSources as $source){

                foreach ($rows as $row) {
                    $data = [];
                    foreach ($row as $key => $value) {
                        $data[$header[$key]] = $value;
                    }
                    if($source->getSourceCode()==$data['source_code']){
                        $source->setEnabled($data['enabled']);
                        $this->sourceRepository->save($source);
                    }
                }
            }
       }
    }



    public function setSalesChannel(array $fixtures){
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);
            $stocks = $this->getStocks();

            foreach($stocks as $stock) {
                foreach ($rows as $row) {
                    $data = [];
                    foreach ($row as $key => $value) {
                        $data[$header[$key]] = $value;
                    }
                    $stockName = $stock->getName();
                    if ($data['stock_name'] == $stockName) {
                        $stockId = $stock->getStockId();
                        $stock = $this->stockRepository->get($stockId);
                        $extensionAttributes = $stock->getExtensionAttributes();
                        $salesChannel = $this->salesChannelInterface->create();
                        $salesChannel->setCode($data['in_channel']);
                        $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
                        $salesChannels[] = $salesChannel;
                        $extensionAttributes->setSalesChannels($salesChannels);
                        $this->stockRepository->save($stock);
                    }

                }
            }
        }
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\SourceInterface[]
     */
    public function getSources(){
        $search = $this->searchCriteria
            ->addFilter(SourceInterface::SOURCE_CODE, 'default', 'neq')->create();
        $allSources = $this->sourceRepository->getList($search)->getItems();
        return($allSources);
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\StockInterface[]
     */
    public function getStocks(){
        $search = $this->searchCriteria
            ->addFilter(StockInterface::NAME, 'Default Stock', 'neq')->create();
        $allStock = $this->stockRepository->getList($search)->getItems();
        return($allStock);
    }
}