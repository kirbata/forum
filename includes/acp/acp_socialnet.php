<?php
/**
 *
 * @package phpBB3 Social Network
 * @version 0.6.1
 * @copyright (c) 2011 Kamahl & Culprit http://socialnetwork.phpbb3hacks.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * @package Socialnet
 * @subpackage ACP
 */
class acp_socialnet
{
	/**
	 * @var string $mode Default phpBB variable called from $p_master
	 */
	var $mode = '';
	/**
	 * @var string $u_action Kompletni ACP URL pro odesilani formularu
	 */
	var $u_action;
	/**
	 * @var string $acpPanel_title Zakladni nadpis ACP panelu
	 */
	var $acpPanel_title = '';
	/**
	 * @var string $acpPanel_explain Zakladni popis panelu pod titulkem
	 */
	var $acpPanel_explain = '';
	/**
	 * @var string $motivationPicture URL motivacniho obrazku
	 */
	var $motivationPicture = '';
	/**
	 * @var array $new_config Pole pro nove nastaveni
	 */
	var $new_config = array();

	function main($id, $mode)
	{
		global $config, $db, $user, $auth, $template;
		global $phpbb_root_path, $phpbb_admin_path, $phpEx, $socialnet_root_path;

		$user->add_lang(array('acp/common', 'acp/board', 'mods/socialnet_acp', 'acp/users'));

		$this->tpl_name = 'acp_socialnet';
		$this->page_title = 'ACP_CAT_SOCIALNET';
		$this->acpPanel_title = $user->lang[$this->page_title];

		// Default call function
		$call_module = 'sett_main';
		$block_match = $module_match = array();

		// Prepare function call for ACP panel
		if (preg_match('/^module_(.+)/i', $mode, $module_match))
		{
			$call_module = 'module';
			$module = $module_match[1];
		}
		else if ($mode != 'main')
		{
			$call_module = $mode;
			$module = $mode;
		}
		else
		{
			$module = $mode;
			$mode = 'socialNet';
		}
		// Call specific function of this module dependend on $mode
		$this->$call_module($id, $module);

		// Prepare motivation picture
		$this->motivationPicture = $socialnet_root_path . 'styles/images/' . $mode . '.png';

		$template->assign_vars(array(
			'L_TITLE'					 => $this->acpPanel_title,
			'L_TITLE_EXPLAIN'			 => $this->acpPanel_explain,
			'T_SOCIALNET_IMAGES_PATH'	 => $socialnet_root_path . 'styles/images',
			'S_MOTIONPICTURE'			 => file_exists($this->motivationPicture) ? true : false,
			'S_MOTIONPICTURE_IMG_URL'	 => $this->motivationPicture,
			'S_MODE'					 => $mode,
			'U_FIND_USERNAME'			 => append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=acp_socialnet_sn_basictools&amp;field=username&amp;select_single=true'),
		));
	}

