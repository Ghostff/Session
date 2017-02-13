# Session php(7.1)
PHP Session Manager (non-blocking, flash, segment, session encryption)

#Initializing Session
```php
$session = Session::start($optiona_session_name);

# Register Error Handler
$session->registerErrorHandler(function($error)
{
    # Debug::Log($error)
    # throw new  RuntimeException($error);
    # how ever you like
});
```

#Using Segment
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

#Retrieve all session and flash data
```php
# Array
$session->getAll();
```
