<?php
/**
 * This file is part of web2Project.
 *
 * web2Project is free software; you can redistribute it and/or modify it under the terms of the Clear BSD License as
 *  published by MetaCarta. The full text of this license is included in LICENSE.
 */

require_once 'bootstrap.php';

$loginFromPage = 'index.php';

// don't output anything. Usefull for fileviewer.php, gantt.php, etc.
$suppressHeaders = w2PgetParam($_GET, 'suppressHeaders', false);

// write the HTML headers
if (!$suppressHeaders) {
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: no-cache, must-revalidate, no-store, post-check=0, pre-check=0'); // HTTP/1.1
    header('Pragma: no-cache'); // HTTP/1.0
    header("Content-type: text/html; charset=UTF-8");
}
// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// check if session has previously been initialised
if (!isset($_SESSION['AppUI']) || isset($_GET['logout'])) {
	if (isset($_GET['logout']) && isset($_SESSION['AppUI']->user_id)) {
		$AppUI = &$_SESSION['AppUI'];
		$user_id = $AppUI->user_id;
		addHistory('login', $AppUI->user_id, 'logout', $AppUI->user_first_name . ' ' . $AppUI->user_last_name);
	}

	$_SESSION['AppUI'] = new w2p_Core_CAppUI();
}
$AppUI = &$_SESSION['AppUI'];
$last_insert_id = $AppUI->last_insert_id;

$AppUI->setStyle();

//Function for update lost action in user_access_log
$AppUI->updateLastAction($last_insert_id);
// load default preferences if not logged in
if ($AppUI->doLogin()) {
	$AppUI->loadPrefs(0);
}

// load module based locale settings
$AppUI->setUserLocale();
include W2P_BASE_DIR . '/locales/' . $AppUI->user_locale . '/locales.php';
include W2P_BASE_DIR . '/locales/core.php';
setlocale(LC_TIME, $AppUI->user_lang);

//Function register logout in user_acces_log
if (isset($user_id) && isset($_GET['logout'])) {
	$AppUI->registerLogout($user_id);
}

// set the default ui style
$uistyle = $AppUI->getPref('UISTYLE') ? $AppUI->getPref('UISTYLE') : w2PgetConfig('host_style');
include W2P_BASE_DIR . '/style/' . $uistyle . '/overrides.php';
$uiName = str_replace('-', '', $uistyle);

$uiClass = 'style_' . $uiName;
$theme = new $uiClass($AppUI);

// check is the user needs a new password
if (w2PgetParam($_POST, 'lostpass', 0)) {
	if (w2PgetParam($_POST, 'sendpass', 0)) {
		sendNewPass();
	} else {
        include $theme->resolveTemplate('lostpass');
	}
	exit();
}

// check if the user is trying to log in
// Note the change to REQUEST instead of POST.  This is so that we can
// support alternative authentication methods such as the PostNuke
// and HTTP auth methods now supported.
if (isset($_POST['login'])) {
	$username = w2PgetParam($_POST, 'username', '');
	$password = w2PgetParam($_POST, 'password', '');
	$redirect = w2PgetParam($_POST, 'redirect', '');
	$ok = $AppUI->login($username, $password);
	if (!$ok) {
		$AppUI->setMsg('Login Failed', UI_MSG_ERROR);
	} else {
		//Register login in user_acces_log
		$AppUI->registerLogin();
	}
	addHistory('login', $AppUI->user_id, 'login', $AppUI->user_first_name . ' ' . $AppUI->user_last_name);
	$AppUI->redirect('' . $redirect);
}

// check if we are logged in
if ($AppUI->doLogin()) {
	$redirect = $_SERVER['QUERY_STRING'] ? strip_tags($_SERVER['QUERY_STRING']) : '';
	if (strpos($redirect, 'logout') !== false) {
		$redirect = '';
	}

    include $theme->resolveTemplate('login');
	// destroy the current session and output login page
	session_unset();
	session_destroy();
	exit;
}

if (W2P_PERFORMANCE_DEBUG) {
    $w2p_performance_setuptime = (array_sum(explode(' ', microtime())) - $w2p_performance_time);
}

/**
 * TODO: We should validate that the module identified by $m is actually
 *   installed & active. If not, we should go back to the defaults.
 */
