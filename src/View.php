<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Core;

/**
 * 视图输出.
 */
class View
{
	/**
	 * 模板输出变量.
	 *
	 * @var array
	 */
	protected $tVar = [];

	/**
	 * 模板变量赋值
	 *
	 * @param string $name
	 * @param [type] $value
	 */
	public function assign($name = '', $value)
	{
		if (is_array($name)) {
			$this->tVar = array_merge($this->tVar, $name);
		} elseif (is_object($name)) {
			foreach ($name as $key => $val) {
				$this->tVar[$key] = $val;
			}
		} else {
			$this->tVar[$name] = $value;
		}
	}

	/**
	 * 取得模板变量的值
	 *
	 * @param string $name
	 */
	public function get($name = '')
	{
		if (isset($this->tVar[$name])) {
			return $this->tVar[$name];
		}

		return false;
	}

	/**
	 * 取得所有模板变量.
	 */
	public function getAllVar()
	{
		return $this->tVar;
	}

	/**
	 * 加载模板和页面输出.
	 *
	 * @param string $templateFile 模板文件名
	 * @param string $charset 模板输出字符集
	 * @param string $contentType 输出类型
	 */
	public function display($templateFile = '', $charset = '', $contentType = '')
	{
		// 解析并获取模板内容
		$content = $this->fetch($templateFile);
		// 输出模板内容
		$this->show($content, $charset, $contentType);
	}

	/**
	 * 输出内容文本可以包括Html.
	 *
	 * @param string $content 输出内容
	 * @param string $charset 模板输出字符集
	 * @param string $contentType 输出类型
	 */
	public function show($content = '', $charset = 'utf-8', $contentType = 'text/html')
	{
		// 网页字符编码
		header('Content-Type:' . $contentType . '; charset=' . $charset);
		header('Cache-control: private');  //支持页面回跳
		// 输出模板文件
		echo $content;
	}

	/**
	 * 解析和获取模板内容.
	 *
	 * @param string $templateFile 模板文件名
	 */
	public function fetch($templateFile = '')
	{
		$module = MODULE_NAME;
		$action = ACTION_NAME;
		if (! empty($templateFile)) {
			$names = explode(':', $templateFile);
			if (count($names) == 2) {
				$module = ucfirst($names[0]);
				$action = $names[1];
			} elseif (count($names) == 1) {
				$action = $names[0];
			}
		}
		$path = APP_PATH . DIRECTORY_SEPARATOR . GROUP_NAME . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $action . '.php';
		// 模板文件不存在直接返回
		if (! is_file($path)) {
			halt('_TPL_NOT_EXIST_ : ' . $templateFile);
		}
		// 页面缓存
		ob_start();
		ob_implicit_flush(0);
		// 模板阵列变量分解成为独立变量
		extract($this->tVar, EXTR_OVERWRITE);
		// 直接载入PHP模板
		include $path;
		// 获取并清空缓存
		$content = ob_get_clean();
		// 输出模板文件
		return $content;
	}
}
