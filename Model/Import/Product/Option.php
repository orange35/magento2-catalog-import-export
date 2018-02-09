<?php

namespace Orange35\CatalogImportExport\Model\Import\Product;

use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory as ProductOptionValueCollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product\Option as MagentoOption;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\File\Uploader as FileUploader;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Orange35\CatalogImportExport\Model\Import\ImportLogger;

/**
 * This class overrides parent to make the following methods public and as result - available for plugins:
 * - processOptionRow()
 * - getSpecificTypeData()
 *
 * @package Orange35\CatalogImportExport\Model\Import\Product
 *
 */
class Option extends MagentoOption
{
    /**
     * This property copied from parent since it is private and can't be used in child
     */
    private $columnMaxCharacters = '_custom_option_max_characters';

    /**
     * @var ImportLogger
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory $colIteratorFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        ProcessingErrorAggregatorInterface $errorAggregator,
        array $data = [],
        ProductOptionValueCollectionFactory $productOptionValueCollectionFactory = null,
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
        parent::__construct(
            $importData, $resource, $resourceHelper, $_storeManager, $productFactory, $optionColFactory,
            $colIteratorFactory, $catalogData, $scopeConfig, $dateTime, $errorAggregator, $data,
            $productOptionValueCollectionFactory
        );
        $this->initLogger();
    }

    private function initLogger()
    {
        $file = $this->filesystem->getDirectoryWrite(DirectoryList::LOG)
            ->getAbsolutePath('/import-custom-option.log');
        if (false === ($handle = fopen($file, 'a+'))) {
            throw new \Exception('Can not open file for write ' . $file);
        }
        $this->logger = new ImportLogger($handle);
    }


    /**
     * The method copied from child as is just to call the "processOptionRow" overridden method
     * @param array $rowData
     * @return array
     */
    protected function _getMultiRowFormat($rowData)
    {
        // Parse custom options.
        $rowData = $this->_parseCustomOptions($rowData);
        $multiRow = [];
        if (empty($rowData['custom_options'])) {
            return $multiRow;
        }

        $i = 0;
        foreach ($rowData['custom_options'] as $name => $customOption) {
            $i++;
            foreach ($customOption as $rowOrder => $optionRow) {
                $row = array_merge(
                    [
                        self::COLUMN_STORE => '',
                        self::COLUMN_TITLE => $name,
                        self::COLUMN_SORT_ORDER => $i,
                        self::COLUMN_ROW_SORT => $rowOrder
                    ],
                    $this->processOptionRow($name, $optionRow)
                );
                $name = '';
                $multiRow[] = $row;
            }
        }

        return $multiRow;
    }

    /**
     * This method just copied from parent and made public instead of private
     */
    public function processOptionRow($name, $optionRow)
    {
        $result = [
            self::COLUMN_TYPE => $name ? $optionRow['type'] : '',
            self::COLUMN_IS_REQUIRED => $optionRow['required'],
            self::COLUMN_ROW_SKU => $optionRow['sku'],
            self::COLUMN_PREFIX . 'sku' => $optionRow['sku'],
            self::COLUMN_ROW_TITLE => '',
            self::COLUMN_ROW_PRICE => '',
        ];

        if (isset($optionRow['option_title'])) {
            $result[self::COLUMN_ROW_TITLE] = $optionRow['option_title'];
        }

        if (isset($optionRow['price'])) {
            $percent_suffix = '';
            if (isset($optionRow['price_type']) && $optionRow['price_type'] == 'percent') {
                $percent_suffix =  '%';
            }
            $result[self::COLUMN_ROW_PRICE] = $optionRow['price'] . $percent_suffix;
        }

        $result[self::COLUMN_PREFIX . 'price'] = $result[self::COLUMN_ROW_PRICE];

        if (isset($optionRow['max_characters'])) {
            $result[$this->columnMaxCharacters] = $optionRow['max_characters'];
        }

        $result = $this->addFileOptions($result, $optionRow);

        return $result;
    }

    /**
     * private method can't be called from child so it is duplicated as is
     */
    private function addFileOptions($result, $optionRow)
    {
        foreach (['file_extension', 'image_size_x', 'image_size_y'] as $fileOptionKey) {
            if (!isset($optionRow[$fileOptionKey])) {
                continue;
            }

            $result[self::COLUMN_PREFIX . $fileOptionKey] = $optionRow[$fileOptionKey];
        }

        return $result;
    }

    protected function _getSpecificTypeData(array $rowData, $optionTypeId, $defaultStore = true)
    {
        return $this->getSpecificTypeData($rowData, $optionTypeId, $defaultStore);
    }

    /**
     * Allow override by plugin
     */
    public function getSpecificTypeData(array $rowData, $optionTypeId, $defaultStore = true)
    {
        return parent::_getSpecificTypeData($rowData, $optionTypeId, $defaultStore);
    }

    public function importFile(array $rowData, $key, $importPath, $destinationPath)
    {
        $optionTitle = $this->getOptionTitle($rowData);

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
            $this->logger->error($e->getMessage(), $rowData);
            $file = null;
        }
        return $file;
    }

    /**
     * Workaround to store option title in static variable and use it later in log file.
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
     * @param $rowData
     * @return mixed
     */
    private function getOptionTitle($rowData)
    {
        static $optionName;
        if (!empty($rowData[Option::COLUMN_TITLE])) {
            $optionName = $rowData[Option::COLUMN_TITLE];
        }
        return $optionName;
    }
}
