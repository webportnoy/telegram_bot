<?php

/**
 * Telegram Bot Api class
 *
 * @api
 * @since PHP 5.4
 * @author Pavel Kuznetsov [pk@webportnoy.ru]
 * @link https://core.telegram.org/bots/api
 *
*/

class TelegramBotApi{

	const VERSION = '1.0';

	protected $apiToken = null;

	protected $apiUrl = "https://api.telegram.org/bot";

	protected $chatId = null;

	public $debug = false;

    public function __construct( $token = null ){
		if( isset( $token ) ){
			$this->apiToken = $token;
		}
		else{
            throw new Exception('Required "token" not supplied in construct');
		}
	}

	/**
	 * Setting webhook
	 * 
	 * @link https://core.telegram.org/bots/api#setwebhook
	 */
	public function setWebhook( $bot_url ){
		return $this->call("setWebhook", ['url' => $bot_url] );
	}

	/**
	 * Getting message from user
	 *
	 */
	public function getWebhookUpdate(){
		$body = json_decode(file_get_contents('php://input'), true);

		if( isset( $body["message"]["chat"]["id"] ) ){
			$this->chatId = $body["message"]["chat"]["id"];
		}

		return $body;
	}

	/**
	 * Sending text message to chat
	 *
	 * @param string|array $params Text message or array of parameters
	 *
	 * @link https://core.telegram.org/bots/api#sendmessage
	 */
	public function sendMessage( $params ){
		if( is_string( $params ) ){
			$params = ['text' => $params];
		}
		if( !isset( $params['chat_id'] ) && isset( $this->chatId ) ){
			$params['chat_id'] = $this->chatId;
		}
		return $this->call("sendMessage", $params);
	}

	/**
	 * Sending image to chat
	 *
	 * @param string|array  $params  Url or File Id or array of parameters
	 * @param string        $caption (Optional) Caption for image
	 *
	 * @link https://core.telegram.org/bots/api#sendphoto
	 */
	public function sendPhoto( $params, $caption = null ){
		if( is_string( $params ) ){
			$params = ['photo' => $params];
		}
		if( !isset( $params['chat_id'] ) && isset( $this->chatId ) ){
			$params['chat_id'] = $this->chatId;
		}
		if( !isset( $params['caption'] ) && isset( $caption ) ){
			$params['caption'] = $caption;
		}
		return $this->call("sendPhoto", $params);
	}

	/**
	 * Sending document to chat
	 *
	 * @param string|array  $params  Url or File Id or array of parameters
	 * @param string        $caption (Optional) Caption for document
	 *
	 * @link https://core.telegram.org/bots/api#senddocument
	 */
	public function sendDocument( $params, $caption = null ){
		if( is_string( $params ) ){
			$params = ['document' => $params];
		}
		if( !isset( $params['chat_id'] ) && isset( $this->chatId ) ){
			$params['chat_id'] = $this->chatId;
		}
		if( !isset( $params['caption'] ) && isset( $caption ) ){
			$params['caption'] = $caption;
		}
		return $this->call("sendDocument", $params);
	}

	/**
	 * Run api request
	 *
	 * @param string  $api_method  Method to be called.
	 * @param array   $params    (Optional) Array of parameters
	 *
	 * @link https://core.telegram.org/bots/api#available-methods
	 */
	public function call( $api_method = null, $params = null ){
		if( !$api_method ){
            throw new Exception('Required "api_method" not supplied for call()');
		}

		$query = "{$this->apiUrl}{$this->apiToken}/{$api_method}";
		if( is_array( $params ) ){
			$query .= "?" . http_build_query( $params );
		}

		$context  = stream_context_create( ['http' => [ 'method'  => 'POST' ] ] );
		if( $this->debug ){
			@file_put_contents( "request.txt", $query . "\n", FILE_APPEND );
		}

		$result = @file_get_contents( $query, false, $context );
		if( !$result ){
            throw new Exception("Api request fail. Query was: {$query}");
		}

		return json_decode( $result, 1 );

	}

}

?>