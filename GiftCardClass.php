<?php
/*
 * 2015 Loulou66 and Eolia
 * giftcard module for Prestashop
 * All Rights Reserved
 */
class GiftCardClass extends ObjectModel {
	public $id_giftcard;
	public $id_shop;
	public $image_path;
	public $id_product;
	public $id_category;
	public $display_duration;
	public $duration;
	public $validity;
	public $display_code;
	public $pos_code_x;
	public $pos_code_y;
	public $colorcode;
	public $display_text;
	public $colortext;
	public $pos_text_x;
	public $pos_text_y;
	public $text_size;
	public $display_shadow;
	public $colorshadow;
	public $tax;	
	public $partial_use;
	public $highlight;
	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'giftcard',
		'primary' => 'id_giftcard',
		'multilang' => false,
		'fields' => array(
			'id_shop'		=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
			'image_path'	=>	array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32),
			'id_product'	=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt' ),
			'id_category'	=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt' ),
			'display_duration'=>	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'duration'		=>	array('type' => self::TYPE_INT, 'validate' => 'isInt'),
			'validity'		=>	array('type' => self::TYPE_DATE),
			'display_code'	=>	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'colorcode'		=>	array('type' => self::TYPE_STRING),
			'pos_code_x'	=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt' ),
			'pos_code_y'	=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt' ),
			'display_text'	=>	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'colortext'		=>	array('type' => self::TYPE_STRING),
			'pos_text_x'	=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt' ),
			'pos_text_y'	=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt' ),
			'text_size'		=>	array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt' ),
			'display_shadow'=>	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'colorshadow'	=>	array('type' => self::TYPE_STRING),
			'tax'			=>	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'partial_use'	=>	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'highlight'		=>	array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),			
		)
	);

	static public function getByIdShop($id_shop)
	{
		$id = Db::getInstance()->getvalue('SELECT `id_giftcard` FROM `'._DB_PREFIX_.'giftcard` WHERE `id_shop` ='.(int)$id_shop);
		return new GiftCardClass($id);
	}

	static public function getByIdProduct($id_shop, $id_product)
	{
		$id = Db::getInstance()->getvalue('SELECT `id_giftcard` FROM `'._DB_PREFIX_.'giftcard` WHERE `id_shop` ='.(int)$id_shop.' AND `id_product` = '.$id_product);		
		return new GiftCardClass($id);		
	}
	
	public function copyFromPost()
	{
		/* Classical fields */
		foreach ($_POST as $key => $value)
		{
			if (key_exists($key, $this) && $key != 'id_'.$this->table)
				$this->{$key} = $value;
		}		
	}
}
