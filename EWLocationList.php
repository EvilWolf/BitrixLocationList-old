<?php
// Класс для работы с локациями
// @todo - причесать
class EWLocationList
{
	private static $_instance = null;

	private $loaded = false;
	private $arRegions = [];
	private $arCitys = [];
	private $arCityByRegion = [];

	private function __construct()
	{
		// приватный конструктор ограничивает реализацию getInstance ()
	}
	protected function __clone()
	{
		 // ограничивает клонирование объекта
	}
	static public function getInstance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	private function getLocationList($arSort = ["CITY_NAME" => "ASC", "REGION_NAME" => "ASC"]) {
		if (!CModule::IncludeModule('sale')) {
			return false;
		}

		if ($this->loaded) {
			return true;
		}

		$obCache = new CPHPCache();
		if ($obCache->InitCache(36000000, serialize($arSort).'CityLocationsCache_new', 'CityLocations'))
		{ 	// Если кэш валиден
			$vars = $obCache->GetVars();

			$arRegions = $vars['REGIONS'];
			$arCitys = $vars['CITYS'];
			$arCityByRegion = $vars['CITY_BY_REGION'];
		}
		elseif ($obCache->StartDataCache())
		{ 	// Если кэш невалиден
			// Получаем данные из CSaleLocation
			$rsLocations = CSaleLocation::GetList(
				$arSort, // arOrder
				["COUNTRY_ID" => 4, "COUNTRY_LID" => "ru", "REGION_LID" => "ru", "CITY_LID" => "ru"], // arFilter
				false, // arGroupBy
				false, // arNavStartParams
				[] // arSelect
			);

			$arRegions = [];
			$arCityByRegion = [];
			$arCitys = [];

			while ($arLocation = $rsLocations->GetNext()) {
				// Если есть регион в выборке, но нет региона в нашем списке регионов
				if (!empty($arLocation['REGION_ID']) AND empty($arRegions[$arLocation['REGION_ID']])) {
					$arRegions[$arLocation['REGION_ID']] = $arLocation['REGION_NAME'];
				}

				// Если региона нет в списке ar[регион][города]
				if (!empty($arLocation['REGION_ID']) AND empty($arCityByRegion[$arLocation['REGION_ID']])) {
					$arCityByRegion[$arLocation['REGION_ID']] = [];
				}

				// Если пришёл город
				if (!empty($arLocation['CITY_ID'])) {
					// var_dump($arLocation);
					$arCityByRegion[$arLocation['REGION_ID']][$arLocation['CITY_ID']] = $arLocation['CITY_NAME'];
					$arCitys[$arLocation['CITY_ID']] = $arLocation['CITY_NAME'];
				}
			}

			// Сохраняем переменные в кэш.
			$obCache->EndDataCache([
				'REGIONS' => $arRegions,
				'CITYS' => $arCitys,
				'CITY_BY_REGION' => $arCityByRegion,
			]);
		}

		$this->arRegions = $arRegions;
		$this->arCitys = $arCitys;
		$this->arCityByRegion = $arCityByRegion;
		$this->loaded = true;
		return true;
	}

	public function getCityList() {
		if ($this->getLocationList(["CITY_NAME" => "ASC"])) {
			return $this->arCitys;
		}
		return [];
	}

	public function getCityWithRegions() {
		if ($this->getLocationList(["CITY_NAME" => "ASC"])) {
			return $this->arCityByRegion;
		}
		return [];
	}

	public function getRegionList() {
		if ($this->getLocationList(["REGION_NAME" => "ASC"])) {
			return $this->arRegions;
		}
		return [];
	}
}
