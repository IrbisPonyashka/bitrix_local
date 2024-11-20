<?if( !check_bitrix_sessid()) return;?>

<?
    if ($errorException = $APPLICATION->getException()) {
        // ошибка при установке модуля
        echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>"[error]", "DETAILS"=>$errorException->GetString(), "HTML"=>true));
    } else {
        // модуль успешно установлен
       echo CAdminMessage::ShowNote("Модуль harvest установлен");
    }
?>

<form action="<?= $APPLICATION->getCurPage(); ?>"> <!-- Кнопка возврата к списку модулей -->
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID; ?>" />
    <input type="submit" value="Вернуться в список модулей.">
</form>