<?php
/**
 */

namespace Yunyala\LaravelModelToMarkdown;

use Doctrine\Inflector\InflectorFactory;
use Illuminate\Config\Repository;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\ObjectId;

class LaravelModelToMarkdown
{
    protected $session;

    protected $config;

    public function __construct(SessionManager $session, Repository $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * 转换
     * @return string
     * @throws \ReflectionException
     */
    public function convert()
    {
        // 调用遍历函数
        $dir = $this->config['laravelmodeltomarkdown.model_dir'];
        $dir = self::format_file_path(base_path().'\\'.$dir);
        self::traversalDir($dir);
        return '执行完成！';
    }

    /**
     * 将文件目录中的\更换为/
     * @param $target
     * @param $find
     * @param $replace
     * @return array|string|string[]|void
     */
    public static function format_file_path($target,$find='\\',$replace='/'){
        return str_replace($find,$replace,$target);
    }

    /**
     * 遍历目录
     * @param $dir
     * @return void
     * @throws \ReflectionException
     */
    public static function traversalDir($dir) {
        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != "." && $file != "..") {
                        $path = $dir . DIRECTORY_SEPARATOR . $file;
                        $path = self::format_file_path($path);
                        if (is_dir($path)) {
                            self::traversalDir($path);
                        } else {
                            self::createMdFile($path);
                        }
                    }
                }
                closedir($handle);
            }
        }
    }

    /**
     * 创建Markdown文件
     * @param $path
     * @return void
     * @throws \ReflectionException
     */
    public static function createMdFile($path)
    {
        $content = file_get_contents($path);
        // 获取类的fillable属性，包含模型的列名和注释
        $fillable = self::getFillableProperty($content);
        $field_names = $fillable['field_names'];
        $field_comments = $fillable['field_comments'];
        // 获取集合中文名
        $class_chinese_name = self::getCollectionChineseName($content);
        // 获取集合名
        $collection_name = self::getCollectionName($content);
        // 获取类的casts属性，即列名对应的类型
        $casts = self::getCastProperty($content);
        // 获取集合第一条文档数据
        $collection_cn_name = $class_chinese_name;
        $authRole = DB::connection('mongodb')->collection($collection_name)->first();
        $authRole = self::recursiveChangeObjectId2String($authRole)->all(); // 递归改变ObjectId对象成String
        $authRole = var_export($authRole, true);
        $authRole = self::string2MongodbVisualFormat($authRole); // 字符串转为Mongodb可视化格式
        // 转换查询结果为 Markdown 格式
        $markdown = "# {$collection_cn_name}表（{$collection_name}）\n";
        $markdown .= "## 定义\n";
        $markdown .= "```\n";
        $markdown .= "$authRole\n";
        $markdown .= "```\n";
        $markdown .= "## 说明\n";
        $markdown .= "| 列名 | 数据类型 | 主键 | 索引 | 描述 |\n";
        $markdown .= "|---|---|---|---|---|\n";
        foreach ($field_names as $column_key => $column) {
            $comment = trim($field_comments[$column_key]);
            $cast = trim($casts[$column_key]);
            $markdown .= "| $column | $cast |   |   | $comment |\n";
        }
        // 将字符串写入文件，并下载
        Storage::disk('public')->put("markdown/{$collection_name}.md", $markdown);
    }

    /**
     * 获取类的fillable属性，包含模型的列名和注释
     * @param $content 类文件的内容，字符串形式
     * @return array
     */
    public static function getFillableProperty($content) {
        // \$ 表示匹配 $ 字符（$ 在正则表达式中有特殊含义，需要进行转义）
        // \d 表示匹配任意一个数字
        // \s* 表示匹配零个或多个空白字符
        // =\\s* 表示匹配等号和可能存在的空白字符
        // (.*?) 表示非贪婪匹配任意字符（包括空格、标点符号、数字、字母等），非贪婪指匹配到尽可能少的字符，并使用捕获组将其内容保存为子组 1
        // \1 表示反向引用第一子组中的匹配内容，确保引号匹配一致
        // /s 修饰符表示匹配任意空白字符，包括空格、制表符、换行符等
        // [\x{4e00}-\x{9fa5}] 表示匹配中文字符，在使用 Unicode 字符集进行匹配时，必须在正则表达式的末尾加上 u 标记，表示开启 Unicode 模式
        // \w 表示匹配英文字符（包括字母、数字和下划线）
        // . 是一个特殊字符，表示匹配任何单个字符，除了换行符 \n
        // 如果要匹配包括 \n 在内的任何字符，可以使用模式修饰符 s，例如 /./s
        preg_match('/\$fillable\s*=\s*\[(.*?)\]/s', $content, $matches);
//        preg_match('/\$description\s*=\s*\'(.*?)\'/s', $content, $matches);
//        preg_match_all('/(["\'])(.*?)\1\s*,\s*\/\/\s*([\x{4e00}-\x{9fa5}\w]*)/u', $matches[1], $matches2);
        preg_match_all('/(["\'])(.*)\1\s*,?\s*\/\/(.*)/u', $matches[1], $matches2);
        $field_names = $matches2[2];
        $field_comments = $matches2[3];
        $fillable = [
            'field_names' => $field_names,
            'field_comments' => $field_comments,
        ];
        return $fillable;
    }

    /**
     * 获取类的cast属性
     * @param $content
     * @return array
     */
    public static function getCastProperty($content) {
        preg_match('/\$casts\s*=\s*\[(.*?)\]/s', $content, $matches);
        preg_match_all('/(["\'])(.*)\1\s*=>\s*(["\'])(.*)\3/u', $matches[1]??'', $matches2);
        return $matches2[4];
    }

    /**
     * 获取集合中文名
     * @param $content
     * @return mixed
     */
    public static function getCollectionChineseName($content) {
        preg_match('/\*\s*([\x{4e00}-\x{9fa5}\w]*)\n\s*\*\s*Class/u', $content, $matches);
        return $matches[1]??'';
    }

    /**
     * 获取集合名
     * @param $content
     * @return mixed
     */
    public static function getCollectionName($content) {
        // 获取 $collection 属性
        preg_match('/\$collection\s*=\s*(["\'])(.*)\1;/s', $content, $matches);
        $collection_name = $matches[2]??'';
        // 如果 $colleciton属性为空，则获取文件类名充当集合名，且需要转换为下划线+复数的形式
        if (!$collection_name) {
            preg_match('/class\s*(.*)\s*extends/', $content, $matches);
            $collection_name = trim($matches[1]);
            // 使用第三方类库doctrine/inflector转换类名为下划线+复数形式
            $inflector = InflectorFactory::create()->build();
            $collection_name = $inflector->tableize($collection_name);
            $collection_name = $inflector->pluralize($collection_name);
        }
        return $collection_name;
    }

    /**
     * 递归改变ObjectId对象成String
     * @param $authRole
     * @return \Illuminate\Support\Collection
     */
    public static function recursiveChangeObjectId2String($authRole)
    {
        $authRole = collect($authRole)->map(function ($item, $key) {
            if ($item instanceof ObjectId) {
                $item = self::objectId2String($item);
            }
            if (is_object($item)) {
                $item = self::recursiveChangeObjectId2String($item);
            }
            if (is_array($item)) {
                if (self::isAssocArray($item)) {
                    $item = self::recursiveChangeObjectId2String($item);
                } else {
                    foreach ($item as &$value) {
                        $value = self::recursiveChangeObjectId2String($value);
                        $value = $value->all();
                    }
                }
            }
            return $item;
        });
        return $authRole;
    }

    /**
     * 判断是否为关联数组
     * @param $arr
     * @return bool
     */
    public static function isAssocArray($arr) {
        if (!is_array($arr)) return false;
        $keys = array_keys($arr);
        return isset($keys[0]) ? !is_numeric($keys[0]) : false;
    }

    /**
     * objectId对象转为string
     * @param $obj
     * @return string
     */
    public static function objectId2String($obj) {
        $obj = (string) $obj;
        $obj = 'ObjectId("'.$obj.'")';
        return $obj;
    }

    /**
     * 字符串转为Mongodb可视化格式
     * @param $authRole
     * @return array|string|string[]|null
     */
    public static function string2MongodbVisualFormat($authRole) {
        // 替换字符串头
        $authRole = preg_replace("/^array \(/", "{", $authRole);
        // 替换字符串尾
        $authRole = preg_replace("/\)$/", "}", $authRole);
        // 删除字符串ObjectId前后的单引号
        $authRole = preg_replace("/'ObjectId\((.*)\)'/", "ObjectId($1)", $authRole);
        // 替换字符串单引号为双引号
        $authRole = preg_replace("/'/", "\"", $authRole);
        return $authRole;
    }

}
