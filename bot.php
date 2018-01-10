<?php

/**
 * Use this file for webhook. It will reply any command from users
 * 
 */

require_once("test_bot.php");

$bot = new TestBot();
$bot->replyCommand();

?>