<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
define('COLAPHP_CORE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR); //框架目录
define('COLAPHP_CORE_CONFIG', 'COLAPHPCONFIG'); //框架配置项名

if (! function_exists('halt')) {
	/**
	 * 错误输出.
	 *
	 * APP_DEBUG|ERROR_PAGE|SHOW_ERROR_MSG 可在项目env中配置
	 * @param mixed $error
	 */
	function halt($error)
	{
		$e = [];
		$config = config(COLAPHP_CORE_CONFIG);
		if (env('APP_DEBUG')) {
			//调试模式下输出错误信息
			if (! is_array($error)) {
				$trace = debug_backtrace();
				$e['message'] = $error;
				$e['file'] = $trace[0]['file'];
				$e['class'] = $trace[0]['class'];
				$e['function'] = $trace[0]['function'];
				$e['line'] = $trace[0]['line'];
				$traceInfo = '';
				$time = date('y-m-d H:i:m');
				foreach ($trace as $t) {
					$traceInfo .= '[' . $time . '] ' . $t['file'] . ' (' . $t['line'] . ') ';
					$traceInfo .= $t['class'] . $t['type'] . $t['function'] . '(';
					$traceInfo .= implode(', ', $t['args']);
					$traceInfo .= ')<br/>';
				}
				$e['trace'] = $traceInfo;
			} else {
				$e = $error;
			}
			// 包含异常页面模板
			include $config['TMPL_EXCEPTION_FILE'];
		} else {
			//否则定向到错误页面
			$error_page = env('ERROR_PAGE');
			if (! empty($error_page)) {
				redirect($error_page);
			} else {
				if (env('SHOW_ERROR_MSG')) {
					$e['message'] = is_array($error) ? $error['message'] : $error;
				} else {
					$e['message'] = $config['ERROR_MESSAGE'];
				}
				// 包含异常页面模板
				include $config['TMPL_EXCEPTION_FILE'];
			}
		}
		exit;
	}
}

if (! function_exists('getCoreLog')) {
	/**
	 * 获取全局Log.
	 *
	 * @return object
	 */
	function getCoreLog()
	{
		static $log;
		if (! isset($log)) {
			$log = new \Colaphp\Utils\Log();
			$log->init(['path' => is_null(LOG_PATH) ? ROOT_PATH : LOG_PATH, 'apart_level' => ['error']]);
		}
		return $log;
	}
}

if (! function_exists('loadClass')) {
	/**
	 * 调用项目类.
	 *
	 * @param string $name 支持三或二级 group/action|model/class
	 * @param string $psr4
	 * @param string $ext
	 */
	function loadClass($name = '', $psr4 = 'App', $ext = '.php')
	{
		static $_class = [];
		if (isset($_class[$name])) {
			return $_class[$name];
		}
		if (empty($name)) {
			return false;
		}
		$names = explode('/', $name);
		if (count($names) == 3) {
			$group = strtolower($names[0]);
			$model = strtolower($names[1]);
			$action = ucfirst($names[2]);
		} elseif (count($names) == 2) {
			$group = strtolower(GROUP_NAME);
			$model = strtolower($names[0]);
			$action = ucfirst($names[1]);
		} else {
			return false;
		}
		$class = sprintf('\\%s\\%s\\%s\\%s', $psr4, $group, $model, $action);
		//$class = basename($class);//TODO WIN系统需带上命名空间
		$file = ROOT_PATH . sprintf('/%s/%s/%s/%s', strtolower($psr4), $group, $model, $action) . $ext;
		if (! is_file($file)) {
			return false;
		}
		include $file;
		if (! class_exists($class, false)) {
			return false;
		}
		$obj = new $class();
		$_class[$name] = $obj;
		return $obj;
	}
}
