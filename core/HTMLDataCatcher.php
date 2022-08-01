<?php

namespace core\HTMLDataCatcher;

class HTMLDataCatcher
{
    protected string $channelUrl = 'https://t.me/s/channelUrl?q='; //URL целевого канала с GET параметром для поиска по каналу

    /**
     * Получает ID сообщения телеграм канала из HTML кода заданной страницы
     * @param $article string Артикул товара из сообщения пользователя
     * @return int ID сообщения в телеграм канале, если не найден товар, то возвращает -1
     */
    public function getMessageId(string $article) : int
    {
        preg_match('/' . $article . '&before=\K\d+/', $this->getHtmlCode($article), $matches);
        return (!isset($matches[0]) ? -1 : $matches[0] - 1);
    }

    /**
     * Получает HTML код страницы с искомым товаром
     * @param $article string Искомый артикул
     * @return string Код HTML страницы
     */
    public function getHtmlCode(string $article) : string
    {
        $request = curl_init();
        $requestOptions = [
            CURLOPT_URL => $this->channelUrl . $article,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
        ];
        curl_setopt_array($request, $requestOptions);
        $out = curl_exec($request);
        curl_close($request);
        return $out;
    }

    /**
     * Получает текст сообщения телеграм канала из HTML кода заданной страницы
     * @param string $article Артикул товара из сообщения пользователя
     * @return string Текст сообщения в телеграм канале
     */
    function getMessageText(string $article) : string
    {
        $badWords = [
            '/<mark class="highlight">/',
            '/<\/mark>/',
        ];
        preg_match('/js-message_text" dir="auto">(.*)<\/div>/', $this->getHtmlCode($article), $matches); //Ищем текст поста
        $messageText = preg_replace($badWords, '', $matches[1]); //Убираем из HTML кода разметку подсвечивания
        $messageText = preg_replace('/&gt;/', '&#62;', $messageText); //Меняем символ > из HTML в Decimal
        $messageText = preg_replace('/&lt;/', '&#60;', $messageText); //Меняем символ < из HTML в Decimal
        return preg_replace('/<br\/>/', "\n", $messageText); //Меняем перевод строки с <br/> на \n
    }
}