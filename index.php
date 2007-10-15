<?php
	/**
	* phpGroupWare
	*
	* phpgroupware base
	* @copyright Copyright (C) 2000-2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @package phpgroupware
	* @version $Id: index.php,v 1.134 2006/12/28 04:09:50 skwashd Exp $
	*/

	$phpgw_info = array();
	if (!file_exists('header.inc.php'))
	{
		Header('Location: setup/index.php');
		exit;
	}

	/**
	* @global string $GLOBALS['sessionid']
	*/
	$GLOBALS['sessionid'] = isset($_REQUEST['sessionid'])? $_REQUEST['sessionid'] : '';

	$invalid_data = false;
	// This is the preliminary menuaction driver for the new multi-layered design
	if (isset($_GET['menuaction']))
	{
		list($app,$class,$method) = explode('.',$_GET['menuaction']);
		if (! $app || ! $class || ! $method)
		{
			$invalid_data = true;
		}
	}
	else
	{
	//$phpgw->log->message('W-BadmenuactionVariable, menuaction missing or corrupt: %1',$menuaction);
	//$phpgw->log->commit();

		$app = 'home';
		$invalid_data = true;
	}

	$api_requested = false;
	if ($app == 'phpgwapi')
	{
		$app = 'home';
		$api_requested = true;
	}

	$GLOBALS['phpgw_info']['flags'] = array
	(
		'noheader'   => true,
	//	'nonavbar'   => true,
		'currentapp' => $app
	);
	
	/**
	* Include phpgroupware header
	*/
	include_once('header.inc.php');

	if ($app == 'home' && ! $api_requested)
	{
		$GLOBALS['phpgw']->redirect_link('/home.php');
	}

	if ($api_requested)
	{
		$app = 'phpgwapi';
	}

	$GLOBALS[$class] = CreateObject("{$app}.{$class}");

	if ( !$invalid_data 
		&& is_object($GLOBALS[$class])
		&& isset($GLOBALS[$class]->public_functions) 
		&& is_array($GLOBALS[$class]->public_functions) 
		&& isset($GLOBALS[$class]->public_functions[$method])
		&& $GLOBALS[$class]->public_functions[$method] )

	{
		if ( isset($_GET['phpgw_return_as']) && $_GET['phpgw_return_as'] == 'json' )
		{
			Header('Content-Type: text/javascript');
			$return_data = $GLOBALS[$class]->$method();
			echo execMethod('phpgwapi.Services_JSON.encode', $return_data);
			$GLOBALS['phpgw_info']['flags']['nofooter'] = true;
			$GLOBALS['phpgw']->common->phpgw_exit();
		}
		else
		{
			$GLOBALS[$class]->$method();
		}
		unset($app);
		unset($class);
		unset($method);
		unset($invalid_data);
		unset($api_requested);
	}
	else
	{
		//FIXME make this handle invalid data better
		if (! $app || ! $class || ! $method)
		{
			$GLOBALS['phpgw']->log->message(array(
				'text' => 'W-BadmenuactionVariable, menuaction missing or corrupt: %1',
				'p1'   => $menuaction,
				'line' => __LINE__,
				'file' => __FILE__
			));
		}

		if ( ( !isset($GLOBALS[$class]->public_functions)
			|| !is_array($GLOBALS[$class]->public_functions)
			|| !isset($GLOBALS[$class]->public_functions[$method])
			|| !$GLOBALS[$class]->public_functions[$method] )
			&& $method)
		{
			$GLOBALS['phpgw']->log->message(array(
				'text' => 'W-BadmenuactionVariable, attempted to access private method: %1',
				'p1'   => $method,
				'line' => __LINE__,
				'file' => __FILE__
			));
		}
		$GLOBALS['phpgw']->log->commit();

		$GLOBALS['phpgw']->redirect_link('/home.php');
	}
	$GLOBALS['phpgw']->common->phpgw_footer();
?>