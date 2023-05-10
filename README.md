# LaravelModelToMarkdown

## About Project

A Model-to-markdown conversion helper base on Mongodb for Laravel

## Requirements

- PHP >= 5.4
- Mongodb PHP Extension
- jenssegers/mongodb Composer package

## Installation

```php
composer require "yunyala/laravel-model-to-markdown"

// update config/app.php: 
'providers' => [
    ...
    Yunyala\LaravelModelToMarkdown\LaravelModelToMarkdownServiceProvider::class
],
'aliases' => [
    ...
    'LaravelModelToMarkdown' => Yunyala\LaravelModelToMarkdown\Facades\LaravelModelToMarkdown::class
],

composer dump-autoload

php artisan vendor:publish --provider="Yunyala\LaravelModelToMarkdown\LaravelModelToMarkdownServiceProvider"
```

## Code Examples

```php
// Convert Model files to Markdown files. Before conversion, pay attention to modifying the model directory path in the configuration file - lavelmodeltomarkdown.php
LaravelModelToMarkdown::convert();
```

