<?php

namespace Orange35\CatalogImportExport\Model\Import\Product;

use Magento\CatalogImportExport\Model\Import\Product\Option as MagentoOption;

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
            self::COLUMN_ROW_TITLE => '',
            self::COLUMN_ROW_PRICE => ''
        ];
        if (isset($optionRow['_custom_option_store'])) {
            $result[self::COLUMN_STORE] = $optionRow['_custom_option_store'];
        }
        if (isset($optionRow['required'])) {
            $result[self::COLUMN_IS_REQUIRED] = $optionRow['required'];
        }
        if (isset($optionRow['sku'])) {
            $result[self::COLUMN_ROW_SKU] = $optionRow['sku'];
            $result[self::COLUMN_PREFIX . 'sku'] = $optionRow['sku'];
        }
        if (isset($optionRow['option_title'])) {
            $result[self::COLUMN_ROW_TITLE] = $optionRow['option_title'];
        }

        if (isset($optionRow['price'])) {
            $percent_suffix = '';
            if (isset($optionRow['price_type']) && $optionRow['price_type'] == 'percent') {
                $percent_suffix = '%';
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

    /**
     * Workaround to make method public
     */
    protected function _getSpecificTypeData(array $rowData, $optionTypeId, $defaultStore = true)
    {
        return $this->getSpecificTypeData($rowData, $optionTypeId, $defaultStore);
    }

    /**
     * Allow to override by plugin
     */
    public function getSpecificTypeData(array $rowData, $optionTypeId, $defaultStore = true)
    {
        return parent::_getSpecificTypeData($rowData, $optionTypeId, $defaultStore);
    }

    /**
     * Workaround to make method public
     */
    protected function _getOptionData(array $rowData, $productId, $optionId, $type)
    {
        return $this->getOptionData($rowData, $productId, $optionId, $type);
    }

    /**
     * Allow to override by plugin
     */
    public function getOptionData(array $rowData, $productId, $optionId, $type)
    {
        return parent::_getOptionData($rowData, $productId, $optionId, $type);
    }
}
