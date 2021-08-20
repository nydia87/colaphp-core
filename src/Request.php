<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Core;

use Colaphp\Utils\Upload;

/**
 * 处理Request请求
 */
class Request
{
	/**
	 * 请求类型.
	 * @var string
	 */
	protected $method;

	/**
	 * 当前php://input.
	 *
	 * @var string
	 */
	protected $input;

	/**
	 * 当前请求参数.
	 * @var array
	 */
	protected $param = [];

	/**
	 * 当前GET参数.
	 * @var array
	 */
	protected $get = [];

	/**
	 * 当前POST参数.
	 * @var array
	 */
	protected $post = [];

	/**
	 * 当前REQUEST参数.
	 * @var array
	 */
	protected $request = [];

	/**
	 * 初始化.
	 */
	public function __construct()
	{
		// 保存 php://input
		$this->input = file_get_contents('php://input');
		// 请求方法
		$this->method();
	}

	/**
	 * 设置键值
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name = '', $value = '')
	{
		$this->$name = $value;
	}

	/**
	 * 获取键值
	 *
	 * @param string $name
	 * @return string
	 */
	public function __get($name = '')
	{
		return $this->$name ?: '';
	}

	/**
	 * 当前的请求类型.
	 *
	 * @return string
	 */
	public function method()
	{
		if (! $this->method) {
			if ($this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
				$this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
			} else {
				$this->method = $this->methodOrigin();
			}
		}

		return $this->method;
	}

	/**
	 * 获取当前请求的参数.
	 *
	 * @param mixed $name 变量名
	 * @return mixed
	 */
	public function param($name = '')
	{
		$method = $this->methodOrigin();

		// 自动获取请求变量
		switch ($method) {
				case 'POST':
					$vars = $this->post(false);
					break;
				case 'PUT':
				case 'DELETE':
				case 'PATCH':
					$vars = $this->put(false);
					break;
				default:
					$vars = [];
			}

		// 当前请求参数和URL地址中的参数合并
		$this->param = array_merge($this->param, $this->get(false), $vars);

		// 获取包含文件上传信息的数组
		if ($name === true) {
			$file = $this->file();
			$data = is_array($file) ? array_merge($this->param, $file) : $this->param;
			return $this->input($data, '');
		}

		return $this->input($this->param, $name);
	}

	/**
	 * 获取request变量.
	 *
	 * @param false|string $name 变量名
	 * @return mixed
	 */
	public function request($name = '')
	{
		if (empty($this->request)) {
			$this->request = $_REQUEST;
		}

		return $this->input($this->request, $name);
	}

	/**
	 * 获取GET参数.
	 *
	 * @param false|string $name 变量名
	 *
	 * @return mixed
	 */
	public function get($name = '')
	{
		if (empty($this->get)) {
			$this->get = $_GET;
		}

		return $this->input($this->get, $name);
	}

	/**
	 * 获取POST参数.
	 *
	 * @param false|string $name 变量名
	 * @return mixed
	 */
	public function post($name = '')
	{
		if (empty($this->post)) {
			$this->post = ! empty($_POST) ? $_POST : $this->getInputData($this->input);
		}

		return $this->input($this->post, $name);
	}

	/**
	 * 获取PUT参数.
	 *
	 * @param false|string $name 变量名
	 * @return mixed
	 */
	public function put($name = '')
	{
		if (is_null($this->put)) {
			$this->put = $this->getInputData($this->input);
		}

		return $this->input($this->put, $name);
	}

	/**
	 * 获取上传的文件信息.
	 * @param string $name 名称
	 * @return null|array|\Colaphp\Utils\Upload
	 */
	public function file($name = '')
	{
		if (empty($this->file)) {
			$this->file = isset($_FILES) ? $_FILES : [];
		}

		$files = $this->file;
		if (! empty($files)) {
			if (strpos($name, '.')) {
				list($name, $sub) = explode('.', $name);
			}

			// 处理上传文件
			$array = $this->doUploadFile($files, $name);

			if ($name === '') {
				// 获取全部文件
				return $array;
			}
			if (isset($sub, $array[$name][$sub])) {
				return $array[$name][$sub];
			}
			if (isset($array[$name])) {
				return $array[$name];
			}
		}
	}

