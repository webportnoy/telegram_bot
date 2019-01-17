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

	const VERSION = '1.2';

	protected $apiToken = null;

	protected $apiUrl = "https://api.telegram.org/bot";

	public $chatId = null;

	public $debug = false;

	public $proxy = "";

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
			$this->message = $body["message"];
		}
		elseif( isset( $body['callback_query']["message"]["chat"]["id"] ) ){
			$this->chatId = $body['callback_query']["message"]["chat"]["id"];
			$this->message = $body['callback_query']["message"];
		}

		if( $this->debug ){
			@file_put_contents( "log.txt", print_r( $body, 1 ) . "\n", FILE_APPEND );
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
	 * Editing message
	 *
	 * @param array $params Array of parameters
	 *
	 * @link https://core.telegram.org/bots/api#editmessagetext
	 */
	public function editMessageText( $params ){
		if( !isset( $params['chat_id'] ) && isset( $this->chatId ) ){
			$params['chat_id'] = $this->chatId;
		}
		return $this->call("editMessageText", $params);
	}

	/**
	 * Deleting message
	 *
	 * @param int|array $params Message id or array of parameters
	 * 
	 * @link https://core.telegram.org/bots/api#deletemessage
	 */
	public function deleteMessage( $params ){
		if( !is_array( $params ) ){
			$params = [ 'message_id' => $params ];
		}
		if( !isset( $params['chat_id'] ) && isset( $this->chatId ) ){
			$params['chat_id'] = $this->chatId;
		}
		return $this->call("deleteMessage", $params);
	}

	/**
	 * Reaction of inline keyboard click
	 * 
	 * @param int|array $params Callback query id or array of parameters 
	 *
	 * @link https://core.telegram.org/bots/api#answercallbackquery
	 */
	public function answerCallbackQuery( $params ){
		if( !is_array( $params ) ){
			$params = ['callback_query_id' => $params];
		}
		return $this->call("answerCallbackQuery", $params);
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
	 * Sending audio to chat
	 *
	 * @param string|array  $params  Url or File Id or array of parameters
	 * @param string        $caption (Optional) Caption for image
	 *
	 * @link https://core.telegram.org/bots/api#sendaudio
	 */
	public function sendAudio( $params, $caption = null ){
		if( is_string( $params ) ){
			$params = ['audio' => $params];
		}
		if( !isset( $params['chat_id'] ) && isset( $this->chatId ) ){
			$params['chat_id'] = $this->chatId;
		}
		if( !isset( $params['caption'] ) && isset( $caption ) ){
			$params['caption'] = $caption;
		}
		return $this->call("sendAudio", $params);
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

		if( $this->debug ){
			@file_put_contents( "log.txt", $query . "\n", FILE_APPEND );
		}

		$context = $this->getStreamContext();

		ini_set('display_errors' , 1 );
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		ob_start();
		$result = file_get_contents( $query, false, $context );
		$decoded = $result ? @json_decode( $result, 1 ) : [];
		$err = ob_get_clean();
		if( !$result || !$decoded['ok'] ){
			if( $this->debug ){
				@file_put_contents( "log.txt", "Api request fail - {$err}\n {$result}\n", FILE_APPEND );
			}
            throw new Exception("Api request fail. Message: {$decoded['description']}. Query was: {$query}");
		}

		return $result;

	}

	/**
	 * Building stream context with or without proxy
	 *
	 * @return resource stream link
	 */
	private function getStreamContext(){
		$stream_options = [
				'http' => [
					'method'  => 'POST',
					'ignore_errors' => '1'
					]
				];
		if( $this->proxy ){
			$stream_options = [
				'http' => [
					"method"  => "POST",
					"timeout" => 20,
					'ignore_errors' => '1',
					"proxy"   => $this->proxy,
					'request_fulluri' => True
				]
			]; 
		}
		if( $this->debug ){
			@file_put_contents( "log.txt", print_r( $stream_options, 1 ) . "\n", FILE_APPEND );
		}
		return stream_context_create( $stream_options );
	}

}

?>