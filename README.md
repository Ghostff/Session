# Session PHP(7+)
PHP Session Manager (non-blocking, flash, segment, session encryption). Uses PHP [open_ssl](http://php.net/manual/en/book.openssl.php) for optional encrypt/decryption of session data.

### Driver support  Scope
![file](https://img.shields.io/badge/FILE-completed-brightgreen.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![cookie](https://img.shields.io/badge/COOKIE-completed-brightgreen.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![pdo](https://img.shields.io/badge/PDO-completed-brightgreen.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![memcached](https://img.shields.io/badge/MEMCACHED-completed-brightgreen.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![redis](https://img.shields.io/badge/REDIS-active-brightgreen.svg?style=flat-square)&nbsp;&nbsp;&nbsp;[![license](https://img.shields.io/pypi/l/Django.svg?style=flat-square)]()&nbsp;&nbsp;&nbsp;[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg?style=flat-square)](http://php.net/releases/7_0_0.php)

# Installation   
You can download the Latest [release version ](https://github.com/Ghostff/Session/releases/) as a standalone, alternatively you can use [Composer](https://getcomposer.org/) 
```json
$ composer require ghostff/session
```
```json
"require": {
    "ghostff/session": "^1.0"
}
```    

Basic usage:
## Registering Error Handler (optional)
```php
#This method must be implemented before Session::start
Session::registerErrorHandler(function($error, $error_code)
{
    #Debug::Log($error);
});
```

## Setting or getting session id *:void*
```php
#When setting ID, method must be implemented before Session::start
Session::id(bin2hex(random_bytes(32)));

#Get ID
echo Session::id();
```

## Initializing Session
```php
$session = Session::start($optional_session_namespace);
```


## Using Segment *:Segment*
```php
 $segment = $session->segment($required_segment_name);
```

## Setting Session Data
```php
$session->name = 'foo';
# Setting Segment
$segment->name = 'bar';

# Setting Flash
$session->flash->name = 'foobar';
# Setting Segment Flash
$segment->flash->name = 'barfoo';

$session->commit();
```

## Retrieving Session Data
```php
echo $session->name; # outputs foo
# Retrieving Segment
echo $segment->name; # outputs bar

# Retrieving Flash
echo $session->flash->name; # outputs foobar
# Retrieving Segment Flash
echo $segment->flash->name; # outputs barfoo
```

## Removing Session Data
```php
$session->remove->name;
# Removing Segment
$segment->remove->name;

# Removing Flash
$session->remove->flash->name;
# Removing Segment Flash
$segment->remove->flash->name;
```

## Retrieve all session or segment data *:array*
```php
$session->all($optional_segment);
```

## Check if variable exist in current session namespace *:bool*
```php
$session->exist($variable_name, $option_segment, $in_flash);
```


## Removing active session or segment data *:void*
```php
$session->clear($optional_segment);
```

## Destroying session *:void*
```php
$session->destroy();
```

## Regenerate session ID *:void*
```php
$session->rotate($keep_old_session_data);
```

## Change Log *v1.01.0*
**Initializing Session**

A new optional argument(`$auto_save: true`) was added to the `start` method.
```php
$session = Session::start($optional_session_namespace, $auto_save);
```
Which allows uncommitted (forgot to commit) changes to saves automatically. Is set to `false`, uncommitted changes will be discarded.

