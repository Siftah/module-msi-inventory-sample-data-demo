<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\MsiInventorySampleDataDemo\Setup;

class SetSession
{

    public function __construct(\Magento\Framework\App\State $state)
    {
        try{
            $state->setAreaCode('adminhtml');
        }
        catch(\Magento\Framework\Exception\LocalizedException $e){
            // left empty
        }
    }

}
