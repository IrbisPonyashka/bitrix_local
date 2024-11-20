<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Filter\HeaderSections;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */


Extension::load(['ui.dialogs.messagebox', 'crm_common', 'crm.settings-button-extender', 'crm.entity-list.panel']);
Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');

if ($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message"><?=$error->getMessage();?></span>
		</div>
		<?php
	}

	return;
}
echo CCrmViewHelper::RenderItemStatusSettings($arParams['entityTypeId'], ($arParams['categoryId'] ?? null));
/** @see \Bitrix\Crm\Component\Base::addTopPanel() */
$this->getComponent()->addTopPanel($this);

/** @see \Bitrix\Crm\Component\Base::addToolbar() */
$this->getComponent()->addToolbar($this);


?>

<div class="ui-alert ui-alert-danger" style="display: none;">
	<span class="ui-alert-message" id="crm-type-item-list-error-text-container"></span>
	<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
</div>

<div class="crm-type-item-list-wrapper" id="crm-type-item-list-wrapper">
	<div class="crm-type-item-list-container<?php
		if ($arResult['grid'])
		{
			echo ' crm-type-item-list-grid';
		}
		?>" id="crm-type-item-list-container">
		<?php
		if ($arResult['grid'])
		{
			echo '<div id="crm-type-item-list-progress-bar-container"></div>';

			if (!empty($arResult['interfaceToolbar']))
			{
				$APPLICATION->IncludeComponent(
					'bitrix:crm.interface.toolbar',
					'',
					[
						'TOOLBAR_ID' => $arResult['interfaceToolbar']['id'],
						'BUTTONS' => $arResult['interfaceToolbar']['buttons'],
					]
				);
			}
			
			if( isset($_REQUEST["IFRAME"]) ){
				$APPLICATION->IncludeComponent(
					'bitrix:main.ui.filter',
					'',
					$arResult["toolbar_parameters"]["filter"]
				); 

                ?>                
                    <div class="upload-button-container" style="margin-top: 18px;margin-bottom: 18px;display: flex;justify-content: end;">
                        <!-- <button id="pdf-upload-button" class="ui-btn ui-btn-primary" onclick="BX.Crm.Router.Instance.closeSettingsMenu();BX.Event.EventEmitter.emit('BX.Crm.ItemListComponent:onStartExportCsv');">
                            Экспорт в CSV
                        </button> -->
                        <button id="expenses-excl-upload-button" class="ui-btn ui-btn-success">
                        <!-- <button id="excl-upload-button" class="ui-btn ui-btn-success" onclick="BX.Crm.Router.Instance.closeSettingsMenu();BX.Event.EventEmitter.emit('BX.Crm.ItemListComponent:onStartExportExcel');"> -->
                            Экспорт в Excel
                        </button>
                    </div>
                <?
			}

			$arResult['grid']['HEADERS_SECTIONS'] = HeaderSections::getInstance()
				->filterGridSupportedSections($arResult['grid']['HEADERS_SECTIONS'] ?? []);

			$APPLICATION->IncludeComponent(
				"bitrix:main.ui.grid",
				"",
				$arResult['grid']
			);

		}
		?>
	</div>
</div>

