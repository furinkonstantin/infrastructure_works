<?php

    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
    use Bitrix\Main\Loader;
    use \Bitrix\Sale\Basket;
    use \Bitrix\Main\Mail\Event;
    use \Bitrix\Main\Localization\Loc;
    
    Loader::includeModule("sale");
    Loc::loadMessages(__FILE__);
    $from = date("d.m.Y", time() - (24 * 3600 * 3 * 7 * 10));
    $to = date("d.m.Y", time());
	$obBasket = Basket::getList(
        array('filter' => 
            array(
                '>=DATE_INSERT' => $from,
                '<=DATE_INSERT' => $to,
                'ORDER_ID' => false
            )
        )
    );
    $basket = array();
    $userIds = array();
    while($bItem = $obBasket->Fetch()){
        $userIds[] = $bItem["FUSER_ID"];
        $basket[$bItem["FUSER_ID"]][] = $bItem;
    }
    $userIds = array_unique($userIds);
    
    $users = array();
    $dbUser = \Bitrix\Main\UserTable::getList(
        array('filter' => 
            array(
                'ID' => $userIds
            )
        )
    );
    while ($arUser = $dbUser->fetch()) {
        $users[$arUser["ID"]] = $arUser;
    }
    
    foreach($basket as $userId => $arItems) {
        $user = $users[$userId];
        if ($user) {
            $productList = "";
            foreach($arItems as $arItem) {
                $productList .= Loc::getMessage("BASKET_NAME") . $arItem["NAME"] . " " . Loc::getMessage("BASKET_PRICE") . $arItem["PRICE"] . " " . Loc::getMessage("BASKET_QUANTITY") . $arItem["QUANTITY"] . "<br/>";
            }
            
            $mailFields = array(
                "LOGIN" => $user["LOGIN"],
                "USER_NAME" => $user["LAST_NAME"] . " " . $user["NAME"] . " " . $user["SECOND_NAME"],
                "PRODUCT_LIST" => $productList
            );
            
            Event::Send(array(
                "EVENT_NAME" => "OLD_PRODUCTS_IN_BASKET",
                "LID" => "s1",
                "C_FIELDS" => $mailFields
            ));
        }
    }