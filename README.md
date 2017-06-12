# Session PHP(7+)
PHP Session Manager (non-blocking, flash, segment, session encryption). Uses PHP [open_ssl](http://php.net/manual/en/book.openssl.php) for optional encrypt/decryption of session data.

### Driver support  Scope
![file](https://img.shields.io/badge/FILE-completed-blue.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![cookie](https://img.shields.io/badge/COOKIE-completed-blue.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![pdo](https://img.shields.io/badge/PDO-completed-blue.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![memcached](https://img.shields.io/badge/MEMCACHED-active-blue.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![redis](https://img.shields.io/badge/REDIS-active-brightgreen.svg?style=flat-square)&nbsp;&nbsp;&nbsp;


## Registering Error Handler
```php
#This method must be implemented before Session::start
Session::registerErrorHandler(function($error, $error_code)
{
    #Debug::Log($error);
});
```

## Setting session id *:void*
```php
#This method must be implemented before Session::start
Session::id(bin2hex(random_bytes(32)));
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
$session->exists($variable_name, $in_flash);
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
$session->rotate(true);
```
