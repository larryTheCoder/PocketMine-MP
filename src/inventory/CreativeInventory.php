<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeContentEntry;
use pocketmine\utils\SingletonTrait;
use Webmozart\PathUtil\Path;
use function file_get_contents;
use function json_decode;

final class CreativeInventory{
	use SingletonTrait;

	/** @var CreativeContentEntry[]|null */
	private static $creativeContents = null;

	/** @var Item[] */
	private $creative = [];

	private function __construct(){
		$creativeItems = json_decode(file_get_contents(Path::join(\pocketmine\RESOURCE_PATH, "vanilla", "creativeitems.json")), true);

		foreach($creativeItems as $data){
			$item = Item::jsonDeserialize($data);
			if($item->getName() === "Unknown"){
				continue;
			}
			$this->add($item);
		}
	}

	/**
	 * Removes all previously added items from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function clear() : void{
		self::$creativeContents = null;

		$this->creative = [];
	}

	/**
	 * @return Item[]
	 */
	public function getAll() : array{
		return $this->creative;
	}

	public function getItem(int $index) : ?Item{
		return $this->creative[$index] ?? null;
	}

	public function getItemIndex(Item $item) : int{
		foreach($this->creative as $i => $d){
			if($item->equals($d, !($item instanceof Durable))){
				return $i;
			}
		}

		return -1;
	}

	/**
	 * @param bool $isSpectator
	 * @return CreativeContentEntry[]
	 * @internal
	 */
	public function getCreativeContents(bool $isSpectator) : array{
		if(self::$creativeContents === null){
			$typeConverter = TypeConverter::getInstance();

			$nextEntryId = 1;
			self::$creativeContents = array_map(function(Item $item) use ($typeConverter, &$nextEntryId): CreativeContentEntry{
				return new CreativeContentEntry($nextEntryId++, $typeConverter->coreItemStackToNet($item));
			}, $this->getAll());
		}

		return $isSpectator ? [] : self::$creativeContents;
	}

	/**
	 * Adds an item to the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function add(Item $item) : void{
		self::$creativeContents = null;

		$this->creative[] = clone $item;
	}

	/**
	 * Removes an item from the creative menu.
	 * Note: Players who are already online when this is called will not see this change.
	 */
	public function remove(Item $item) : void{
		$index = $this->getItemIndex($item);
		if($index !== -1){
			self::$creativeContents = null;

			unset($this->creative[$index]);
		}
	}

	public function contains(Item $item) : bool{
		return $this->getItemIndex($item) !== -1;
	}
}
