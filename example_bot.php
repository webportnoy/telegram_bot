<?php

require_once("lib/telegram_bot.php");

class TestBot extends TelegramBot{

	//protected $token = "";
	//protected $bot_name = "";
	//public $proxy = "tcp://185.93.3.123:8080";

	/**
	 * Предустановленные варианты команд
	 * команда => метод для обработки команды
	 */
	protected $commands = [
			"/start" => "cmd_start",
			"/help" => "cmd_help",
			"Привет" => "cmd_privet",
			"Картинка" => "cmd_kartinka",
			"Гифка" => "cmd_gifka",
			"Новости" => "cmd_novosti",
			"Музыка" => "cmd_music",
			"Подкаст" => "cmd_podcast",
			"Инлайн" => "cmd_inlinemenu"
		];

	/**
	 * Предустановленные клавиатуры
	 *
	 * Справка по клавитурам: https://core.telegram.org/bots/api#replykeyboardmarkup
	 * 
	 */
	public $keyboards = [
		'default' => [
			'keyboard' => [
				["Привет", "Новости"], // Две кнопки в ряд
				["Картинка", "Гифка"],
				["Музыка", "Подкаст"],
				["Инлайн меню"] // Кнопка на всю ширину
			]
		],
		'inline' => [
			// Две кнопки в ряд
			[
				// вызовет метод callback_act1(),
				[
					'text' => "ℹ️ Действие 1",
					'callback_data'=> "act1"
				],
				// вызовет метод callback_act2(),
				// дополнительные параметры будут доступны в переменной $this->result['callback_query']["data"]
				[
					'text' => "🔗 C параметрами",
					'callback_data'=> "act2 param1 param2"
				]
			],
			[
				['text' => "🌎 Действие 3", 'callback_data'=> "act3"],
				['text' => "📚 Действие 4", 'callback_data'=> "act4"]
			],
			// Кнопка на всю ширину
			[
				['text' => "🚪 Закрыть", 'callback_data'=> "logout"],
			]
		],
		'back' =>[[['text' => "↩ Назад", 'callback_data'=> "back"]]]
	];

	/**
	 * Обработка ввода команды "/start"
	 */
	function cmd_start(){
		$this->api->sendMessage([
			'text' => "Добро пожаловать в бота!",
			'reply_markup' => json_encode($this->keyboards['default'])
		]);
	}

	/**
	 * Обработка ввода команды "Привет"
	 */
	function cmd_privet(){
		$this->api->sendMessage( "И тебе привет, @" . $this->result["message"]["from"]["username"] . "." );
	}

	/**
	 * Обработка ввода команды "Картинка" отправляет сообщение с картинкой
	 */
	function cmd_kartinka(){
		$this->api->sendPhoto( "https://webportnoy.ru/upload/alno/alno3.jpg", "Описание картинки" );
	}

	/**
	 * Обработка ввода команды "Гифка" отправляет сообщение с гифкой
	 */
	function cmd_gifka(){
		$this->api->sendDocument( "https://webportnoy.ru/upload/1.gif", "Описание гифки" );
	}

	/**
	 * Обработка ввода команды "Новости" отправляет сообщение со списком новостей из RSS-ленты
	 */
	function cmd_novosti(){
		$rss = simplexml_load_file('http://vposelok.com/feed/1001/');
		$text = "";
		foreach( $rss->channel->item as $item ){
			$text .= "\xE2\x9E\xA1 " . $item->title . " (<a href='" . $item->link . "'>читать</a>)\n\n";
		}
		$this->api->sendMessage([
			'parse_mode' => 'HTML', 
			'disable_web_page_preview' => true, 
			'text' => $text 
		]);
	}

	/**
	 * Обработка ввода команды "Музыка" отправляет сообщение с аудиофайлом
	 * 20 Mb maximum: https://core.telegram.org/bots/api#sending-files
	 */
	function cmd_music(){
		$url = "http://vposelok.com/files/de-phazz_-_strangers_in_the_night.mp3";
		$this->api->sendAudio( $url );
	}

