<?php
/**
 *
 *===================================================================
 *
 *  StopForumSpam integration library
 *-------------------------------------------------------------------
 * @package     sfslib
 * @author      emberlabs.org
 * @copyright   (c) 2010 - 2011 emberlabs.org
 * @license     MIT License
 * @link        https://github.com/emberlabs/sfslib
 *
 *===================================================================
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 *
 */

namespace emberlabs\sfslib;

/**
 * StopForumSpam integration library - Manager object
 * 	     Provides quick and easy access to the library's functionality.
 *
 * @package     sfsintegration
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class Library
{
	protected static $instance;

	public static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct()
	{
		// set default options...
		$defaults = array(
			'sfs.timeout'				=> 30,
			'sfs.transmitter'			=> 'file',
		);

		Core::declareTransmitter('file', '\\emberlabs\\sfslib\\Transmitter\\File');
		Core::declareTransmitter('curl', '\\emberlabs\\sfslib\\Transmitter\\cURL');

		foreach($configs as $name => $config)
		{
			if(Core::getConfig($name) === NULL)
			{
				Core::setConfig($name, $config);
			}
		}
	}

	public function setKey()
	{
		// asdf
	}

	public function getKey()
	{
		// asdf
	}

	public function newRequest($username, $email, $ip)
	{
		// asdf
	}

	public function newReport($username, $email, $ip)
	{
		// asdf
	}
}
