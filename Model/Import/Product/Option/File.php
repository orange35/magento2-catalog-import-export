<?php

namespace Orange35\CatalogImportExport\Model\Import\Product\Option;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Uploader as FileUploader;
use Magento\CatalogImportExport\Model\Import\Product\Option;
use Psr\Log\LoggerInterface;
use Throwable;

class File implements FileInterface
{
    private $filesystem;
    private $logger;

    public function __construct(Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public function import(array $rowData, $key, $importPath, $destinationPath)
    {
        /**
         * Workaround to store custom option title in static variable and use it later in a log file.
         * The issue is magento clears a title for a second and next options. Why? Have no idea. It's magento.
         *
         * vendor/magento/module-catalog-import-export/Model/Import/Product/Option.php
         *     protected function _getMultiRowFormat($rowData)
         *     {
         *         ...
         *                 $name = '';
         *         ...
         *     }
         *
         */
        static $optionTitle;
        if (!empty($rowData[Option::COLUMN_TITLE])) {
            $optionTitle = $rowData[Option::COLUMN_TITLE];
        }
        /**
         * Workaround end
         */

        if (empty($rowData[$key])) {
            return null;
        }
        $file = $rowData[$key];

        $source = $importPath . '/' . ltrim($file, '/');

        $file = basename($file);
        $file = FileUploader::getCorrectFileName($file);
        $file = strtolower($file);
        $file = FileUploader::getDispretionPath($file) . '/' . $file;

        $destination = $destinationPath . '/' . ltrim($file, '/');
        try {
            $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)->copyFile($source, $destination);
        } catch (\Exception $e) {
            $rowData[Option::COLUMN_TITLE] = $optionTitle;
            $this->logger->warning($this->createMessage($e, $rowData));
            $file = null;
        }
        return $file;
    }

    private function createMessage(Throwable $e, array $rowData)
    {
        $message = 'Import '
            . "[SKU={$rowData['sku']}"
            . ";OPTION={$rowData[Option::COLUMN_TITLE]}"
            . ";VALUE={$rowData[Option::COLUMN_ROW_TITLE]}"
            . "]. " . $e->getMessage()
            . PHP_EOL;

        return $message;
    }
}