	/**
	 * Loads configuration panel for any module defined in acp
	 * @access public
	 * @param integer $id phpBB variable
	 * @param string $module  Which acp panel module should be loaded into phpBB ACP
	 * @return void
	 */
	function module($id, $module)
	{
		global $config, $user, $auth, $template;
		global $phpbb_root_path, $phpbb_admin_path, $phpEx, $socialnet_root_path;

		$access_module = true;
		$error = array();

		// IS module enabled?
		if ($config['module_' . $module] == 0 || $config['sn_global_enable'] == 0)
		{
			$error[] = $user->lang['SN_MODULE_DISABLED'];
		}

		$module_acp_filename = "{$socialnet_root_path}acp/acp_{$module}.{$phpEx}";
		$module_filename = "{$socialnet_root_path}{$module}.{$phpEx}";

		// Exists ACP function file for this module?
		$s_sn_module = isset($user->lang['SN_MODULE_' . strtoupper($module)]) ? $user->lang['SN_MODULE_' . strtoupper($module)] : '{ SN_MODULE_' . strtoupper($module) . ' }';
		if (!file_exists($module_acp_filename))
		{
			$module_acp_filename = str_replace($phpbb_root_path, '/', $module_acp_filename);
			$access_module = false;
			$error[] = sprintf($user->lang['SN_ACP_MODULE_NOT_ACCESSIBLE'], $s_sn_module, $module_acp_filename);
		}
		// Exists module file for board?
		if (!file_exists($module_filename))
		{
			$module_filename = str_replace($phpbb_root_path, '/', $module_filename);
			$error[] = sprintf($user->lang['SN_MODULE_NOT_ACCESSIBLE'], $s_sn_module, $module_filename);
		}
		else
		{
			include($module_filename);
			if (!class_exists('socialnet_' . $module))
			{
				$error[] = sprintf($user->lang['SN_MODULE_NOT_EXISTS'], $module, $module_filename);
			}
		}

		// IF exist ACP function module try to load
		if ($access_module)
		{
			include($module_acp_filename);
			$acp_module = 'acp_' . $module;
			if (!class_exists($acp_module))
			{
				$error[] = $user->lang['ACP_PANEL_NOT_ACCESSIBLE'];
			}
			else
			{
				$modul = new $acp_module($this);
				$modul->main($id);
			}
		}
		$template->assign_vars(array(
			'S_ERROR'	 => (sizeof($error)) ? true : false,
			'ERROR_MSG'	 => implode('<br />', $error),
			'B_ACP_SN_' . strtoupper($module)			 => true
		));

		$this->acpPanel_title = isset($user->lang['ACP_SN_' . strtoupper($module) . '_SETTINGS']) ? $user->lang['ACP_SN_' . strtoupper($module) . '_SETTINGS'] : '{ ACP_SN_' . strtoupper($module) . '_SETTINGS }';
		$this->acpPanel_explain = sprintf($user->lang['ACP_SN_MODULE_SETTINGS_EXPLAIN'], isset($user->lang['SN_MODULE_' . strtoupper($module)]) ? $user->lang['SN_MODULE_' . strtoupper($module)] : '{ SN_MODULE_' . strtoupper($module) . ' }');

	}

