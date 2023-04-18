# Development Remark

## Test
> 使用PHPUnit測試

### PHP7.0

```bash
# 下載指定版本的 PHPUnit 
$ wget https://phar.phpunit.de/phpunit-6.5.phar

# 執行 PHPUnit 
$ php7.0 phpunit-6.5.phar -c phpunit-6.5.xml
```


### PHP7.4
```bash
# 下載指定版本的 PHPUnit 
$ wget https://phar.phpunit.de/phpunit-9.6.phar

# 環境變數
$ XDEBUG_MODE=coverage; export XDEBUG_MODE;

# 執行 PHPUnit 
$ php7.4 phpunit-9.6.phar -c phpunit-9.6.xml
```


### PHP8.1
```sh
# 下載指定版本的 PHPUnit 
$ wget https://phar.phpunit.de/phpunit-10.1.phar

# 環境變數
$ XDEBUG_MODE=coverage; export XDEBUG_MODE;

# 執行 PHPUnit 
$ php8.1 phpunit-10.1.phar -c phpunit-10.1.xml
```