<script type="text/javascript">

    BX.ready( () => {
        var parentBottomBlockNode = document.querySelector('#crm-type-item-list-190-8_bottom_panels')
        
        if (parentBottomBlockNode) {
            var rightBlockNode = parentBottomBlockNode.querySelector('.main-grid-cell-right');
            var rightBlockNodeParent = rightBlockNode.parentNode;
            var tdNodeBlock = document.createElement("td");
                tdNodeBlock.className="main-grid-panel-total main-grid-panel-cell main-grid-cell-left";
                tdNodeBlock.innerHTML = `
							<div class="main-grid-panel-content">
                                <span class="main-grid-panel-content-title">Общая сумма(USD):</span>&nbsp;
									<span class="main-grid-panel-content-text">
										<?=$arResult["EXPENSES_SUM_USD"]?>
									</span>
                            </div>
							<div class="main-grid-panel-content">
                                <span class="main-grid-panel-content-title">Общая сумма(UZS):</span>&nbsp;
									<span class="main-grid-panel-content-text">
										<?=$arResult["EXPENSES_SUM_UZS"]?>
									</span>
                            </div>`;


            rightBlockNodeParent.insertBefore(tdNodeBlock, rightBlockNode);
        }

		var exclBtn = document.querySelector("#expenses-excl-upload-button")
		let random_numbers = getRandomNumber();
		if(exclBtn){
			exclBtn.addEventListener("click", async (e) => {
				e.preventDefault();
				
				let firstExpReq = await getExclExportFetchRequest(random_numbers); 
				if(firstExpReq.status){
					if(firstExpReq.data.DOWNLOAD_LINK){
						downloadFile(firstExpReq.data.DOWNLOAD_LINK, firstExpReq.data.FILE_NAME)
					}else{
						let secondExpReq = await getExclExportFetchRequest(random_numbers); 
						if(secondExpReq.data.DOWNLOAD_LINK){
							downloadFile(secondExpReq.data.DOWNLOAD_LINK, secondExpReq.data.FILE_NAME)
						}
						
					}
				}
			})
		}

		function getRandomNumber() {
			const min = 1000000000000; // Минимальное значение (1 триллион)
			const max = 2000000000000; // Максимальное значение (2 триллиона)
			return Math.floor(Math.random() * (max - min + 1)) + min;
		}
		function downloadFile(url, filename) {
			const link = document.createElement('a'); // Создаем элемент <a>
			link.href = url; // Указываем ссылку на файл
			link.download = filename; // Указываем имя файла для загрузки
			document.body.appendChild(link); // Добавляем <a> к документу
			link.click(); // Программное "нажатие" на ссылку
			document.body.removeChild(link); // Удаляем элемент <a>
		}

		async function getExclExportFetchRequest(random_numbers)
		{
			return new Promise((resolve, reject) => {
				
				const myHeaders = new Headers();
				
				myHeaders.append("X-Bitrix-Csrf-Token", "<?=$_SESSION["fixed_session_id"]?>");

				const formdata = new FormData();
					formdata.append("SITE_ID", "s1");
					formdata.append("entityTypeId", "190");
					formdata.append("categoryId", "8");
					formdata.append("EXPORT_TYPE", "excel");
					formdata.append("COMPONENT_NAME", "micros:crm.item.list");
					formdata.append("PROCESS_TOKEN", `crm.item.list.export.csv${random_numbers}`);

				const requestOptions = {
					method: "POST",
					headers: myHeaders,
					body: formdata,
					redirect: "follow"
				};

				fetch("https://crm.kostalegal.com/bitrix/services/main/ajax.php?action=bitrix%3Acrm.api.itemExport.dispatcher", requestOptions)
					.then((response) => response.text())
					.then((result) => {
						result = JSON.parse(result);
						resolve(result);
					})
					.catch((error) => reject(error));
			})
		}

    });

</script>

<?php

$messages = array_merge(Container::getInstance()->getLocalization()->loadMessages(), Loc::loadLanguageFile(__FILE__));

if (!empty($arResult['restrictedFieldsEngine']))
{
	Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['restrictedFieldsEngine'];
}
?>

<script>
	BX.ready(function() {
		BX.message(<?=Json::encode($messages)?>);

		let params = <?=CUtil::PhpToJSObject($arResult['jsParams'], false, false, true);?>;
		params.errorTextContainer = document.getElementById('crm-type-item-list-error-text-container');

		params.progressBarContainerId = 'crm-type-item-list-progress-bar-container';

		(new BX.Crm.ItemListComponent(params)).init();

		<?php if (isset($arResult['RESTRICTED_VALUE_CLICK_CALLBACK'])):?>
		BX.addCustomEvent(window, 'onCrmRestrictedValueClick', function() {
			<?= $arResult['RESTRICTED_VALUE_CLICK_CALLBACK']; ?>
		});
		<?php endif;?>
	});
</script>
