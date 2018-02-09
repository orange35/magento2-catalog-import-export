<?php

namespace Orange35\CatalogImportExport\Model\Import;

use Psr\Log\AbstractLogger;
use Magento\CatalogImportExport\Model\Import\Product\Option;

class ImportLogger extends AbstractLogger
{
    protected $handle;

    public function __construct($handle)
    {
        $this->handle = $handle;
    }

    public function log($level, $message, array $context = [])
    {
        $message = date('Y-m-d H:i:s')
            . "\t[SKU={$context['sku']}"
            . ";OPTION={$context[Option::COLUMN_TITLE]}"
            . ";VALUE={$context[Option::COLUMN_ROW_TITLE]}"
            . "]\t" . $message
            . PHP_EOL;
        if ($this->handle) {
            fwrite($this->handle, $message);
        } else {
            trigger_error($message);
        }
    }
}
