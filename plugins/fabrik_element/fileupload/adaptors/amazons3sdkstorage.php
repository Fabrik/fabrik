<?php
/**
 * Amazon s3 Storage adaptor for Fabrik file upload element
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
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

class Amazons3sdkstorage extends FabrikStorageAdaptor
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
		$this->params = $params;
		$this->ssl = $params->get('fileupload_ssl', false);

		$this->s3 = new Aws\S3\S3Client([
			'region'  => $this->getLocation(),
			'version' => 'latest',
			'credentials' => [
				'key' => $params->get('fileupload_aws_accesskey'),
				'secret' => $params->get('fileupload_aws_secretkey')
			]
		]);

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

		return $loc == '' ? 'us-east-1' : $loc;
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
		static $exists = array();

		if (!array_key_exists($filepath, $exists))
		{
			try
			{
				$exists[$filepath] = $this->s3->doesObjectExist(
					$this->getBucketName(),
					$this->urlToKey($filepath)
				);
			}
			catch (Exception $e)
			{
				if (FabrikHelperHTML::isDebug())
				{
					JFactory::getApplication()->enqueueMessage('S3 exists: ' . $e->getMessage());
				}
				return false;
			}
		}

		return $exists[$filepath];
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
	 * Convert url to key
	 *
	 * @param   string  $url  URL
	 *
	 * @return string  path
	 */
	private function urlToKey($url)
	{
		$url = urldecode($this->urlToPath($url));
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
		return $this->s3->doesBucketExist($this->getBucketName());
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
		$mimeType = GuzzleHttp\Psr7\mimetype_from_filename($filepath);

		if (!$this->bucketExists())
		{
			try
			{
				$this->s3->createBucket([
					'Bucket' => $this->getBucketName(),
					'ACL' => $this->getAcl(),
					'CreateBucketConfiguration' => [
						'LocationConstraint' => $this->getLocation()
					]
				]);
			}
			catch (Exception $e)
			{
				if (FabrikHelperHTML::isDebug())
				{
					JFactory::getApplication()->enqueueMessage('S3 upload createBucket: ' . $e->getMessage());
				}
				return false;
			}
		}

		// $$$ rob avoid urls like http://bucket.s3.amazonaws.com//home/users/path/to/file/Chrysanthemum.jpg
		$filepath = JString::ltrim($filepath, '/');

		// Move the file
		try
		{
			$s3Params = [
				'SourceFile' => $tmpFile,
				'Bucket' => $this->getBucketName(),
				'Key' => $this->urlToKey($filepath),
				'ACL' => $this->getAcl(),
				'ContentType' => $mimeType
			];

			if ($this->isEncrypted())
			{
				$s3Params['ServerSideEncryption'] = 'AES256';
			}

			$this->s3->putObject($s3Params);
		}
		catch (Exception $e)
		{
			if (FabrikHelperHTML::isDebug())
			{
				JFactory::getApplication()->enqueueMessage('S3 upload putObject: ' . $e->getMessage());
			}
			return false;
		}

		$this->uploadedFilePath = $this->getS3BaseURL() . str_replace(" ", "%20", $filepath);

		return true;
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
		try
		{
			$s3Params = [
				'Body' => $buffer,
				'Bucket' => $this->getBucketName(),
				'Key' => $this->urlToKey($file),
				'ACL' => $this->getAcl()
			];

			if ($this->isEncrypted())
			{
				$s3Params['ServerSideEncryption'] = 'AES256';
			}

			$this->s3->putObject($s3Params);
		}
		catch (Exception $e)
		{
			if (FabrikHelperHTML::isDebug())
			{
				JFactory::getApplication()->enqueueMessage('S3 write: ' . $e->getMessage());
			}
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
		try
		{
			$s3Info = $this->s3->getObject([
				'Bucket' => $this->getBucketName(),
				'Key' => $this->urlToKey($filepath)
			]);
		}
		catch (Exception $e)
		{
			if (FabrikHelperHTML::isDebug())
			{
				JFactory::getApplication()->enqueueMessage('S3 upload read: ' . $e->getMessage());
			}
			return false;
		}

		$s3Info = $s3Info->toArray();

		return $s3Info['Body'];
	}

	/**
	 * Read a file
	 *
	 * @param   string  $filepath  file path
	 * @param   int     $chunkSize  chunk size
	 *
	 * @return  bool  returns false if error
	 */

	public function stream($filepath, $chunkSize = 1024 * 1024)
	{
		/**
		 * Use the S3 stream wrapper, so we can treat the file "normally", through the s3:// protocol
		 *
		 * https://docs.aws.amazon.com/aws-sdk-php/v2/guide/feature-s3-stream-wrapper.html
		 */

		$this->s3->registerStreamWrapper();

		$path = 's3://' . $this->getBucketName() . '/' . $this->urlToKey($filepath);

		// now just fopen/fread as if it was a local file
		if ($stream = fopen($path, 'r')) {
			while (!feof($stream)) {
				echo fread($stream, $chunkSize);
				ob_flush();
				flush();
			}

			fclose($stream);

			return true;
		}
		else
		{
			return false;
		}
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
				$acl = 'private';
				break;
			case 2:
			default:
				$acl = 'public-read';
				break;
			case 3:
				$acl = 'public-read-write';
				break;
		}

		return $acl;
	}

	/**
	 * Get the S3 Acl setting
	 *
	 * @return string
	 */

	protected function isEncrypted()
	{
		$params = $this->getParams();
		return $params->get('fileupload_aws_encrypt', '0') === '1';
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
			$path = empty($path) ? '' : JPath::clean($path);
		}

		$path = str_replace("\\", '/', $path);
		$path = ltrim($path, '/');

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
		try
		{
			$this->s3->deleteObject(
				[
					'Bucket' => $this->getBucketName(),
					'Key'    => $this->urlToKey($filepath)
				]
			);
		}
		catch (Exception $e)
		{
			if (FabrikHelperHTML::isDebug())
			{
				JFactory::getApplication()->enqueueMessage('S3 delete: ' . $e->getMessage());
			}
			return false;
		}
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
		$origFile = $file;

		if ($params->get('make_thumbnail', '0') === '0')
		{
			return '';
		}

		$w = new FabrikWorker;

		$ulDir = '/' . ltrim($params->get('ul_directory'), '/\\');

		if ($this->appendServerPath())
		{
			$ulDir = rtrim(COM_FABRIK_BASE. '/\\') . $ulDir;
		}

		$ulDir = $this->clean($ulDir);
		$ulDir = $w->parseMessageForPlaceHolder($ulDir);

		$thumbdir = '/' . ltrim($params->get('thumb_dir'), '/\\');

		if ($this->appendServerPath())
		{
			$thumbdir = rtrim(COM_FABRIK_BASE, '/\\') . $thumbdir;
		}

		$thumbdir = $this->clean($thumbdir);
		$thumbdir = $w->parseMessageForPlaceHolder($thumbdir);

		$ulDir = rtrim($ulDir, '/\\') . '/';
		$thumbdir = rtrim($thumbdir, '/\\') . '/';

		$file = $w->parseMessageForPlaceHolder($file);
		$file = str_replace($ulDir, $thumbdir, $file);

		$f = basename($file);
		$dir = dirname($file);

		// Jaanus added: create also thumb suffix as for filesystemstorage
		$ext = JFile::getExt($f);
		$fclean = JFile::stripExt($f);
		$file = $dir . '/' . $params->get('thumb_prefix') . $fclean . $params->get('thumb_suffix') . '.' . $ext;

		if ($origFile === $file)
		{
			// if they set same folder and no prefex, it can wind up being same file, which blows up
			return '';
		}

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
		$origFile = $file;

		if ($params->get('fileupload_crop', '0') === '0')
		{
			return '';
		}

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

		if ($origFile === $file)
		{
			return '';
		}

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
	 * @return	array|bool
	 */

	public function getFileInfo($filepath)
	{
		try
		{
			$s3Info = $this->s3->headObject([
				'Bucket' => $this->getBucketName(),
				'Key'    => $this->urlToKey($filepath)
			]);
		}
		catch (Exception $e)
		{
			if (FabrikHelperHTML::isDebug())
			{
				JFactory::getApplication()->enqueueMessage('S3 getFileInfo: ' . $e->getMessage());
			}
			return false;
		}

		$s3Info = $s3Info->toArray();

		$thisFileInfo = array(
			'filesize' => $s3Info['@metadata']['headers']['content-length'],
			'mime_type' => $s3Info['@metadata']['headers']['content-type'],
			'filename' => basename($filepath)
		);

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
		if (empty($filepath))
		{
			return '';
		}

		$params = $this->getParams();
		static $presigned = array();

		if ($this->getAcl() === 'private' && $lifetime = (int) $params->get('fileupload_amazon_auth_url', 0))
		{
			if (!array_key_exists($filepath, $presigned))
			{
				try
				{
					$cmd                  = $this->s3->getCommand('GetObject', [
						'Bucket' => $this->getBucketName(),
						'Key'    => $this->urlToKey($filepath),
						'ResponseCacheControl' => "no-cache"
					]);
					$request              = $this->s3->createPresignedRequest($cmd, '+' . $lifetime . ' seconds');
					$presigned[$filepath] = (string) $request->getUri();
				}
				catch (Exception $e)
				{
					if (FabrikHelperHTML::isDebug())
					{
						JFactory::getApplication()->enqueueMessage('S3 preRenderPath: ' . $e->getMessage());
					}
					return false;
				}
			}
			$filepath = $presigned[$filepath];
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
