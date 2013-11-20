<?php
namespace CollinsAPI;

/**
 * Contains configuration data needed for app development.
 *
 * @author Antevorte GmbH
 */
abstract class Config
{
	const ENTRY_POINT_URL = 'http://ant-shop-api1.wavecloud.de/api';
	const APP_ID = '52';
	const APP_PASSWORD = 'f8b62cb6827780006284fc084533893b';
	
	const ENABLE_LOGGING = true;
	const LOGGING_PATH = null;
	const LOGGING_TEMPLATE = "Request:\r\n{{request}}\r\n\r\nResponse:\r\n{{response}}";
}