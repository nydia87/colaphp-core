# colaphp-core
# require colaphp/utils
框架核心、实现MVC功能

版本^1.0 基本mvc功能

```php
//定义根目录
define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
//定义App目录
define('APP_PATH', ROOT_PATH . 'app' . DIRECTORY_SEPARATOR);
//运行
\Colaphp\Core\App::run();
```