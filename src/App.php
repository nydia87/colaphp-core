<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Colaphp\Core;

use Colaphp\Utils\Config;
use Colaphp\Utils\Log;

/**
 * 框架核心.
 */
class App
{
	/**
	 * 应用初始化.
	 */
	public static function run()
	{
		//加载默认配置
		Config::load(COLAPHP_CORE_PATH . 'convention.php', COLAPHP_CORE_CONFIG);
		//注册
		static::register();
		//启动配置
		static::start();
		//路由调度
		static::dispatch();
		//执行
		static::exec();
		//记录日志
		$coreLog = getCoreLog();
		$coreLog->save();
	}

	/**
	 * 自定义错误处理.
	 *
	 * @param int $errno 错误类型
	 * @param string $errstr 错误信息
	 * @param string $errfile 错误文件
	 * @param int $errline 错误行数
	 */
	public static function appError($errno, $errstr, $errfile, $errline)
	{
		$coreLog = getCoreLog();
		switch ($errno) {
			case E_ERROR:
			case E_USER_ERROR:
			  $errorStr = "[{$errno}] {$errstr} " . basename($errfile) . " 第 {$errline} 行.";
			  if (env('LOG_RECORD')) {
			  	$coreLog->write($errorStr, Log::ERROR);
			  }
			  halt($errorStr);
			  break;
			case E_USER_WARNING:
				$errorStr = "[{$errno}] {$errstr} " . basename($errfile) . " 第 {$errline} 行.";
				$coreLog->warning($errorStr);
				break;
			case E_STRICT:
			case E_USER_NOTICE:
			default:
			  $errorStr = "[{$errno}] {$errstr} " . basename($errfile) . " 第 {$errline} 行.";
			  $coreLog->notice($errorStr);
			  break;
		}
	}

	/**
	 * 自定义异常处理.
	 *
	 * @param [type] $e
	 */
	public static function appException($e)
	{
		halt($e->__toString());
	}

	/**
	 * 应用注册.
	 */
	private static function register()
	{
		// 设定错误和异常处理
		set_error_handler(['Colaphp\Core\App', 'appError']);
		set_exception_handler(['Colaphp\Core\App', 'appException']);
	}

	/**
	 * 启动配置.
	 */
	private static function start()
	{
		//项目中必须配置的路径
		$paths = ['ROOT_PATH', 'APP_PATH'];
		foreach ($paths as $path) {
			if (! defined($path)) {
				$msg = "Your App need define path : {$path}";
				dump($msg);
				exit;
			}
		}
		//session配置
		$config = [];
		$keys = getSessionKeys();
		foreach ($keys as $k) {
			$val = env("session.{$k}");
			if (! empty($val)) {
				$config[$k] = $val;
			}
		}
		$config = ! empty($config) ? $config : [
			'prefix' => 'COLAPHP',
			'auto_start' => true,
		];
		session($config);
	}

