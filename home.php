<?php
	/**
	* phpGroupWare
	*
	* phpgroupware base
	* @author Joseph Engo <jengo@phpgroupware.org>
	* @copyright Copyright (C) 2000-2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @package phpgroupware
	* @version $Id: home.php,v 1.36 2006/12/26 11:35:34 skwashd Exp $
	*/

	/**
	* @global array $phpgw_info
	*/
	$phpgw_info = array();

	$GLOBALS['sessionid'] = $_REQUEST['sessionid'];
	if (!isset($GLOBALS['sessionid']) || !$GLOBALS['sessionid'])
	{
		Header('Location: login.php');
		exit;
	}

	$GLOBALS['phpgw_info']['flags'] = array(
		'noheader'                => True,
		'nonavbar'                => True,
		'currentapp'              => 'home',
		'enable_network_class'    => True,
		'enable_contacts_class'   => True,
		'enable_nextmatchs_class' => True
	);
	
	/**
	* Include phpgroupware header
	*/
	include_once('header.inc.php');

	// check if forward parameter is set
	if ( isset($_GET['phpgw_forward']) && is_array($_GET['phpgw_forward']) )
	{
		foreach($_GET as $name => $value)
		{
			// find phpgw_ in the $_GET parameters but skip phpgw_forward because of redirect call below
			if (ereg('phpgw_', $name) && ($name != 'phpgw_forward'))
			{
				$name = substr($name, 6); // cut 'phpgw_'
				$extra_vars[$name] = $value;
			}
		}

		$GLOBALS['phpgw']->redirect_link($_GET['phpgw_forward'], $extra_vars);
		exit;
	}

	if ( isset($GLOBALS['phpgw_info']['server']['force_default_app'])
		&& $GLOBALS['phpgw_info']['server']['force_default_app'] != 'user_choice')
	{
		$GLOBALS['phpgw_info']['user']['preferences']['common']['default_app'] = $GLOBALS['phpgw_info']['server']['force_default_app'];
	}

	/* The 1990s just called and asked for their frames back
	if ( isset($GLOBALS['phpgw_info']['user']['preferences']['common']['useframes'])
		&& ($GLOBALS['phpgw_info']['server']['useframes'] == 'allowed'
		|| $GLOBALS['phpgw_info']['server']['useframes'] == 'always') )
		{
			if (isset($_GET['cd']) && $_GET['cd'] == 'yes')
			{
				if (! $navbarframe && ! $framebody)
				{
					$tpl = new Template(PHPGW_TEMPLATE_DIR);
					$tpl->set_file(array(
						'frames'       => 'frames.tpl',
						'frame_body'   => 'frames_body.tpl',
						'frame_navbar' => 'frames_navbar.tpl'
					));
					$tpl->set_var('navbar_link',$GLOBALS['phpgw']->link('index.php','navbarframe=True&cd=yes'));
					if ($GLOBALS['forward'])
					{
						$tpl->set_var('body_link',$GLOBALS['phpgw']->link($GLOBALS['forward']));
					}
					else
					{
						$tpl->set_var('body_link',$GLOBALS['phpgw']->link('index.php','framebody=True&cd=yes'));
					}

					if ($GLOBALS['phpgw_info']['user']['preferences']['common']['frame_navbar_location'] == 'bottom')
					{
						$tpl->set_var('frame_size','*,60');
						$tpl->parse('frames_','frame_body',True);
						$tpl->parse('frames_','frame_navbar',True);
					}
					else
					{
						$tpl->set_var('frame_size','60,*');
						$tpl->parse('frames_','frame_navbar',True);
						$tpl->parse('frames_','frame_body',True);
					}
					$tpl->pparse('out','frames');
				}
				if ($navbarframe)
				{
					$GLOBALS['phpgw']->common->phpgw_header();
					echo parse_navbar();
				}
			}
		}
		else
		*/
		if (isset($_GET['cd']) && $_GET['cd']=='yes' 
			&& isset($GLOBALS['phpgw_info']['user']['preferences']['common']['default_app'])
			&& $GLOBALS['phpgw_info']['user']['preferences']['common']['default_app']
			&& $GLOBALS['phpgw_info']['user']['apps'][$GLOBALS['phpgw_info']['user']['preferences']['common']['default_app']])
		{
			$GLOBALS['phpgw']->redirect_link('/' . $GLOBALS['phpgw_info']['user']['preferences']['common']['default_app'] . '/' . 'index.php');
			exit;
		}
		else
		{
			$GLOBALS['phpgw']->common->phpgw_header();
			echo parse_navbar();
		}

		$GLOBALS['phpgw']->translation->add_app('mainscreen');
		if (lang('mainscreen_message') != 'mainscreen_message*')
		{
			echo '<div class="msg">' . stripslashes(lang('mainscreen_message')) . '</div>';
		}

		if ((isset($GLOBALS['phpgw_info']['user']['apps']['admin']) &&
			$GLOBALS['phpgw_info']['user']['apps']['admin']) &&
			(isset($GLOBALS['phpgw_info']['server']['checkfornewversion']) &&
			$GLOBALS['phpgw_info']['server']['checkfornewversion']))
		{
			$GLOBALS['phpgw']->network->set_addcrlf(False);
			$lines = $GLOBALS['phpgw']->network->gethttpsocketfile('http://www.phpgroupware.org/currentversion');
			for ($i=0; $i < count($lines); ++$i)
			{
				if (ereg('currentversion',$lines[$i]))
				{
					$line_found = explode(':',chop($lines[$i]));
				}
			}
			if($GLOBALS['phpgw']->common->cmp_version($GLOBALS['phpgw_info']['server']['versions']['phpgwapi'],$line_found[1]))
			{
				echo '<p>There is a new version of phpGroupWare available from <a href="'
					. 'http://www.phpgroupware.org">http://www.phpgroupware.org</a>';
			}

			$_found = False;
			$GLOBALS['phpgw']->db->query("SELECT app_name,app_version FROM phpgw_applications",__LINE__,__FILE__);
			while($GLOBALS['phpgw']->db->next_record())
			{
				$_db_version  = $GLOBALS['phpgw']->db->f('app_version');
				$_app_name    = $GLOBALS['phpgw']->db->f('app_name');
				$_versionfile = $GLOBALS['phpgw']->common->get_app_dir($_app_name) . '/setup/setup.inc.php';
				if(file_exists($_versionfile))
				{
					include($_versionfile);
					$_file_version = $setup_info[$_app_name]['version'];
					$_app_title    = $GLOBALS['phpgw_info']['apps'][$_app_name]['title'];
					unset($setup_info);

					if($GLOBALS['phpgw']->common->cmp_version_long($_db_version,$_file_version))
					{
						$_found = True;
						$_app_string .= '<br />' . $_app_title;
					}
					unset($_file_version);
					unset($_app_title);
				}
				unset($_db_version);
				unset($_versionfile);
			}
			if($_found)
			{
				echo '<br />' . lang('The following applications require upgrades') . ':' . "\n";
				echo $_app_string . "\n";
				echo '<br />' . lang('Please run setup to become current') . '.' . "\n";
				unset($_app_string);
			}
		}

	if (isset($GLOBALS['phpgw_info']['user']['apps']['notifywindow']) &&
		$GLOBALS['phpgw_info']['user']['apps']['notifywindow'])
	{
?>
<script language="JavaScript" type="text/javascript">
	var NotifyWindow;

	function opennotifywindow()
	{
		if (NotifyWindow)
		{
			if (NotifyWindow.closed)
			{
				NotifyWindow.stop;
				NotifyWindow.close;
			}
		}
		NotifyWindow = window.open("<?php echo $GLOBALS['phpgw']->link('/notify.php')?>", "NotifyWindow", "width=300,height=35,location=no,menubar=no,directories=no,toolbar=no,scrollbars=yes,resizable=yes,status=yes");
		if (NotifyWindow.opener == null)
		{
			NotifyWindow.opener = window;
		}
	}
</script>
<?php
		echo '<a href="#" onclick="opennotifywindow()">' . lang('Open notify window') . '</a>';
	}

	// This initializes the users portal_order preference if it does not exist.
	if ( (!isset($GLOBALS['phpgw_info']['user']['preferences']['portal_order']) || !is_array($GLOBALS['phpgw_info']['user']['preferences']['portal_order']) )
		&& $GLOBALS['phpgw_info']['apps'] )
	{
		$GLOBALS['phpgw']->preferences->delete('portal_order');
		$order = 0;
		foreach ( $GLOBALS['phpgw_info']['apps'] as $p )
		{
			if ( isset($GLOBALS['phpgw_info']['user']['apps'][$p['name']])
				&& $GLOBALS['phpgw_info']['user']['apps'][$p['name']] )
			{
				$GLOBALS['phpgw']->preferences->add('portal_order', ++$order, $p['id']);
			}
		}
		$GLOBALS['phpgw_info']['user']['preferences'] = $GLOBALS['phpgw']->preferences->save_repository();
	}

	if ( isset($GLOBALS['phpgw_info']['user']['preferences']['portal_order'])
		&& is_array($GLOBALS['phpgw_info']['user']['preferences']['portal_order']) )
	{
		$app_check = array();
		ksort($GLOBALS['phpgw_info']['user']['preferences']['portal_order']);
		foreach($GLOBALS['phpgw_info']['user']['preferences']['portal_order'] as $app)
		{
			if(!isset($app_check[$app]) || !$app_check[$app])
			{
				$app_check[$app] = true;
				$sorted_apps[] = $GLOBALS['phpgw']->applications->id2name($app);
			}
		}
	}
	else
	{
		$sorted_apps = array
		(
			'email',
			'calendar',
			'news_admin',
			'addressbook',
		);
	}

	$GLOBALS['phpgw']->hooks->process('home',$sorted_apps);

	if ( isset($GLOBALS['portal_order']) )
	{
		$GLOBALS['phpgw']->preferences->delete('portal_order');
		@reset($GLOBALS['portal_order']);
		while(list($app_order,$app_id) = each($GLOBALS['portal_order']))
		{
			$GLOBALS['phpgw']->preferences->add('portal_order',$app_order,$app_id);
		}
		$GLOBALS['phpgw']->preferences->save_repository();
	}

	//$phpgw->common->debug_phpgw_info();
	//$phpgw->common->debug_list_core_functions();
	$GLOBALS['phpgw']->common->phpgw_footer();
?>