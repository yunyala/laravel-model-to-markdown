<?php

namespace Yunyala\LaravelModelToMarkdown\Test;

use Yunyala\LaravelModelToMarkdown\Facades\LaravelModelToMarkdown;

class Test
{
    /**
     * 将模型文件转换成markdown文档，转换前注意修改配置文件-laravelmodeltomarkdown.php中的模型目录路径。
     * @return mixed
     */
    public function convert()
    {
        return LaravelModelToMarkdown::convert();
    }

}

$test = new Test();
$test->convert();
