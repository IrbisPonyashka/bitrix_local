<?php

namespace IrbisPonyashka\classes\crm;

use \Bitrix\Main,
   \Bitrix\Crm\Service;

Main\Loader::requireModule('crm');

class Container extends Service\Container
{
   public function getFactory(int $entityTypeId): ?Service\Factory
   {
        if ( $entityTypeId == 184 ) { // stavki_spetsialistov

            // Сгенерируем название сервиса
            $identifier = static::getIdentifierByClassName(static::$dynamicFactoriesClassName, [$entityTypeId]);
            // если такой есть то вернем с ним
            if ( Main\DI\ServiceLocator::getInstance()->has($identifier) ){
                return Main\DI\ServiceLocator::getInstance()->get($identifier);
            }

            // Объекта нет. Получим 'объект смарт-процесса'
            $type = $this->getTypeByEntityTypeId($entityTypeId);
            
            // Не получилось, смарт-процесс удален            
            if ( !$type ){
                return null;
            }
            
            $factory = new Factory($type); // подцепляем нашу фабрику 
                Main\DI\ServiceLocator::getInstance()->addInstance(
                $identifier,
                $factory
            );
            
            return $factory;
        }

        // Если тип не наш - передаем в родительский метод
        return parent::getFactory($entityTypeId);
    }

}