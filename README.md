# Infrangible Backend Widget

Building blocks for the admin pages of the Infrangible Magento 2 modules. It provides:

- base controllers for the usual admin actions on objects: list, grid, add, edit, view, save, delete, mass delete and
  CSV export of the selected records, also as tabs inside other edit pages (for example the product edit page)
- grid blocks with helpers to add columns of many types (text, numbers, dates, options, stores, customer groups,
  categories, products, product options, EAV attributes, ...), mass actions, row actions and grouping of records
- form blocks with helpers to add fields of many types (text, number, price, discount, date, editor, image, options,
  stores, websites, customer groups, CMS pages and blocks, EAV attributes, product options, autocomplete, ...)
- selection of the visible grid columns by every admin user, and a **Reset Columns Selection** button in the account
  settings of the admin
- configuration field types for ISO dates, editors with image insertion and read-only values

The module has no admin pages of its own. Other modules use it for theirs.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.1 or newer

## Installation

```bash
composer require infrangible/m2-backend-widget
bin/magento module:enable Infrangible_BackendWidget
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy`.

## Usage

### Admin pages of objects

A module creates the controllers of its admin pages by extending the classes in
`Infrangible\BackendWidget\Controller\Backend\Object` (for example `Index`, `Grid`, `Edit`, `Save`, `Delete`,
`MassDelete`, `MassExport`) and implementing their abstract methods, which return the model and resource classes, the
routes and the messages. The grid and the form are blocks that extend `Infrangible\BackendWidget\Block\Grid` and
`Infrangible\BackendWidget\Block\Form`; they add their columns and fields in `prepareFields()`.

The blocks get their settings as layout arguments, for example `object_name`, `model_class`, `allow_add`,
`allow_edit` or the routes and parameters of the URLs (`edit_url_route`, `edit_url_params`, ...). Boolean arguments
can be given as booleans or as the strings `1`/`0`/`true`/`false`.

The controllers in `Infrangible\BackendWidget\Controller\Backend\Object\Tab` do the same for objects that are edited
in a tab of another edit page, and answer the save and delete requests with JSON.

### Grid columns

Every admin user can choose the visible columns of a grid with the **Show columns** button. The selection is kept in the
admin session. **Account Setting** in the admin has a **Reset Columns Selection** button that shows all columns again.

Grids that extend `Infrangible\BackendWidget\Block\Grid\GroupBy` can group their records by one or more columns and
show the number of records per group.

### Autocomplete fields

Autocomplete form fields search in a collection while the admin user types. The collection, the fields, the
expressions and the conditions of the search are defined by the form. The field signs them with the encryption key
of the installation, and the search only runs with signed values, so that requests can not search in other
collections or add their own SQL. Values of the found records are escaped before they are shown.

### Access

All controllers are admin controllers: the admin user has to be logged in. The Ajax controllers of this module
(column selection, autocomplete search, product options, attribute option values) can be used by every logged-in
admin user (ACL resource `Magento_Backend::admin`). The object controllers check the ACL resource that the extending
module returns in `getAclResourceName()`.

## License

Infrangible Backend Widget is licensed under the MIT License - see the LICENSE file for details.
