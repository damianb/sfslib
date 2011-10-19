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
use \emberlabs\sfslib\Core;
use \OpenFlame\Framework\Utility\JSON;

/**
 * StopForumSpam integration library - Transmitter object
 * 	     Provides functionality to communicate with StopForumSpam.
 *
 * @package     sfslib
 * @author      emberlabs.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/emberlabs/sfslib
 */
class File implements TransmitterInterface
{
	public function send(\emberlabs\sfslib\Transmission\TransmissionInstanceInterface $transmission)
	{
		// Set the stream timeout, just in case
		$stream = stream_context_create(array(
			'http'	=> array(
				'timeout'	=> Core::getConfig('sfs.timeout'),
			),
		));

		$json = @file_get_contents($transmission->buildURL() . '&useragent=' . rawurlencode(Core::getUserAgent()), false, $stream);

		return $transmission->newResponse($json);
	}
}