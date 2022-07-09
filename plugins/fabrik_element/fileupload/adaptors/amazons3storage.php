<?php
/**
 * Amazon s3 Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ROOT . '/plugins/fabrik_element/fileupload/adaptor.php';

/**
 * Amazon s3 Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class Amazons3storage extends FabrikStorageAdaptor
{
	/**
	 * Are we using SSL to store/retrieve files
	 *
	 * @var bool
	 */
	protected $ssl = false;

	/**
	 * S3 domain
	 * Must have trailing slash
	 *
	 * @var string
	 */
	protected $domain = 's3.amazonaws.com/';

	/**
	 * Constructor
	 *
	 * @param   Registry  &$params  options
	 */

	public function __construct(&$params)
	{
		require_once COM_FABRIK_FRONTEND . '/libs/amazons3/S3.php';
		$this->ssl = $params->get('fileupload_ssl', false);
		$this->s3 = new S3($params->get('fileupload_aws_accesskey'), $params->get('fileupload_aws_secretkey'), $this->ssl);
		parent::__construct($params);
	}

	/**
	 * Get the bucket name
	 *
	 * @return  string
	 */

	protected function getBucketName()
	{
		$params = $this->getParams();
		$w = new FabrikWorker;

		return $w->parseMessageForPlaceHolder($params->get('fileupload_aws_bucketname', 'robclayburnsfabrik'));
	}

	/**
	 * Get upload location
	 *
	 * @return  bool
	 */

	protected function getLocation()
	{
		$loc = $this->params->get('fileupload_aws_location');

		return $loc == '' ? false : $loc;
	}

	/**
	 * Does a file exist
	 *
	 * @param   string  $filepath     path to test for
	 * @param   bool    $prependRoot  ignored in this adaptor
	 *
	 * @return  bool
	 */

	public function exists($filepath, $prependRoot = true)
	{
		if (!$this->bucketExists())
		{
			return false;
		}

		$re = '/^' . preg_quote(COM_FABRIK_BASE) . '/';
		$filepath = preg_replace($re, '', $filepath);
		$bucket = $this->getBucketName();
		$filepath = str_replace("%20", " ", $filepath);
		$filepath = $this->urlToPath($filepath);
		$response = $this->s3->getObject($bucket, $filepath);

		return $response === false ? false : true;
	}

	/**
	 * Convert a full url into a full server path
	 *
	 * @param   string  $url  URL
	 *
	 * @see /plugins/fabrik_element/fileupload/storageAdaptor#urlToPath($url)
	 *
	 * @return string  path
	 */

	public function urlToPath($url)
	{
		$prefix = $this->ssl ? 'https://' : 'http://';
		$bucket = $this->getBucketName();
		$url = $this->removePrependedURL($url);

		if (strstr($url, $prefix))
		{
			// We've got the full url to the image - remove the bucket name etc. to get file name
			$url = str_replace($prefix, '', $url);
			$url = str_replace($bucket . '.' . $this->domain, '', $url);
		}

		return $url;
	}

	/**
	 * Remove the full server path from the filepath
	 *
	 * @param   string  $filepath  file path
	 *
	 * @return  string
	 */

	private function removePrependedURL($filepath)
	{
		if (substr($filepath, 0, JString::strlen(COM_FABRIK_BASE)) == COM_FABRIK_BASE)
		{
			$filepath = Fabrikstring::ltrimword($filepath, COM_FABRIK_BASE);
		}

		return $filepath;
	}

	/**
	 * Does the bucket exist
	 *
	 * @return  bool
	 */

	private function bucketExists()
	{
		$bucket = $this->getBucketName();
		$buckets = $this->s3->listBuckets();

		if (!is_array($buckets))
		{
			return false;
		}

		if (!in_array($bucket, $buckets))
		{
			return false;
		}

		return true;
	}

	/**
	 * Upload the file
	 *
	 * @param   string  $tmpFile   tmp file location
	 * @param   string  $filepath  final upload location
	 *
	 * @return  bool
	 */

	public function upload($tmpFile, $filepath)
	{
		$filepath = str_replace("\\", '/', $filepath);
		$bucket = $this->getBucketName();
		$acl = $this->getAcl();

		if (!$this->bucketExists())
		{
			$this->s3->putBucket($bucket, $acl, $this->getLocation());
		}

		// $$$ rob avoid urls like http://bucket.s3.amazonaws.com//home/users/path/to/file/Chrysanthemum.jpg
		$filepath = JString::ltrim($filepath, '/');

		// Move the file
		if ($this->s3->putObjectFile($tmpFile, $bucket, $filepath, $acl))
		{
			$this->uploadedFilePath = $this->getS3BaseURL() . str_replace(" ", "%20", $filepath);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * When creating file paths, do we need to append them with JPATH_SITE
	 *
	 * @since  3.0.6.2
	 *
	 * @return  bool
	 */

	public function appendServerPath()
	{
		$params = $this->getParams();

		return (bool) $params->get('fileupload_s3_serverpath', 1);
	}

	/**
	 * Build the base url for the files
	 *
	 * @return string
	 */

	private function getS3BaseURL()
	{
		$prefix = $this->ssl ? 'https://' : 'http://';
		$bucket = $this->getBucketName();

		return $prefix . $bucket . '.' . $this->domain;
	}

	/**
	 * Write a file
	 *
	 * @param   string  $file    file name
	 * @param   string  $buffer  the buffer to write
	 *
	 * @return  bool
	 */

	public function write($file, $buffer)
	{
		$file = $this->urlToPath($file);
		$file = str_replace("%20", " ", $file);
		$file = str_replace("\\", '/', $file);
		$bucket = $this->getBucketName();

		if ($this->s3->putObject($buffer, $bucket, $file, S3::ACL_PUBLIC_READ))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Read a file
	 *
	 * @param   string  $filepath  file path
	 *
	 * @return  mixed  Returns file contents or boolean False if failed
	 */

	public function read($filepath)
	{
		$file = $this->urlToPath($filepath);
		$file = str_replace("%20", " ", $file);
		$file = str_replace("\\", '/', $file);
		$bucket = $this->getBucketName();
		$s3object = $this->s3->getObject($bucket, $file);

		if ($s3object === false)
		{
			return false;
		}

		return $s3object->body;
	}

	/**
	 * Stream a file
	 *
	 * NOT IMPLEMENTED - use amazons3sdk adapter if you need streaming
	 *
	 * @param   string  $filepath  file path
	 * @param  int  $chunkSize
	 */

	public function stream($filepath, $chunkSize = 1048576)
	{
		echo $this->read($filepath);
		ob_flush();
		flush();
		return true;
	}

	/**
	 * Get the S3 Acl setting
	 *
	 * @return string
	 */

	protected function getAcl()
	{
		$params = $this->getParams();
		$acl = $params->get('fileupload_amazon_acl', 2);

		switch ($acl)
		{
			case 1:
				$acl = S3::ACL_PRIVATE;
				break;
			case 2:
			default:
				$acl = S3::ACL_PUBLIC_READ;
				break;
			case 3:
				$acl = S3::ACL_PUBLIC_READ_WRITE;
				break;
		}

		return $acl;
	}

	/**
	 * Does a folder exist - not applicable for S3 storage
	 *
	 * @param   string  $folder  folder to test
	 *
	 * @return  true
	 */

	public function folderExists($folder)
	{
		return true;
	}

	/**
	 * Create a folder - not applicable for S3 storage
	 *
	 * @param   string   $path  Folder path
	 * @param   bitmask  $mode  Permissions
	 *
	 * @return  bool
	 */

	public function createFolder($path, $mode = 0755)
	{
		return true;
	}

	/**
	 * Clean a path
	 *
	 * @param   string  $path  path to clear
	 *
	 * @return  string  cleaned path
	 */

	public function clean($path)
	{
		$prefix = $this->ssl ? 'https://' : 'http://';

		if (strstr($path, $prefix))
		{
			// If we are cleaning up a full url then check that fabrik hasn't unwittingly prepended the JPATH_SITE to the start of the url
			$path = $this->removePrependedURL($path);
			$part = Fabrikstring::ltrimword($path, $prefix);
			$path = $prefix . JPath::clean($part);
		}
		else
		{
			$path = JPath::clean($path);
		}

		$path = str_replace("\\", '/', $path);

		return $path;
	}

	/**
	 * Clean a file name
	 *
	 * @param   string  $filename       File name to clean
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  string  cleaned name
	 */

	public function cleanName($filename, $repeatCounter)
	{
		$this->randomizeName($filename);

		return $filename;
	}

	/**
	 * Delete a file
	 *
	 * @param   string  $filepath  File to delete
	 * @param   bool    $prependRoot  also test with root prepended
	 *
	 * @return  void
	 */

	public function delete($filepath, $prependRoot = true)
	{
		$filepath = $this->urlToPath($filepath);
		$this->s3->deleteObject($this->getBucketName(), $filepath);
	}

	/**
	 * Set a file's permissions
	 *
	 * @param   string  $filepath  file to set permissions for
	 *
	 * @return  string
	 */

	public function setPermissions($filepath)
	{
		// Not applicable
	}

	/**
	 * Get the file's URL
	 *
	 * @param   string  $file  file path
	 *
	 * @return  string  URL
	 */

	public function getFileUrl($file)
	{
		return $file;
	}

	/**
	 * Get the thumbnail file for the file given
	 *
	 * @param   string  $file  file path
	 *
	 * @return  string	thumbnail
	 */

	public function _getThumb($file)
	{
		$params = $this->getParams();
		$w = new FabrikWorker;

		$ulDir = COM_FABRIK_BASE . $params->get('ul_directory');
		$ulDir = $this->clean($ulDir);
		$ulDir = $w->parseMessageForPlaceHolder($ulDir);

		$thumbdir = $this->clean(COM_FABRIK_BASE . $params->get('thumb_dir'));
		$thumbdir = $w->parseMessageForPlaceHolder($thumbdir);

		$file = $w->parseMessageForPlaceHolder($file);
		$file = str_replace($ulDir, $thumbdir, $file);

		$f = basename($file);
		$dir = dirname($file);

		// Jaanus added: create also thumb suffix as for filesystemstorage
		$ext = JFile::getExt($f);
		$fclean = JFile::stripExt($f);
		$file = $dir . '/' . $params->get('thumb_prefix') . $fclean . $params->get('thumb_suffix') . '.' . $ext;

		return $file;
	}

	/**
	 * Get the cropped file for the file given
	 *
	 * @param   string  $file  main image file path
	 *
	 * @return  string  cropped image
	 */

	public function _getCropped($file)
	{
		$params = $this->getParams();
		$w = new FabrikWorker;

		$ulDir = COM_FABRIK_BASE . $params->get('ul_directory');
		$ulDir = $this->clean($ulDir);
		$ulDir = $w->parseMessageForPlaceHolder($ulDir);

		$thumbdir = $this->clean(COM_FABRIK_BASE . $params->get('fileupload_crop_dir'));
		$thumbdir = $w->parseMessageForPlaceHolder($thumbdir);

		$file = $w->parseMessageForPlaceHolder($file);
		$file = str_replace($ulDir, $thumbdir, $file);

		$f = basename($file);
		$dir = dirname($file);
		$file = $dir . '/' . $f;

		return $file;
	}

	/**
	 * Convert a full server path into a full url
	 *
	 * @param   string  $path  server path
	 *
	 * @return  string  url
	 */

	public function pathToURL($path)
	{
		return $path;
	}

	/**
	 * Make a nested folder structure - not applicable for S3 storaga
	 *
	 * @param   string  $folderPath  path to folder - e.g. /images/stories
	 * @param   int     $mode        folder permissions
	 *
	 * @return  void
	 */

	public function makeRecursiveFolders($folderPath, $mode = 0755)
	{
		return;
	}

	/**
	 * Get file info
	 *
	 * @param   string  $filepath  path
	 *
	 * @return	array
	 */

	public function getFileInfo($filepath)
	{
		$bucket = $this->getBucketName();
		$s3Info = $this->s3->getObjectInfo($bucket, $filepath);

		if ($s3Info === false)
		{
			return false;
		}

		$thisFileInfo = array('filesize' => $s3Info['size'], 'mime_type' => $s3Info['type'], 'filename' => basename($filepath));

		return $thisFileInfo;
	}

	/**
	 * Get the complete folder path, including the server root
	 *
	 * @param   string  $filepath  the file path
	 *
	 * @return  string
	 */

	public function getFullPath($filepath)
	{
		$filepath = $this->urlToPath($filepath);
		$filepath = str_replace("%20", " ", $filepath);
		$filepath = str_replace("\\", '/', $filepath);

		return $filepath;
	}

	/**
	 * Allows storage model to modify pathname just before it is rendered.  For instance,
	 * if using Amazon S3 with 'Authenticated URL' option.
	 *
	 * @param   string  $filepath  path to file
	 *
	 * @return  string
	 */

	public function preRenderPath($filepath)
	{
		$params = $this->getParams();

		if ($lifetime = (int) $params->get('fileupload_amazon_auth_url', 0))
		{
			$file = $this->urlToPath($filepath);
			$file = str_replace("%20", " ", $file);
			$file = str_replace("\\", '/', $file);
			$bucket = trim($this->getBucketName());
			//$hostbucket = !$this->ssl;
			$hostbucket = false;
			$filepath = $this->s3->getAuthenticatedURL($bucket, $file, $lifetime, $hostbucket, $this->ssl);
		}

		return $filepath;
	}

	/**
	 * Check for snooping
	 *
	 * @param   string   $folder   The file path
	 *
	 * @return  void
	 */
	public function checkPath($folder)
	{
		return;
	}

	/**
	 * Return the directory separator - can't use DIRECTORY_SEPARATOR by default, as s3 uses /
	 *
	 * @return string
	 *
	 * @since 3.8
	 */
	public function getDS()
	{
		return '/';
	}
}
