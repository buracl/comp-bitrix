<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Highloadblock\HighloadBlockTable;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Application;

/**
 *
 * Для обновления кеша в init.php добавляем event_handler
 *
 *
    $eventManager->addEventHandler('', 'TESTOnAfterAdd', 'clearTESTCache');
    $eventManager->addEventHandler('', 'TESTOnAfterUpdate', 'clearTESTCache');
    $eventManager->addEventHandler('', 'TESTOnAfterDelete', 'clearTESTCache');

    function clearTESTCache($event)
    {
        $taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
        $taggedCache->clearByTag("highloadblock_id_9");
    }
 *
 */


class TestComponent extends CBitrixComponent
{

    function executeComponent()
    {
        \Bitrix\Main\Loader::includeModule("highloadblock");

        $grid_options = new Bitrix\Main\Grid\Options('user_list');
        $nav = new Bitrix\Main\UI\PageNavigation('user_list');

        $sort = $grid_options->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
        $nav_params = $grid_options->GetNavParams();

        $nav->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        $hblockId = 9; // ID хайлоуда
        $cachePath = '/testcache';
        $tag = 'highloadblock_id_9';
        $cacheTtl = 3600;
        $cacheKey = md5(serialize([
            "tag" => $tag,
            "nav" => $nav_params["nPageSize"],
            "sort" => $sort,
            "page" => $nav->getCurrentPage()
        ]));


        $cache = Cache::createInstance();
        $taggedCache = Application::getInstance()->getTaggedCache();

        try {
            if ($cache->initCache($cacheTtl, $cacheKey, $cachePath)) {

                $data = $cache->getVars();
                $this->arResult['list'] = $data['list'];
                $nav->setRecordCount($data['count']);

            } elseif ($cache->startDataCache()) {
                $taggedCache->startTagCache($cachePath);
                $taggedCache->registerTag($tag);

                $cacheInvalid = false;
                if ($cacheInvalid) {
                    $taggedCache->abortTagCache();
                    $cache->abortDataCache();
                }

                $taggedCache->endTagCache();


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
                        'ttl' => $cacheTtl,
                        'cache_joins' => true
                    ),
                ));
                $count = $rsData->getCount();
                $nav->setRecordCount($count);

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

                $cache->endDataCache(
                    [
                        'count' => $count,
                        "list" => $this->arResult['list'],
                    ]
                );
            }

            $this->arResult['nav'] = $nav;

            $this->IncludeComponentTemplate();
        } catch (Exception $e) {
            $this->IncludeComponentTemplate('error');
        }
    }
}
