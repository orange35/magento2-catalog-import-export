<?php

namespace Orange35\CatalogImportExport\Model\Export;

use Magento\Catalog\Model\Product\Option\Value;

interface ProductInterface
{
    public function getCustomOptionValueAdditionalFields(Value $value);
}
