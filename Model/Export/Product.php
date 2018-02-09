<?php

namespace Orange35\CatalogImportExport\Model\Export;

use Magento\Catalog\Model\Product\Option\Value;
use Magento\CatalogImportExport\Model\Export\Product as MagentoProduct;
use Magento\Store\Model\Store;

class Product extends MagentoProduct
{
    /**
     * The whole method is overridden just to call injectCustomOptionValueAdditionalFields() method
     * @param int[] $productIds
     * @return array
     */
    protected function getCustomOptionsData($productIds)
    {
        $customOptionsData = [];

        foreach (array_keys($this->_storeIdToCode) as $storeId) {
            if (Store::DEFAULT_STORE_ID != $storeId) {
                continue;
            }
            $options = $this->_optionColFactory->create();
            /* @var \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $options*/
            $options->addOrder('sort_order');
            $options->reset()->addOrder('sort_order')->addTitleToResult(
                $storeId
            )->addPriceToResult(
                $storeId
            )->addProductToFilter(
                $productIds
            )->addValuesToResult(
                $storeId
            );

            foreach ($options as $option) {
                $row = [];
                $productId = $option['product_id'];

                $row['name'] = $option['title'];
                $row['type'] = $option['type'];
                $row['required'] = $option['is_require'];
                $row['price'] = $option['price'];
                $row['price_type'] = ($option['price_type'] == 'percent') ? $option['price_type'] : 'fixed';
                $row['sku'] = $option['sku'];
                if ($option['max_characters']) {
                    $row['max_characters'] = $option['max_characters'];
                }

                foreach (['file_extension', 'image_size_x', 'image_size_y'] as $fileOptionKey) {
                    if (!isset($option[$fileOptionKey])) {
                        continue;
                    }

                    $row[$fileOptionKey] = $option[$fileOptionKey];
                }

                $values = $option->getValues();

                if ($values) {
                    foreach ($values as $value) {
                        $valuePriceType = ($value['price_type'] == 'percent') ? $value['price_type'] : 'fixed';
                        $row['option_title'] = $value['title'];
                        $row['price'] = $value['price'];
                        $row['price_type'] = $valuePriceType;
                        $row['sku'] = $value['sku'];
                        $this->injectCustomOptionValueAdditionalFields($row, $value);
                        $customOptionsData[$productId][$storeId][] = $this->optionRowToCellString($row);
                    }
                } else {
                    $customOptionsData[$productId][$storeId][] = $this->optionRowToCellString($row);
                }
                $option = null;
            }
            $options = null;
        }

        return $customOptionsData;
    }

    /**
     * @param array $row
     * @param Value $value
     * @return void
     */
    public function injectCustomOptionValueAdditionalFields(array &$row, Value $value)
    {
        $row = array_merge($row, $this->getCustomOptionValueAdditionalFields($value));
    }

    /**
     * Returns array with additional fields from catalog_product_option_type_value table
     * which can be added by other modules like Orange35_ImageConstructor or Orange35_Colopickercustom
     * @param Value $value
     * @return array
     */
    public function getCustomOptionValueAdditionalFields(Value $value)
    {
        static $meta;
        if (null === $meta) {
            $meta = $this->_connection->describeTable('catalog_product_option_type_value');
            unset($meta['option_type_id'], $meta['option_id'], $meta['sku'], $meta['sort_order']);
        }

        $data = $value->getData();
        $row = array_intersect_key($data, $meta);
        return $row;
    }
}
