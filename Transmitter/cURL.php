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

namespace emberlabs\sfslib\Transmitter;
use \emberlabs\sfslib\Library as SFS;
use \emberlabs\sfslib\Internal\cURLException;
use \emberlabs\openflame\Core\Core;

/**
 * StopForumSpam integration library - Transmitter object
 * 	     Provides functionality to communicate with StopForumSpam.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class cURL implements TransmitterInterface
{
	/**
	 * Constructor
	 * @throws cURLException
	 */
	public function __construct()
	{
		if(!function_exists('curl_init'))
		{
			throw new cURLException('cURL not supported by current server configuration');
		}
	}

	/**
	 * Send a POST transmission to StopForumSpam
	 * @param \emberlabs\sfslib\Transmission\TransmissionInstanceInterface $transmission - The transmission to send
	 * @return \emberlabs\sfslib\Transmission\TransmissionResultInterface - The transmission result object
	 */
	public function sendPOST(\emberlabs\sfslib\Transmission\TransmissionInstanceInterface $transmission)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $transmission->buildPOSTURL() . '&useragent=' . rawurlencode(SFS::getUserAgent()));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $transmission->buildPOSTData());
		curl_setopt($curl, CURLOPT_TIMEOUT, Core::getConfig('sfs.timeout'));
		curl_setopt($curl, CURLOPT_USERAGENT, $this->buildUserAgent());
		$json = curl_exec($curl);
		if(curl_errno($curl))
		{
			throw new cURLException('cURL error: ' . curl_error($curl), curl_errno($curl));
		}
		curl_close($curl);

		return $transmission->newResponse($json);
	}

	/**
	 * Send a GET transmission to StopForumSpam
	 * @param \emberlabs\sfslib\Transmission\TransmissionInstanceInterface $transmission - The transmission to send
	 * @return \emberlabs\sfslib\Transmission\TransmissionResultInterface - The transmission result object
	 */
	public function sendGET(\emberlabs\sfslib\Transmission\TransmissionInstanceInterface $transmission)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $transmission->buildGETURL() . '&useragent=' . rawurlencode(SFS::getUserAgent()));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, Core::getConfig('sfs.timeout'));
		curl_setopt($curl, CURLOPT_USERAGENT, $this->buildUserAgent());
		$json = curl_exec($curl);
		if(curl_errno($curl))
		{
			throw new cURLException('cURL error: ' . curl_error($curl), curl_errno($curl));
		}
		curl_close($curl);

		return $transmission->newResponse($json);
	}
}
