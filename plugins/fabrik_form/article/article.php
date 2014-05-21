<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.article
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create Joomla article(s) upon form submission
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.article
 * @since       3.0
 */

class PlgFabrik_FormArticle extends PlgFabrik_Form
{
	/**
	 * Images
	 * @var object
	 */
	public $images = null;

	/**
	 * Create articles - needed to be before store as we are altering the metastore element's data
	 *
	 * @return	bool
	 */

	public function onAfterProcess()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$this->data = $this->getProcessData();

		if (!$this->shouldProcess('article_conditon', null))
		{
			return;
		}

		$store = $this->metaStore();

		if ($catElement = $formModel->getElement($params->get('categories_element'), true))
		{
			$cat = $catElement->getFullName() . '_raw';
			$categories = (array) JArrayHelper::getValue($this->data, $cat);
			$this->mapCategoryChanges($categories, $store);
		}
		else
		{
			$categories = (array) $params->get('categories');
		}

		foreach ($categories as $category)
		{
			$id = isset($store->$category) ? $store->$category : null;
			$item = $this->saveAritcle($id, $category);
			$store->$category = $item->id;
		}

		$this->setMetaStore($store);

		return true;
	}

	/**
	 * If changing selected category on editing a record, the new category id needs to be assigned as a
	 * property to $store with the existing article id. Not tested if say for example the category element
	 * is a multi-select
	 *
	 * @param   array   $categories  Categories selected by the user
	 * @param   object  &$store      Previously stored categoryid->articleid map
	 *
	 * @return  object  $store
	 */
	protected function mapCategoryChanges($categories, &$store)
	{
		$defaultAricleId = null;

		if (!empty($categories))
		{
			foreach ($store as $catid => $articleId)
			{
				if (!in_array($catid, $categories))
				{
					// We've swapped categories for an existing article
					$defaultAricleId = $articleId;
				}
			}
			foreach ($categories as $category)
			{
				if (!isset($store->$category))
				{
					$store->$category = $defaultAricleId;
				}
			}
		}

		return $store;
	}

	/**
	 * Save article
	 *
	 * @param   int  $id     Article Id
	 * @param   int  $catid  Category Id
	 *
	 * @return JTable
	 */
	protected function saveAritcle($id, $catid)
	{
		$dispatcher = JEventDispatcher::getInstance();
		// Include the content plugins for the on save events.
		JPluginHelper::importPlugin('content');

		$params = $this->getParams();
		$user = JFactory::getUser();
		$data = array('articletext' => $this->buildContent(), 'catid' => $catid, 'state' => 1, 'language' => '*');
		$attribs = array('title' => '', 'publish_up' => '', 'publish_down' => '', 'featured' => '0', 'state' => '1', 'metadesc' => '', 'metakey' => '', 'tags' => '');

		$data['images'] = json_encode($this->images());

		if (is_null($id))
		{
			$data['created'] = JFactory::getDate()->toSql();
			$attribs['created_by'] = $user->get('id');
		}
		else
		{
			$data['modified'] = JFactory::getDate()->toSql();
			$data['modified_by'] = $user->get('id');
		}

		foreach ($attribs as $attrib => $default)
		{
			$elementId = (int) $params->get($attrib);
			$data[$attrib] = $this->findElementData($elementId, $default);
		}

		$data['tags'] = (array) $data['tags'];


		$this->generateNewTitle($id, $catid, $data);
		$isNew = is_null($id) ? true : false;

		if (!$isNew)
		{
			$readmore = 'index.php?option=com_content&view=article&id=' . $id;
			$data['articletext'] = str_replace('{readmore}', $readmore, $data['articletext']);
		}


		$item = JTable::getInstance('Content');
		$item->load($id);
		$item->bind($data);


		// Trigger the onContentBeforeSave event.
		$result = $dispatcher->trigger('onContentBeforeSave', array('com_content.article', $item, $isNew));

		$item->store();

		// Featured is handled by the admin content model.
		JTable::addIncludePath(COM_FABRIK_BASE . 'administrator/components/com_content/tables');
		JModelLegacy::addIncludePath(COM_FABRIK_BASE . 'administrator/components/com_content/models');
		$articleModel = JModelLegacy::getInstance('Article', 'ContentModel');
		$articleModel->featured($item->id, $item->featured);

		// Trigger the onContentAfterSave event.
		$result = $dispatcher->trigger('onContentAfterSave', array('com_content.article', $item, $isNew));

		// New record - need to re-save with {readmore} replacement
		if ($isNew && strstr($data['articletext'], '{readmore}'))
		{
			$readmore = 'index.php?option=com_content&view=article&id=' . $item->id;
			$data['articletext'] = str_replace('{readmore}', $readmore, $data['articletext']);
			$item->bind($data);
			$item->store();
		}

		return $item;
	}

	/**
	 * Get the element data from the Fabrik form
	 *
	 * @param   int     $elementId  Element id
	 * @param   string  $default    Default value
	 *
	 * @return mixed
	 */
	protected function findElementData($elementId, $default = '')
	{
		$formModel = $this->getModel();
		$value = '';

		if ($elementId === 0)
		{
			return $default;
		}

		if ($elementModel = $formModel->getElement($elementId, true))
		{
			$fullName = $elementModel->getFullName(true, false);
			$value = $formModel->getElementData($fullName, false, $default, 0);

			if (is_array($value) && count($value) === 1)
			{
				$value = array_shift($value);
			}
		}

		return $value;
	}

	/**
	 * Build the Joomla article image data
	 *
	 * @return stdClass
	 */
	protected function images()
	{
		if (isset($this->images))
		{
			return $this->images;
		}

		$params = $this->getParams();
		$formModel = $this->getModel();
		$introImg = $params->get('image_intro', '');
		$fullImg = $params->get('image_full', '');
		$img = new stdClass;

		if ($introImg !== '')
		{
			$size = $params->get('image_intro_size', 'cropped');
			list($file, $placeholder) = $this->setImage($introImg, $size);

			if ($file !== '')
			{
				$img->image_intro = str_replace('\\', '/', $file);
				$img->image_intro = FabrikString::ltrimword($img->image_intro, '/');
				$img->float_intro = '';
				$img->image_intro_alt = '';
				$img->image_intro_caption = '';

				$elementModel = $formModel->getElement($introImg, true);

				if (get_class($elementModel) === 'PlgFabrik_ElementFileupload')
				{
					$name = $elementModel->getFullName(true, false);
					$img->$name = $placeholder;
				}
			}
		}

		if ($fullImg !== '')
		{
			$size = $params->get('image_full_size', 'thumb');
			list($file, $placeholder) = $this->setImage($fullImg, $size);

			if ($file !== '')
			{
				$img->image_fulltext = str_replace('\\', '/', $file);
				$img->image_fulltext = FabrikString::ltrimword($img->image_fulltext, '/');
				$img->float_fulltext = '';
				$img->image_fulltext_alt = '';
				$img->image_fulltext_caption = '';

				$elementModel = $formModel->getElement($fullImg, true);

				if (get_class($elementModel) === 'PlgFabrik_ElementFileupload')
				{
					$name = $elementModel->getFullName(true, false);
					$img->$name = $placeholder;
				}
			}
		}
		// Parse any other fileupload image
		$uploads = $formModel->getListModel()->getElementsOfType('fileupload');

		foreach ($uploads as $upload)
		{
			$name = $upload->getFullName(true, false);
			$shortName = $upload->getElement()->name;
			$size = $params->get('image_full_size', 'thumb');

			list($file, $placeholder) = $this->setImage($upload->getElement()->id, $size);

			$img->$name = $placeholder;
			$img->$shortName = $placeholder;
		}

		$this->images = $img;

		return $this->images;
	}

	/**
	 * Get thumb/cropped/full image paths
	 *
	 * @param   int     $elementId  Element id
	 * @param   string  $size       Type of file to find (cropped/thumb/full)
	 *
	 * @return  array   ($image, $placeholder)
	 */
	protected function setImage($elementId, $size)
	{
		$file = $this->findElementData($elementId);

		if ($file === '')
		{
			return array('', '');
		}

		// Initial upload $file is json data / ajax upload?
		if ($f = json_decode($file))
		{
			if (array_key_exists(0, $f))
			{
				$file = $f[0]->file;
			}
		}

		$formModel = $this->getModel();
		$elementModel = $formModel->getElement($elementId, true);

		if (get_class($elementModel) === 'PlgFabrik_ElementFileupload')
		{
			$name = $elementModel->getHTMLName();
			$data[$name] = $file;
			$elementModel->setEditable(false);
			$placeholder = $elementModel->render($data);

			$storage = $elementModel->getStorage();
			$file = $storage->clean(JPATH_SITE . '/' . $file);
			$file = $storage->pathToURL($file);

			switch ($size)
			{
				case 'cropped':
					$file = $storage->_getCropped($file);
					break;
				case 'thumb':
					$file = $storage->_getThumb($file);
					break;
			}

			$file = $storage->urlToPath($file);
			$file = str_replace(JPATH_SITE, '', $file);
			$first = substr($file, 0, 1);

			if ($first === '\\' || $first == '/')
			{
				$file = FabrikString::ltrimiword($file, $first);
			}
		}

		return array($file, $placeholder);
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $id     Article id
	 * @param   integer  $catid  The id of the category.
	 * @param   array    &$data  The row data.
	 *
	 * @return	null
	 */
	protected function generateNewTitle($id, $catid, &$data)
	{
		// If its an existing article don't edit name
		if ((int) $id !== 0)
		{
			$data['alias'] = JApplication::stringURLSafe(JStringNormalise::toDashSeparated($data['title']));
			return;
		}

		$table = JTable::getInstance('Content');
		$alias = JApplication::stringURLSafe(JStringNormalise::toDashSeparated($data['title']));

		$title = $data['title'];

		while ($table->load(array('alias' => $alias, 'catid' => $catid)))
		{
			$title = JString::increment($title);
			$alias = JString::increment($alias, 'dash');
		}

		$data['title'] = $title;
		$data['alias'] = $alias;
	}

	/**
	 * Update the form model with the new meta store data
	 *
	 * @param   object  $store  Meta store (categoryid => articleid)
	 *
	 * @return  null
	 */
	protected function setMetaStore($store)
	{
		$formModel = $this->getModel();
		$params = $this->getParams();

		if ($elementModel = $formModel->getElement($params->get('meta_store'), true))
		{
			$key = $elementModel->getElement()->name;
			$val = json_encode($store);
			$listModel = $formModel->getListModel();

			// Ensure we store to the main db table
			$listModel->clearTable();

			$rowId = JFactory::getApplication()->input->getString('rowid');
			$listModel->storeCell($rowId, $key, $val);
		}
		else
		{
			throw new RuntimeException('setMetaStore: No meta store element found for element id ' . $params->get('meta_store'));
		}
	}

	/**
	 * Get the meta store - this is a categoryid => articleid mapping object
	 *
	 * @return  object
	 */
	protected function metaStore()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$metaStore = new stdClass;

		if ($elementModel = $formModel->getElement($params->get('meta_store'), true))
		{
			$fullName = $elementModel->getFullName(true, false);
			$metaStore = $formModel->getElementData($fullName);
			$metaStore = json_decode($metaStore);
		}
		else
		{
			throw new RuntimeException('metaStore: No meta store element found for element id ' . $params->get('meta_store'));
		}

		return $metaStore;
	}

	/**
	 * Run from list model when deleting rows
	 *
	 * @param   array  &$groups  List data for deletion
	 *
	 * @return	bool
	 */

	public function onDeleteRowsForm(&$groups)
	{
		$formModel = $this->getModel();
		$params = $this->getParams();
		$deleteMode = $params->get('delete_mode', 'DELETE');
		$item = JTable::getInstance('Content');
		$userId = JFactory::getUser()->get('id');

		if ($elementModel = $formModel->getElement($params->get('meta_store'), true))
		{
			$fullName = $elementModel->getFullName(true, false) . '_raw';

			foreach ($groups as $group)
			{
				foreach ($group as $rows)
				{
					foreach ($rows as $row)
					{
						$store = json_decode($row->$fullName);

						if (is_object($store))
						{
							$store = JArrayHelper::fromObject($store);

							foreach ($store as $catid => $articleId)
							{
								switch ($deleteMode)
								{
									case 'DELETE':
										$item->delete($articleId);
										break;
									case 'UNPUBLISH':
										$ok = $item->publish(array($articleId), 0, $userId);
										break;
									case 'TRASH':
										$item->publish(array($articleId), -2, $userId);
										break;
								}
							}
						}
					}
				}
			}
		}
		else
		{
			throw new RuntimeException('Article plugin: onDeleteRows, did not find meta store element: ' . $params->get('meta_store'));
		}
	}

	/**
	 * Create the article content
	 *
	 * @return string
	 */
	protected function buildContent()
	{
		$images = $this->images();
		$formModel = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$params = $this->getParams();
		$template = JPath::clean(JPATH_SITE . '/plugins/fabrik_form/article/tmpl/' . $params->get('template', ''));
		$contentTemplate = $params->get('template_content');
		$content = $contentTemplate != '' ? $this->_getConentTemplate($contentTemplate) : '';
		$messageTemplate = '';

		if (JFile::exists($template))
		{
			$messageTemplate = JFile::getExt($template) == 'php' ? $this->_getPHPTemplateEmail($template) : $this
			->_getTemplateEmail($template);

			if ($content !== '')
			{
				$messageTemplate = str_replace('{content}', $messageTemplate, $content);
			}
		}

		$message = '';

		if (!empty($messageTemplate))
		{
			$message = $messageTemplate;
		}
		elseif (!empty($content))
		{
			// Joomla article template:
			$message = $content;
		}

		$message = stripslashes($message);

		$editURL = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&amp;view=form&amp;fabrik=' . $formModel->get('id') . '&amp;rowid='
			. $input->get('rowid', '', 'string');
		$viewURL = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&amp;view=details&amp;fabrik=' . $formModel->get('id') . '&amp;rowid='
			. $input->get('rowid', '', 'string');
		$editlink = '<a href="' . $editURL . '">' . FText::_('EDIT') . '</a>';
		$viewlink = '<a href="' . $viewURL . '">' . FText::_('VIEW') . '</a>';
		$message = str_replace('{fabrik_editlink}', $editlink, $message);
		$message = str_replace('{fabrik_viewlink}', $viewlink, $message);
		$message = str_replace('{fabrik_editurl}', $editURL, $message);
		$message = str_replace('{fabrik_viewurl}', $viewURL, $message);

		foreach ($images as $key => $val)
		{
			$this->data[$key] = $val;
		}

		$w = new FabrikWorker;
		$output = $w->parseMessageForPlaceholder($message, $this->data, true);

		return $output;
	}

	/**
	 * Use a php template for advanced email templates, particularly for forms with repeat group data
	 *
	 * @param   string  $tmpl  Path to template
	 *
	 * @return string email message
	 */

	protected function _getPHPTemplateEmail($tmpl)
	{
		$emailData = $this->data;

		// Start capturing output into a buffer
		ob_start();
		$result = require $tmpl;
		$message = ob_get_contents();
		ob_end_clean();

		if ($result === false)
		{
			return false;
		}

		return $message;
	}

	/**
	 * Template email handling routine, called if email template specified
	 *
	 * @param   string  $template  path to template
	 *
	 * @return  string	email message
	 */

	protected function _getTemplateEmail($template)
	{
		return file_get_contents($template);
	}

	/**
	 * Get content item template
	 *
	 * @param   int  $contentTemplate  Joomla article ID to load
	 *
	 * @return  string  content item html (translated with Joomfish if installed)
	 */

	protected function _getConentTemplate($contentTemplate)
	{
		$app = JFactory::getApplication();

		if ($app->isAdmin())
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('introtext, ' . $db->quoteName('fulltext'))->from('#__content')->where('id = ' . (int) $contentTemplate);
			$db->setQuery($query);
			$res = $db->loadObject();
		}
		else
		{
			JModelLegacy::addIncludePath(COM_FABRIK_BASE . 'components/com_content/models');
			$articleModel = JModelLegacy::getInstance('Article', 'ContentModel');
			$res = $articleModel->getItem($contentTemplate);
		}

		if ($res->fulltext !== '')
		{
			$res->fulltext = '<hr id="system-readmore" />' . $res->fulltext;
		}

		return $res->introtext . ' ' . $res->fulltext;
	}
}
