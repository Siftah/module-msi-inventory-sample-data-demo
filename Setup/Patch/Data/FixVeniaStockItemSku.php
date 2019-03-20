<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\MsiInventorySampleDataDemo\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;



class FixVeniaStockItemSku implements DataPatchInterface
{
    /** @var ResourceConnection  */
    protected $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function apply(){
        //Unable to use the inventory API as it will throw an error when used during install/updates
        //due to magento restrictions on DDL statements within transactions
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('inventory_source_item');
            $sql = "update " . $tableName . " set sku='VA19-GO-NA' where sku = 'VA19-GO-NA2'";
            $connection->query($sql);
        }catch(\Exception $e){
            //ignore an error that would likely come from a key violation, indicating the product has already been fixed
        }
    }


    public static function getDependencies()
    {
        return [InstallDemoInventory::class];
    }

    public function getAliases()
    {
        return [];
    }
}