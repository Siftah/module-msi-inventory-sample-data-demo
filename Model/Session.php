<?php

namespace MagentoEse\MsiInventorySampleDataDemo\Model;

use Magento\Framework\App\State;

class Session
{

    public function __construct(State $state)
    {
        try{
            $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }
        catch(\Magento\Framework\Exception\LocalizedException $e){
            // left empty
        }
    }

}