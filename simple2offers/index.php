<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('iblock');
CModule::IncludeModule('sale');
CModule::IncludeModule('catalog');

$obElement = new CIBlockElement();
$obCatalog = new CCatalogProduct();
$obPrice   = new CPrice();

/*

	$intSKUIBlock ID инфоблока предложений (должен быть торговым каталогом)
	$intProductIBlock ID инфоблока товаров
*/
$intSKUIBlock     = 22;
$intProductIBlock = 15;

$arFilter = [
	'IBLOCK_ID' => $intProductIBlock,
	// 'ID' => 2877,
	'TYPE' => 1, // Detect product type (is simple) (don`t work)
];
$arrIblocks = CCatalogProduct::GetList(null, $arFilter);

while ($arProduct = $arrIblocks->GetNext())
{
	$isSimple = !count(CCatalogSKU::getOffersList($arProduct['ID']));

	if (!$isSimple)
		continue;
	
	echo "Procuct ID: {$arProduct['ID']}; Name: {$arProduct['ELEMENT_NAME']}\r\n";
	
	$arPrice = CPrice::GetBasePrice($arProduct['ID']);
	
	if (empty($arPrice['PRICE']))
	{
		die('Cant find price by product ID: ' . $arProduct['ID']);
	}
	
	$productUpdated = CCatalogProduct::Update($arProduct['ID'], ['TYPE' => 3]);
	
	// CML2_LINK - свойство привязки торгового предложения к товарам
	$arProp['CML2_LINK'] = $arProduct['ID'];
	$arFields = array(
		'NAME' => $arProduct['~ELEMENT_NAME'],
		'IBLOCK_ID' => $intSKUIBlock,
		'ACTIVE' => 'Y',
		'PROPERTY_VALUES' => $arProp,
		'QUANTITY' => $arProduct['QUANTITY'],
	);
	
	// Добавляем как элемент инфоблока
	$intOfferID = $obElement->Add($arFields);
	
	if (!$intOfferID)
		die('Cant add offer ' . $obPrice->LAST_ERROR . PHP_EOL . print_r($arFields, true) . PHP_EOL);
	
	// Обновляем как элемент каталога
	$arFields['ID'] = $intOfferID;
	$updated = $obCatalog->Add($arFields);
	
	$arFieldsPrice = array(
		"PRODUCT_ID" => $intOfferID, // ID-шник только что добавленного ТП
		"CATALOG_GROUP_ID" => 1, // Базовая цена
		"PRICE" => $arPrice['PRICE'], // Тут ставим цену из базы или цену родительского товара
		"CURRENCY" => "RUB",
	);
	
	$intPriceID = $obPrice->Add($arFieldsPrice);
	
	if (!$intPriceID)
		die('Cant add price: ' . $obPrice->LAST_ERROR . PHP_EOL . print_r($arFieldsPrice, true) . PHP_EOL);
	
	
	echo ' Ok'.PHP_EOL.PHP_EOL;
}
