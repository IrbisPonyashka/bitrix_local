<?php

namespace IrbisPonyashka\classes\crm;

use \Bitrix\Main,
   \Bitrix\Crm,
   \Bitrix\Crm\Service,
   \Bitrix\Crm\Service\Factory\Dynamic,
   \Bitrix\Crm\Service\Operation,
   \Bitrix\Crm\Service\Context,
   \Bitrix\Main\Result,
   \Bitrix\Crm\Item,
   \Bitrix\Bizproc\Workflow\Type\GlobalConst,
   \IrbisPonyashka\Tasks\Reports;

Main\Loader::requireModule('crm');

class Factory extends Dynamic{

    // задача, чтобы только определенные пользователи могли редактировать определенные поля
    /* public function getUserFieldsInfo(): array
    {
        $fields = parent::getUserFieldsInfo();
                //список полей, которые только через апи можно изменить
        $arReadFields = [
            "UF_CRM_3_1711372268355", 
            "UF_CRM_3_1711372325073", 
            "UF_CRM_3_1711372343816", 
            "UF_CRM_3_1711617100002", 
            "UF_CRM_3_1711617197261", 
            "UF_CRM_3_1711617340871", 
        ]; 
        foreach ($arReadFields as $field) {
            if(isset($fields[$field])){
                    $fields[$field]['ATTRIBUTES'][] = \CCrmFieldInfoAttr::Immutable;
            }
        }
        
                //а тут или юзеры (через роли-константы), или админы (роль-константа)
        $userId = Service\Container::getInstance()->getContext()->getUserId();
        if(\CModule::IncludeModule("bizproc")){
            //кс-2
                $dir1 = str_replace("user_","",GlobalConst::getValue("Constant1709837236398")); 
                    $dir2 = str_replace("user_","",GlobalConst::getValue("Constant1709837193965"));
                    $glBuh = str_replace("user_","",GlobalConst::getValue("Constant1709837399990"));
                $buhEc = str_replace("user_","",GlobalConst::getValue("Constant1709837310294"));
                    $zamDirEcFin = str_replace("user_","",GlobalConst::getValue("Constant1709837367630"));
                $glInj = str_replace("user_","",GlobalConst::getValue("Constant1709837345998"));
            $injPto = str_replace("user_","",GlobalConst::getValue("Constant1712053287946"));

                $admins2 = GlobalConst::getValue("Constant1711462433918");
                $admins = [];
                foreach ($admins2 as $admin) {
                $admins[] = str_replace("user_","",$admin);
                }
            
            
                //замечания в согласовании  только директор 1, 2, только гл бух
            if(isset($fields["UF_CRM_3_1711132374160"]) && !in_array($userId, [$dir1,$dir2,$glBuh]) ){
                    $fields['UF_CRM_3_1711132374160']['ATTRIBUTES'][] = \CCrmFieldInfoAttr::Immutable;               
            }
            if(!in_array($userId, $admins)){
                $arFields = [
                    "UF_CRM_3_1709319836452" => [$buhEc], //субподрядная организация
                    "UF_CRM_3_1709319853364" => [$buhEc], //наименование объекта
                    "UF_CRM_3_1711708605142" => [$buhEc], //сумма                 
                    "UF_CRM_3_1711708531004" => [$buhEc], //комментарий
                    "UF_CRM_3_1709319932498" => [$buhEc], //дата акта
                    "UF_CRM_3_1709319908868" => [$buhEc], //номер акта в унф
                    "UF_CRM_3_1710079389817" => [$buhEc], //документ кс2
                    "UF_CRM_3_1710079558113" => [$buhEc], //поступления на объект
                    "UF_CRM_3_1710164767315" => [$buhEc], //возвраты с объекта
                    "UF_CRM_3_1711371018377" => [$buhEc], //комментарий к поступлениям
                    "UF_CRM_3_1711370249573" => [$buhEc, $zamDirEcFin],  //акт перерасхода финальный
                    "UF_CRM_3_1711370137171" => [$buhEc, $zamDirEcFin], //документы для подписания
                    "UF_CRM_3_1711975268246" => [$injPto], //согласовано или нет инж пто
                    "UF_CRM_3_1711975160397" => [$injPto], //коммент инж пто
                    "UF_CRM_3_1710079657466" => [$glInj]//акт перерасхода   
                ];           
                //AddMessage2Log($arFields);
                foreach ($arFields as $field => $arUsers) {
                    if(isset($fields[$field]) && !in_array($userId, $arUsers)){
                    $fields[$field]['ATTRIBUTES'][] = \CCrmFieldInfoAttr::Immutable;
                    }                                     
                }
            }           
        }   
        return $fields;
    } */
    public function getAddOperation(Item $item, Service\Context $context = null): Operation\Add        
    {

        $operation = parent::getAddOperation($item, $context);
        $operation->addAction(
            Operation::ACTION_BEFORE_SAVE,
            new class extends Operation\Action {
                public function process(Item $item): Result
                {

                    // $result = new Result();
                    // $data = $item->getData();
                    
                    // $result->addError(new Main\Error(json_encode($data)));
                    
                    // return $result;

                    /*ваша функция ДО добавления*/
                    return new Result();
                }
            }
        );

        return $operation->addAction(
            Operation::ACTION_AFTER_SAVE,
            new class extends Operation\Action {
                public function process(Item $item): Result
                {

                    /*ваша функция ПОСЛЕ добавления*/
                    return new Result();
                }
            }
        );
    }

