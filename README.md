# Session php(7.1)
PHP Session Manager (non-blocking, flash, segment, session encryption). Uses PHP [open_ssl](http://php.net/manual/en/book.openssl.php) for optional encrypt/decryption of session data.

###Driver support  Scope
 - File&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;: `done`
 - Cookie&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: `active`
 - Database&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: `queued`
 - Memcached&nbsp;&nbsp;: `queued`
 - Redis&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: `queued`


#Initializing Session *:void*
```php
$session = Session::start($optional_session_namespace);

# Register Error Handler
$session->registerErrorHandler(function($error, $error_code)
{
    # Debug::Log($error)
    # throw new  RuntimeException($error);
});
```

#Using Segment *:Segment*
```php
 $segment = $session->segment($required_segment_name);
```

#Setting Session Data
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

#Retrieving Session Data
```php
echo $session->name; # outputs foo
# Retrieving Segment
echo $segment->name; # outputs bar

# Retrieving Flash
echo $session->flash->name; # outputs foobar
# Retrieving Segment Flash
echo $segment->flash->name; # outputs barfoo
```

#Removing Session Data
```php
$session->remove->name;
# Removing Segment
$segment->remove->name;

# Removing Flash
$session->remove->flash->name;
# Removing Segment Flash
$segment->remove->flash->name;
```

#Retrieve all session and flash data *:array*
```php
# Array
$session->all();
```

#Get/Set session name *:string*
```php
$session = Session::start($optional_session_namespace);
# set
$session->name('foo');

# retrieve
$session->name(); #outputs foo
```

#Get/Set session id *:string*
```php
$session = Session::start($optional_session_namespace);
# set
$session->id(bin2hex(openssl_random_pseudo_bytes(32)));

# retrieve
$session->name(); #outputs something like e916b0ff9f8217e52786ee51f2e24..
```

#Check if variable exist in current session namespace *:bool*
```php
$session->exists($variable_name);
```


#Removing a specific current namespace data *:void*
```php
$session->clear();
```

#Destroying session *:void*
```php
$session->destroy();
```

#Regenerate session ID *:void*
```php
$session->rotate(true);
```
