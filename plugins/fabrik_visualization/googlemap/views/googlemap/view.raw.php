<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewGooglemap extends JView
{
	
function display($tmpl = 'default')
	{
		$document 		=& JFactory::getDocument();
		$usersConfig 	= JComponentHelper::getParams('com_fabrik');
		$model				= &$this->getModel();
		$model->setId(JRequest::getVar('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0))));
		$this->row 		=& $model->getVisualization();
		echo 					 $model->getJSIcons();
	}
}
?>