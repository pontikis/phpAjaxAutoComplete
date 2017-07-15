# phpAjaxAutoComplete

php ajax call to create autocomplete control

Project page https://github.com/pontikis/phpAjaxAutoComplete


## Features

* Creates array with list results
* Limit results by searching each term part in different column
* Highlight results
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

```

Basic search:

![001](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/001.png)

Limit results:

![002](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/002.png)


### Search in one column

```php

```


Basic search:

![003](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/003.png)

Limit results:

![004](https://raw.githubusercontent.com/pontikis/phpAjaxAutoComplete/master/screenshots/004.png)
