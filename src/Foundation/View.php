<?php
declare(strict_types=1);

namespace Minimal\Foundation;

use Minimal\Application;

/**
 * 视图类
 */
class View
{
    /**
     * 全局变量
     */
    protected array $vars;

    /**
     * 模板文件
     */
    protected string $file;

    /**
     * 缓存列表
     */
    protected array $cache;

    /**
     * 缓存有效期
     */
    protected int $expire = 60 * 30;

    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 渲染文件
     */
    public function fetch(string $file, array $vars = []) : static
    {
        // 页面变量
        $this->vars = $vars;
        // 模板文件
        $this->file = $this->realpath($file);

        // 存在缓存、保存缓存文件路径
        if ($this->hasCache($this->file)) {
            $this->file = $this->cacheFilePath($this->file);
            return $this;
        }

        // 解析模板
        $content = $this->parse($this->file);
        // 缓存模板、保存缓存文件路径
        $this->file = $this->cache($this->file, $content);

        // 返回结果
        return $this;
    }

    /**
     * 真实路径
     */
    public function realpath(string $path) : string
    {
        if (is_file($path)) {
            return $path;
        }
        return $this->app->viewPath($path);
    }

    /**
     * 是否存在缓存文件
     */
    public function hasCache(string $file) : bool
    {
        return isset($this->cache[$file]['time']) && is_file($file) && $this->cache[$file]['time'] === filemtime($file);
    }

    /**
     * 获取缓存文件路径
     */
    public function cacheFilePath(string $file) : string
    {
        // 已缓存过路径
        if (isset($this->cache[$file]['file'])) {
            return $this->cache[$file]['file'];
        }

        // 文件路径
        $viewPath = $this->app->viewPath();
        $cachePath = $this->app->cachePath('view/');
        if (str_starts_with($file, $viewPath)) {
            $cacheFile = $cachePath . mb_substr($file, mb_strlen($viewPath));
        } else {
            $cacheFile = $cachePath . md5($file) . '.php';
        }

        // 对应目录
        if (!is_dir(dirname($cacheFile)) && !mkdir(dirname($cacheFile), 0777, true)) {
            throw new Exception('很抱歉、无法创建缓存文件！', 0, [$cacheFile]);
        }

        // 返回结果
        return $this->cache[$file]['file'] = $cacheFile;
    }

    /**
     * 缓存文件
     */
    public function cache(string $file, string $content) : string
    {
        // 缓存路径
        $cacheFile = $this->cacheFilePath($file);

        // 写入缓存
        if (false === file_put_contents($cacheFile, $content)) {
            throw new Exception('很抱歉、无法缓存模板文件！', 0, [$cacheFile]);
        }

        // 写入时间
        $this->cache[$file]['time'] = filemtime($file);

        // 返回结果
        return $cacheFile;
    }

    /**
     * 解析文件
     */
    public function parse(string $file) : string
    {
        // 读取文件
        $content = file_get_contents($file);
        if (false === $content) {
            return '';
        }

        // 正则转换
        $content = preg_replace(
            [
                // 控制语句 if|switch 等以 :; 结尾的
                '/\{\{\s*?(.*)([:;]{1})\s*?\}\}/U',
                // 将 switch 和 第一个 case 换到同一行，不然报错
                '/\?>\s*?<\?php\s*?case /U',
                // 将普通表达式 转换成 echo
                '/\{\{\s*?(.*)\s*?\}\}/U',
            ],
            [
                '<?php $1$2 ?>',
                '?><?php case ',
                '<?php echo $1; ?>'
            ],
            $content
        );

        // 返回结果
        return $content;
    }

    /**
     * 输出内容
     */
    public function __toString() : string
    {
        // 页面变量
        extract($this->vars, EXTR_SKIP);

        // 开始输出
        ob_start();
        try {
            include $this->file;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage(), 432);
        }
        return ob_get_clean();
    }
}