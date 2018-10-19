<?php
/** @noinspection PhpUnusedParameterInspection */

namespace Orange35\CatalogImportExport\Model\Export;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\CatalogImportExport\Model\Export\Product as MagentoProduct;
use Magento\Store\Model\Store;

class Product extends MagentoProduct implements ProductInterface
{
    /**
     * The whole method is overridden just to call injectCustomOptionValueAdditionalFields() method
     * @param int[] $productIds
     * @return array
     */
    // phpcs:ignore MEQP2.PHP.ProtectedClassMember.FoundProtected
    protected function getCustomOptionsData($productIds)
    {
        $customOptionsData = [];

        foreach (array_keys($this->_storeIdToCode) as $storeId) {
            $options = $this->_optionColFactory->create();
            /* @var \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $options*/
            $options->reset()->addOrder(
                'sort_order',
                \Magento\Catalog\Model\ResourceModel\Product\Option\Collection::SORT_ORDER_ASC
            )->addTitleToResult(
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
                if (Store::DEFAULT_STORE_ID === $storeId) {
                    $row['required'] = $option['is_require'];
                    $row['price'] = $option['price'];
                    $row['price_type'] = ($option['price_type'] === 'percent') ? 'percent' : 'fixed';
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
                }
                $row = array_merge($row, $this->getCustomOptionAdditionalFields($option));
                $values = $option->getValues();

                if ($values) {
                    foreach ($values as $value) {
                        $row['option_title'] = $value['title'];
                        if (Store::DEFAULT_STORE_ID === $storeId) {
                            $row['option_title'] = $value['title'];
                            $row['price'] = $value['price'];
                            $row['price_type'] = ($value['price_type'] === 'percent') ? 'percent' : 'fixed';
                            $row['sku'] = $value['sku'];
                        }
                        $row = array_merge($row, $this->getCustomOptionValueAdditionalFields($value));
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
     * This method can be overridden by plugin
     * @param Option $option
     * @return array like ['my_field' => 'my_value']
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
    public function getCustomOptionAdditionalFields(Option $option)
    {
        return [];
    }

    /**
     * This method can be overridden by plugin
     * @param Value $value
     * @return array like ['my_field' => 'my_value']
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
    public function getCustomOptionValueAdditionalFields(Value $value)
    {
        return [];
    }
}
