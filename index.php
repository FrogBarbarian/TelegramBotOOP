<?php
use core\FlowControl\FlowControl;
use core\HTMLDataCatcher\HTMLDataCatcher;
use core\APIActions\APIActions;
use core\MessageData\MessageData;

function handler ($event, $context)
{
    $vars = require 'config\variables.php'; //Подключаем важные переменные

    $token = $vars['token']; //Токен бота
    $channelId = $vars['channelId']; //ID телеграм канала
    $curators = $vars['curators']; //Список кураторов бота

    $data = json_decode($event['body'], true); //Декодируем json данные запроса в массив php
    $chatId = $data['message']['from']['id']; //ID чата запроса
    $text = $data['message']['text']; //Текст запроса
    $reply = ''; //Ответ пользователю

    $APIActions = new APIActions();
    $FlowControl = new FlowControl();
    $HTMLDataCatcher = new HTMLDataCatcher();
    $MessageData = new MessageData();

    //Проверка на валидность написавшего
    if (!in_array($chatId, $curators)) {
        $reply = "<b>Вы не являетесь куратором бота</b>";
        $APIActions->sendMessage($token, $reply, $chatId);
        $text = 'continue';
    }
    //Подключаем конфиг клавиатур
    $keyboards = require 'config\keyboards.php';

    //Базовая клавиатура
    $basicKeyboard = $keyboards['basicKeyboard'];
    //Клавиатура при смене статуса
    $statusKeyboard = $keyboards['statusKeyboard'];

    //Место старта основного кода

    //Проверяем установлено состояние ввода или это первичное сообщение
    if (isset($_POST['state']) && $_POST['id'] == $chatId) {
        switch ($_POST['state']) {
            //Если пользователь ввел /status
            case 'status':
                $article = preg_replace('/^0+/', '', $text); //Отсекаем нули вначале артикула

                //Проверяет введенный артикул на существование на канале
                switch ($HTMLDataCatcher->getMessageId($article)) {
                    case -1:
                        $reply = "<b>Артикул введен неверно, либо товар не найден</b>";
                        $APIActions->sendMessage($token, $reply, $chatId, $basicKeyboard);
                        $text = 'end';
                        break;
                    default:
                        $reply = "<b>Что с товаром?</b>";
                        $APIActions->sendMessage($token, $reply, $chatId, $statusKeyboard);
                        $FlowControl->setState('entered status', $chatId, $article);
                        $text = 'continue';
                        break;
                }
                break;
            //Пользователь ввел валидный артикул после /status, проверяем последующий ввод
            case 'entered status':
                $messageId = $HTMLDataCatcher->getMessageId($_POST['text']);
                switch ($text) {
                    case 'В продаже':
                        $newText = $MessageData->changeMessageStatus($_POST['text'], 0);
                        if ($newText === false) {
                            $text = 'invalid status';
                        } else {
                            $APIActions->changeMessage($token, $channelId, $messageId, $newText);
                            $text = 'success status';
                        }
                        break;
                    case 'В резерве':
                        $newText = $MessageData->changeMessageStatus($_POST['text'], 1);
                        if ($newText === false) {
                            $text = 'invalid status';
                        } else {
                            $APIActions->changeMessage($token, $channelId, $messageId, $newText);
                            $text = 'success status';
                        }
                        break;
                    case 'Продано':
                        $newText = $MessageData->changeMessageStatus($_POST['text'], 2);
                        if ($newText === false) {
                            $text = 'invalid status';
                        } else {
                            $APIActions->changeMessage($token, $channelId, $messageId, $newText);
                            $text = 'success status';
                        }
                        break;
                    default:
                        $reply = "<b>Введены неверные данные</b>";
                        $APIActions->sendMessage($token, $reply, $chatId, $basicKeyboard);
                        $text = 'end';
                        break;
                }
                $FlowControl->setState($text, $chatId, $messageId);
                break;
            //Если пользователь выбрал изменить цены на канале
            case 'prices':
                $pricesArray = explode("\n", $text); //Разбиваем ожидаемые данные
                $_POST['badWays'] = ''; //Сохраняет артикулы, которые не найдены на канале
                $_POST['badCount'] = 0; //Количество ненайденных товаров
                for ($i = 0; $i < count($pricesArray); $i++) {
                    $res = $MessageData->priceChanger($pricesArray[$i]);
                    if (!$res[1]) {
                        $_POST['badWays'] .= $res[0] . ' ';
                        $_POST['badCount'] += 1;
                    } else {
                        $APIActions->changeMessage($token, $channelId, $res[0], $res[1]);
                    }
                }
                if ($_POST['badWays'] !== '') {
                    $reply = $_POST['badWays'] . "\nЭти товары не найдены или что то пошло не так (<b>" . $_POST['badCount'] . " шт</b>)";
                } else {
                    $reply = 'Все хорошо, все цены изменены';
                }
                $APIActions->sendMessage($token, $reply, $chatId, $basicKeyboard);
                $text = 'end';
                break;
        }
    }

    //Когда пользователь нажал на одну из первоначальных кнопок (или вручную ввел)
    switch ($text) {
        case 'Поменять статус':
            $text = '/status';
            break;
        case 'Поменять цены':
            $text = '/prices';
            break;
        default:
            break;
    }

    //Базовые обработки пользовательского ввода
    switch ($text) {
        case '/start':
            $reply = "Данный бот предназначен для автоматизации работы с телеграм каналом ОГО Уценка";
            $APIActions->sendMessage($token, $reply, $chatId, $basicKeyboard);
            $FlowControl->unsetState();
            break;
        case '/help':
            $reply = "<b>Список команд:</b>\n/status - поменять статус поста\n/prices - изменить цены";
            $APIActions->sendMessage($token, $reply, $chatId, $basicKeyboard);
            $FlowControl->unsetState();
            break;
        case '/status':
            $reply = "<b>Введите артикул без пробелов</b>";
            $APIActions->sendMessage($token, $reply, $chatId, json_encode(['remove_keyboard' => true], true));
            $FlowControl->setState('status', $chatId, $text);
            break;
        case '/prices':
            $reply = "<b>Чтобы изменить цены напишите данные в таком виде:</b>\n<i>358777 15000 21000\n360370 1540 3250</i>\nАртикул   дисконт цена   полная цена\n<b>Рекомендую не более 20 ценников за раз</b>";
            $APIActions->sendMessage($token, $reply, $chatId, json_encode(['remove_keyboard' => true], true));
            $FlowControl->setState('prices', $chatId, $text);
            break;
        case 'end':
            $FlowControl->unsetState();
            break;
        case 'continue':
            break;
        case 'invalid status':
            $reply = "<b>Не удалось присвоить статус</b>";
            $APIActions->sendMessage($token, $reply, $chatId, $basicKeyboard);
            $FlowControl->unsetState();
            break;
        case 'success status':
            $reply = "<a href=\"https://t.me/testforogobot/{$HTMLDataCatcher->getMessageId($_POST['text'])}\"><b>Статус успешно присвоен</b></a>";
            $APIActions->sendMessage($token, $reply, $chatId, $basicKeyboard);
            $FlowControl->unsetState();
            break;
        default:
            $reply = "<b>Неверная команда</b>\nЯ могу /status поменять <b>статус</b> поста\n/prices изменить <b>цены</b>";
            $APIActions->sendMessage($token, $reply, $chatId);
            $FlowControl->unsetState();
            break;
    }

    return [
        'statusCode' => 200,
        'body' => '',
        'isBase64Encoded'=> false,
    ];
}