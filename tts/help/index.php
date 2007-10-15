<?php
	/**
	* Trouble Ticket System - User manual
	*
	* @copyright Copyright (C) 2001,2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @package tts
	* @subpackage manual
	* @version $Id: index.php,v 1.3 2005/05/10 16:12:38 powerstat Exp $
	*/

	$phpgw_flags = Array(
		'currentapp'	=> 'manual'
	);
	$phpgw_info['flags'] = $phpgw_flags;
	
	/**
	* Include phpgroupware header
	*/
	include('../../header.inc.php');
	$appname = 'tts';
	
	/**
	* Include TTS setup
	*/
	include(PHPGW_SERVER_ROOT.'/'.$appname.'/setup/setup.inc.php');
?>
<img src="<?php echo $phpgw->common->image($appname,'navbar.gif'); ?>" border="0"><p/>
<font face="<?php echo $phpgw_info['theme']['font']; ?>" size="2">
Version: <b><?php echo $setup_info[$appname]['version']; ?></b>
</font>
<?php $phpgw->common->phpgw_footer(); ?>