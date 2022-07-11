<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Fabrik\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Crypt\Cipher;
use Joomla\CMS\Crypt\Key;
use Joomla\CMS\Crypt\Cipher\SodiumCipher;
//use Joomla\CMS\Encrypt\Aes;

class FCipher
{
	private $key;

	private $cipher;

	private $type;

	public function __construct($type = 'aes')
	{
		$this->type = $type;

		if ($type === 'crypt')
		{
			$this->cipher = new Cipher\CryptoCipher();
			$this->key    = $this->getKey();
		}
		else if ($type === 'simple')
		{
			$this->key = $this->oldKey();
		}
		else if ($type === 'sodium')
		{
			$this->cipher = new SodiumCipher();
			$this->key    = $this->getKey();
		}
		else
		{
			$config = \JFactory::getConfig();
			$secret = $config->get('secret', '');

			if (trim($secret) == '')
			{
				throw new RuntimeException('You must supply a secret code in your Joomla configuration.php file');
			}

			$this->cipher = new \FOFEncryptAes($secret, 256);
		}
	}

	public function encrypt($data)
	{
		try
		{
			if ($this->type === 'crypt')
			{
				return bin2hex($this->cipher->encrypt($data, $this->key));
			}
			else if ($this->type === 'sodium')
			{
				$this->cipher->setNonce(\Sodium\randombytes_buf(\Sodium\CRYPTO_BOX_NONCEBYTES));
				return bin2hex($this->cipher->encrypt($data, $this->key));
			}
			else if ($this->type === 'simple')
			{
				return $this->oldEncrypt($data, $this->key);
			}
			else
			{
				return $this->cipher->encryptString($data);
			}
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	public function decrypt($data)
	{
		try
		{
			if ($this->type === 'crypt')
			{
				return $this->cipher->decrypt(hex2bin($data), $this->key);
			}
			else if ($this->type === 'sodium')
			{
				$this->cipher->setNonce(\Sodium\randombytes_buf(\Sodium\CRYPTO_BOX_NONCEBYTES));
				return bin2hex($this->cipher->decrypt($data, $this->key));
			}
			else if ($this->type === 'simple')
			{
				return $this->oldDecrypt($data, $this->key);
			}
			else
			{
				//return rtrim($this->cipher->decryptString($data), "\0");
				return $this->cipher->decryptString($data);
			}
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	private function getKey()
	{
		$fbConfig = \JComponentHelper::getParams('com_fabrik');
		$privateKey = $fbConfig->get('fabrik_private_key', '');
		$publicKey = $fbConfig->get('fabrik_public_key', '');

		if (empty($privateKey))
		{
			$key = $this->generateKey();
		}
		else
		{
			$key = new Key('crypto', hex2bin($privateKey), hex2bin($publicKey));
		}

		return $key;
	}

	private function generateKey()
	{
		$fbConfig = \JComponentHelper::getParams('com_fabrik');
		$key = $this->cipher->generateKey();
		//$privateKey = $key->getPrivate();
		//$publicKey = $key->getPublic();
		$privateKey = $key->private;
		$publicKey = $key->public;
		$fbConfig->set('fabrik_private_key', bin2hex($privateKey));
		$fbConfig->set('fabrik_public_key', bin2hex($publicKey));

		$componentid = \JComponentHelper::getComponent('com_fabrik')->id;
		$table = \JTable::getInstance('extension');
		$table->load($componentid);
		$table->bind(array('params' => $fbConfig->toString()));

		// check for error
		if (!$table->check()) {
			echo $table->getError();
			return false;
		}

		// Save to database
		if (!$table->store()) {
			echo $table->getError();
			return false;
		}

		return $key;
	}

	/**
	 * Method to decrypt a data string.
	 *
	 * NOTE - this is the old deprecated J! simple crypt, only here for legacy (converting old to new)

	 *
	 * @param   string  $data  The encrypted string to decrypt.
	 * @param   object     $key   The key[/pair] object to use for decryption.
	 *
	 * @return  string  The decrypted data string.
	 *
	 * @since   12.1
	 * @throws  \InvalidArgumentException
	 */
	public function oldDecrypt($data, $key)
	{
		// Validate key.
		if ($key->type != 'simple')
		{
			throw new \InvalidArgumentException('Invalid key of type: ' . $key->type . '.  Expected simple.');
		}

		$decrypted = '';
		$tmp = $key->public;

		// Convert the HEX input into an array of integers and get the number of characters.
		$chars = $this->_hexToIntArray($data);
		$charCount = count($chars);

		// Repeat the key as many times as necessary to ensure that the key is at least as long as the input.
		for ($i = 0; $i < $charCount; $i = strlen($tmp))
		{
			$tmp = $tmp . $tmp;
		}

		// Get the XOR values between the ASCII values of the input and key characters for all input offsets.
		for ($i = 0; $i < $charCount; $i++)
		{
			$decrypted .= chr($chars[$i] ^ ord($tmp[$i]));
		}

		return $decrypted;
	}

	/**
	 * Method to encrypt a data string.
	 *
	 * NOTE - this is the old deprecated J! simple crypt, only here for legacy (converting old to new)
	 *
	 * @param   string  $data  The data string to encrypt.
	 * @param   object     $key   The key[/pair] object to use for encryption.
	 *
	 * @return  string  The encrypted data string.
	 *
	 * @since   12.1
	 * @throws  \InvalidArgumentException
	 */
	public function oldEncrypt($data, $key)
	{
		// Validate key.
		if ($key->type != 'simple')
		{
			throw new \InvalidArgumentException('Invalid key of type: ' . $key->type . '.  Expected simple.');
		}

		$encrypted = '';
		$tmp = $key->private;

		// Split up the input into a character array and get the number of characters.
		$chars = preg_split('//', $data, -1, PREG_SPLIT_NO_EMPTY);
		$charCount = count($chars);

		// Repeat the key as many times as necessary to ensure that the key is at least as long as the input.
		for ($i = 0; $i < $charCount; $i = strlen($tmp))
		{
			$tmp = $tmp . $tmp;
		}

		// Get the XOR values between the ASCII values of the input and key characters for all input offsets.
		for ($i = 0; $i < $charCount; $i++)
		{
			$encrypted .= $this->_intToHex(ord($tmp[$i]) ^ ord($chars[$i]));
		}

		return $encrypted;
	}

	/**
	 * Method to generate a new encryption key[/pair] object.
	 *
	 * @param   array  $options  Key generation options.
	 *
	 * @return  Key
	 *
	 * @since   12.1
	 */
	public function oldKey(array $options = array())
	{
		// Create the new encryption key[/pair] object.
		$key = new \stdClass();

		// Just a random key of a given length.
		$key->type    = 'simple';
		$key->private = \JFactory::getConfig()->get('secret');
		$key->public  = $key->private;

		return $key;
	}

	/**
	 * Convert hex to an integer
	 *
	 * @param   string   $s  The hex string to convert.
	 * @param   integer  $i  The offset?
	 *
	 * @return  integer
	 *
	 * @since   11.1
	 */
	private function _hexToInt($s, $i)
	{
		$j = (int) $i * 2;
		$k = 0;
		$s1 = (string) $s;

		// Get the character at position $j.
		$c = substr($s1, $j, 1);

		// Get the character at position $j + 1.
		$c1 = substr($s1, $j + 1, 1);

		switch ($c)
		{
			case 'A':
				$k += 160;
				break;
			case 'B':
				$k += 176;
				break;
			case 'C':
				$k += 192;
				break;
			case 'D':
				$k += 208;
				break;
			case 'E':
				$k += 224;
				break;
			case 'F':
				$k += 240;
				break;
			case ' ':
				$k += 0;
				break;
			default:
				(int) $k = $k + (16 * (int) $c);
				break;
		}

		switch ($c1)
		{
			case 'A':
				$k += 10;
				break;
			case 'B':
				$k += 11;
				break;
			case 'C':
				$k += 12;
				break;
			case 'D':
				$k += 13;
				break;
			case 'E':
				$k += 14;
				break;
			case 'F':
				$k += 15;
				break;
			case ' ':
				$k += 0;
				break;
			default:
				$k += (int) $c1;
				break;
		}

		return $k;
	}

	/**
	 * Convert hex to an array of integers
	 *
	 * @param   string  $hex  The hex string to convert to an integer array.
	 *
	 * @return  array  An array of integers.
	 *
	 * @since   11.1
	 */
	private function _hexToIntArray($hex)
	{
		$array = array();

		$j = (int) strlen($hex) / 2;

		for ($i = 0; $i < $j; $i++)
		{
			$array[$i] = (int) $this->_hexToInt($hex, $i);
		}

		return $array;
	}

	/**
	 * Convert an integer to a hexadecimal string.
	 *
	 * @param   integer  $i  An integer value to convert to a hex string.
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	private function _intToHex($i)
	{
		// Sanitize the input.
		$i = (int) $i;

		// Get the first character of the hexadecimal string if there is one.
		$j = (int) ($i / 16);

		if ($j === 0)
		{
			$s = ' ';
		}
		else
		{
			$s = strtoupper(dechex($j));
		}

		// Get the second character of the hexadecimal string.
		$k = $i - $j * 16;
		$s = $s . strtoupper(dechex($k));

		return $s;
	}


}