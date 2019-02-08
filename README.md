# Description

Module extends the **magento/module-catalog-import-export** to allow overriding custom options import/export functionality. 

The following methods are public and available for plugins:

**Orange35\CatalogImportExport\Model\Import\Product\Option:**

- `processOptionRow(...)`
- `getSpecificTypeData(...)` 
- `getOptionData(...)`

**Orange35\CatalogImportExport\Model\Export\Product:**

- `getCustomOptionAdditionalFields(\Magento\Catalog\Model\Product\Option $option)`
- `getCustomOptionValueAdditionalFields(\Magento\Catalog\Model\Product\Option\Value $value)` 

# Install

## Install via composer (recommended)
    cd ~/public_html/
    composer require orange35/magento2-catalog-import-export
    php bin/magento setup:upgrade

**Note:** the `~/public_html/` is a project root directory which may be different in your environment

## Manual installation using zip

    cd ~/public_html/
    wget https://github.com/orange35/magento2-catalog-import-export/archive/1.0.0.zip
    mkdir -p app/code/Orange35/CatalogImportExport
    upzip 1.0.0.zip -d app/code/Orange35/CatalogImportExport
    rm -f 1.0.0.zip
    php bin/magento module:enable Orange35_CatalogImportExport
    php bin/magento setup:upgrade

## Manual installation using git

    cd ~/public_html/
    mkdir -p app/code/Orange35
    cd app/code/Orange35/
    git clone https://github.com/orange35/magento2-catalog-import-export CatalogImportExport
    cd -
    php bin/magento module:enable Orange35_CatalogImportExport
    php bin/magento setup:upgrade
