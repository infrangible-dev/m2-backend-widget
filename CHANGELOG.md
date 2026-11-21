# Changelog

## 4.0.5

### Fixed

- `Block\Form::addImageField()` and `addOptionsMultiSelectField()` ignored the object that was passed to them and always
  used the object of the form; the object of the form is now only used if none is passed.

### Changed

- The search terms of the product search and the option values of `Helper\ProductOption` are built with `array_map()`.
- The checks of the module use the current rules of the workflows (unused parameters, redundant casts and `@var` tags,
  loops that are `array_map()` calls, and the exceptions of PHP's functions that PhpStorm requires in `@throws` tags).

## 4.0.4

### Changed

- Files that import deprecated classes of Magento's legacy grid and form widgets document the reason for the whole
  file, which also covers the imports.

## 4.0.3

### Changed

- The store filter of grids escapes with Magento's escaper instead of the deprecated escape methods of blocks.
- The back button of forms no longer passes the deprecated JSON helper to its parent block.
- Every use of Magento's deprecated grid and form widgets is documented with the reason why it is needed.

## 4.0.2

### Security

- The controllers that return the custom options of products need the resource `Magento_Catalog::products`. Before,
  every admin user could call them.
- The other Ajax controllers (grid columns, option labels of attributes, autocomplete search) declare that every admin
  user may call them, as before.

## 4.0.1

### Changed

- Requires `infrangible/m2-foundation` 3.0, the module that presents Infrangible in the Magento admin.

## 4.0.0

### Security

- The autocomplete search (`infrangible_backendwidget/search/term` and `search/value`) took the collection class, SQL
  expressions and conditions from the request, so a request could search in any collection and add its own SQL. The
  autocomplete field now signs these values with the encryption key of the installation, and the search rejects
  requests whose values do not match the signature.
- The admin controllers based on `Infrangible\Core\Controller\Adminhtml\Json` and `Ajax` (column selection,
  autocomplete search, product options, attribute option values) did not require an admin login. With m2-core 5 they
  are admin actions and require a logged-in admin user.
- The grid filter of comma-separated values (`filterInSet()`) added the filter value to the SQL query without quoting.
- Grids based on `GroupBy` added the columns of the parameter `group_by` to the SQL query unchecked. Only plain column
  names are accepted now.
- The grid columns of customer names, product names, product options, categories, customer groups, descriptions and
  email templates showed the values without escaping, and the autocomplete field showed the found values as HTML. All
  values are escaped now, as are the values in the templates and the title of buttons.

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.2 or newer. Magento 2.2 to 2.4.5 and PHP 7.1 to 8.1 are no longer
  supported; use version 3.x for these.
- Requires `infrangible/m2-core` 5.0 and no longer `infrangible/m2-foundation`, which the module does not use.
- Controllers based on `Infrangible\BackendWidget\Controller\Adminhtml\Search\Base` and the autocomplete form element
  get additional constructor arguments (search helper, variables, escaper).
- All class properties have native types. Classes that extend classes of this module and redeclare their properties
  have to declare the same types. Constructors use constructor property promotion.
- The layout arguments of the grid and form blocks are converted to the types of their properties: strings, booleans
  (also given as `1`, `0`, `true` or `false`) and arrays. Arguments of another type are rejected. Missing string
  arguments are empty strings instead of `null`.
- All parameters and return values have native types. Classes that override methods of this module have to declare
  compatible types, in particular `: void` on the hooks `prepareFields()`, `followUpFields()`, `beforeSave()`,
  `afterSave()`, `beforeDelete()`, `afterDelete()`, `beforeExport()` and `afterExport()`. Adding `: void` to these
  overrides also works with version 3.x.
- The filter condition and frame callbacks of the grid columns (`add...ColumnWithFilterCondition()`,
  `add...ColumnWithFrame()`) have to be arrays of an object and a method name, as Magento requires.
- `execute()` of the object controllers (`Add`, `Delete`, `Grid`, `MassDelete`, `Save`, `Tab\Edit`) returns the
  response instead of `void`, `MassExport::execute()` returns the CSV result or the response. `Edit::execute()` returns
  the page or, after a redirect, the response (`ResultInterface|ResponseInterface`).
- The routes and parameters of the edit, view, delete, mass delete and mass export URLs of `Block\Grid` and
  `Block\Grid\Container` are declared in the trait `Block\ObjectUrlArguments`.
- The methods that add and handle the response of tab controllers moved from `Tab\Delete` and `Tab\Save` to the trait
  `Tab\Ajax`.
- Configuration errors (a missing model, collection, resource or block class) throw a `LogicException` instead of
  `Exception`; it extends `Exception`, so existing `catch (Exception)` blocks still work.

### Fixed

- Debug output (`console.debug`) was written to the browser console in tabs and tab grids.

### Changed

- Translations for 24 locales.
- Dependencies only allow patch releases of the tested minor versions (`~X.Y.Z`).
- Checks with PHPStan level 9, the Magento coding standard and unit tests on every push.
