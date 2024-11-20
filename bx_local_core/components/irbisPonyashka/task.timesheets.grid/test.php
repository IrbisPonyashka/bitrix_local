<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;

class VacancyListIYBKPI extends CBitrixComponent
{

    /**
     * @var false|mixed
     */
    public function executeComponent()
    {
        global $USER;
        
        $this->arUser = $USER;

        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options('vacancy_list');
        $filterData = $filterOption->getFilter([]);
        foreach ($filterData as $k => $v) {
            $filter[$k] = $v;
        };
        $this->arResult['FILTER'] = $filter;

        $gridOption = new GridOptions('vacancy_list');
        $gridSortOptions = $gridOption->GetSorting();
        $nav_params = $gridOption->GetNavParams();
        $this->arResult["NAV"] = new Bitrix\Main\UI\PageNavigation('vacancy_list');
        $this->arResult["NAV"]->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        $this->sortBy = array_keys($gridSortOptions["sort"])[0];
        $this->sortAs = strtoupper($gridSortOptions["sort"][$this->sortBy]);

        $this->httpClient = new HttpClient();
        $this->arResult['AUTH_TOKEN'] = $this->getAuthByLogin();
        $this->arLocations = $this->getLocationList();
        $this->arSources = $this->getSourceList();
        $this->arResult['VACANCYS'] = $this->getVacancyList($this->sortBy,$this->sortAs);
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['FILTER_COLUMNS'] = $this->getFilter();
        $this->arResult['ROWS'] = $this->arResult['VACANCYS']["items"] && count($this->arResult['VACANCYS']["items"]) ? $this->getRows($this->arResult['VACANCYS']) : [];

        $this->includeComponentTemplate();
        return $this->arResult;
        /* $this->colFields = Array(
            "id", "JOB_TITLE", "DESCRIPTION", "STATUS", "SALARY", "LOCATION", "POSITION", "DEPARTMENT", "SOURCE"
        ); */
    }
    private function getColumns()
    {
        return $columns = Array(
            ['id' => 'id', 'name' => 'ID', 'sort' => 'id', 'default' => true],
            ['id' => 'JOB_TITLE', 'name' => 'Вакансия', 'sort' => false, 'default' => true],
            ['id' => 'DESCRIPTION', 'name' => 'Описание', 'sort' => false, 'default' => true],
            // ['id' => 'STATUS', 'name' => 'Статус', 'sort' => false, 'default' => true],
            ['id' => 'SALARY', 'name' => 'Зарплата', 'sort' => false, 'default' => true],
            ['id' => 'LOCATION', 'name' => 'Бизнес-единица', 'sort' => false, 'default' => true],
            ['id' => 'POSITION', 'name' => 'Позиция', 'sort' => false, 'default' => true],
            ['id' => 'DEPARTMENT', 'name' => 'Отдел', 'sort' => false, 'default' => true],
            // ['id' => 'SOURCE', 'name' => 'Источник', 'sort' => false, 'default' => true],
        );
    }
    private function getFilter()
    {
        // 2214 - опубликованный, 932 - новая, 933 - Заморожена
        $locationItems = [];
        foreach ($this->arLocations as $key => $items) {
            $locationItems[$items["id"]] = $items["name"];
        }
        return $columns = Array(
            // ['id' => 'id', 'name' => 'ID', 'type' => 'number'],
            // ['id' => 'JOB_TITLE', 'name' => 'Вакансия', 'type' => 'string'],
            // ['id' => 'DESCRIPTION', 'name' => 'Описание', 'type' => 'string'],
            // ['id' => 'SALARY', 'name' => 'Зарплата', 'type' => 'string'],
            // ['id' => 'POSITION', 'name' => 'Позиция', 'type' => 'string'],
            // ['id' => 'DEPARTMENT', 'name' => 'Отдел', 'type' => 'string'],
            ['id' => 'STATUS', 'name' => 'Статус', 'type' => 'list','items' => ['2214' => 'Опубликованные']],
            ['id' => 'LOCATION', 'name' => 'Бизнес-единица', 'type' => 'list','items' => $locationItems],
            ['id' => 'SOURCE', 'name' => 'Источник', 'type' => 'list','items' => ['Внутренний' => 'Внутренний', 'Внутренний и Внешний' => 'Внутренний и Внешний']],
        );
    }
    public function getRows($vacancys)
    {
        $rows = [];
        $deatailUrl = "https://crm.ipakyulibank.uz/local/components/micros/vacany.list.iyb-kpi/templates/.default/sidepanel.php";
        foreach ($vacancys["items"] as $key => $arItems)
        {
            $arItems["source_list"] = $this->arSources;
            $rows[] = [
                'data'    => [
                    "id" => $arItems["number"],
                    "JOB_TITLE" => $arItems["jobTitle"],
                    "DESCRIPTION" => $arItems["description"],
                    "STATUS" => $arItems["status"]["name"],
                    "SALARY" => $arItems["proposedSalary"],
                    "LOCATION" => $arItems["location"]["name"],
                    "POSITION" => $arItems["position"]["name"],
                    "DEPARTMENT" => $arItems["department"]["name"],
                    "SOURCE" => array_values(array_filter($arItems["customFields"], function ($v) {
                        return $v["code"] == "string_value21";
                    }, ARRAY_FILTER_USE_BOTH))[0]["value"]
                ],
                'actions' => [
                    [
                        'text'    => 'Отклик',
                        'onclick' => "
                            let loadingIconPopup = BX.PopupWindowManager.create(
                            'popup-message', null, {
                                content: '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"36\" height=\"36\" fill=\"#fff\" viewBox=\"0 0 24 24\"><script xmlns=\"\"/><style>.spinner_ajPY{transform-origin:center;animation:spinner_AtaB .75s infinite linear}@keyframes spinner_AtaB{100%{transform:rotate(360deg)}}</style><path d=\"M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z\" opacity=\".25\"/><path d=\"M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z\" class=\"spinner_ajPY\"/><script xmlns=\"\"/></svg>',
                                darkMode: true,
                                autoHide: true,
                                overlay: {
                                    backgroundColor: 'black',
                                    opacity: 400
                                }, 
                            });
                            loadingIconPopup.show();
                            BX.ajax({
                                url: 'https://crm.ipakyulibank.uz/local/components/micros/vacany.list.iyb-kpi/templates/.default/ajax.php?candidate=true',
                                data: ".json_encode([
                                    "action" => "add",
                                    "data" => [
                                        "vacancies" => [
                                            array( "id" => $arItems["id"], "name" => "string" )
                                        ],
										"firstName" => $this->arUser->GetFirstName(),
										"lastName" => $this->arUser->GetLastName(),
                                        "emails" => [
                                            array( "email" => $this->arUser->GetEmail(), "primary" => true )
                                        ]
                                    ]
                                ]).",
                                method: 'POST',
                                dataType: 'json',
                                processData: false,
                                onsuccess: (data) => {
									console.log(data);
                                    loadingIconPopup.close();
                                    let objData = JSON.parse(data);
                                    if(objData.success == true){
                                        BX.UI.Dialogs.MessageBox.alert(
                                            `<div class=\"ui-alert ui-alert-success\">
                                                <span class=\"ui-alert-message\"><strong>Спасибо</strong> заявка отправлена!</span>
                                            </div>`
                                        );
                                    }else{
                                        BX.UI.Dialogs.MessageBox.alert(
                                            `<div class=\"ui-alert ui-alert-warning\">
                                                <span class=\"ui-alert-message\"><strong>Что-то пошло не так</strong></span>
                                                <br>
                                                <span class=\"ui-alert-message\"><strong> `+objData.error.user_msg+` </strong></span>
                                                <br>
                                                <span class=\"ui-alert-message\"><strong> `+objData.error.developer_msg+` </strong></span>
                                            </div>`
                                        );
                                    }
                                },
                                onfailure: (data) => { 
                                    loadingIconPopup.close();
                                    BX.UI.Dialogs.MessageBox.alert(
                                        `<div class=\"ui-alert ui-alert-warning\">
                                            <span class=\"ui-alert-message\"><strong>Что-то пошло не так</strong></span>
                                            <span class=\"ui-alert-message\"><strong>${data}\"</strong></span>
                                        </div>`
                                    );
                                }
                            });"
                    ],
                    [
                        'text'    => 'Подробно',
                        'onclick' => "BX.SidePanel.Instance.open(\"$deatailUrl\",
                            { 
                                width: 800,
                                Title: \" Вакансия ID - ".$arItems['id']." \",
                                allowChangeHistory: false,
                                requestMethod: \"post\",
                                requestParams: { // post-параметры
                                    DETAIL: \"true\",
                                    DATA: ".json_encode(json_encode($arItems,1),1).",
                                }
                            }
                        );"
                    ]

                ],
            ];
        }
        return $rows;
    }

    private function getAuthByLogin()
    {
        return "FREE$300205$3DD0FBC166C9907B";

        $this->httpClient->setHeader("Content-Type", "application/json");
        $this->httpClient->setHeader("accessToken", "5BCBE145-5157-4452-8D3A-AE1B4F9334BB");
        $url = "https://kpi.ipakyulibank.uz/services/api/v2/email_login";
        $body = array(
            "email" => "K.Gulomjonova@ipakyulibank.uz",
            "password" => "KamGul0013337"
        );
        $responeJson = $this->httpClient->post($url, json_encode($body));
        $respone = json_decode($responeJson,1);
        if($respone["success"] && $respone["success"] == true){
            return $respone["data"]["token"];
        }else{
            return false;
        }
    }

    private function getVacancyList($sortBy,$sortAs)
    {
        $this->httpClient->setHeader("Content-Type", "application/json");
        $this->httpClient->setHeader("accessToken", "5BCBE145-5157-4452-8D3A-AE1B4F9334BB");
        $this->httpClient->setHeader("x-auth", $this->arResult['AUTH_TOKEN']);
        $url = "https://kpi.ipakyulibank.uz/services/api/v3/vacancy/list";

        if($sortBy && $sortBy == "id"){
            $sortBy = "VACANCY_ID";
        }else if($sortBy && $sortBy == "status"){
            $sortBy = "STATUS_ID";
        }
        $body = array(
            "sortAs" => $sortAs??"string",
            "sortBy" => $sortBy??"string",
        );
        // if(!empty($this->arResult['FILTER'])){
        $body["filters"] = [];
        if(!empty($this->arResult['FILTER']["STATUS"])){
            // 2214 - опубликованный, 932 - новая, 933 - Заморожена
            $body["filters"][] = array(
                "filterCodeName" => "status",
                "items" => [
                    array("id" => $this->arResult['FILTER']["STATUS"])
                ]
            );
        }else{
			$body["filters"][] = array(
			    "filterCodeName" => "status",
			    "items" => [
			        array("id" => "2214")
			    ]
			);
        }
        if(!empty($this->arResult['FILTER']["LOCATION"])){
            $body["filters"][] = array(
                "filterCodeName" => "location",
                "items" => [
                    array("id" => $this->arResult['FILTER']["LOCATION"])
                ]
            );
        }
        if(!empty($this->arResult['FILTER']["SOURCE"])){
            $body["filters"][] = array(
                "filterCodeName" => "string_value21",
                "items" => [
                    array("code" => $this->arResult['FILTER']["SOURCE"])
                ]
            );
        }
        // }

		//$body = array("{}");
		$respone = $this->httpClient->post($url, json_encode($body));
        $respone = json_decode($respone,1);
		//echo '<pre>'; print_r($respone); echo '</pre>';

        if($respone["success"] && $respone["success"] == true){
            return $respone["data"];
        }else{
            return $respone;
        }
    }

    private function getLocationList()
    {
        $this->httpClient->setHeader("Content-Type", "application/json");
        $this->httpClient->setHeader("accessToken", "5BCBE145-5157-4452-8D3A-AE1B4F9334BB");
        $this->httpClient->setHeader("x-auth", $this->arResult['AUTH_TOKEN']);
        $url = "https://kpi.ipakyulibank.uz/services/api/v3/vacancy/location_list?city=Toshkent";
        $respone = $this->httpClient->get($url, '{}');
        $respone = json_decode($respone,1);
		// echo '<pre>'; print_r([$respone,"getLocationList"]); echo '</pre>';

        if($respone["success"] && $respone["success"] == true){
            return $respone["data"];
        }else{
            return false;
        }
    }

    private function getSourceList()
    {
        $this->httpClient->setHeader("Content-Type", "application/json");
        $this->httpClient->setHeader("accessToken", "5BCBE145-5157-4452-8D3A-AE1B4F9334BB");
        $this->httpClient->setHeader("x-auth", $this->arResult['AUTH_TOKEN']);
        $url = "https://kpi.ipakyulibank.uz/services/api/v3/reference/_CANDIDATE_SOURCE";
        $respone = $this->httpClient->get($url, '{}');
        $respone = json_decode($respone,1);
        if($respone){
            return $respone;
        }else{
            return false;
        }
    }
}
