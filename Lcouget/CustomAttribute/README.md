
# Lcouget_CustomAttribute module

This is an example module creation development that creates a Product Custom Attribute and adds several related functionality for development practising purposes.

## Features

- Installation of Product custom attribute (`lcouget_custom_attribute`) and automatically set into `Default` Attribute Set.
- Custom Attribute is shown on Product Detail Page.
- It also includes a custon Attribute rule validation form for rule validation testing purposes only. It validates the following rules:
  - required.
  - no-whitespaces.
  - alphanumeric.
- Admin Page:
  - On admin side, custom Attribute is added on all products inside **Default** Attribute Set.
  - Feature toggle: Enable/Disable custom attribute usability and display with System Flag.
- CLI module handle:
  - There is a console command to handle module via CLI (see CLI section)

  
## Installation

### Prerequisites

- Vanilla Magento version 2.4.6 or above (tested on 2.4.7-p1 version).
- To check Magento versions, please visit [Official Adobe site](https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/system-requirements).

### Installation Instructions (manual installation)

- Install Vanilla Magento 2.4.6 or above.
- To ensure correct testing, it is recommended to install Sample Data by using this command:
  `bin/magento sampledata:deploy`. You can also create just a catalog with few products. 
- Get Custom Attribute module code from github. [(Download here)](https://github.com/lcouget/m2-customattribute/archive/refs/heads/master.zip)
- Unzip downloaded file and go inside `m2-customattribute-master` folder.
- Copy `Lcouget` folder inside your Magento instance `app/code` folder.
- At this point, you can check if module is detected bu using the command:
  - `php bin/magento module:status --disabled`
  - The module `Lcouget_CustomAttribute` should appear as disabled.
- Proceed to install the module by using this commands on your Magento `root` folder:
  - `php bin/magento module:enable Lcouget_CustomAttribute`
  - `php bin/magento setup:upgrade` 
  - `php bin/magento setup:di:compile`
  - `php bin/magento setup:static-content:deploy`
  - `php bin/magento cache:flush`
- Done! The module is ready to use!

## Usability

### Admin Changes

#### Enable Module

- **Option 1: Feature Toggle**

After Module installation, inside admin page you should see a new menu option `Lcouget`. Click on 
**Custom Attribute Settings** options an go to **General Configuration** section. Then set **Enable** option to **Yes**
and click on **Save Config** button.

<image 1>

The clear **Configuration** cache to see the changes.

- **Option 2: CLI command**

You can also enable/disable the module by using `custom-attribute:manage` CLI command (See CLI section).

#### Set Custom Attribute

By default, custom attribute is added to all products, so you can set custom attribute inside Product Attribute Page on adminhtml.

After custom attribute value set, click on Save button. You should see changes on Frontend Product Detail Page.
<image 2>

#### Attribute display
As mentioned before, custom attribute is added on Product Attributes Page.
<image 2>

This new attribute is also added on Catalog product grid.
<image 5>

### Frontend Changes

Custom attribute is shown on Product Detail Page on Frontend. It also adds a small form for
validation testing purposes only. 
<image 3>
_Note: this form doesn't change custom attribute value, it only retun an error
if some validation rule fails._

<image 4>

### CLI
There is also a Console command to execute via CLI. To see all available options, run 
    `php bin/magento custom-attribute:manage [options] new_custom_attribute_value`

<image 6>

#### Command Options

- **Update attribute - all products (default)**: With no options, the default behavior is to update custom attribute value to all products. _Note: this process could take a while to finish depending on Catalog size_. 

Example: `php bin/magento custom-attribute:manage NewValue`

- **Update attribute - selected product (sku)**: You can just update custom attribute on selected product by setting sku option.

Example: `php bin/magento custom-attribute:manage --sku=24-MB01 NewValue`

- **Feature toggle**: You can enable/disable module with --enable (or -e) and --disable (or -d) option
  
Example: `php bin/magento custom-attribute:manage --enable`

_Note: on all options, caches are automatically refreshed._

_Note 2: New attribute value is also validated with same validation rules applied on frontend._


