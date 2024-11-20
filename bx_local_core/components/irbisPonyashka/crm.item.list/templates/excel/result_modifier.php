<?php


if(isset($arResult["HEADERS"]))
{
    foreach ($arResult["HEADERS"] as $arKey => $arHead) {
        if($arHead["id"] == "UF_CRM_5_SUM")
        {
            $curr = array(
                "id" => "UF_CRM_5_CURRENCY",
                "name" => "Валюта",
                "sort" => "UF_CRM_5_CURRENCY",
                "default" => 1,
                "editable" => 1,
                "type" => "text",
            );
            array_splice($arResult["HEADERS"], $arKey+1, 0, [$curr]);
        }
    }
}

if(isset($arResult["ITEMS"]))
{
    foreach ($arResult["ITEMS"] as $arKey => $arItem) {
        if(!empty($arItem["UF_CRM_5_SUM"]))
        {
            $str_len = strlen($arItem["UF_CRM_5_SUM"]);
            if(substr( $arItem["UF_CRM_5_SUM"], 0, 1) == "$")
            {
                $arResult["ITEMS"][$arKey]["UF_CRM_5_CURRENCY"] = substr( $arItem["UF_CRM_5_SUM"], 0, 1);
                $arResult["ITEMS"][$arKey]["UF_CRM_5_SUM"] = (int)substr( $arItem["UF_CRM_5_SUM"] , 1);

            }else if(substr($arItem["UF_CRM_5_SUM"] , $str_len - 3, $str_len) == "UZS"){

                $arResult["ITEMS"][$arKey]["UF_CRM_5_CURRENCY"] = substr($arItem["UF_CRM_5_SUM"] , $str_len - 3, $str_len);
                $arResult["ITEMS"][$arKey]["UF_CRM_5_SUM"] = (int)substr( $arItem["UF_CRM_5_SUM"] , 0, $str_len - 3);
            }
        }
    }
}

// echo '<pre>'; print_r($arResult); echo '</pre>';
// die;