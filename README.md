# phpAjaxAutoComplete

php ajax call to create autocomplete control

Copyright Christos Pontikis http://www.pontikis.net

Project page https://github.com/pontikis/phpAjaxAutoComplete

License [MIT](https://github.com/pontikis/phpAjaxAutoComplete/blob/master/LICENSE)


## Features

* Creates array with list results
* Limit results by searching each term part in different column
* Highlight results
* Sanitizes user input (using regex)
* Multilanguage
* Databases supported: MySQL (or MariaDB), PostgreSQL
* Supports case and accent insensitive search in PostgreSQL
* Prepared statements supported
* Fixed WHERE sql supported

## Dependencies

### back-end
* tested with php 5.6 and php 7
* dacapo (database abstraction - MySQL, MariaDB, PostGreSQL) - https://github.com/pontikis/dacapo

### front-end
* jquery https://jquery.com/ (tested with v3.2.1)
* jquery-ui (autocomplete) http://jqueryui.com/ (tested with v1.12.1)
* jQuery UI Autocomplete HTML Extension http://github.com/scottgonzalez/jquery-ui-extensions (optional)

## Files
 
1. ``phpAjaxAutoComplete.class.php`` php class


## Documentation

See ``docs/doxygen/html`` for html documentation of ``phpAjaxAutoComplete`` class. 


## How to use

### Search in two columns   

```php
require_once C_CLASS_PHP_AJAXAUTOCOMPLETE_PATH;
require_once C_PROJECT_PATH . '/app/common/utils.php'; // get_age() function
$options = array(
	'term' => $_GET['term'],
	'preg_match_pattern' => '/[^\040\pL]/u',
	'msg_invalid_characters_in_term' => gettext('Only letters and space are permitted') . '...',
	'select_sql' => 'SELECT p.id as id, ' .
		'CONCAT(d.lastname, \' \', d.firstname) AS value, ' .
		'CONCAT(d.lastname, \' \', d.firstname) AS label, ' .
		'd.father_name, d.photo_id, d.gravatar_email, d.date_of_birth, d.date_of_death ' .
		'FROM patients p INNER JOIN demographics d ON p.demographics_id = d.id',
	'parts_where_sql' => array(
		'd.lastname LIKE ' . $ds->getSqlPlaceholder(),
		'd.firstname LIKE ' . $ds->getSqlPlaceholder(),
	),
	'order_sql' => 'ORDER BY lastname, firstname',
	'term_parts_max' => 2,
);
$paac = new phpAjaxAutoComplete($ds, $options);
$result = $paac->createList();
// custom label format
if(!$paac->getLastError()) {
	foreach($result as $key => $row) {
		$father_name = $row['father_name'] ? ' - ' . '<strong>' . gettext('Father name') . ': ' . '</strong>' . $row['father_name'] : '';
		$age = $row['date_of_birth'] ? ' - ' . '<strong>' . gettext('Age') . ': ' . '</strong>' . get_age($row['date_of_birth'], $row['date_of_death']) : '';
		$result[$key]['label'] .= $father_name . $age;
	}
}
echo json_encode($result);
```

Basic search:

![001](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/001.png)

Limit results:

![002](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/002.png)


### Search in one column

```php
require_once C_CLASS_PHP_AJAXAUTOCOMPLETE_PATH;
$sql_placeholder = $ds->getSqlPlaceholder();
$options = array(
	'term' => $_GET['term'],
	'preg_match_pattern' => '/[^\040\pL\pN_-]/u',
	'msg_invalid_characters_in_term' => gettext('Only letters and digits, space, underscore and dash are permitted') . '...',
	'select_sql' => 'SELECT id, ' .
		'medication AS value, ' .
		'medication AS label ' .
		'FROM  kb_medication',
	'fixed_where_sql' => array(
		'(class_code IS NOT NULL OR tenant_id = ' . $sql_placeholder . ')',
	),
	'fixed_bind_params' => array($_SESSION['tenant_id']),
	'parts_where_sql' => array(
		'(medication LIKE ' . $sql_placeholder . ' OR chemical LIKE ' . $sql_placeholder . ')',
	),
	'order_sql' => 'ORDER BY medication'
);
$paac = new phpAjaxAutoComplete($ds, $options);
echo json_encode($paac->createList());
```

Basic search:

![003](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/003.png)

Limit results:

![004](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/004.png)
