<?php

/**
 * Telegram Bot class
 * Extend it for your bot
 *
 * @author Pavel Kuznetsov [pk@webportnoy.ru]
 * @since PHP 5.4
 */

require_once("telegram_bot_api.php");

class TelegramBot{

	protected $token = null; 
	protected $bot_name = null;
	public    $api = null;
	protected $result = null;
	protected $commands = [
			"/start" => "cmd_start",
			"/help" => "cmd_help"
		];
	/**
	 * HTTP proxy URI (not socks)
	 * @example "tcp://122.183.137.190:8080"
	 */
	public    $proxy = "";

	/**
	 * Creates bot class
	 * @param string $token Telegram Bot Api token
	 */
    public function __construct( $token = null ){
		if( !isset( $this->token ) && !isset( $token ) ){
			$this->showWebhookForm();
		}
		if( !isset( $this->token ) && isset( $token ) ){
			$this->token = $token;
		}
		$this->api = new TelegramBotApi( $this->token );
		if( $this->proxy ){
			$this->api->proxy = $this->proxy;
		}
		// Uncomment this to get log.txt
		//$this->api->debug = true;
	}

	/**
	 * Gets command when called as webhook and calls method for this command
	 *
	 */
	public function replyCommand(){
		$this->result = $this->api->getWebhookUpdate();
		if( !empty($this->result) ){
			if( isset( $this->result['callback_query'] ) ){
				$this->callCallback();
			}
			else{
				$this->callCommand();
			}
		}
		else{
			echo "I'm a telegram bot";
			exit;
		}
	}

	/**
	 * Extracts command from entered text
	 *
	 * @param string $text
	 */
	public function getCommand( $text ){
		// Commands in group like /start@some_bot
		if( isset( $this->bot_name ) && strpos( $text, $this->bot_name ) ){
			$text = str_replace( $this->bot_name, "", $text );
		}
		$text = explode(" ", $text );
		$text = $text[0];
		if( $text && array_key_exists( $text, $this->commands ) && method_exists( $this, $this->commands[$text] ) ){
			return $this->commands[$text];
		}
		return false;
	}

	/**
	 * Calls method for entered command (if it exists)
	 *
	 */
	public function callCommand(){
		$text = $this->result["message"]["text"];
		$cmd = $this->getCommand( $text );
		if( $cmd ){
			$this->$cmd();
		}
		else{
			$this->cmd_default();
		}
	}

	/**
	 * Calls callback of inline keyboard press
	 * 
	 */
	public function callCallback(){
		$query = explode(" ", $this->result['callback_query']['data'] );
		$cmd = "callback_{$query[0]}";
		if( method_exists( $this, $cmd ) ){
			$this->$cmd( $this->result['callback_query']['data'] );
		}
		else{
			$this->callback_default( $this->result['callback_query']['data'] );
		}
	}

	/**
	 * Sending message to list of users
	 * 
	 * @param string $message Text message
	 * @param array $userIdList Array of users id
	 * @param array $params (Optional) Array of parameters to pas to telegram api
	 * 
	 * @return boolean|array Returns false if all messages are sent or array of users id who didn`t get the message
	 * 
	 * @link https://core.telegram.org/bots/api#sendmessage
	 */
	function mailing( $message, $userIdList, $params = null ){
		$errors = false;
		for( $i=0; $i < count($userIdList); $i++ ){
			$defaults = [
					'chat_id' => $userIdList[$i],
					'text' => $message,
					'parse_mode'=> 'HTML'
				];
			$params = is_array( $params ) ? array_merge( $params, $defaults ) : $defaults;
			try{
				$this->api->sendMessage( $params );
			}
			catch( Exception $e ){
				if( !is_array( $errors ) ){
					$errors = array();
				}
				$errors[] = $userIdList[$i];
			}
		}
		return $errors;
	}

	/**
	 * Default method for command /start
	 *
	 */
	function cmd_start(){
		$this->api->sendMessage( "Wellcome to bot!" );
	}

	/**
	 * Default method for command /help
	 *
	 */
	function cmd_help(){
		
		$this->api->sendMessage([
			'text' => "Available commands:\n " . implode("\n ", array_keys( $this->commands ) ) . "",
			'parse_mode'=> 'HTML'
		]);
	}

	/**
	 * Default method. It calls if command is not recognized
	 *
	 */
	function cmd_default(){
		$this->api->sendMessage( "Enter a command." );
	}

	/**
	 * Default callback for inline button
	 */
	function callback_default( $query ){
		$this->api->answerCallbackQuery( [
			'callback_query_id' => $this->result['callback_query']["id"],
			'text' => "Action \"{$query}\" is not working now.",
			'show_alert' => true
		] );
	}

	/**
	 * Answer method on click for inline button
	 */
	function callbackAnswer( $text, $keyboard ){
		$this->api->answerCallbackQuery( $this->result['callback_query']["id"] );
		$this->api->editMessageText([
			'chat_id' => $this->result['callback_query']['message']['chat']["id"],
			'message_id' => $this->result['callback_query']['message']['message_id'],
			'text' => $text,
			'parse_mode' => "HTML",
			'reply_markup' => json_encode( ['inline_keyboard'=>$keyboard] )
		]);
	}

	/**
	 * Shows form and register webhook on telegram side
	 */
	function showWebhookForm(){
		$html = <<<EOF
<!DOCTYPE html><html lang="ru">
<head><meta charset="UTF-8"><title>Setting Webhook</title></head>
<body style="text-align: center">
	<h2>Setting telegram bot webhook</h2>
	<form action="" method="get">
		<p>
			<input type="text" name="url" placeholder="bot url" value="https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}">
			<br>https protocol required
		</p>
		<p>
			<input type="text" name="token" placeholder="token">
			<br>Talk to <a href="https://telegram.me/botfather" target="_blank">@BotFather</a>, to get it
		</p>
		<button type="submit">Set webhook</button>
	</form>
</body>
</html>
EOF;
		if( !empty( $_GET ) ){

			$this->api = new TelegramBotApi( $_GET['token'] );
			$res = $this->api->setWebhook( $_GET['url'] );
			if( $res['ok'] ){
				echo "Webhook is set! Fill token in your bot source to make it working!";
			}
			else{
				echo "Something wrong: " . $res['description'];
			}
		}
		else{
			echo $html;
		}

		exit;
	}


}

?>