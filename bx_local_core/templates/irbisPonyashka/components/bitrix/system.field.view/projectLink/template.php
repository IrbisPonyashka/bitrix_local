<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Socialnetwork\WorkgroupTable;
CModule::IncludeModule("socialnetwork");

\Bitrix\Main\UI\Extension::load('ui.entity-selector');

$projectId = !empty($arResult["VALUE"]) ? $arResult["VALUE"][0] ?? null : null;
$project = $projectId ? WorkgroupTable::getList( [ "select" => ["*"], "filter" => [ "ID" => $projectId ]  ] )->Fetch() : null;

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
	var value = <?=json_encode($project)?>;
	console.log(value);
	var tagselectorParams = {
		multiple:false,
		readonly:true,
		tagBgColor:"#fff0",
		dialogOptions: {
			context: "MTECHSUPPORT_MODULE_PROJECT_CONTEXT",
			entities: [
				{ id: "project" },
			],    
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
	if(document.querySelector("tr[data-id='<?=$arParams["arUserField"]["ENTITY_VALUE_ID"]?>'] #project-container")){
		new BX.UI.EntitySelector.TagSelector(tagselectorParams).renderTo(document.querySelector("tr[data-id='<?=$arParams["arUserField"]["ENTITY_VALUE_ID"]?>'] #project-container"));
	}else{
		new BX.UI.EntitySelector.TagSelector(tagselectorParams).renderTo(document.querySelector("#project-container"));
	}
</script>