	/**
	 * 路由设置.
	 */
	private static function dispatch()
	{
		$config = config(COLAPHP_CORE_CONFIG);
		if (! empty($_GET[$config['VAR_PATHINFO']])) { // 判断URL里面是否有兼容模式参数
			$_SERVER['PATH_INFO'] = $_GET[$config['VAR_PATHINFO']];
			unset($_GET[$config['VAR_PATHINFO']]);
		}
		// 分析PATHINFO信息
		if (empty($_SERVER['PATH_INFO'])) {
			$types = explode(',', $config['URL_PATHINFO_FETCH']);
			foreach ($types as $type) {
				if (strpos($type, ':') === 0) {// 支持函数判断
					$_SERVER['PATH_INFO'] = call_user_func(substr($type, 1));
					break;
				}
				if (! empty($_SERVER[$type])) {
					$_SERVER['PATH_INFO'] = (strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME']) === 0) ?
						substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
					break;
				}
			}
		}
		$depr = $config['URL_PATHINFO_DEPR'];
		if (! empty($_SERVER['PATH_INFO'])) {
			if ($config['URL_HTML_SUFFIX']) {
				$_SERVER['PATH_INFO'] = preg_replace('/\.' . trim($config['URL_HTML_SUFFIX'], '.') . '$/i', '', $_SERVER['PATH_INFO']);
			}
			$paths = explode($depr, trim($_SERVER['PATH_INFO'], '/'));
			if ($config['VAR_URL_PARAMS']) {
				// 直接通过$_GET['_URL_'][1] $_GET['_URL_'][2] 获取URL参数 方便不用路由时参数获取
				$_GET[$config['VAR_URL_PARAMS']] = $paths;
			}
			$var = [];
			if (! isset($_GET[$config['VAR_GROUP']])) {
				$grouplist = env('APP_GROUP_LIST') ? env('APP_GROUP_LIST') : $config['DEFAULT_GROUP_LIST'];
				$var[$config['VAR_GROUP']] = in_array(strtolower($paths[0]), explode(',', strtolower($grouplist))) ? array_shift($paths) : '';
			}
			if (! isset($_GET[$config['VAR_MODULE']])) {// 还没有定义模块名称
				$var[$config['VAR_MODULE']] = array_shift($paths);
			}
			$var[$config['VAR_ACTION']] = array_shift($paths);

			// 解析剩余的URL参数
			preg_replace_callback('@(\w+)' . $depr . '([^' . $depr . '\/]+)@', function ($res) use (&$var) { $var[$res[1]] = $res[2]; }, implode($depr, $paths));

			$_GET = array_merge($var, $_GET);
		}

		// 获取分组 模块和操作名称
		define('GROUP_NAME', self::getGroup($config['VAR_GROUP'], $config));
		define('MODULE_NAME', self::getModule($config['VAR_MODULE'], $config));
		define('ACTION_NAME', self::getAction($config['VAR_ACTION'], $config));
		//保证$_REQUEST正常取值
		$_REQUEST = array_merge($_POST, $_GET);
	}

	/**
	 * 执行方法.
	 */
	private static function exec()
	{
		// 安全检测
		if (! preg_match('/^[A-Za-z_0-9]+$/', MODULE_NAME)) {
			$module = false;
		} else {
			//创建控制器实例
			$module = loadClass(GROUP_NAME . '/' . 'controller' . '/' . MODULE_NAME);
		}
		if (! $module) {
			halt('_CLASS_NOT_EXIST_ : ' . GROUP_NAME . '~' . MODULE_NAME);
		}
		//获取当前操作名
		$action = ACTION_NAME;
		if (! method_exists($module, $action)) {
			halt('_ACTION_NOT_EXIST_ : ' . GROUP_NAME . '~' . MODULE_NAME . '~' . ACTION_NAME);
		}
		//执行当前操作
		call_user_func([&$module, $action]);
	}

	/**
	 * 获得实际的模块名称.
	 *
	 * @param mixed $var
	 * @param mixed $config
	 */
	private static function getModule($var, $config)
	{
		$module = (! empty($_GET[$var]) ? $_GET[$var] : $config['DEFAULT_MODULE']);
		unset($_GET[$var]);
		// 智能识别方式 /user_type/index/ 识别到 UserTypeAction 模块
		$module = ucfirst(parse_name(strtolower($module), 1));
		return strip_tags($module);
	}

	/**
	 * 获得实际的操作名称.
	 *
	 * @param mixed $var
	 * @param mixed $config
	 */
	private static function getAction($var, $config)
	{
		$action = ! empty($_POST[$var]) ?
			$_POST[$var] :
			(! empty($_GET[$var]) ? $_GET[$var] : $config['DEFAULT_ACTION']);
		unset($_POST[$var],$_GET[$var]);
		return strip_tags($action);
	}

	/**
	 * 获得实际的分组名称.
	 *
	 * @param mixed $var
	 * @param mixed $config
	 */
	private static function getGroup($var, $config)
	{
		$group = (! empty($_GET[$var]) ? $_GET[$var] : $config['DEFAULT_GROUP']);
		unset($_GET[$var]);
		return strip_tags(strtolower($group));
	}
}