    public function getUpdateOperation(Item $item, Service\Context $context = null): Operation\Update
    {

        $operation = parent::getUpdateOperation($item, $context);
        $operation->addAction(
            Operation::ACTION_BEFORE_SAVE,
            new class extends Operation\Action {

                public function process(Item $item): Result
                {
                    $result = new Result();

                    $employee_id = $item->get("UF_CRM_6_SPECIALIST");

                    // все старые значения полей
                    $actualFields = $item->getData(\Bitrix\Main\ORM\Objectify\Values::ACTUAL);
                    
                    // все измененные значения полей
                    $currentFields = $item->getData(\Bitrix\Main\ORM\Objectify\Values::CURRENT);
                    
                    if(!empty($employee_id) && $actualFields["UF_CRM_6_RATE"] != $currentFields["UF_CRM_6_RATE"])
                    {
                        // подтвержденные отчеты
                        $filter = [
                            "STATUS" => "1" 
                        ];

                        !empty($employee_id) ? $filter["EMPLOYEE_ID"] = $employee_id : null;
                        // !empty($project_id) ? $filter["EMPLOYEE_ID"] = $employee_id : null;

                        $query = Reports\TasksReportTable::GetList([
                            "filter" => $filter,
                        ]);

                        $count = $query->getSelectedRowsCount();

                        if($count > 0){
                            $message = "Данная ставка используется в $count";
                            $message .= $count > 1 ? " отчетах" : " отчете" ;
                            $result->addError(new Main\Error($message));
                        }  
                    }
                    
                    return $result;
                }
            }
        );
        return $operation->addAction(
            Operation::ACTION_AFTER_SAVE,
            new class extends Operation\Action {
                public function process(Item $item): Result
                {
                     /*ваша функция ПОСЛЕ изменения*/
                    return new Result();
                }
            }
        );
    }

    public function getDeleteOperation(Item $item, \Bitrix\Crm\Service\Context $context = null): Operation\Delete
    {

        $operation = parent::getDeleteOperation($item, $context);
        $operation->addAction(
            Operation::ACTION_BEFORE_SAVE,
            new class extends Operation\Action {
                public function process(Item $item): Result
                {
                    $result = new Result();

                    $employee_id = $item->get("UF_CRM_6_SPECIALIST");
                    
                    if(!empty($employee_id))
                    {
                        // подтвержденные отчеты
                        $filter = [
                            "STATUS" => "1" 
                        ];

                        !empty($employee_id) ? $filter["EMPLOYEE_ID"] = $employee_id : null;
                        // !empty($project_id) ? $filter["EMPLOYEE_ID"] = $employee_id : null;

                        $query = Reports\TasksReportTable::GetList([
                            "filter" => $filter,
                        ]);

                        $count = $query->getSelectedRowsCount();

                        if($count > 0){
                            $message = "Данная ставка используется в $count";
                            $message .= $count > 1 ? " отчетах" : " отчете" ;
                            $result->addError(new Main\Error($message));
                        }  
                    }
                    
                    return $result;
                }

            }
        );

        return $operation;
    }
       
}
