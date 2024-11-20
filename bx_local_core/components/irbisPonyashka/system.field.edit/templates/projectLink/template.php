<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Socialnetwork\WorkgroupTable;

CModule::IncludeModule("socialnetwork");

// \Bitrix\Main\UI\Extension::load('ui.entity-selector');
// \Bitrix\Main\UI\Extension::load('ui.alerts');
// \Bitrix\Main\UI\Extension::load('ui.button.panel');
// \Bitrix\Main\UI\Extension::load('ui.buttons');
// \Bitrix\Main\UI\Extension::load('ui.feedback.form');
// \Bitrix\Main\UI\Extension::load('ui.forms');
// \Bitrix\Main\UI\Extension::load('ui.icons');
// \Bitrix\Main\UI\Extension::load('ui.label');
// \Bitrix\Main\UI\Extension::load('ui.layout-form');
// \Bitrix\Main\UI\Extension::load('ui.menu-configurable');
// \Bitrix\Main\UI\Extension::load('ui.notification');
// \Bitrix\Main\UI\Extension::load('ui.reactions-select');
// \Bitrix\Main\UI\Extension::load('ui.select');
// \Bitrix\Main\UI\Extension::load('ui.sidepanel-content');
// \Bitrix\Main\UI\Extension::load('ui.sidepanel.layout');
// \Bitrix\Main\UI\Extension::load('ui.toolbar');

$projectId = !empty($arResult["VALUE"]) ? $arResult["VALUE"][0] ?? null : null;
$project = $projectId ? \Bitrix\Socialnetwork\WorkgroupTable::getList( [ "select" => ["*"], "filter" => [ "ID" => $projectId ]  ] )->Fetch() : null;

if($project && $project["IMAGE_ID"] ){
	$rsFile = CFile::GetByID( $project["IMAGE_ID"] ); 
	$arFile = $rsFile->Fetch();
	$project["LOGO"] = $arFile["SRC"];
}else if($project){ 
	$project["LOGO"] = "/bitrix/images/socialnetwork/workgroup/".$project["AVATAR_TYPE"].".png"; 
}
?>

<div class="fields string" id="main_<?=$arParams["arUserField"]["FIELD_NAME"]?>">
	<div class="fields string">
		<input type="hidden" name="<?=$arParams["arUserField"]["FIELD_NAME"]?>" value="<?=$arResult["VALUE"][0]?>" class="fields string" >
		<span id="project-container"></span>
	</div>
</div>

<script>
	var input = document.querySelector('[name=<?=$arParams["arUserField"]["FIELD_NAME"]?>]');
	input.addEventListener("change", (e) => {
		console.log(e.target);
	})
	var value = <?=json_encode($project)?>;
	console.log(input);
	var tagselectorParams = {
		multiple:false,
		dialogOptions: {
			context: "MTECHSUPPORT_MODULE_PROJECT_CONTEXT",
			entities: [
				{
					id: "project",
				},
			],    
		},
		events: {
			onBeforeTagAdd: function (event) {
				let data = event.getData()["tag"];                
				let arrayVal = {id:data["id"],entityId:"project",title:data["title"],link:data["link"],avatar:data["avatar"]};
				input.value = arrayVal.id;
				input.dispatchEvent(new Event('change', { bubbles: true }));
			},
			onBeforeTagRemove: function (event) {
				input.value = "";
				input.dispatchEvent(new Event('change', { bubbles: true }));
			}
		}
	};
	if(value){
		tagselectorParams.items = [
			{
				"id": value.ID,
				"entityId": "project",
				"title": value.NAME,
				"avatar": value.LOGO
			}
		];
	}
	new BX.UI.EntitySelector.TagSelector(tagselectorParams).renderTo(document.getElementById("project-container"));
</script>
