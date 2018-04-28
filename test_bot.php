<?php

/**
 * Basic telegram bot example
 * 
 */

require_once("lib/telegram_bot.php");

class TestBot extends TelegramBot{

	/**
	 * Fill token after setting webhook
	 * 
	 */
	//protected $token = "";

	/**
	 * Fill you bot name if you want to use it in groups
	 * @example "@my_test_bot"
	 */
	//protected $bot_name = "@";

	/**
	 * HTTP proxy URI (not socks)
	 * @example "tcp://122.183.137.190:8080"
	 */
	//public $proxy = "tcp://122.183.137.190:8080";


}

?>