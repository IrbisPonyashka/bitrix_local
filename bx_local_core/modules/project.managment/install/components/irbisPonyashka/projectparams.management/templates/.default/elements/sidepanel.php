<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\UI\Extension::load('ui.sidepanel-content');
\Bitrix\Main\UI\Extension::load('ui.sidepanel.layout');
\Bitrix\Main\UI\Extension::load('ui.layout-form');
\Bitrix\Main\UI\Extension::load('ui.form');
\Bitrix\Main\UI\Extension::load("ui.dialogs.messagebox");
\Bitrix\Main\UI\Extension::load("ui.alerts");
\Bitrix\Main\UI\Extension::load("ui.entity-selector");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?  $APPLICATION->ShowHead(); ?>
    <title> Создание конфигурации </title>
</head>
<style>
    .ui-form-row .ui-form-content {
        width: 320px;
    }
</style>
<body>
    <? if ( $_REQUEST["action"] && $_REQUEST["MAP"] ) {?>
        <?
            $crmClientsSelectorCOptions = array(
            "ID" => "projects_param_client_selector",
            "API_VERSION" => 3,
            "LIST" => [],
            "INPUT_NAME" => "CUSTOMER_ID",
            "USE_SYMBOLIC_ID" => true,
            "SELECTOR_OPTIONS" => array(
                'contextCode' => 'CRM',
                'enableSonetgroups' => 'N',
                'enableUsers' => 'N',
                'enableAll' => 'N',
                'enableDepartments' => 'N',
                'enableCrm' => 'Y',
                'enableCrmCompanies' => 'Y',
                'enableCrmContacts' => 'Y',
                'crmPrefixType' => 'SHORT'
            )
        );
        ?>
        <div class="ui-slider-section ui-slider-section-icon ui-slider-section-column">
            <div class="ui-slider-content-box">
                <div class="ui-slider-heading-1"> Создание конфигурации проекта </div>
<!--                <p class="ui-slider-paragraph-2">  Заполните, поля ниже </p>-->
            </div>
            <div class="ui-slider-content-box">
                <form id="<?=$_REQUEST["action"] ?>" class="ui-form">
                    <!-- form fields -->
                    <? foreach ( $_REQUEST["MAP"] as $fieldKey => $field ) {?>
                        <? if( $field["title"] ) { ?>
                            <div class="ui-form-row" id="<?=$fieldKey?>">
                                <? // echo '<pre style="display:none">'; print_r([ $fieldKey, $field, $_REQUEST ]); echo '</pre>'; ?>
                                <div class="ui-form-label">
                                    <div class="ui-ctl-label-text">
                                        <?=$field["title"]?>
                                    </div>
                                </div>
                                <div class="ui-form-content">
                                    <? if( $fieldKey == "CUSTOMER_ID") {?>
                                        <div class="">
                                            <?$APPLICATION->IncludeComponent(
                                                'bitrix:main.user.selector',
                                                '',
                                                $crmClientsSelectorCOptions
                                            );?>
                                        </div>
                                    <? }else if($fieldKey == "PROJECT_ID"){ ?>
                                        <div class="ui-ctl-wrapp">
                                            <input type="hidden" class="ui-ctl-element"
                                                   name="<?=$fieldKey?>"
                                                   placeholder="<?=$field["title"]?>">
                                        </div>
                                    <? }else { ?>
                                        <div class="ui-ctl ui-ctl-textbox ui-ctl-wd">
                                            <input type="text" class="ui-ctl-element"
                                                   name="<?=$fieldKey?>"
                                                   placeholder="<?=$field["title"]?>">
                                        </div>
                                    <? } ?>
                                </div>
                            </div>
                        <? } ?>
                    <? } ?>
                    <!-- form fields end-->

                    <!-- submit button -->
                    <button class="ui-btn ui-btn-primary" type="submit">Сохранить</button>
                    <!-- submit button END -->
                </form>
            </div>
        </div>
        <script>
            BX.ready( () => {
                /* project Selector  start */
                    /*let clientSelectorOptions = {
                        id: 'CUSTOMER_ID',
                        multiple: false,
                        placeholder: 'Выберите клиента',
                        dialogOptions: {
                            entities: [
                                {id: 'crm_company'}
                            ],
                        },
                        events: {
                            onInput : (e) => {
                                const selector = e.getTarget();
                                selector.addTag({
                                    id: 1,
                                    title: "test",
                                    entityId: 'crm_company',
                                });
                            }
                        }
                    };*/
                    let projectsSelectorOptions = {
                        id: 'PROJECT_ID',
                        multiple: false,
                        placeholder: 'Выберите проект',
                        textBoxWidth: 320,
                        tagMaxWidth: 320,
                        dialogOptions: {
                            entities: [
                                {id: 'project'}
                            ],
                        },
                        events: {
                            onTagAdd: function(e) {
                                var value = e.data.tag.id;
                                document.querySelector("[name=PROJECT_ID]").value = value;
                            }
                        }
                    };
                    // const clientSelector = new BX.UI.EntitySelector.TagSelector(clientSelectorOptions);
                    const projectSelector = new BX.UI.EntitySelector.TagSelector(projectsSelectorOptions);
                    // clientSelector.renderTo(document.querySelector('#CUSTOMER_ID .ui-form-content'));
                    projectSelector.renderTo(document.querySelector('#PROJECT_ID .ui-ctl-wrapp'));
                /* project Selector  end */

                /* form submit */
                if(document.querySelector(".ui-form")){
                    let htmlForm = document.querySelector(".ui-form");
                    htmlForm.addEventListener("submit", (e) => {
                        e.preventDefault();
                        const formData = new FormData(htmlForm);

                        for (const key of formData.keys()) {
                            if(document.querySelector(key).value == null){
                                return;
                            }else{

                            }
                        }
                    })
                }
                /* form submit end*/

                function callFetch(url, methodType, header)
                {

                }

            })
        </script>
    <?} else {
        header("HTTP/1.0 404 Not Found");
    } ?>

</body>
</html>
