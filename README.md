qetag
=====
qiniu etag

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer require chenkby/qetag
```

or add

```
"chenkby/qetag": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?php
    $key = \chenkby\qetag\QEtag::getEtag($filename);
?>
```