	/**
	 * Turn on/off global use of Social Network
	 *
	 * Globální zapnutí či vypnutí Social Network<br />
	 * Pokud je potřeba v rámci jednotlivých modulů něco donastavit, bude zavolána funkce v rámci modulu <acp_modul>::acp_sett_main()
	 *
	 * @param integer $id phpBB variable
	 * @return void
	 */
	function sett_main($id)
	{
		global $db, $user, $template, $config, $cache, $socialnet_root_path, $phpEx, $socialnet;

		$display_vars = array(
			'title'	 => 'SETTINGS',
			'vars'	 => array(
				'legend1'			 => 'SETTINGS',
				'sn_global_enable'	 => array('lang' => 'SN_GLOBAL_ENABLE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
			),
		);

		$cfg_array = request_var('config', array('' => ''), true);

		$enabled = isset($cfg_array['sn_global_enable']) ? $cfg_array['sn_global_enable'] : $config['sn_global_enable'];

		$new_value = $enabled ? '1' : '0';

		$cache->purge('modules_acp');
		$cache->purge('modules_ucp');

		$sql = "UPDATE " . MODULES_TABLE . "
				SET module_enabled = '{$new_value}'
				WHERE module_basename = 'socialnet'
					AND module_mode LIKE 'module_%'";
		$db->sql_query($sql);

		// Spustit dodatecne upravy, jednotlivych modulu
		$sql = "SELECT module_mode
				FROM " . MODULES_TABLE . "
				WHERE module_basename = 'socialnet'
					AND module_class = 'acp'
					AND module_mode LIKE 'module_%'";
		$rs = $db->sql_query($sql);
		$modules = $db->sql_fetchrowset($rs);
		$db->sql_freeresult($rs);

		for ($i = 0; $i < count($modules) && isset($modules[$i]); $i++)
		{
			$module = preg_replace('/^module_/si', '', $modules[$i]['module_mode']);
			$acp_filename = "{$socialnet_root_path}acp/acp_{$module}.{$phpEx}";
			if (file_exists($acp_filename))
			{
				include_once($acp_filename);
				$acp_moduleclass = 'acp_' . $module;
				if (method_exists($acp_moduleclass, 'acp_sett_main'))
				{
					eval("$acp_moduleclass::acp_sett_main();");
				}
			}
		}

		$this->_settings($id, 'sn_main', $display_vars);
		$user->add_lang('install');

		if ($config['sn_global_enable'] == 0)
		{
			$user->data['user_im_sound'] = 0;
			$user->data['user_im_soundname'] = 'undefined';
			$socialnet = new socialnet();
		}
		$socialnet->_version_checker(array('host' => 'socialnetwork.phpbb3hacks.com', 'directory' => '', 'filename' => 'sn_modules.xml'));
		$this->acpPanel_explain = $user->lang['ACP_SN_WELCOME_TEXT'];
	}

	/**
	 * Configure which module of Social Network will be used
	 *
	 * Globální zapnutí či vypnutí jednotlivých modulů Social Network<br />
	 * Pokud je potřeba v rámci jednotlivých modulů něco donastavit, bude zavolána funkce v rámci modulu <acp_modul>::acp_sett_modules()
	 *
	 * @desc function take list available modules from SN_CONFIG_TABLE where config_name LIKE 'module_%'
	 * @param integer $id phpBB variable
	 * @return void
	 */
	function sett_modules($id)
	{
		global $config, $db, $user, $cache, $template, $socialnet_root_path, $phpEx;

		$sql = "SELECT * FROM " . SN_CONFIG_TABLE . " WHERE config_name LIKE 'module_%' ORDER BY config_name";

		$rs = $db->sql_query($sql);

		$modules = array();
		$modules['legend1'] = 'ACP_SN_AVAILABLE_MODULES';
		$module_current = array();
		while ($row = $db->sql_fetchrow($rs))
		{
			//$module_lang = 'SN_' . strtoupper(preg_replace( '/_([0-9])+_/si', '_', $row['config_name'])); // IF config_name of module is with number
			$module_current[$row['config_name']] = $row['config_value'];
			$module_lang = 'SN_' . strtoupper($row['config_name']) . '_NAME';
			$module_lang = isset($user->lang[$module_lang]) ? $module_lang : 'SN_' . strtoupper($row['config_name']);
			$modules[$row['config_name']] = array('lang' => $module_lang, 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true);
			$user->lang[$module_lang . '_EXPLAIN'] = sprintf($user->lang['SN_MODULE_EXPLAIN'], isset($user->lang[$module_lang]) ? $user->lang[$module_lang] : $module_lang);
		}

		$display_vars = array(
			'title'	 => 'ACP_BOARD_SETTINGS',
			'vars'	 => $modules
		);

		$cfg_array = request_var('config', array('' => ''), true);

		$cache->purge('modules_acp');
		$cache->purge('modules_ucp');

		foreach ($cfg_array as $module => $enabled)
		{
			$sql = "UPDATE " . MODULES_TABLE . "
							SET module_display = '{$enabled}'
							WHERE module_basename = 'socialnet'
								AND module_mode LIKE '{$module}%'";
			$db->sql_query($sql);

			if ($module_current[$module] != $enabled)
			{
				// Spustit dodatecne upravy, jednotlivych modulu pri zmene nastaveni
				$acp_moduleclass = 'acp_' . preg_replace('/^module_/si', '', $module);
				if (file_exists("{$socialnet_root_path}acp/{$acp_moduleclass}.{$phpEx}"))
				{
					include_once("{$socialnet_root_path}acp/{$acp_moduleclass}.{$phpEx}");
					if (method_exists($acp_moduleclass, 'acp_sett_modules'))
					{
						$module = new $acp_moduleclass($this);
						$module->acp_sett_modules($enabled);
					}
				}
			}
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		$this->_settings($id, 'sn_modules', $display_vars);

		$this->acpPanel_title = $user->lang['ACP_SN_AVAILABLE_MODULES'] . ' ' . $user->lang['SETTINGS'];
		$this->acpPanel_explain = $user->lang['ACP_SN_AVAILABLE_MODULES_EXPLAIN'];
	}

	/**
	 * Nastavení pro Potvrzovací Box
	 *
	 * Potvrzovací Box je nedílnou součástí všech stránek při použití Social Network
	 *
	 * @param integer $id phpBB variable
	 * @return void
	 */
	function sett_confirmBox($id)
	{
		global $user;

		$this->acpPanel_title = $user->lang['ACP_SN_CONFIRMBOX_SETTINGS'];
		$this->acpPanel_explain = $user->lang['ACP_SN_CONFIRMBOX_SETTINGS_EXPLAIN'];

		$display_vars = array(
			'title'	 => 'ACP_SN_CONFIRMBOX_SETTINGS',
			'vars'	 => array(
				'legend1'			 => 'ACP_SN_CONFIRMBOX_SETTINGS',
				'sn_cb_enable'		 => array('lang' => 'SN_CB_ENABLE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
				'sn_cb_resizable'	 => array('lang' => 'SN_CB_RESIZABLE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
				'sn_cb_draggable'	 => array('lang' => 'SN_CB_DRAGGABLE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
				'sn_cb_modal'		 => array('lang' => 'SN_CB_MODAL', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
				'sn_cb_width'		 => array('lang' => 'SN_CB_WIDTH', 'validate' => 'string', 'type' => 'text:4:6', 'explain' => true),
			)
		);

		$this->_settings($id, 'sn_cb', $display_vars);
	}

	function blocks_enable($id)
	{
		global $user, $config;

		$blocks = $this->_array_filter_key($config, 'catch_blocks');

		$block_vars = array();
		if (!empty($blocks))
		{
			foreach ($blocks as $key => $value)
			{
				$lang_key = strtoupper($key);
				$user->lang[$lang_key . '_EXPLAIN'] = isset($user->lang['SN_BLOCK_ENABLE_EXPLAIN']) ? $user->lang['SN_BLOCK_ENABLE_EXPLAIN'] : '{ SN_BLOCK_ENABLE_EXPLAIN } %1$s';
				$user->lang[$lang_key . '_EXPLAIN'] = sprintf($user->lang[$lang_key . '_EXPLAIN'], $user->lang[$lang_key]);
				$block_vars[$key] = array('lang' => $lang_key, 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true);
			}
		}

		asort($block_vars);

		$display_vars = array(
			'title'	 => 'SETTINGS',
			'vars'	 => array_merge(array(
				'legend1' => 'SETTINGS', ),
			$block_vars));

		$this->_settings($id, 'sn_blocks', $display_vars);
		$this->acpPanel_title = $user->lang['ACP_SN_BLOCKS_ENABLE'];
		$this->acpPanel_explain = sprintf($user->lang['ACP_SN_BLOCKS_ENABLE_EXPLAIN'], $user->lang['ACP_SN_BLOCKS_CONFIGURATION']);

	}

	function blocks_config($id, $block)
	{
		global $config, $phpbb_root_path, $phpEx, $template, $user;

		$avail_blocks = $this->_array_filter_key($config, 'catch_blocks');
		asort($avail_blocks);
		$block_settings = array();
		$block_first = '';

		foreach ($avail_blocks as $block_name => $enable)
		{
			$blockName = substr($block_name, 3);
			$block_className = 'acp_socialnet_' . $blockName;
			$block_acpfile = "{$phpbb_root_path}socialnet/acp/{$blockName}.{$phpEx}";

			if (!file_exists($block_acpfile))
			{
				continue;
			}
			include($block_acpfile);
			if (!class_exists($block_className))
			{
				continue;
			}

			$block_settings[$blockName] = $block_className;
			if ($block_first == '')
			{
				$block_first = $blockName;
			}

		}

		if (count($block_settings) != 0)
		{

			$current_block = request_var('block', @$_COOKIE[$config['cookie_name'] . '_sn_acp_block']);

			if ($current_block == '')
			{
				$current_block = $block_first;
			}

			$user->set_cookie('sn_acp_block', $current_block, time() + 86400);

			foreach ($block_settings as $blockName => $blockClass)
			{
				$template->assign_block_vars('block_tabs', array(
					'BLOCK'		 => $blockName,
					'TITLE'		 => $user->lang[strtoupper('sn_' . $blockName)],
					'S_SELECTED' => $blockName == $current_block,
				));
			}

			$template->assign_var('SN_CURRENT_BLOCK', $current_block);
			$template->assign_var('U_ACTION', $this->u_action);

			$o_block = new $block_settings[$current_block]($this);
			$o_block->main($id);

			$this->tpl_name = 'acp_socialnet_blocks';
			$this->acpPanel_title = $user->lang['ACP_SN_BLOCKS_CONFIGURATION'];
			$this->acpPanel_explain = $user->lang['ACP_SN_BLOCKS_CONFIGURATION_EXPLAIN'];
		}
	}

	/**
	 * Displays all admin settings for each acp panel
	 * @param mixed $id phpBB $id value
	 * @param string $mode Neccessary for log changes
	 * @param array $display_vars Standard $display_vars array to display configuration options
	 */
	function _settings($id, $mode, $display_vars = array())
	{
		global $db, $user, $auth, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		global $cache;

		$form_key = 'acp_' . $mode . '_settings';
		add_form_key($form_key);

		$submit = (isset($_POST['submit'])) ? true : false;

		$display_vars = array_merge(array('title' => 'ACP_IM_SETTINGS'), $display_vars);

		if (isset($display_vars['lang']))
		{
			$user->add_lang($display_vars['lang']);
		}

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		// We validate the complete config if whished
		if (sizeOf($display_vars['vars']))
		{
			validate_config_vars($display_vars['vars'], $cfg_array, $error);
		}

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		if (sizeOf($display_vars['vars']))
		{
			foreach ($display_vars['vars'] as $config_name => $null)
			{
				if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
				{
					continue;
				}

				$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

				if ($submit)
				{
					$this->_set_config($config_name, $config_value);
				}
			}
		}

		if ($submit)
		{
			add_log('admin', 'LOG_CONFIG_' . strtoupper($mode));

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			//'S_SETTINGS' => true,
			'S_ERROR'	 => (sizeof($error)) ? true : false,
			'ERROR_MSG'	 => implode('<br />', $error),

			'S_MODE'	 => $this->mode,
			'S_FOUNDER'	 => ($user->data['user_type'] == USER_FOUNDER) ? true : false,

			'U_ACTION'	 => $this->u_action
		));

		// Output relevant page
		if (sizeOf($display_vars['vars']))
		{
			foreach ($display_vars['vars'] as $config_key => $vars)
			{
				if (!is_array($vars) && strpos($config_key, 'legend') === false)
				{
					continue;
				}

				if (strpos($config_key, 'legend') !== false)
				{
					$template->assign_block_vars('options', array(
						'S_LEGEND'	 => true,
						'LEGEND'	 => (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars));

					continue;
				}

				$type = explode(':', $vars['type']);

				$l_explain = '';
				if ($vars['explain'] && isset($vars['lang_explain']))
				{
					$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
				}
				else if ($vars['explain'])
				{
					$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
				}

				$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

				if (empty($content))
				{
					continue;
				}

				$template->assign_block_vars('options', array(
					'KEY'			 => $config_key,
					'TITLE'			 => (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
					'S_EXPLAIN'		 => $vars['explain'],
					'TITLE_EXPLAIN'	 => $l_explain,
					'CONTENT'		 => $content,
				));

				unset($display_vars['vars'][$config_key]);
			}
		}

	}

	function _array_filter_key($input, $callback)
	{
		if (!is_array($input))
		{
			trigger_error('array_filter_key() expects parameter 1 to be array, ' . gettype($input) . ' given', E_USER_WARNING);
			return array();
		}

		if (empty($input))
		{
			return $input;
		}

		$filteredKeys = array_filter(array_keys($input), $callback);
		if (empty($filteredKeys))
		{
			return array();
		}

		$_input = array_intersect_key(array_flip($filteredKeys), $input);

		foreach ($_input as $key => $value)
		{
			$_input[$key] = $input[$key];
		}

		return $_input;
	}

	/**
	 * Save configuration values into SOCIALNET CONFIG TABLE (SN_CONFIG_TABLE)
	 * @param string $config_name Config name
	 * @param mixed $config_value Configuration value
	 * @param boolean $is_dynamic Yes if dynamic configuration
	 * @return void
	 */
	function _set_config($config_name, $config_value, $is_dynamic = false)
	{
		global $db, $cache, $config;

		$sql = 'UPDATE ' . SN_CONFIG_TABLE . "
			SET config_value = '" . $db->sql_escape($config_value) . "'
			WHERE config_name = '" . $db->sql_escape($config_name) . "'";
		$db->sql_query($sql);

		if (!$db->sql_affectedrows() && !isset($config[$config_name]))
		{
			$sql = 'INSERT INTO ' . SN_CONFIG_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'config_name'	 => $config_name,
				'config_value'	 => $config_value,
				'is_dynamic'	 => ($is_dynamic) ? 1 : 0));
			$db->sql_query($sql);
		}

		$config[$config_name] = $config_value;

		if (!$is_dynamic)
		{
			$cache->destroy('config');
		}

	}

}

function catch_blocks($elem)
{
	$filter = '/^sn_block/si';
	return preg_match($filter, $elem);
}

?>