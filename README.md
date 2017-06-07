# Session php(7.1)
PHP Session Manager (non-blocking, flash, segment, session encryption). Uses PHP [open_ssl](http://php.net/manual/en/book.openssl.php) for optional encrypt/decryption of session data.

### Driver support  Scope
![File](https://img.shields.io/badge/FILE-completed-blue.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![File](https://img.shields.io/badge/COOKIE-completed-blue.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![File](https://img.shields.io/badge/SQL-completed-blue.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![File](https://img.shields.io/badge/MEMCACHE-active-brightgreen.svg?style=flat-square)&nbsp;&nbsp;&nbsp;![File](https://img.shields.io/badge/REDIS-queued-lightgray.svg?style=flat-square)&nbsp;&nbsp;&nbsp;

## Initializing Session
```php
# Register Error Handler
Session::registerErrorHandler(function($error, $error_code)
{
    # Debug::Log($error)
    # throw new  RuntimeException($error);
});

$session = Session::start($optional_session_namespace);
```
## Setting session id *:string*
```php
Session::id(bin2hex(random_bytes(32)));

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