	/**
	 * Обработка ввода команды "Подкаст" отправляет сообщение с последним выпуском подкаста
	 * Если файл подкаста меньше 20 Мб, то он будет отправлен сообщением, в противном случае будет добавлена ссылка на скачивание.
	 * 20 Mb maximum: https://core.telegram.org/bots/api#sending-files
	 */
	function cmd_podcast(){
		$rss = simplexml_load_file('https://meduza.io/rss/podcasts/tekst-nedeli');

		$item = $rss->channel->item;
		$enclosure = (array) $item->enclosure;
		$size = round( $enclosure['@attributes']['length'] / (1024*1024), 1 );
		$text = "🎙 {$item->title}";

		if( $size < 20 ){
			$this->api->sendAudio( $enclosure['@attributes']['url'] );
		}
		else{
			$text .= "\n\n⬇️ <a href='" . $enclosure['@attributes']['url'] . "'>скачать</a> {$size}Mb";
		}

		$this->api->sendMessage([
			'parse_mode' => 'HTML', 
			'disable_web_page_preview' => true, 
			'text' => $text 
		]);
	}

	/**
	 * Ответ на ввод, не распознанный как команда
	 */
	function cmd_default(){
		// Ответ на сообщения содержащих слово тариф. Например "Расскажи мне о тарифах" или "Какие есть тарифы?"
		if( stripos( $this->result["message"]["text"], "тариф" ) !== false ){
			$this->api->sendMessage( "Тариф1: 123\nТариф2: 234\nТариф3: 345" );
		}
		// Если пользователь хочет поддержки
		elseif( stripos( $this->result["message"]["text"], "поддержк" ) !== false ){
			$this->api->sendMessage( "Техническая поддержка пока доступна только по email." );
		}
		// Если не сработали никакие команды
		else{
			$this->api->sendMessage([
				'text' => "Не знаю что ответить, не научили меня еще таким командам. Могу показать структуру сообщения:\n<pre>" . print_r( $this->result, 1) . "</pre>",
				'parse_mode'=> 'HTML'
			]);
		}
	}

	/**
	 * Обработка ввода команды "Инлайн" отправляет сообщение с клавиатурой, прикрепелнной к сообщению.
	 */
	function cmd_inlinemenu(){
		$this->api->sendMessage([
			'text'=>"Ниже выведены кнопки, нажатие на которые может выполнять какие-то действия. Бот не ответит на кнопке будет отображаться иконка часиков.",
			'reply_markup' => json_encode( [
				'inline_keyboard'=> $this->keyboards['inline']
			] )
		]);
	}


	// Простой ответ на нажатие кнопки
	// с изменением текущего сообщения и клавиатуры под ним
	function callback_act1(){
		$text = "Вы нажали на кнопку \"Действие 1\"";
		$this->callbackAnswer( $text, $this->keyboards['back'] );
	}

	// Ответ на нажатие кнопки с обработкой дополнительных параметров
	function callback_act2( $query ){
		$text = "Вы нажали на кнопку \"C параметрами\" ";
		$text .= "Вот какие параметры были переданы с нажанием кнопки:\n {$query}";
		$this->callbackAnswer( $text, $this->keyboards['back'] );
	}

	// Ответ на нажатие кнопки всплывающим окном
	function callback_act3( $query ){
		$this->api->answerCallbackQuery( [
			'callback_query_id' => $this->result['callback_query']["id"],
			'text' => "Вы нажали кнопку \"Действие 3\"",
			'show_alert' => true
		] );
	}

	// Ответ на кнопку "Назад" выводит начальную клавиатуру
	function callback_back(){
		$text = "Вы вернулись к началу Инлайн меню";
		$this->callbackAnswer( $text, $this->keyboards['inline'] );
	}

	// Ответ на кнопку "Закрыть"
	function callback_logout(){
		$this->api->answerCallbackQuery( $this->result['callback_query']["id"] );
		$this->api->deleteMessage( $this->result['callback_query']['message']['message_id'] );
	}

}

?>