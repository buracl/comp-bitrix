<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Highloadblock\HighloadBlockTable;

class TestComponent extends CBitrixComponent
{
    function executeComponent()
    {
        \Bitrix\Main\Loader::includeModule("highloadblock");

        $hblockId = 9; // ID хайлоуда

        try {

            $grid_options = new Bitrix\Main\Grid\Options('user_list');
            $sort = $grid_options->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);

            $nav_params = $grid_options->GetNavParams();

            $nav = new Bitrix\Main\UI\PageNavigation('user_list');
            $nav->allowAllRecords(true)
                ->setPageSize($nav_params['nPageSize'])
                ->initFromUri();

            $this->arResult['nav'] = $nav;

            $arHLBlock = HighloadBlockTable::getById($hblockId)->fetch();
            $obEntity = HighloadBlockTable::compileEntity($arHLBlock);
            $strEntityDataClass = $obEntity->getDataClass();

            $filter = [];
            if ($this->arParams['SHOW_ONLY_ACTIVE'])
                $filter['UF_ACTIVE'] = true;

            $rsData = $strEntityDataClass::getList(array(
                'select' => array('*'),
                'order' => $sort["sort"],
                'filter' => $filter,
                'limit' => $nav->getLimit(),
                'offset' => $nav->getOffset(),
                'count_total' => true,
                'cache' => array(
                    'ttl' => 3600,
                    'cache_joins' => true
                ),
            ));
            $nav->setRecordCount($rsData->getCount());

            $this->arResult['list'] = [];
            while ($arItem = $rsData->Fetch()) {

                $active  = "Нет";
                if ($arItem['UF_ACTIVE'] == 1)
                    $active  = "Да";

                $this->arResult['list'][] = [
                    'data' => [
                        "ID" => $arItem['ID'],
                        "UF_POST" => $arItem['UF_POST'],
                        "UF_ACTIVE" => $active
                    ]
                ];
            }

            $this->IncludeComponentTemplate();
        } catch (Exception $e) {
            $this->IncludeComponentTemplate('error');
        }
    }
}
