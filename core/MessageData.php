<?php

namespace core\MessageData;

use core\HTMLDataCatcher\HTMLDataCatcher;

class MessageData
{
    private HTMLDataCatcher $HTMLDataCatcher;

    public function __construct()
    {
        $this->HTMLDataCatcher = new HTMLDataCatcher();
    }
    /**
     * Конструирует пост с измененным статусом
     * @param string $article Артикул товара
     * @param string $status Новый статус
     * @return bool|string False если условие выполнения не валидно|полный текст поста
     */
    public function changeMessageStatus(string $article, string $status) : bool|string
    {
        $messageText = $this->HTMLDataCatcher->getHtmlCode($article); //Получаем текст поста

        $hasUpdStatus = strripos($messageText, 'UPD'); //Проверка на наличие какого либо статуса
        $hasReserveStatus = strripos($messageText, 'В РЕЗЕРВЕ'); //Проверка на наличие статуса резерва
        $hasSoldStatus = strripos($messageText, 'ПРОДАНО'); //Проверка на наличие статуса продажи

        switch ($status) {
            case 0: //Если пользователь ввел "В продаже"
                preg_match("/<b>UPD: В РЕЗЕРВЕ<\/b>\\n\\n(.+)/s", $messageText, $matches);
                return ($hasReserveStatus !== false) ? $matches[1] : false;
            case 1: //Если пользователь ввел "В резерве"
                return ($hasUpdStatus === false) ? "<b>UPD: В РЕЗЕРВЕ</b>\n\n" . $messageText : false;
            case 2: //Если пользователь ввел "Продано"
                if ($hasSoldStatus) {
                    return false;
                } else {
                    $messageText = preg_replace('/(р\..+&#60;)/s', 'р.</b>', $messageText); //Убираем из поста ">ЗАРЕЗЕРВИРОВАТЬ<"
                    if ($hasReserveStatus) {
                        return preg_replace('/В РЕЗЕРВЕ/', "ПРОДАНО", $messageText);
                    } else {
                        return "<b>UPD: ПРОДАНО</b>\n\n" .  $messageText;
                    }
                }
        }
        return false;
    }

    /**
     * Конструирует пост с измененными ценами
     * @param string $data Данные в виде: артикул цена дисконт цена нового
     * @return array [$article, false] артикул и неудавшейся стейтмент
     * @return array [$messageId, $messageText] ID сообщения и текст сообщения
     */
    public function priceChanger(string $data) : array
    {
        preg_match_all('/\d+/', $data, $matches);
        $article = preg_replace('/^0+/', '', $matches[0][0]);
        $discountPrice = $matches[0][1];
        $fullPrice = $matches[0][2];

        $messageId = $this->HTMLDataCatcher->getMessageId($article);

        switch ($messageId) {
            case -1:
                return [$article, false]; //Если сообщение не найдено
            default:
                $messageText = $this->HTMLDataCatcher->getMessageText($article);
                $hasSoldStatus = strripos($messageText, 'ПРОДАНО');
                if ($hasSoldStatus | is_null($discountPrice) | is_null($fullPrice) | $fullPrice <= $discountPrice) {
                    return [$article, false]; //Если товар уже продан или неверный ввод цен
                } else {
                    $messageText = preg_replace('/<s>.+р\./', '<s>' . $fullPrice . '</s> ' . $discountPrice . ' р.',  $messageText);
                    return [$messageId, $messageText]; //Если сообщение найдено
                }
        }
    }
}