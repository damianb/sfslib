<?php
/**
 *
 *===================================================================
 *
 *  StopForumSpam integration library
 *-------------------------------------------------------------------
 * @package     sfsintegration
 * @author      Damian Bushong
 * @copyright   (c) 2010 Damian Bushong
 * @license     MIT License
 * @link        http://github.com/Obsidian1510/SFSIntegration
 *
 *===================================================================
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 *
 */

/**
 * SFS Integration - SFS Request Object,
 *      Requests a user check against the StopForumSpam database using the JSON API.
 *
 *
 * @package     sfsintegration
 * @author      Damian Bushong
 * @license     MIT License
 * @link        http://github.com/Obsidian1510/SFSIntegration
 */
class SFSRequest extends SFSTransmission
{
	/**
	 * @const - Constant defining what API response serialization method we are using here.
	 */
	const SFS_API_METHOD = 'json';

	/**
	 * @const - Constant defining the base URL of the StopForumSpam API.
	 */
	const SFS_API_URL = 'http://www.stopforumspam.com/api';

	/**
	 * Builds the URL for our StopForumSpam API _GET request, based on the chunks of information we are looking for.
	 * @return string - The URL to use for the _GET request.
	 */
	protected function buildURL()
	{
		$url = self::SFS_API_URL . '?';
		$url .= ($this->username) ? 'username=' . $this->prepareAPIData($this->username) . '&' : '';
		$url .= ($this->email) ? 'email=' . $this->prepareAPIData($this->email) . '&' : '';
		$url .= ($this->ip) ? 'ip=' . $this->prepareAPIData($this->ip) . '&' : '';
		$url .= 'f=' . self::SFS_API_METHOD;

		return $url;
	}

	/**
	 * Sends the StopForumSpam API _GET request, based on the chunks of information we are looking for.
	 * @return SFSResult - The results of the lookup.
	 *
	 * @throws SFSRequestException
	 *
	 * @todo maybe rewrite this, it's a hell of a mess and provides no way to force one method or the other
	 */
	public function send()
	{
		if(empty($this->username) && empty($this->email) && empty($this->ip))
			throw new SFSRequestException('No request data provided for SFS API request', SFSRequestException::ERR_NO_REQUEST_DATA);

		if(function_exists('curl_init'))
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $this->buildURL());
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_TIMEOUT, $this->sfs->getRequestTimeout());
			curl_setopt($curl, CURLOPT_USERAGENT, $this->buildUserAgent());
			$json = curl_exec($curl);
			if(curl_errno($curl))
			{
				if(@ini_get('allow_url_fopen'))
				{
					// Setup the stream timeout, just in case
					$ctx = stream_context_create(array(
						'http'	=> array(
							'timeout'	=> $this->sfs->getTimeout(),
						),
					));

					$json = @file_get_contents($this->buildURL() . '&useragent=' . urlencode($this->buildUserAgent()), false, $ctx);
				}
				else
				{
					throw new SFSRequestException('No reliable method is available to send the request to the StopForumSpam API', SFSRequestException::ERR_NO_REQUEST_METHOD_AVAILABLE);
				}
			}
			curl_close($curl);

			unset($curl);
		}
		elseif(@ini_get('allow_url_fopen'))
		{
			// Setup the stream timeout, just in case
			$ctx = stream_context_create(array(
				'http'	=> array(
					'timeout'	=> $this->sfs->getTimeout(),
				),
			));

			$json = @file_get_contents($this->buildURL() . sprintf('&useragent=%1$s', urlencode($this->buildUserAgent())), false, $ctx);
		}
		else
		{
			throw new SFSRequestException('No reliable method is available to send the request to the StopForumSpam API', SFSRequestException::ERR_NO_REQUEST_METHOD_AVAILABLE);
		}

		// If no JSON response received, asplode.
		if(!$json)
			throw new SFSRequestException('No data recieved from SFS API', SFSRequestException::ERR_API_RETURN_EMPTY);

		// Be prepared in case we get invalid JSON...
		try
		{
			$data = SFSJSON::decode($json, false);
		}
		catch(SFSJSONException $e)
		{
			// Bad JSON, we'll chain the exception.
			// Also, due to how OfJSON is coded, this will return much more detailed errors in environments with PHP 5.3.0 or newer.
			throw new SFSRequestException(sprintf('Invalid JSON recieved from SFS API - %1$s', $e->getMessage()), SFSRequestException::ERR_API_RETURNED_BAD_JSON);
		}

		// Did the StopForumSpam API return an error?
		if(isset($data['error']))
			throw new SFSRequestException(sprintf('StopForumSpam API error: %1$s', $data['error']), SFSRequestException::ERR_API_RETURNED_ERROR);

		// Pass the requested data to the SFSResult object instantiation, so we know what we requested.
		$requested_data = array('username' => $this->username, 'email' => $this->email, 'ip' => $this->ip);

		return new SFSResult($this->sfs, $data, $requested_data);
	}
}
