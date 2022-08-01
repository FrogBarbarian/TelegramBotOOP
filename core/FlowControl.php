<?php

namespace core\FlowControl;

/**
 * Управляет параметрами для корректного потока общения с пользователем
 */
class FlowControl
{
    /**
     * Задает параметры в $_POST для дальнейшего взаимодействия
     * @param string Новый статус бота
     * @param string ID чата написавшего
     * @param string Текст от пользователя, если требуется в контексте
     * @return void
     */
    public function setState(string $state, string $chatId, string $text = '') : void
    {
        $_POST['state'] = $state;
        $_POST['id'] = $chatId;
        $_POST['text'] = $text;
    }

    /**
     * Очищает кастомные параметры $_POST
     * @return void
     */
    public function unsetState() : void
    {
        unset($_POST['state']);
        unset($_POST['id']);
        unset($_POST['text']);
    }
}