	/**
	 * 获取变量 支持过滤和默认值
	 *
	 * @param array $data 数据源
	 * @param false|string $name 字段名
	 * @return mixed
	 */
	public function input($data = [], $name = '')
	{
		// 获取原始数据
		if ($name === false) {
			return $data;
		}

		$name = (string) $name;
		if ($name != '') {
			// 解析name
			if (strpos($name, '/')) {
				list($name, $type) = explode('/', $name);
			}

			$data = $this->getData($data, $name);

			if (is_null($data)) {
				return null;
			}

			if (is_object($data)) {
				return $data;
			}
		}

		return $data;
	}

	/**
	 * 当前请求 CONTENT_TYPE.
	 *
	 * @return string
	 */
	public function contentType()
	{
		$contentType = $this->server('CONTENT_TYPE');

		if ($contentType) {
			if (strpos($contentType, ';')) {
				list($type) = explode(';', $contentType);
			} else {
				$type = $contentType;
			}
			return trim($type);
		}

		return '';
	}

	/**
	 * 获取请求内容.
	 *
	 * @param string $content
	 */
	protected function getInputData($content)
	{
		if (strpos($this->contentType(), 'application/json') !== false || strpos($content, '{"') === 0) {
			return (array) json_decode($content, true);
		}
		if (strpos($content, '=')) {
			parse_str($content, $data);
			return $data;
		}

		return [];
	}

	/**
	 * 解析数据.
	 *
	 * @param array $data 数据源
	 * @param false|string $name 字段名
	 * @return mixed
	 */
	protected function getData(array $data, $name)
	{
		foreach (explode('.', $name) as $val) {
			if (isset($data[$val])) {
				$data = $data[$val];
			} else {
				return;
			}
		}

		return $data;
	}

	/**
	 * 执行上传操作.
	 *
	 * @param [type] $files
	 * @param [type] $name
	 */
	protected function doUploadFile($files, $name)
	{
		$array = [];
		foreach ($files as $key => $file) {
			if ($file instanceof Upload) {
				$array[$key] = $file;
			} elseif (is_array($file['name'])) {
				$item = [];
				$keys = array_keys($file);
				$count = count($file['name']);

				for ($i = 0; $i < $count; ++$i) {
					if ($file['error'][$i] > 0) {
						if ($name == $key) {
							$this->throwUploadFileError($file['error'][$i]);
						} else {
							continue;
						}
					}

					$temp['key'] = $key;

					foreach ($keys as $_key) {
						$temp[$_key] = $file[$_key][$i];
					}

					$item[] = (new Upload($temp['tmp_name']))->setUploadInfo($temp);
				}

				$array[$key] = $item;
			} else {
				if ($file['error'] > 0) {
					if ($key == $name) {
						$this->throwUploadFileError($file['error']);
					} else {
						continue;
					}
				}

				$array[$key] = (new Upload($file['tmp_name']))->setUploadInfo($file);
			}
		}

		return $array;
	}

	/**
	 * 获取Server参数.
	 *
	 * @param string $name
	 * @param [type] $default
	 * @return string
	 */
	protected function server($name = '')
	{
		if (empty($name)) {
			return $_SERVER;
		}
		$name = strtoupper($name);

		return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
	}

	/**
	 * 文件上传错误.
	 *
	 * @param [type] $error
	 */
	protected function throwUploadFileError($error)
	{
		static $fileUploadErrors = [
			1 => 'upload File size exceeds the maximum value',
			2 => 'upload File size exceeds the maximum value',
			3 => 'only the portion of file is uploaded',
			4 => 'no file to uploaded',
			6 => 'upload temp dir not found',
			7 => 'file write error',
		];

		$msg = $fileUploadErrors[$error];

		throw new \Exception($msg);
	}

	/**
	 * 获取原始请求类型.
	 *
	 * @return string
	 */
	private function methodOrigin()
	{
		return $this->server('REQUEST_METHOD') ?: 'GET';
	}
}
