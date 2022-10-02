[!['Build Status'](https://travis-ci.org/shopwwi/laravel-cache.svg?branch=main)](https://github.com/shopwwi/laravel-cache) [!['Latest Stable Version'](https://poser.pugx.org/shopwwi/laravel-cache/v/stable.svg)](https://packagist.org/packages/shopwwi/laravel-cache) [!['Total Downloads'](https://poser.pugx.org/shopwwi/laravel-cache/d/total.svg)](https://packagist.org/packages/shopwwi/laravel-cache) [!['License'](https://poser.pugx.org/shopwwi/laravel-cache/license.svg)](https://packagist.org/packages/shopwwi/laravel-cache)

# 安装
- php 7.x
```
composer require shopwwi/laravel-cache illuminate/cache ^8.0
```
- php 8.x
```
composer require shopwwi/laravel-cache
```
## 配置文件
```
//路径 config/laravelcache.php

```
## 支持驱动

- apc
- array
- file 本地缓存
- redis 缓存
- memcached 缓存
- database 数据库缓存
- dynamodb
- octane

## 缓存使用
### 获取缓存实例
要获取缓存存储实例，您可以使用 `Cache Facade`，我们将在本文档中使用它。`Cache Facade` 提供了对 `Laravel` 缓存合约底层实现的方便、简洁的访问：
```php
<?php

namespace app\controller;

use Shopwwi\LaravelCache\Cache;

class UserController extends Controller
{
    /**
     * 显示应用程序的所有用户的列表。
     *
     * @return Response
     */
    public function index()
    {
        $value = Cache::get('key');

        //
    }
}

```
### 访问多个缓存存储
使用 `Cache Facade`，您可以通过 `store` 方法访问各种缓存存储。传递给` store` 方法的键应该对应于 `cache` 配置文件中的 `stores` 配置数组中列出的存储之一：
```php

$value = Cache::store('file')->get('foo');

Cache::store('redis')->put('bar', 'baz', 600); // 10 Minutes

```
### 从缓存中检索项目
`Cache` 门面的 `get` 方法用于从缓存中检索项目。如果缓存中不存在该项目，则将返回 `null`。如果您愿意，您可以将第二个参数传递给 `get` 方法，指定您希望在项目不存在时返回的默认值：
```php

$value = Cache::get('key');

$value = Cache::get('key', 'default');

```
您甚至可以将闭包作为默认值传递。如果指定的项在缓存中不存在，则返回闭包的结果。传递闭包允许您推迟从数据库或其他外部服务中检索默认值：
```php

$value = Cache::get('key', function () {
    return Db::table(...)->get();
});

```
### 检查项目是否存在
`has` 方法可用于确定缓存中是否存在项目。如果项目存在但其值为` null`，此方法也将返回 `false`：
```php
if (Cache::has('key')) {
    //
}
```

### 递增 / 递减值
`increment` 和 `decrement` 方法可用于调整缓存中整数项的值。这两种方法都接受一个可选的第二个参数，指示增加或减少项目值的数量：

```php

Cache::increment('key');
Cache::increment('key', $amount);
Cache::decrement('key');
Cache::decrement('key', $amount);

```

### 检索和存储
有时您可能希望从缓存中检索一个项目，但如果请求的项目不存在，也存储一个默认值。例如，您可能希望从缓存中检索所有用户，或者，如果它们不存在，则从数据库中检索它们并将它们添加到缓存中。您可以使用 `Cache::remember` 方法执行此操作：

```php

$value = Cache::remember('users', $seconds, function () {
    return Db::table('users')->get();
});

```
如果缓存中不存在该项，则传递给 `remember` 方法的闭包将被执行，并将其结果放入缓存中。

您可以使用 `rememberForever` 方法从缓存中检索一个项目，或者如果它不存在则永久存储它：

```php

$value = Cache::rememberForever('users', function () {
    return Db::table('users')->get();
});

```

检索和删除
如果您需要从缓存中检索一个项目然后删除该项目，您可以使用 `pull` 方法。 与 `get` 方法一样，如果缓存中不存在该项，则将返回 `null`：

```php

$value = Cache::pull('key');

```

在缓存中存储项目
您可以使用 `Cache Facade` 上的 `put` 方法将项目存储在缓存中：

```php

Cache::put('key', 'value', $seconds = 10);

```

如果存储时间没有传递给 `put` 方法，该项目将被无限期存储：

```php

Cache::put('key', 'value');

```
除了将秒数作为整数传递之外，您还可以传递一个表示缓存项所需过期时间的 `DateTime` 实例：

```php

Cache::put('key', 'value', now()->addMinutes(10));

```
### 如果不存在则存储
`add` 方法只会将缓存存储中不存在的项目添加到缓存中。如果项目实际添加到缓存中，该方法将返回 `true`。 否则，该方法将返回 `false`。 `add` 方法是一个原子操作：

```php

Cache::add('key', 'value', $seconds);

```

永久存储
`forever` 方法可用于将项目永久存储在缓存中。由于这些项目不会过期，因此必须使用 `forget` 方法手动将它们从缓存中删除：

```php

Cache::forever('key', 'value');

```
技巧：如果您使用的是 `Memcached` 驱动程序，则当缓存达到其大小限制时，可能会删除「永久」存储的项目。


### 从缓存中删除项目
您可以使用 `forget` 方法从缓存中删除项目：

```php

Cache::forget('key');

```
您还可以通过提供零或负数的过期秒数来删除项目：

```php

Cache::put('key', 'value', 0);

Cache::put('key', 'value', -5);

```

您可以使用 `flush` 方法清除整个缓存：

```php

Cache::flush();

```

## 缓存标签
### 存储缓存标签
缓存标签允许您在缓存中标记相关项目，然后刷新所有已分配给定标签的缓存值。您可以通过传入标记名称的有序数组来访问标记缓存。例如，让我们访问一个标记的缓存并将一个值「put」缓存中：

```php

Cache::tags(['people', 'artists'])->put('John', $john, $seconds);

Cache::tags(['people', 'authors'])->put('Anne', $anne, $seconds);

```

### 访问缓存标签
要检索标记的缓存项，请将相同的有序标签列表传递给 `tags` 方法，然后使用您要检索的键调用 `get` 方法：

```php

$john = Cache::tags(['people', 'artists'])->get('John');

$anne = Cache::tags(['people', 'authors'])->get('Anne');

```
### 删除缓存标签
您可以刷新所有分配了标签或标签列表的项目。例如，此语句将删除所有标记为「people」、「authors」或两者的缓存。 因此，`Anne` 和 `John` 都将从缓存中删除：

```php

Cache::tags(['people', 'authors'])->flush();

```

相反，此语句将仅删除带有 `authors` 标记的缓存值，因此将删除 `Anne`，但不会删除 `John`：

```php

Cache::tags('authors')->flush();

```

更多文档请查看laravel官方文档 如果你觉得这个插件帮助到你 希望你前往github点上你的小星星！同样如果你遇到问题 你可以通过isuse联系到我 或者8988354@qq.com
