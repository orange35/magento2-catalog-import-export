# Description

Module extends the **magento/module-catalog-import-export** to allow overriding custom option value import/export functionality:
- adds to csv export additional non standard magento fields (if there are any) from `catalog_product_option_type_value` table 
- the following methods are public which means available for plugins - `Magento\CatalogImportExport\Model\Import\Product\Option`:
  - `processOptionRow(...)`
  - `getSpecificTypeData(...)` 

# Install

## Install via composer (recommended)
```bash
cd ~/public_html/
composer require orange35/module-catalog-import-export
php bin/magento setup:upgrade
```
**Note:** the `~/public_html/` is a project root directory which may be different in your environment

## Manual installation using zip
```bash
cd ~/public_html/
wget https://github.com/orange35/module-catalog-import-export/archive/1.0.0.zip
mkdir -p app/code/Orange35/CatalogImportExport
upzip 1.0.0.zip -d app/code/Orange35/CatalogImportExport
rm -f 1.0.0.zip
php bin/magento setup:upgrade
```

## Manual installation using git
```bash
cd ~/public_html/
mkdir -p app/code/Orange35
cd app/code/Orange35/
git clone https://github.com/orange35/module-catalog-import-export CatalogImportExport
cd -
php bin/magento setup:upgrade
```