$perms = &$AppUI->acl();
$def_a = 'index';
if (!isset($_GET['m']) && !empty($w2Pconfig['default_view_m'])) {
	if (!$perms->checkModule($w2Pconfig['default_view_m'], 'view', $AppUI->user_id)) {
		$m = 'public';
		$def_a = 'welcome';
	} else {
		$m = $w2Pconfig['default_view_m'];
		$def_a = !empty($w2Pconfig['default_view_a']) ? $w2Pconfig['default_view_a'] : $def_a;
		$tab = $w2Pconfig['default_view_tab'];
        $_GET['tab'] = $tab;
	}
}

$loader = new w2p_FileSystem_Loader();
$m = $loader->makeFileNameSafe(w2PgetParam($_GET, 'm', 'public'));
$a = $loader->makeFileNameSafe(w2PgetParam($_GET, 'a', $def_a));
/**
 * This check for $u implies that a file located in a subdirectory of higher depth than 1 in relation to the module base
 *   can't be executed. So it wouldn't be possible to run for example the file module/directory1/directory2/file.php
 */
$u = $loader->makeFileNameSafe(w2PgetParam($_GET, 'u', ''));

if ($m == 'projects' && $a == 'view' && $w2Pconfig['projectdesigner_view_project'] && !w2PgetParam($_GET, 'bypass') && !(isset($_GET['tab']))) {
	if ($AppUI->isActiveModule('projectdesigner')) {
		$m = 'projectdesigner';
		$a = 'index';
	}
}

if ($u && file_exists(W2P_BASE_DIR . '/modules/' . $m . '/' . $u . '/' . $u . '.class.php')) {
	include W2P_BASE_DIR . '/modules/' . $m . '/' . $u . '/' . $u . '.class.php';
}

// include the module ajax file - we use file_exists instead of @ so  that any parse errors in the file are reported,
//   rather than errors further down the track.
$modajax = $AppUI->getModuleAjax($m);
if (file_exists($modajax)) {
	include $modajax;
}
if ($u && file_exists(W2P_BASE_DIR . '/modules/' . $m . '/' . $u . '/' . $u . '.ajax.php')) {
	include W2P_BASE_DIR . '/modules/' . $m . '/' . $u . '/' . $u . '.ajax.php';
}

// do some db work if dosql is set
// TODO - MUST MOVE THESE INTO THE MODULE DIRECTORY
if (isset($_POST['dosql'])) {
	require W2P_BASE_DIR . '/modules/' . $m . '/' . ($u ? ($u . '/') : '') . $loader->makeFileNameSafe($_POST['dosql']) . '.php';
}

// start output proper
if (isset($_POST['dosql']) && $_POST['dosql'] == 'do_file_co') {
	ob_start();
} else {
	if(!ob_start('ob_gzhandler')) {
		ob_start();
	}
}

if (!$suppressHeaders) {
	include $theme->resolveTemplate('header');
}

$pageHandler = new w2p_Output_PageHandler();
$all_tabs   = $pageHandler->loadExtras($_SESSION, $AppUI, $m, 'tabs');
$all_crumbs = $pageHandler->loadExtras($_SESSION, $AppUI, $m, 'crumbs');

$module_file = W2P_BASE_DIR . '/modules/' . $m . '/' . ($u ? ($u . '/') : '') . $a . '.php';
if (file_exists($module_file)) {
	require $module_file;
} else {
	// TODO: make this part of the public module?
	$titleBlock = new w2p_Theme_TitleBlock($AppUI->_('Warning'), 'log-error.gif');
	$titleBlock->show();

    echo $theme->styleRenderBoxTop();
    include $theme->resolveTemplate('missing_module');
}
if (!$suppressHeaders) {
	//echo '<iframe name="thread" src="' . W2P_BASE_URL . '/modules/index.html" width="0" height="0" frameborder="0"></iframe>';
	//echo '<iframe name="thread2" src="' . W2P_BASE_URL . '/modules/index.html" width="0" height="0" frameborder="0"></iframe>';
 	//Theme footer goes before the performance box
    $AppUI->getTheme()->loadCalendarJS();
    include $theme->resolveTemplate('footer');
	if (W2P_PERFORMANCE_DEBUG) {
		include $theme->resolveTemplate('performance');
	}
    include $theme->resolveTemplate('message_loading');

	//close the body and html here, instead of on the theme footer.
	echo '</body>
          </html>';
}
ob_end_flush();