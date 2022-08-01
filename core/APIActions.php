<?php

namespace core\APIActions;

class APIActions
{
    /**
     * Отправляет сообщение через бота пользователю
     * @param string $token Токен бота
     * @param string $text Текст бота
     * @param string $chatId ID чата кому отправляется сообщение
     * @param array $replyMarkup Подключение клавиатуры, если требуется
     * @return void
     */
    public function sendMessage(string $token, string $text, string $chatId, mixed $replyMarkup = []) : void
    {
        $ch = curl_init();
        $chPost = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/sendMessage',
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chatId,
                'parse_mode' => 'HTML',
                'text' => $text,
                'reply_markup' => $replyMarkup,
            ],
        ];

        curl_setopt_array($ch, $chPost);
        curl_exec($ch);
    }



    /**
     * Меняет сообщение в телеграм канале
     * @param string $token Токен бота
     * @param string $channelId ID телеграм канала
     * @param string $messageId ID сообщения
     * @param string $newText Новый текст для сообщения
     * @return void
     */
    public function changeMessage(string $token, string $channelId, string $messageId, string $newText) : void
    {
        $ch = curl_init();
        $chPost = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/editMessageCaption',
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $channelId,
                'message_id' => $messageId,
                'parse_mode' => 'HTML',
                'caption' => $newText,
            ],
        ];

        curl_setopt_array($ch, $chPost);
        curl_exec($ch);
    }
}