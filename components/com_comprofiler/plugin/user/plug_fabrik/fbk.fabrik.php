<?php
/**
*/

use CB\Database\Table\PluginTable;
use CB\Database\Table\TabTable;
use CB\Database\Table\UserTable;
use CBLib\Language\CBTxt;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;

// TODO: This should be in a function: We should have no code in files outside of classes:
$_PLUGINS->loadPluginGroup( 'user' );

class getFabrikTab extends cbTabHandler {

	function getFabrikTab()
	{
		$this->cbTabHandler();
	}

	function __construct($tab,$user,$ui)
	{
		// $$$ hugh - added privacy option, so you can restrict who sees the tab, requested on forums:
		// http://fabrikar.com/forums/showthread.php?p=128127#post128127
		// privacy setting:
		// 0 = public
		// 1 = profile owner only
		// 2 = profile owner and admins
		$private = (int)$this->params->get('fabrik_private', '0');
		if ($private > 0) {
			$viewer = JFactory::getuser();
			if ($private === 1) {
				if ($user->get('user_id') != $viewer->get('id')) {
					return false;
				}
			}
			else if ($private === 2) {
				if ($user->get('id') !== $viewer->get('id') && ($viewer->get('gid') != 24 && $viewer->get('gid') != 25)) {
					return false;
				}
			}
		}
		$dispatcher = new JDispatcher();
		JPluginHelper::importPlugin('content', 'fabrik', true, $dispatcher);
		if (JPluginHelper::importPlugin('content', 'fabrik', true, $dispatcher) !== true)
		{
			throw new RuntimeException(JText::_('Fabrik content plugin not loaded in CB tab!  Check that it is installed and enabled.'), 400);
		}
		$dispatcher->register('content', 'plgContentFabrik');
		$args = array();
		$article = new stdClass();
		$txt = $this->params->get('plugintext');

		// $$$ hugh - set profile user in session so Fabrik user element can get at it
		// TODO - should really make this table/form specific!

		$session = JFactory::getSession();
		// $$$ hugh - testing using a unique session hash, which we will stuff in the
		// plugin args, and will get added where necessary in Fabrik lists and forms so
		// we can actually track the right form submissions with their coresponding CB
		// profiles.
		$social_hash = md5(serialize(array(JRequest::getURI(), $tab, $user)));
		$session->set('fabrik.plugin.' . $social_hash . '.profile_id', $user->get('id'));
		// do the old style one without the hash for backward compat
		$session->set('fabrik.plugin.profile_id', $user->get('id'));

		if (empty($txt))
		{
			$txt = '{fabrik_social_profile_hash=' . $social_hash . '}';
		}
		else
		{
			$txt = rtrim($txt, '}') . " fabrik_social_profile_hash=" . $social_hash . '}';
		}

		//do some dynamic replacesments with the owner's data
		foreach ($user as $k=>$v) {
			if (strstr( $txt, "{\$my->$k}" )) {
				$txt = str_replace("{\$my->$k}", $v, $txt);
			}
			else if (strstr( $txt, "[\$my->$k]" )) {
				$txt = str_replace("[\$my->$k]", $v, $txt);
			}
			// $$$ hugh - might as well stuff the entire CB user object in the session
			$session->set('fabrik.plugin.' . $social_hash . '.' . $k, $v);
		}

		$params = new stdClass();
		$args[] = 0;
		$article->text = $txt;
		$args[] = &$article;
		$args[] = &$params;
		$res = $dispatcher->trigger('onContentPrepare', $args);
		// $$$ peamak http://fabrikar.com/forums/showthread.php?t=10446&page=2
    $dispatcher->register('content', 'plgContentFabrik');
		return  $article->text;
	}
}
?>
