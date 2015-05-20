<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class GiftCardClass extends ObjectModel
{
	/** @var integer giftcard id*/
	public $id_giftcard;
	
	/** @var integer giftcard id shop*/
	public $id_shop;

	/** @var integer info image_path*/
	public $image_path;

	/** @var integer info id_product*/
	public $id_product;
	
	/** @var integer info id_category*/
	public $id_category;

	/** @var string display_duration*/
	public $display_duration;

	/** @var string duration*/
	public $duration;
	
	/** @var string validity*/
	public $validity;

	/** @var string display_code*/
	public $display_code;

	/** @var string pos_code_x*/
	public $pos_code_x;

	/** @var string pos_code_y*/
	public $pos_code_y;

	/** @var string colorcode*/
	public $colorcode;

	/** @var string display_text*/
	public $display_text;

	/** @var string colortext*/
	public $colortext;

	/** @var string pos_text_x*/
	public $pos_text_x;

	/** @var string pos_text_y*/
	public $pos_text_y;

	/** @var string text_size*/
	public $text_size;

	/** @var string display_shadow*/
	public $display_shadow;

	/** @var string colorshadow*/
	public $colorshadow;

	/** @var string partial_use*/
	public $tax;	
	
	/** @var string partial_use*/
	public $partial_use;	

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
