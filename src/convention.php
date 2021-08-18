<?php
/**
 * @contact  nydia87 <349196713@qq.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0
 */
/*
 * 配置文件
 */
if (! defined('COLAPHP_CORE_PATH')) {
	exit();
}

return [
	/* 默认设定 */
	'DEFAULT_GROUP_LIST' => 'Home,Admin', //分组列表
	'DEFAULT_GROUP' => 'Home',  // 默认分组
	'DEFAULT_MODULE' => 'Index', // 默认模块名称
	'DEFAULT_ACTION' => 'index', // 默认操作名称

	/* 错误设置 */
	'ERROR_MESSAGE' => '您浏览的页面暂时发生了错误！请稍后再试～', //错误显示信息,非调试模式有效

	/* 模板引擎设置 */
	'TMPL_EXCEPTION_FILE' => COLAPHP_CORE_PATH . 'Tpl/exception.tpl', // 异常页面的模板文件
	'TMPL_TEMPLATE_SUFFIX' => '.html',     // 默认模板文件后缀
	'TMPL_FILE_DEPR' => '/', //模板文件MODULE_NAME与ACTION_NAME之间的分割符，只对项目分组部署有效

	/* URL设置 */
	'URL_PATHINFO_DEPR' => '/',	// PATHINFO模式下，各参数之间的分割符号
	'URL_PATHINFO_FETCH' => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL', // 用于兼容判断PATH_INFO 参数的SERVER替代变量列表
	'URL_HTML_SUFFIX' => '.html',  // URL伪静态后缀设置

	/* 系统变量名称设置 */
	'VAR_GROUP' => 'group',     // 默认分组获取变量
	'VAR_MODULE' => 'controller',	// 默认模块获取变量
	'VAR_ACTION' => 'action',		// 默认操作获取变量
	'VAR_PATHINFO' => 's',	// PATHINFO 兼容模式获取变量例如 ?s=/module/action/id/1 后面的参数取决于URL_PATHINFO_DEPR
	'VAR_URL_PARAMS' => '_URL_', // PATHINFO URL参数变量
];
