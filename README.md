# LaravelModelToMarkdown

## About Project

A Model-to-markdown conversion helper base on Mongodb for Laravel

## Installation

> composer require "yunyala/laravel-model-to-markdown"

## Publish Service

```php
php artisan vendor:publish --provider="Yunyala\LaravelModelToMarkdown\LaravelModelToMarkdownServiceProvider"
```

## Code Examples

```php
// 将模型文件转换成markdown文档，转换前注意修改配置文件-laravelmodeltomarkdown.php中的模型目录路径
LaravelModelToMarkdown::convert();
```

