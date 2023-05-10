<?php

namespace Yunyala\LaravelModelToMarkdown\Test;

use Yunyala\LaravelModelToMarkdown\Facades\LaravelModelToMarkdown;

class Test
{
    /**
     * Convert Model files to Markdown files. Before conversion, pay attention to modifying the model directory path in the configuration file - lavelmodeltomarkdown.php
     * @return mixed
     */
    public function convert()
    {
        return LaravelModelToMarkdown::convert();
    }

}

$test = new Test();
$test->convert();
