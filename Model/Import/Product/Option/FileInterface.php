<?php

namespace Orange35\CatalogImportExport\Model\Import\Product\Option;

interface FileInterface
{
    public function import(array $rowData, $key, $importPath, $destinationPath);
}
