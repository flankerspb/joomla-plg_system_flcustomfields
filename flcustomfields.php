<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.flcustomfields
 *
 * @copyright   Copyright (C) 2017 Vitaliy Moskalyuk. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die ();

/**
 * Plug-in to show a custom field in components items
 *
 * @since  3.8.0
 */

class plgSystemFlcustomfields extends JPlugin {
 
	/**
	 * Load the language file on instantiation.
	 * Note this is only available in Joomla 3.1 and higher.
	 * If you want to support 3.0 series you must override the constructor
	 *
	 * @var boolean
	 * @since 3.1
	 */

	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   1.5
	 */
	// public function __construct(&$subject, $config)
	// {
		// parent::__construct($subject, $config);
	// }

	// public function onContentPrepare($context, $article, $params, $page)
	// {
		// var_dump($context);
		// var_dump($article);
		// var_dump($params);
		// var_dump($page);
	// }
	
	public function onContentPrepareForm($form, $data)
	{
		$app = JFactory::getApplication();
		
		if (!$app->isClient('administrator'))
		{
			return;
		}
		
		// Check we have a form.
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}
		
		$input = $app->input->getArray();
		
		$formName = $form->getName();
		
		$params = $this->params->toObject();
		
		// Get plugin forms.
		$formsPath = __DIR__  . '/forms/';
		$forms = scandir($formsPath);
		
		// Check we are manipulating the plugin and set plugin settings.
		if ($formName == 'com_plugins.plugin' && $form->getField('flcustomfields_loadfields'))
		{
			$fields = array();
			
			foreach ($forms as $value)
			{
				$field = explode('.', $value);
				if (count($field) == 4 && $field[3] == 'xml')
				{
					$fields[$field[0]][$field[1]][$field[2]] = $value;
				}
			}
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?><form><fieldset name="basic"><fields name="params">';
			
			foreach ($fields as $component => $c)
			{
				$xml .= "<fields name='{$component}'>";
				
				// JPlugin::loadLanguage($component);
				
				$xml .= "<field type='note' name='{$component}' label='{$component}'/>";
				
				foreach ($c as $item => $i)
				{
					$xml .= "<fields name='{$item}'>";
					
					if ($component == 'com_categories' || $component == 'com_config')
					{
						// JPlugin::loadLanguage($item);
						$name = JText::_($item) . ': ';
					}
					else
					{
						$name = '';
					}
					
					foreach ($i as $field => $f)
					{
						$xml .= "<field name='{$field}' label='{$name}{$field}' description='form: {$f}' type='radio' class='btn-group btn-group-yesno' default='1'><option value='0'>JNO</option><option value='1'>JYES</option></field>";
					}
					
					$xml .= '</fields>';
				}
				
				$xml .= '</fields>';
			}
			
			$xml .= '</fields></fieldset></form>';
			
			$form->load($xml);
			
			return;
		}
		// Check we have a edit layout.
		else if ((array_key_exists('layout',$input) && $input['layout'] == 'edit') || ($input['option'] == 'com_config' && array_key_exists('component',$input)))
		{
			JPlugin::loadLanguage('plg_system_flcustomfields');
			
			// fixes for com_categories
			if (strpos($formName, 'com_categories.category') === 0)
				$formName = str_replace('com_categories.category', 'com_categories.', $formName);
			
			// fixes for external components
			if ($formName == 'com_advancedmodules.module')
				$formName = 'com_modules.module';
			
			// fixes for components config
			if ($formName == 'com_config.component')
				$formName = 'com_config.' . $input['component'];
			
			// fixes for menu items
			if ($formName == 'com_menus.item')
				$formName = 'com_menus.' . $data->request['option'] . '_' . $data->request['view'];
			
			// Show info message
			if (isset($params->info) && $params->info)
			{
				// var_dump($form);
				// var_dump($data);
				$text = JText::sprintf('PLG_SYSTEM_FLCUSTOMFIELDS_INFO', $formName);
				$app->enqueueMessage($text, 'notice');
			}
			
			foreach ($forms as $value)
			{
				$v = explode('.', $value);
				
				if (count($v) == 4 && $v[3] == 'xml' && ($v[0] .'.'. $v[1]) == $formName)
				{
					$c = $v[0];
					$i = $v[1];
					$f = $v[2];
					
					if (isset($params->$c->$i->$f) && $params->$c->$i->$f)
					{
						$file = $formsPath . $value;
						if (is_file($file))
							$form->loadFile($file, false);
					}
				}
			}
		}
	}
}
?>
