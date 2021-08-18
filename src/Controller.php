<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Core;

/**
 * 控制器基类
 */
class Controller
{
    /**
     * 视图实例对象
     *
     * @var [type]
     */
    protected $view = null;

    /**
     * 架构函数
     */
    public function __construct()
    {
        $this->view = new View();
    }

    /**
     * 模板显示
     *
     * @param string $templateFile
     * @param string $charset
     * @param string $contentType
     * @return void
     */
    protected function display($templateFile = '', $charset = '', $contentType = '')
    {
        $this->view->display($templateFile, $charset, $contentType);
    }

    /**
     * 获取输出页面内容
     *
     * @param string $templateFile
     * @return void
     */
    protected function fetch($templateFile = '')
    {
        return $this->view->fetch($templateFile);
    }

    /**
     * 模板变量赋值
     *
     * @param string $name
     * @param [type] $value
     * @return void
     */
    protected function assign($name = '', $value)
    {
        $this->view->assign($name, $value);
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param [type] $value
     */
    public function __set($name = '', $value)
    {
        $this->view->assign($name, $value);
    }

    /**
     * 取得模板显示变量的值
     *
     * @param string $name
     * @return void
     */
    public function __get($name = '')
    {
        return $this->view->get($name);
    }
}
