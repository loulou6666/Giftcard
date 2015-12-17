<?php
/*
 * 2015 Loulou66 and Eolia
 * giftcard module for Prestashop
 * All Rights Reserved
 */
if (!defined('_PS_VERSION_'))
    exit;
include_once _PS_MODULE_DIR_ . 'giftcard/GiftCardClass.php';

class giftcard extends Module {
    public function __construct() {
        $this->name = 'giftcard';
        $this->tab = 'front_office_features';
        $this->version = '3.2.7';
        $this->author = 'Loulou66 and Eolia';
        $this->need_instance = 0;
        $this->_path = dirname(__FILE__);
        if (version_compare(_PS_VERSION_, '1.6', '>'))
            $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Gift Card');
        $this->description = $this->l('Create Gift Card Product');
        $this->confirmUninstall = $this->l('WARNING: if you have some products created by this Gift Card module, you must delete them first.');
        $this->context = Context::getContext();
        $this->addAsTrusted();
    }

    public function install() {	
        if (!parent::install() ||
                !$this->registerHook('actionPaymentConfirmation') ||
                !$this->installDB() ||
                !$this->instalMailsLanguage())
            return false;
        return true;
    }

    public function uninstall() {	
        $datas = $this->checkCards();
        if ($datas) {
            $this->context->controller->errors[] = $this->l('Unable to uninstall this module. You must delete all Gift-Card products created before uninstalling it');
            return false;
        }
        if (!parent::uninstall() || !$this->dropTables())
            return false;
        return true;
    }

    public function installDb() {
        if (!Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'giftcard` (
            `id_giftcard` int(10) unsigned NOT NULL auto_increment,
            `image_path` varchar(255) NOT NULL,
            `id_product` int(10) unsigned NOT NULL,
            `id_category` int(10) unsigned NOT NULL,
            `display_duration` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 1,
            `duration` varchar(255) NOT NULL,
            `validity` date NOT NULL,
            `display_code` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 1,
            `colorcode` varchar(255) NOT NULL,
            `pos_code_x` int(10) unsigned NOT NULL,
            `pos_code_y` int(10) unsigned NOT NULL,
            `display_text` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 1,
            `colortext` varchar(255) NOT NULL,
            `pos_text_x` int(10) unsigned NOT NULL,
            `pos_text_y` int(10) unsigned NOT NULL,
            `text_size`  int(10) unsigned NOT NULL,  
            `display_shadow` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 1,
            `colorshadow` varchar(255) NOT NULL,
            `tax` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 0,
            `partial_use` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 0,
            `highlight` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 0,
            `id_shop` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id_giftcard`))
            ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'))
            return false;
        return true;
    }

    public static function dropTables() {
        if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'giftcard`'))
            return false;
        return true;
    }

    public function instalMailsLanguage() {	
        $mails_dir = _PS_MODULE_DIR_.'giftcard/mails/';
        $folder_en = _PS_MODULE_DIR_.'giftcard/mails/en';
        foreach (Language::getLanguages(true) as $lang) {
            $folder_dest = $mails_dir.$lang['iso_code'];
            if (!file_exists($folder_dest) && !is_dir($folder_dest)) {
                mkdir($folder_dest);
                if ($ori = opendir($folder_en)) {           
                    while (($files = readdir($ori)) !== false) { 
                        if($files != '..'  && $files != '.')
                            copy($folder_en.'/'.$files,  $folder_dest.'/'.$files); 
                    }
                }
                closedir($folder_en);
            }          
        }
        return true;
    }

    public function addAsTrusted() {
        if (defined('self::CACHE_FILE_TRUSTED_MODULES_LIST') == true) {
            if (isset($this->context->controller->controller_name) &&
                    $this->context->controller->controller_name == 'AdminModules') {
                $sxe = new SimpleXMLElement('<theme/>');
                $modules = $sxe->addChild('modules');
                $module = $modules->addChild('module');
                $module->addAttribute('action', 'install');
                $module->addAttribute('name', $this->name);
                $trusted = $sxe->saveXML();
                file_put_contents(_PS_ROOT_DIR_ . '/config/xml/themes/' . $this->name . '.xml', $trusted);
                if(is_file(_PS_ROOT_DIR_ . Module::CACHE_FILE_UNTRUSTED_MODULES_LIST))
                    Tools::deleteFile(_PS_ROOT_DIR_ . Module::CACHE_FILE_UNTRUSTED_MODULES_LIST);
            }
        }
    }

    private function checkCards() {    
        // First: clean the giftcard table
        // A product has could be uninstalled directly from the BO...
        Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'giftcard` 
            WHERE `id_product` NOT IN (
            SELECT `id_product` 
            FROM `' . _DB_PREFIX_ . 'product_shop`)');        
        // Then check remaining items
        return Db::getInstance()->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'giftcard`
            WHERE `id_shop`= ' . (int) $this->context->shop->id . '
            GROUP BY id_category');
    }
    
    private function checkAwaitingOrder($id_product) {    
        /* Verify if one order at least is awaiting payment */
        $states = array(Configuration::get('PS_OS_WS_PAYMENT'),
            Configuration::get('PS_OS_PAYMENT'),
            Configuration::get('PS_OS_CANCELED')
        );
        return Db::getInstance()->getValue('
            SELECT od.`id_order`
            FROM `' . _DB_PREFIX_ . 'order_detail` od
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON(o.`id_order` = od.`id_order`)
            WHERE o.`id_shop` = ' . (int) $this->context->shop->id . '
            AND o.`current_state` NOT IN  (' . implode(',', $states) . ')
            AND od.`product_id` = ' . $id_product);            
    }

    private function deleteProductGiftCard($id) {
        $id_lang = (int) $this->context->language->id;
        $link_rewrite = Tools::link_rewrite($this->l('Gift-Card'));
        $id_shop = (int) $this->context->shop->id;
        if (!$id)
            return $this->_html .= $this->displayError($this->l('Unable to suppress product'));
        $giftcard = new GiftCardClass($id);
        $id_product = $giftcard->id_product;
        if (!$id_product)
            return $this->_html .= $this->displayError($this->l('This Product has already been removed'));
        /* Verify if one order at least is awaiting payment */
        $states = array(Configuration::get('PS_OS_WS_PAYMENT'),
            Configuration::get('PS_OS_PAYMENT'),
            Configuration::get('PS_OS_CANCELED')
        );
        if ($awaiting_order = $this->checkAwaitingOrder($id_product))
            return $this->_html .= $this->displayError($this->l('This product could not be deleted, an associated order is awaiting payment') . ': <a href="index.php?controller=AdminOrders&id_order=' . $awaiting_order . '&vieworder&token=' . Tools::getAdminTokenLite('AdminOrders') . '">id_order n° ' . $awaiting_order . '</a>');
        /* verify is specific category exist */
        $category = $giftcard->id_category;
        if (!$category)
            return $this->_html .= $this->displayError($this->l('Database Error: No category found for this product'));
        $id_customization_field = Db::getInstance()->getValue('
            SELECT  cf.`id_customization_field`
            FROM `' . _DB_PREFIX_ . 'customization_field` cf        
            WHERE cf.`id_product` = "' . $id_product . '" ');
        /* Check existing datas out of this product */
        $datas = Db::getInstance()->executeS('
            SELECT  *
            FROM `' . _DB_PREFIX_ . 'giftcard`
            WHERE `id_shop` = ' . (int) $this->context->shop->id . '
            AND `id_product` != ' . $id_product . '
            GROUP BY id_category'
        );
        if (!$datas) {
            // Delete specific category if giftcard is empty
            Db::getInstance()->delete('category', ' `id_category` = "' . $category . '" ');
            Db::getInstance()->delete('category_lang', ' `id_category` = "' . $category . '" ');
            Db::getInstance()->delete('category_group', ' `id_category` = "' . $category . '" ');
            Db::getInstance()->delete('category_product', ' `id_category` = "' . $category . '" ');
            Db::getInstance()->delete('category_shop', '`id_category` = "' . $category . '" ');
            Category::regenerateEntireNtree();
        }
        $product = new Product($id_product);
        if (!$product->delete())
            return $this->_html .= $this->displayError($this->l('Unable to delete this product'));
        Db::getInstance()->delete('customization_field_lang', '`id_customization_field` = "' . $id_customization_field . '"');
        Db::getInstance()->delete('customization_field', '`id_product` = "' . $id_product . '"');
        Db::getInstance()->delete('product_carrier', '`id_product` = "' . $id_product . '"');
        Db::getInstance()->delete('stock_available', '`id_product` = "' . $id_product . '"');
        $name_attribute_group = $this->l('Value');
        $id_attribute_group = Db::getInstance()->getValue('
            SELECT  agl.`id_attribute_group`
            FROM `' . _DB_PREFIX_ . 'attribute_group_lang` agl
            WHERE agl.`name` = "' . $name_attribute_group . '"
            ');
        $attribute_group = new AttributeGroup($id_attribute_group);
        if (!$attribute_group->delete())
            return $this->_html .= $this->displayError($this->l('Unable to delete attributes of this product'));
        $id_images = Db::getInstance()->executeS('
            SELECT  i.`id_image`
            FROM `' . _DB_PREFIX_ . 'image` i
            WHERE i.`id_product` = ' . (int) $id_product . ' ');
        foreach ($id_images as $key => $id_image) {
            $image = new Image($id_image['id_image']);
            if (!$image->delete())
                return $this->_html .= $this->displayError($this->l('Unable to delete images of this product'));
        }
        /* Clean the temp directory if it was not */
        array_map('unlink', glob(_PS_TMP_IMG_DIR_ . '*' . (int) $this->context->shop->id . '.jpg'));
        Db::getInstance()->delete('giftcard', ' `id_product` = "' . (int) $id_product . '" ');
        return $this->_html .= $this->displayConfirmation($this->l('Gift card successfully deleted'));
    }

    public function cardExist($name) {
        $id_card = Db::getInstance()->getValue('
            SELECT  pl.`id_product`
            FROM `' . _DB_PREFIX_ . 'product_lang` pl
            WHERE pl.`name` = "' . $name . '"
            AND pl.`id_lang`= ' . (int) $this->context->language->id . '
            AND pl.`id_shop`= ' . (int) $this->context->shop->id);
        if ($id_card)
            return $id_card;
        return false;
    }

    private function getTaxRate() {
        $tax_rate = Db::getInstance()->getValue('
            SELECT  t.`rate`
            FROM `' . _DB_PREFIX_ . 'tax` t
            LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON(`id_tax_rules_group` = '.(int)Product::getIdTaxRulesGroupMostUsed().')
            WHERE t.`active` = 1
            AND t.`deleted`= 0
            AND tr.`id_tax` = t.`id_tax`');
        return $tax_rate;
    }
    
    private function createProductGiftCard($name, $value, $image_name, $display_duration ,$duration, $validity, $tax, $partial_use, $highlight, $display_code, $pos_code_x, $pos_code_y, $colorcode, $display_text, $pos_text_x, $pos_text_y, $text_size, $colortext, $display_shadow, $colorshadow) {
        $id_lang = (int) $this->context->language->id;
        $link_rewrite = Tools::link_rewrite($this->l('Gift-Card'));
        $id_shop = (int) $this->context->shop->id;
        $nameImport = _PS_MODULE_DIR_ . $this->name . '/img/models/';
        if (!$image_name)
            $this->_error[] = $this->l('You must choose a model to this card');
        $image_path = $nameImport . $image_name;
        if ($this->cardExist($name))
            $this->_error[] = $this->l('This product already exists') . ': ' . $name;
        else {
            $category_name = $this->displayName;
            /* Check if existing giftcard */
            $id_category = Db::getInstance()->getValue('
                SELECT  cl.`id_category`
                FROM `' . _DB_PREFIX_ . 'category_lang` cl
                WHERE cl.`name`= "' . $category_name . '"                
                ');
            if ($id_category) {			
                $category = new Category((int) $id_category);
                $id_category = $category->id;
            } 
			else {
                /* create category 'Gift Card' for the product */
                $category = new Category();
                foreach (Language::getLanguages(true) as $lang) {
                    $category->name[$lang['id_lang']] = $category_name;
                    $category->link_rewrite[$lang['id_lang']] = $link_rewrite;
                }
                $category->id_parent = Configuration::get('PS_HOME_CATEGORY');
                $category->add();
                $id_category = $category->id;
            }
            $partial = ($partial_use == 0) ? $this->l('This voucher must be used in one time') : $this->l('This voucher can be used several times (as long as the balance is positive)');
            $usetax = $tax == 0 ? $this->l('WT') : $this->l('ATI');
            /* create the new product 'Gift Card' */
            $product = new Product();
            $product->id_category_default = $id_category;
            foreach (Language::getLanguages(false) as $lang) {
                $product->name[$lang['id_lang']] = $name;
                $product->link_rewrite[$lang['id_lang']] = $link_rewrite;
            }            
            $product->wholesale_price = 0;
            $product->reference = 'GC-' . round($value);
            $product->active = 1;
            $product->id_tax_rules_group = (int)Product::getIdTaxRulesGroupMostUsed();
            if ($display_duration)
                $desc_validity =  $this->l('Date of mailing of the card by Email').' +'.$duration.' '.$this->l('Month');
            else
                $desc_validity = Tools::displayDate($validity);           
            $product->description_short = $partial.'.<br/>'.$this->l('The amount value will be use only in').' '.$usetax.'.<br/>'.$this->l('Available until').': '.$desc_validity;
            $product->manufacturer = $this->context->shop->name;
            $product->is_virtual = true;
            $product->out_of_stock = 1;
            $product->supplier = $this->context->shop->name;
            $product->add();
            $id_product = $product->id;
             // set price without taxes in DB if taxes enabled
            $price = ($tax == 1) ? (100 * (float)$value) / (100 + $this->getTaxRate()) : (float)$value;
            $product->price = round((float)$price,6);
            $product->update();
            StockAvailable::setQuantity($id_product, 0, 100, $id_shop);
            StockAvailable::setProductOutOfStock($id_product, $out_of_stock = 1); /* compatibility with module mail-alert */
            // Label insertion
            $names = array($this->l('Personalize your card'), $this->l('Your firstname (display in the mail)'), $this->l('Your lastname (display in the mail)'), $this->l('If you want send this card to a friend enter his Email address'));
            $this->addCustom($product, $names);
            Db::getInstance()->insert('category_product', array(
                'id_category' => $id_category,
                'id_product' => $id_product,
                'position' => 0
            ));
            /* add images for 'Gift Card' product */
            $image = new Image();
            $image->id_product = (int) $id_product;
            $image->cover = 1;
            $image->add();
            $currency = new Currency($this->context->currency->id);
            $card_price = Tools::displayPrice((float)$value, $currency, false, $this->context);
            /* generate images */
            $this->generateImage($id_product, $image->id, $image_path, $card_price, $display_code, $pos_code_x, $pos_code_y, $colorcode, $display_text, $pos_text_x, $pos_text_y, $text_size, $colortext, $display_shadow, $colorshadow);
            Db::getInstance()->insert('giftcard', array(
                'image_path' => $image_name,
                'id_product' => (int) $id_product,
                'id_category' => (int) $id_category,
                'display_duration' => $display_duration,
                'duration' => $duration,
                'validity' => $validity,
                'display_code' => $display_code,
                'pos_code_x' => $pos_code_x,
                'pos_code_y' => $pos_code_y, 
                'colorcode' => $colorcode,
                'display_text' => $display_text,
                'pos_text_x' =>  $pos_text_x,
                'pos_text_y' =>  $pos_text_y,
                'text_size' =>   $text_size,
                'colortext' => $colortext,
                'display_shadow' => $display_shadow,
                'colorshadow' => $colorshadow,
                'tax' => $tax,
                'partial_use' => $partial_use,
                'highlight' => $highlight,
                'id_shop' => $this->context->shop->id
            ));
            return true;
        }
    }

    public function generateImage($id_product, $id_image, $image_path, $value, $display_code, $pos_code_x, $pos_code_y, $colorcode, $display_text, $pos_text_x, $pos_text_y, $text_size, $colortext, $display_shadow, $colorshadow) {
        $image = new Image($id_image);
        $tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS');
        $codecolor = $this->hex2rgb($colorcode);
        $textcolor = $this->hex2rgb($colortext);
        $shadowcolor = $this->hex2rgb($colorshadow);            
        $code = $this->GenerateCardCode(8);
        $price = $value;
        $imageView = imagecreatefrompng($image_path);
        $colorcode2 = imagecolorallocate($imageView, $codecolor[0], $codecolor[1], $codecolor[2]);
        $colortext2 = imagecolorallocate($imageView, $textcolor[0], $textcolor[1], $textcolor[2]);
        $colorshadow2 = imagecolorallocate($imageView, $shadowcolor[0], $shadowcolor[1], $shadowcolor[2]);
        $font1 = _PS_MODULE_DIR_ . 'giftcard/fonts/times.ttf';
        $font2 = _PS_MODULE_DIR_ . 'giftcard/fonts/code.ttf';
        $size_code = 40; //fixed       
        $origin_code = $this->getOrigin($font2, $code, $size_code,  $pos_code_x, $pos_code_y);
        $origin_text = $this->getOrigin($font1, $price, $text_size, $pos_text_x, $pos_text_y);        
        if($display_code)
            imagettftext($imageView, $size_code, 0, $origin_code['x'], $origin_code['y'], $colorcode2, $font2, $code);
        if($display_shadow)
            if($colorshadow2 != $colortext2)
                imagettftext($imageView, $text_size, 0,   $origin_text['x']+2, $origin_text['y']+2, $colorshadow2, $font1, $price);
        if($display_text)                      
            imagettftext($imageView, $text_size, 0, $origin_text['x'], $origin_text['y'], $colortext2, $font1, $price);
        imagealphablending($imageView, false);
        imagesavealpha($imageView, true);
        imagepng($imageView, $tmpName);
        imagedestroy($imageView);        
        $new_path = $image->getPathForCreation();        
        $imagesTypes = ImageType::getImagesTypes('products');
        ImageManager::resize($tmpName, $new_path . '.jpg', null, null, 'jpg', false);       
        foreach ($imagesTypes as $k => $image_type) {		
            if (!ImageManager::resize($tmpName, $new_path . '-' . stripslashes($image_type['name']) . '.' . $image->image_format, $image_type['width'], $image_type['height'], $image->image_format))
                return $this->errors[] = Tools::displayError('An error occurred while copying this image:') . ' ' . stripslashes($image_type['name']);
        }
        @unlink($tmpName);
        unset($tmpName);
        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_product));
    }

    private function addCustom($product, $names) {	
        $product->customizable = 1;
        $product->text_fields =(int)count($names);      
        $product->createLabels(0, (int)count($names));
        if ($product->save()) {		
            $id_customization_fields = Db::getInstance()->executeS('SELECT `id_customization_field` FROM `'._DB_PREFIX_.'customization_field` WHERE `id_product` = '.$product->id);
            foreach (Language::getLanguages(false) as $lang) {
                foreach ($id_customization_fields as $key => $field) {
                    Db::getInstance()->execute('
                        INSERT INTO `'._DB_PREFIX_.'customization_field_lang`
                        (`id_customization_field`, `id_lang`, `name`) VALUES ('.(int)$field['id_customization_field'].', '.(int) $lang['id_lang'].', "'.pSQL($names[$key]).'")
                        ON DUPLICATE KEY UPDATE `name` = "'.pSQL($names[$key]).'"');
                }
            }
            return true;
        }
        return false;
    }

    /* Determine position offset
    * $font (path): the font currently used
    * $value (string): the text or price
    * $size (int): size of the font
    * $position (x and y) (int): 0(left), 1(center), 2(right)
    * return x,y position in an array */    
    private function getOrigin($font, $value, $size, $position_x, $position_y) {        
        $box = imagettfbbox($size, 0, $font, $value);
        $width_text = $box[4] - abs($box[0]);
        $height_text = abs($box[5]) - $box[1];
        $base = $box[1];
        switch ($position_x) {
            case 0:
                $origin['x'] = 2;
                break;
            case 1:
                $origin['x'] = (450 - $width_text)/2;
                break;
            case 2:
                $origin['x'] = (436 - $width_text);
                break;
        }
        switch ($position_y) {
            case 0:
                $origin['y'] = (12 * $size /45 + $height_text);
                break;
            case 1:
                $origin['y'] = (275 + $height_text)/2;
                break;
            case 2:
                $origin['y'] = (273 - $box[1]);
                break;
        }        
        return $origin;
    }

    private function initOptionCreate($id_giftcard) {    
        $token = Tools::getAdminTokenLite('AdminModules');
        $back = Tools::safeOutput(Tools::getValue('back', ''));
        $current_index = AdminController::$currentIndex;       
        $title = $this->l('Create Product Gift Card');
        if ($id_giftcard) {
            $giftcard = $this->getGiftcardFieldValues($id_giftcard);
            $title = $this->l('Update Gift Card') . ': ' . $giftcard['product_name'];
        }
        if (!isset($back) || empty($back))
            $back = $current_index . '&amp;configure=' . $this->name . '&token=' . $token;
        $languages = Language::getLanguages(false);
        foreach ($languages as $k => $language)
            $languages[$k]['is_default'] = (int) $language['id_lang'] == Configuration::get('PS_LANG_DEFAULT');
        $icon = '../modules/' . $this->name . '/img/icon-valid.png';
        $models_dir = _PS_MODULE_DIR_ . 'giftcard/img/models/';
        $list = array_diff(scandir($models_dir), array('..', '.'));
        $models = array();
        foreach ($list as $key => $model) {
            $models[] = array(
                "id" => $model,
                "name" => $model
            );
        }
        $sizes = array();
        for ($i = 45; $i < 95; $i += 5 ) { 
             $sizes[] = array(
                "id" => $i,
                "name" => $i
            );
        }    
        $fields_form = array(
            'form' => array(
                version_compare(_PS_VERSION_, '1.6', '<') ? '' :
                    'legend' => array(
                    'title' => $title,
                    'icon' => 'icon-cogs',
                ),
                'description' => Tools::isSubmit('updategiftcard') ? '' :
                        $this->l('If you push "Create Product Gift Card" button, you go to :') . '<br/>'
                        . $this->l('- Create a new Gift Card product for each price') . '<br/>'
                        . $this->l('- Create a category specific for this Product, if not already exist') . '<br/><font color="red">'
                        . $this->l('- You can edit this Category or Products after creation') . '<br/></font><br/>',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Name of this gift card product'),
                        'name' => 'product_name',
                        'align' => 'text-center',
                        'class' => 'fixed-width-xxl',
                        'desc' => $this->l('Unique name for this card'),
                        'hint' => $this->l('Unique name for this card'),
                        'required' => true
                    ),
                    array(
                        'type' => 'file',
                        'label' => $this->l('Upload a image card'),
                        'desc' => $this->l('Only png format file accepted') . ' (450 x 275 px)',
                        'name' => 'cardimage',
                        'hint' =>  $this->l('Upload your own image image.').' '.$this->l('Only png format file accepted') . ' (450 x 275 px)',
                        'class' => 'fixed-width-xxl',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select a model : '),
                        'name' => 'model',
                        'desc' => $this->l('Select a model in the list'),
                        'hint' => $this->l('Select a model in the list'),
                        'image' => 'true',
                        'class' => 'fixed-width-xxl',
                        'options' => array(
                            'query' => $models,
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),      
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Duration/Validity'),
                        'name' => 'display_duration',
                        'class' => 't',
                        'desc' => $this->l('Display Duration or Validity'),
                        'hint' => $this->l('Display Duration or Validity'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Duration'),
                        'name' => 'duration',
                        'align' => 'text-center',
                        'class' => 'fixed-width-xxl',
                        'required' => true,
                        'desc' => $this->l('Enter the number of months of duration after the receipt of gift cards (by Email).'),
                        'hint' => $this->l('Enter the number of months of duration after the receipt of gift cards (by Email).'),
                        'form_group_class'  => 'display_duration_group'
                    ),                    
                     array(
                        'type' => 'date',
                        'label' => $this->l('Validity'),
                        'name' => 'validity',
                        'size' => 7,
                        'class' => 'datepicker',
                        'required' => true,
                        'values' => '',
                        'desc' => $this->l('Expiration date for the product gift card (from the creation of the Giftcard).'),
                        'hint' => $this->l('Expiration date for the product gift card (from the creation of the Giftcard).'),
                        'form_group_class'  => 'display_validity_group'
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Display Code'),
                        'name' => 'display_code',
                        'class' => 't',
                        'desc' => $this->l('Display Code on card image'),
                        'hint' => $this->l('Display Code on card image'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),                                
                    array(
                       'type' => 'hidden',
                       'name' => 'pos_code_x'
                    ),
                    array(
                       'type' => 'hidden',
                       'name' => 'pos_code_y'
                    ),
                    array(
                       'type' => 'position',
                       'name' => 'pos_code',
                       'label' => $this->l('Put the code in the desired position'),
                       'hint' => $this->l('Put the code in the desired position'),
                       'text' => $this->l('Code will be displayed here'),
                       'form_group_class'  => 'display_code_group'
                    ),
                     array(
                        'type' => 'color',
                        'label' => $this->l('Color Code'),
                        'name' => 'colorcode',
                        'size' => 6,
                        'class' => 'colorpicker',
                        'required' => true,
                        'values' => '',
                        'form_group_class'  => 'display_code_group', 
                        'desc' => $this->l('Choose the color of the barcode.'),
                        'hint' => $this->l('Choose the color of the barcode.')
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Display Price'),
                        'name' => 'display_text',
                        'class' => 't',
                        'desc' => $this->l('Display Price on card image'),
                        'hint' => $this->l('Display Price on card image'),                        
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                    array(
                       'type' => 'hidden',
                       'name' => 'pos_text_x'
                    ),
                    array(
                       'type' => 'hidden',
                       'name' => 'pos_text_y'
                    ),    
                    array(
                       'type' => 'position',
                       'name' => 'pos_text',
                       'label' => $this->l('Put the price on the  desired position'),
                       'hint' => $this->l('Put the price on the  desired position'),
                       'text' => $this->l('Price will be displayed here'),
                       'form_group_class'  => 'display_text_group'
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Select size for price : '),
                        'name' => 'text_size',
                        'desc' => $this->l('Select size in the list'),
                        'hint' => $this->l('Select size in the list'),
                        'image' => 'false',
                        'class' => 'fixed-width-xxl',
                        'form_group_class'  => 'display_text_group',
                        'options' => array(
                            'query' => $sizes,
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),      
                    array(
                        'type' => 'color',
                        'label' => $this->l('Color text'),
                        'name' => 'colortext',
                        'size' => 6,
                        'class' => 'colorpicker',
                        'required' => true,
                        'values' => '',
                        'form_group_class'  => 'display_text_group', 
                        'desc' => $this->l('Choose the color of the text.'),
                        'hint' => $this->l('Choose the color of the text.')
                    ),                   
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Display Shadow'),
                        'name' => 'display_shadow',
                        'class' => 't',
                        'desc' => $this->l('Display Shadow for Text and/or price on card image'),
                        'hint' => $this->l('Display Shadow for Text and/or price on card image'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Color Shadow'),
                        'name' => 'colorshadow',
                        'size' => 6,
                        'class' => 'colorpicker',
                        'required' => true,
                        'values' => '',
                        'form_group_class'  => 'display_shadow_group', 
                        'desc' => $this->l('Choose the color of the Shadow.'),
                        'hint' => $this->l('Choose the color of the Shadow.')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Values of the gift card :'),
                        'name' => 'values',
                        'desc' => $this->l('Enter several values separated with commas'),
                        'hint' => $this->l('Enter several values separated with commas'),
                        'align' => 'text-center',
                        'class' => 'fixed-width-xxl',
                        'size' => 40,
                        'required' => true
                    ),  
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Gift card use taxes included ?'),
                        'name' => 'tax',
                        'class' => 't',
                        'desc' => $this->l('Price of voucher tax included'),
                        'hint' => $this->l('Price of voucher tax included'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),  
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Partial use'),
                        'name' => 'partial_use',
                        'class' => 't',
                        'desc' => $this->l('Allow partial use for this gift card'),
                        'hint' => $this->l('Allow partial use for this gift card'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),                
                    array(
                        'type' => 'switch',
                        'is_bool' => true,
                        'label' => $this->l('Highlight'),
                        'name' => 'highlight',
                        'class' => 't',
                        'desc' => $this->l('Highlight the cart rule (only for card classic)'),
                        'hint' => $this->l('Highlight the cart rule (only for card classic)'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => Tools::isSubmit('updategiftcard') ? $this->l('Update Gift Card') : $this->l('Create Product Gift Card'),
                    'class' => 'button btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                ),
                'buttons' => array(
                    'cancelBlock' => array(
                        'title' => $this->l('Cancel'),
                        'href' => $back,
                        'icon' => 'process-icon-cancel',
                        'class' => 'pull-right'
                    )
                )
            )
        );
        $this->context->smarty->assign(array('models' => '../modules/giftcard/img/models/'));
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Cancel')
            )
        );
        $helper->title = $title;
        $helper->name_controller = 'giftcard';
        $helper->identifier = $this->identifier;
        $helper->languages = $languages;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->allow_employee_form_lang = true;
        if ($id_giftcard)
            $helper->submit_action = 'submitUpdateProduct';
        else
            $helper->submit_action = 'submitCreateProduct';
        $helper->show_toolbar = version_compare(_PS_VERSION_, '1.5.6', '>') ? true : false;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        if ($id_giftcard) {		
            $helper->fields_value = $this->getGiftcardFieldValues($id_giftcard);
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_giftcard');
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_product');
        } 
		else {		
            $helper->fields_value['product_name'] = $this->l('New gift card');
            $helper->fields_value['model'] = 'card-default.png';
            $helper->fields_value['values'] = '10,20,30,40,50,100,200';
            $helper->fields_value['tax'] = 0;
            $helper->fields_value['display_duration'] = 1;
            $helper->fields_value['duration'] = 3;
            $helper->fields_value['validity'] = null;
            $helper->fields_value['display_code'] = 1;
            $helper->fields_value['pos_code_x'] = 2;
            $helper->fields_value['pos_code_y'] = 0;
            $helper->fields_value['colorcode'] = '#000000';
            $helper->fields_value['display_text'] = 1;
            $helper->fields_value['pos_text_x'] = 2;
            $helper->fields_value['pos_text_y'] = 2;
            $helper->fields_value['text_size'] = 45;
            $helper->fields_value['colortext'] = '#000000';
            $helper->fields_value['display_shadow'] = 1;
            $helper->fields_value['colorshadow'] = '#000000';
            $helper->fields_value['partial_use'] = 0;
            $helper->fields_value['highlight'] = 0;
        }
        return $helper->generateForm(array($fields_form));
    }

    public function getGiftcardFieldValues($id_giftcard) {	
        $giftcard = new GiftCardClass($id_giftcard);
        $product = Db::getInstance()->getRow('SELECT  p.`price`, pl.`name` FROM `' . _DB_PREFIX_ . 'product` p
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl  ON(p.`id_product` = pl.`id_product`)
            WHERE p.`id_product`= ' . $giftcard->id_product
        );
        $getprice = ($giftcard->tax == 1) ? $product['price'] + ($product['price'] * $this->getTaxRate() /100) : $product['price'];
        $price = round((float)$getprice, 6);
        $fields_value = array(
            'id_giftcard' => $giftcard->id_giftcard,
            'id_product' => $giftcard->id_product,
            'product_name' => $product['name'],
            'model' => $giftcard->image_path,
            'values' => (float)$price,
            'tax' => $giftcard->tax,
            'display_duration' => $giftcard->display_duration,            
            'duration' => $giftcard->duration,
            'validity' => $giftcard->validity,
            'display_code' => $giftcard->display_code,
            'pos_code_x' => $giftcard->pos_code_x,
            'pos_code_y' => $giftcard->pos_code_y,
            'colorcode' => $giftcard->colorcode,
            'display_text' => $giftcard->display_text,
            'pos_text_x' => $giftcard->pos_text_x,
            'pos_text_y' => $giftcard->pos_text_y,
            'text_size' => $giftcard->text_size,
            'colortext' => $giftcard->colortext,
            'display_shadow' => $giftcard->display_shadow,
            'colorshadow' => $giftcard->colorshadow,
            'partial_use' => $giftcard->partial_use,
            'highlight' => $giftcard->highlight
        );
        return $fields_value;
    }

    protected function giftcardList() {
        $this->fields_list = array(
            'id_giftcard' => array(
                'title' => 'ID',
                'orderby' => false,
                'type' => 'text'
            ),
            'name' => array(
                'title' => $this->l('Product Name'),
                'orderby' => false,
                'type' => 'text'
            ),
            'category' => array(
                'title' => $this->l('Category'),
                'orderby' => false,
                'type' => 'text'
            ),
            'cover' => array(
                'title' => $this->l('Cover Image'),
                'image' => 'p',
                'orderby' => false,
                'filter' => false,
                'search' => false
            ),
            'nb' => array(
                'title' => $this->l('Quantity in stock'),
                'orderby' => false,
                'type' => 'text'
            ),
            'colorcode' => array(
                'title' => $this->l('Color Code'),
                'orderby' => false,
                'color' => 'colorcode',
                'type' => 'text'
            ),
            'colortext' => array(
                'title' => $this->l('Color Price'),
                'orderby' => false,
                'color' => 'colortext',
                'type' => 'text'
            ),
            'colorshadow' => array(
                'title' => $this->l('Color Shadow'),
                'orderby' => false,
                'color' => 'colorshadow',
                'type' => 'text'
            ),
            'values' => array(
                'title' => $this->l('Price'),
                'orderby' => false,
                'width' => 100,
                'type' => 'text'
            ),
            'validity' => array(
                'title' => $this->l('Duration/Validity'),
                'orderby' => false,
                'type' => 'text'
            ),
            'partial_use' => array(
                'title' => $this->l('Partial use'),
                'type' => 'bool',
                'align' => 'center',
                'orderby' => false,
                'active' => 'partialuse'
            ),
            'highlight' => array(
                'title' => $this->l('Highlight'),
                'type' => 'bool',
                'align' => 'center',
                'orderby' => false,
                'active' => 'highlight'
            )
        );
        $helper = new HelperList();
        $helper->module = $this;
        $helper->simple_header = false;
        $helper->shopLinkType = '';
        $helper->identifier = 'id_giftcard';
        $helper->actions = array('edit', 'delete');
        $helper->toolbar_btn['new'] = array(
            'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&configure=' . $this->name . '&newgiftCard',
            'desc' => $this->l('Add new'),
        );
        $helper->imageType = 'jpg';
        $helper->title = $this->l('List of Gift Card');
        $helper->table = 'giftcard';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $displayError = '';
        $errors = '';
        if($this->_error) {		
            foreach ($this->_error as $error) 
                $errors .='<li> - ' . $error . '</li>';
            $displayError = $this->displayError('<ul>' . $this->l('An error at least occured. Unable to create this gift card: ') . $errors . '</ul>');
        }  
		$result = $this->getListContent((int) Configuration::get('PS_LANG_DEFAULT'));
		$helper->listTotal = count($result);		
        return $displayError.$helper->generateList($result, $this->fields_list);
    }

    protected function getListContent($id_lang) {
        $result = array();
        $ids_card = Db::getInstance()->executeS('
            SELECT  pl.`id_product`, pl.`name`, cl.`name` as category_name, sa.`quantity` as quantity, gp.*, i.id_image
            FROM `' . _DB_PREFIX_ . 'product_lang` pl           
            LEFT JOIN `' . _DB_PREFIX_ . 'giftcard` gp ON(gp.`id_shop` = ' . (int) $this->context->shop->id . ') 
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON(cl.`id_category` = gp.`id_category`)
            LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sa ON(sa.`id_product` = gp.`id_product`)
            LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON(i.`id_product` = pl.`id_product`)
            WHERE pl.`id_product` = gp.`id_product`
            AND pl.`id_shop` = ' . (int) $this->context->shop->id . '
            AND pl.`id_lang`= ' . (int) $this->context->language->id . '
            AND cl.`id_lang`= ' . (int) $this->context->language->id . '
            GROUP BY pl.`id_product`
         ');
        foreach ($ids_card as $id_card) {
            // Suppression des images plus anciennes que leur date de validité
            // Elles sont conservées pour les renvoyer manuellement au cas où le client n'aurait pas reçu le mail...
            $folder = new DirectoryIterator(_PS_MODULE_DIR_ . 'giftcard/cards/');
            foreach ($folder as $file)
                if ($file->isFile() && !$file->isDot() && (time() - $file->getMTime() > strtotime($id_card['validity'])))
                    unlink($file->getPathname());
            $product = new Product($id_card['id_product']);
            $image = $id_card['id_image'] ? $id_card['id_image'] : '' ;
            $currency = new Currency($this->context->currency->id);
            $tax = $id_card['tax'] == 0 ? ' ('.$this->l('WT').')' : ' ('.$this->l('ATI').')';
            $price = ($id_card['tax'] == 1) ? $product->price + ($product->price * $this->getTaxRate()) /100 : $product->price;
            if (empty($id_card['display_shadow']) || $id_card['display_shadow'] = 0 || ($id_card['colortext'] == $id_card['colorshadow']))                
                $colorshadow = $this->l('Deactivate');          
            else 
                $colorshadow = $id_card['colorshadow'];           
            if ($id_card['display_duration'])
                $validity = '+ '.$id_card['duration'].' '.$this->l('Month(s)');
            else
                $validity = Tools::displayDate($id_card['validity']);
            $result[] = array(
                'id_giftcard' => $id_card['id_giftcard'],
                'name' => $id_card['name'],
                'category' => $id_card['category_name'],
                'nb' => $id_card['quantity'],
                'display_code' => $id_card['display_code'],
                'colorcode' => ($id_card['display_code'] == 1)  ? $id_card['colorcode'] : $this->l('Deactivate'),
                'display_text' => $id_card['display_text'],
                'colortext' => ($id_card['display_text'] == 1)  ? $id_card['colortext'] : $this->l('Deactivate'),
                'display_shadow' => $id_card['display_shadow'],
                'colorshadow' => $colorshadow,
                'values' => Tools::displayPrice($price, $currency, false, $this->context).$tax,                
                'id_image' => $image ? $image : $this->l('No cover found !'),
                'validity' => $validity,                
                'partial_use' => $id_card['partial_use'],
                'highlight' => $id_card['highlight']
            );
        }
        $price = '';		
        return $result;
    }

    public function getContent() {        
        $this->_html ='';
        $id_giftcard = Tools::getValue('id_giftcard');
        $giftcard = new GiftCardClass($id_giftcard);
        $code_x = isset($giftcard->pos_text_x) ? ($giftcard->pos_code_x * 100) : 200;
        $code_y =  isset($giftcard->pos_text_y) ? ($giftcard->pos_code_y * 60) : 0;
        $text_x =  isset($giftcard->pos_text_x) ? ($giftcard->pos_text_x * 100) :200;
        $text_y =  isset($giftcard->pos_text_y) ? ($giftcard->pos_text_y * 60) : 120;        
        if($id_giftcard !== false)
            $model = $giftcard->image_path;
        else {		
            $models_dir = _PS_MODULE_DIR_ . 'giftcard/img/models/';
            $list = array_diff(scandir($models_dir), array('..', '.'));
            $models = array();
            foreach ($list as $key => $model) {			
                $models[] = array(
                    "id" => $model,
                    "name" => $model
                ); 
            $model = $models[0]['name'];            
            }            
        }

        $this->context->controller->addJqueryUI(array('ui.draggable', 'ui.droppable'));
        $space = '<div class="clear">&nbsp;</div>';
        $this->_error = array();
        $this->postProcess();
        if (Tools::isSubmit('newgiftCard') || Tools::isSubmit('updategiftcard')) {		
            $id_giftcard = Tools::getValue('id_giftcard');
            $this->_html .= '
                <style>
                #drag-pos_code {width: 100px; height: 60px; background: #fff;position:relative;top:'.$code_y.'px;left:'.$code_x.'px;}
                #drag-pos_text {width: 100px; height: 60px; background: #fff;position:relative;top:'.$text_y.'px;left:'.$text_x.'px;}            
                #drag-pos_code p, #drag-pos_text p {padding: 10px 0 0 10px;font-weight:900;}
                #drag-pos_code:hover, #drag-pos_text:hover {cursor: move; }            
                #drop-pos_code, #drop-pos_text {width: 300px; height: 180px; background: url("'.$this->_path.'img/models/'.$model.'");background-size: cover; color:#fff;margin-bottom: 10px;}
                .ui-widget-content {border: 1px solid #aaaaaa; color: #222222; float: left; border-radius: 5px;}
                </style>';
            return $this->_html . $this->initOptionCreate($id_giftcard) . $space . $this->giftcardList();
        } else
            return $this->_html . $this->giftcardList() . $space . $this->_previewMail();
    }

    public function GenerateCardCode($length) {
        $code = "";
        for ($i = 0; $i < $length; $i++) {		
            $randnum = mt_rand(0, 61);
            if ($randnum < 10)
                $code .= chr($randnum + 48);
            else if ($randnum < 36)
                $code .= chr($randnum + 55);
            else
                $code .= chr($randnum + 61);
        }
        $code = strtoupper($code);
        $cartRule = Db::getInstance()->getValue('
                SELECT id_cart_rule FROM ' . _DB_PREFIX_ . 'cart_rule 
                WHERE code = "' . $code . '"');
        if ($cartRule)
            return $this->GenerateCardCode($length);
        return $code;
    }

    private function hex2rgb($hex) {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array($r, $g, $b);        
        return $rgb;
    }

    public function postProcess() {
        $nameImport = _PS_MODULE_DIR_ . $this->name . '/img/models/';
        $image_name = '';                   
        if (Tools::isSubmit('submitCreateProduct')) {		
            if ($_FILES['cardimage']['name'] != '') {			
                $file = Tools::fileAttachment('cardimage');
                $sqlExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $mimeType = array('image/png', 'image/x-png');
                if (!$file || empty($file) || $sqlExtension != 'png' || !in_array($file['mime'], $mimeType))
                    return $this->_html .= $this->displayError($this->l('Bad format image file') . ': ' . $this->l('Only png format file accepted') . ' (450 x 275 px)');
                else {
                    move_uploaded_file($file['tmp_name'], $nameImport . $file['name']);
                    $image_name = $file['name'];
                }
                @unlink($file);
            } else
            $image_name = Tools::getValue('model');
            $values = Tools::getValue('values');
            $name = Tools::getValue('product_name');
            $values = explode(",", $values);
            $display_code = Tools::getValue('display_code');
            $pos_code_x =  Tools::getValue('pos_code_x');
            $pos_code_y =  Tools::getValue('pos_code_y');
            $colorcode = Tools::getValue('colorcode');
            $display_text = Tools::getValue('display_text');
            $pos_text_x =  Tools::getValue('pos_text_x');
            $pos_text_y =  Tools::getValue('pos_text_y');
            $text_size =  Tools::getValue('text_size');
            $colortext = Tools::getValue('colortext');
            $display_shadow = Tools::getValue('display_shadow');
            $colorshadow = Tools::getValue('colorshadow');
            $display_duration = Tools::getValue('display_duration');
            if ($display_duration) {			
                $duration = Tools::getValue('duration'); 
                $validity = '';
            } else {			
                $duration = ''; 
                $validity = Tools::getValue('validity');
            }
            if (empty($duration) && empty($validity)) {
               $display_duration = 1;
               $duration = 3;
               $validity = '';
            }            
            $tax = Tools::getValue('tax');
            $partial_use = Tools::getValue('partial_use');
            $highlight = Tools::getValue('highlight');
            if((int)Configuration::get('PS_TAX') === 0 && $tax == 1)
                return $this->_html .= $this->displayError($this->l('You can not create a card with tax if taxes are not enabled on your shop'));
            foreach ($values as $value) {			
                if (!$this->createProductGiftCard($name . '-' . $value, (float) $value, $image_name, $display_duration ,(int)$duration, $validity, $tax, $partial_use, $highlight, $display_code, $pos_code_x, $pos_code_y, $colorcode,
                                                $display_text, $pos_text_x, $pos_text_y, $text_size, $colortext, $display_shadow, $colorshadow)) {
                    return false;
                }
            }
            return $this->_html .= $this->displayConfirmation($this->l('The Gift Card has been created  successfully.'));
        }

        if (Tools::isSubmit('submitUpdateProduct')) {            
            $createnewmodel = false;
            $id_giftcard = Tools::getValue('id_giftcard');
            $new_price = (float)Tools::getValue('values');
            $giftcard = new GiftCardClass($id_giftcard);
            $currency = new Currency($this->context->currency->id);
            $id_product = $giftcard->id_product;
            if ($awaiting_order = $this->checkAwaitingOrder($id_product))
                return $this->_html .= $this->displayError($this->l('This product could not be modified, an associated order is awaiting payment') . ': <a href="index.php?controller=AdminOrders&id_order=' . $awaiting_order . '&vieworder&token=' . Tools::getAdminTokenLite('AdminOrders') . '">id_order n° ' . $awaiting_order . '</a>');
            if ($_FILES['cardimage']['name'] != '') {			
                $file = Tools::fileAttachment('cardimage');
                $sqlExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $mimeType = array('image/png', 'image/x-png');
                if (!$file || empty($file) || $sqlExtension != 'png' || !in_array($file['mime'], $mimeType))
                    return $this->_html .= $this->displayError($this->l('Bad format image file') . ': ' . $this->l('Only png format file accepted') . ' (450 x 275 px)');
                else {
                    move_uploaded_file($file['tmp_name'], $nameImport . $file['name']);
                    $giftcard->image_path = $file['name'];
                }
                @unlink($file);
            } else
                $giftcard->image_path = Tools::getValue('model');
            $giftcard->display_duration = Tools::getValue('display_duration');    
            if ($giftcard->display_duration) {			
                $giftcard->duration = (int)Tools::getValue('duration'); 
                $giftcard->validity = '';
            } else {
                $giftcard->duration = ''; 
                $giftcard->validity = Tools::getValue('validity');
            }           
             if (empty($giftcard->duration) && empty($giftcard->validity)) {
               $giftcard->display_duration = 1;
               $giftcard->duration = 3;
            }
            $giftcard->display_code = Tools::getValue('display_code');
            $giftcard->pos_code_x =  Tools::getValue('pos_code_x');
            $giftcard->pos_code_y =  Tools::getValue('pos_code_y');
            $giftcard->colorcode = Tools::getValue('colorcode');
            $giftcard->display_text = Tools::getValue('display_text');
            $giftcard->pos_text_x =  Tools::getValue('pos_text_x');
            $giftcard->pos_text_y =  Tools::getValue('pos_text_y');
            $giftcard->text_size =  Tools::getValue('text_size');
            $giftcard->colortext = Tools::getValue('colortext');
            $giftcard->display_shadow = Tools::getValue('display_shadow');
            $giftcard->colorshadow = Tools::getValue('colorshadow');
            $giftcard->tax = Tools::getValue('tax');
            $giftcard->partial_use = Tools::getValue('partial_use');
            $giftcard->highlight = Tools::getValue('highlight');
            $giftcard->update();
            $partial = ($giftcard->partial_use == 0) ? $this->l('This voucher must be used in one time') : $this->l('This voucher can be used several times (as long as the balance is positive)');
            $usetax = ($giftcard->tax == 0) ? $this->l('WT') : $this->l('ATI');            
            $product = new Product($id_product);
            foreach (Language::getLanguages(false) as $lang) {
                $product->name[$lang['id_lang']] = Tools::getValue('product_name');
            }
            $price = ($giftcard->tax == 1) ? (100 * $new_price) / (100 + $this->getTaxRate()) : $new_price;
            $product->price = round((float)$price,6);
            $price = Tools::displayPrice($product->price, $currency, false, $this->context);
            if ($giftcard->display_duration)
                $desc_validity =  $this->l('Date of mailing of the card by Email').' +'.$giftcard->duration.' '.$this->l('Month');
            else
                $desc_validity = Tools::displayDate($giftcard->validity);   
            $product->description_short = $partial.'.<br/>'.$this->l('The amount value will be use only in').' '.$usetax.'.<br/>'.$this->l('Available until').': '.$desc_validity;
            $product->update();
            $nameImport = _PS_MODULE_DIR_ . $this->name . '/img/models/';
            $image_path = $nameImport . $giftcard->image_path;
            $id_image = Db::getInstance()->getValue('
                SELECT `id_image`
                FROM `' . _DB_PREFIX_ . 'image`
                WHERE `id_product` = ' . (int)$product->id . ' ');           
            $currency = new Currency($this->context->currency->id);
            $giftcardprice = Tools::displayPrice($new_price, $currency, false, $this->context);
            $this->generateImage($id_product, $id_image, $image_path, $giftcardprice, $giftcard->display_code, $giftcard->pos_code_x, $giftcard->pos_code_y, $giftcard->colorcode, $giftcard->display_text, $giftcard->pos_text_x,
                                 $giftcard->pos_text_y, $giftcard->text_size, $giftcard->colortext,  $giftcard->display_shadow,  $giftcard->colorshadow);
            ImageManager::thumbnail($image_path, 'product_mini_' . (int) $id_product . '_' . $this->context->shop->id . '.jpg', 45, 'jpg', true, false);
            $images = glob(_PS_ROOT_DIR_ . '/img/tmp/*_mini_*.*');
            foreach ($images as $image) {
                @unlink($image);
            }
            return $this->_html .= $this->displayConfirmation($this->l('The Gift Card has been updated successfully.'));
        }
        if (Tools::isSubmit('deletegiftcard')) {		
            $id = Tools::getValue('id_giftcard');
            return $this->deleteProductGiftCard($id);
        }
        if (Tools::isSubmit('partialusegiftcard')) {		
            $id_giftcard = Tools::getValue('id_giftcard');
            $giftcard = new GiftCardClass($id_giftcard);
            if ($giftcard->partial_use == 1)
                $giftcard->partial_use = 0;
            else
                $giftcard->partial_use = 1;
            $giftcard->update();
        }
        if (Tools::isSubmit('highlightgiftcard')) {        
            $id_giftcard = Tools::getValue('id_giftcard');
            $giftcard = new GiftCardClass($id_giftcard);
            if ($giftcard->highlight == 1)
                $giftcard->highlight = 0;
            else
                $giftcard->highlight = 1;
            $giftcard->update();
        }
    }

    private function _previewMail() {    
        $ps_shop_name = Configuration::get('PS_SHOP_NAME');
        $id_shop = $this->context->shop->id;
        $GiftCardName = $this->l('Gift Card');
        $id_lang = (int) $this->context->language->id;
        $lang = $this->context->language->iso_code;
        $GiftCardDate = date($this->context->language->date_format_lite, mktime(0, 0, 0, date("m") + 3, date("d"), date("Y")));
        $exemple_pdf = $this->l('Exemple of file attached (PDF) :');
        $giftcard_pdf = $this->_path . 'img/giftcard.pdf';
        $text = $this->l('Exemple of email');
        $shop_logo = '../img/'.Configuration::get('PS_LOGO');
        $shop_name = Tools::safeOutput(Configuration::get('PS_SHOP_NAME', null, null, $id_shop));
        $shop_url = Context::getContext()->link->getPageLink('index', true, Context::getContext()->language->id);
        $my_account_url = Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id);
        $guest_tracking_url = Context::getContext()->link->getPageLink('guest-tracking', true, Context::getContext()->language->id);
        $history_url = Context::getContext()->link->getPageLink('history', true, Context::getContext()->language->id);
        $color = Tools::safeOutput(Configuration::get('PS_MAIL_COLOR', null, null, $id_shop));
        $firstname = 'John';
        $lastname = 'DOE';
        $productLink = '#';
        $GiftCardDatas = '<p>' . $this->l('Your customized message.') . '</p><br /><br /><p><span style="font-weight:bold;">' . $this->l('See the conditions of use on the product page') . '</span><a href="' . $productLink . '"> ' . $GiftCardName . '</a></p><br /><br />';
        $GiftCardCode = 'DKD4X1IG';
        $GiftCardValue = '20,00';
        $GiftCardCurrency = $this->context->currency->sign;
        $GiftCardBackground = $this->_path . 'img/ciseau.png';
        $GiftCardImage = $this->_path . 'img/model.png';
        $mail = file_get_contents(_PS_MODULE_DIR_ . 'giftcard/mails/' . $lang . '/preview_mail_gift_card.html');
        $datas = array(
            '#{shop_logo}#',
            '#{firstname}#',
            '#{lastname}#',
            '#{GiftCardName}#',
            '#{GiftCardValue}#',
            '#{GiftCardCode}#',
            '#{GiftCardDate}#',
            '#{GiftCardImage}#',
            '#{GiftCardBackground}#',
            '#{GiftCardTax}#',
            '#{GiftCardDatas}#',
            '#{shop_name}#',
            '#{shop_url}#',
            '#{my_account_url}#',
            '#{guest_tracking_url}#',
            '#{history_url}#',
            '#{color}#'
        );
        $replacement = array(
            $shop_logo,
            $firstname,
            $lastname,
            $GiftCardName,
            $GiftCardValue,
            $GiftCardCode,
            $GiftCardDate,
            $GiftCardImage,
            $GiftCardBackground,
            $GiftCardCurrency . ' (' . $this->l('WT') . ')',
            $GiftCardDatas,
            $shop_name,
            $shop_url,
            $my_account_url,
            $guest_tracking_url,
            $history_url,
            $color
        );
        $mail = preg_replace($datas, $replacement, $mail);
        $this->_html = '
        <fieldset style="background-color:#fff;">
        <h4 style="height: 2.2em;text-transform: uppercase;border-bottom: solid 1px #eee;padding: 10px 5px;margin: 0 10px;">' . $text . '
        <a target="_blank" href="'.$giftcard_pdf.'"><p style="float:right;">'.$exemple_pdf.'<sapn style="color:red;text-transform: lowercase;font-weight:500;"> giftcard.pdf</span></p></a></h4>' . $mail . ' 
        </fieldset>';
        return $this->_html;
    }

    private function isShipped($id_order) {    
        if (Db::getInstance()->getValue('
            SELECT  COUNT(*)
            FROM `' . _DB_PREFIX_ . 'order_history`
            WHERE `id_order_state` IN( ' . (int) Configuration::get('PS_OS_WS_PAYMENT') . ',' . (int) Configuration::get('PS_OS_PAYMENT') . ')
            AND `id_order` = ' . $id_order
            ) > 0)
            return true;
        return false;
    }

     private function getShopUrl() {	 
        $ShopUrl = Db::getInstance()->getRow('
            SELECT domain, domain_ssl, physical_uri, virtual_uri
            FROM `'._DB_PREFIX_.'shop_url`
            WHERE  id_shop = '.$this->context->shop->id);
        if(Configuration::get('PS_SSL_ENABLED') || (!empty($_SERVER['HTTPS'])&& strtolower($_SERVER['HTTPS']) != 'off'))
            return 'https://'.$ShopUrl['domain_ssl'].$ShopUrl['physical_uri'].$ShopUrl['virtual_uri'];
        else
            return 'http://'.$ShopUrl['domain'].$ShopUrl['physical_uri'].$ShopUrl['virtual_uri'];
    }

    public function hookActionPaymentConfirmation($params) {
		if (!$this->isShipped($params['id_order']))        
            $this->createcard($params['id_order'], $params['cart']->id, $params['cart']->id_customer, $params['cart']->id_currency);
    }

    public static function displayDate($date, $id_lang) {	
        $time = strtotime($date);
        $customer_lang = new Language($id_lang);
        $date_format = $customer_lang->date_format_lite;
        return date($date_format, $time);
    }

    private function createCard($id_order, $id_cart, $id_customer, $id_currency) {
        require_once _PS_MODULE_DIR_.'giftcard/HTMLTemplateCardPdf.php';
        $order = new order($id_order);
        $sql = Db::getInstance()->executeS('
            SELECT  `id_product`
            FROM `'._DB_PREFIX_.'giftcard`
            WHERE `id_shop`= '.(int) $this->context->shop->id
        );
        foreach ($sql as $ids)
            $card_products[] = $ids['id_product'];
        foreach ($order->getProducts() as $product) {		
            if (in_array($product['product_id'], $card_products)) {			
                $id_shop = (int) $this->context->shop->id;
                $giftcard = GiftCardClass::getByIdProduct($id_shop, $product['product_id']);
                $customer = new Customer($id_customer);
                $display_code = $giftcard->display_code;
                $pos_code_x = $giftcard->pos_code_x;
                $pos_code_y = $giftcard->pos_code_y;
                $colorcode = $giftcard->colorcode;
                $display_shadow = $giftcard->display_shadow;
                $colorshadow = $giftcard->colorshadow;
                $display_text = $giftcard->display_text;
                $pos_text_x = $giftcard->pos_text_x;
                $pos_text_y = $giftcard->pos_text_y;
                $text_size = $giftcard->text_size;
                $colortext = $giftcard->colortext;                
                $GiftCardConditions = ($giftcard->partial_use == 0) ? $this->lgc('This voucher must be used in one time', $customer->id_lang) : $this->lgc('This voucher can be used several times (as long as the balance is positive)', $customer->id_lang);
                $tax = $giftcard->tax == 0 ? ' ('.$this->lgc('WT', $customer->id_lang).')' : ' ('.$this->lgc('ATI', $customer->id_lang).')';
                $pdftextcontent = $this->lgc('This card is only available on', $customer->id_lang);
                $pdftextfooter = $this->lgc('For more assistance, contact Support:', $customer->id_lang);
                $giftcard_data = $this->lgc('See the conditions of use on the product page', $customer->id_lang).': ';  
                $giftcardbackground = _PS_BASE_URL_.__PS_BASE_URI__.'modules/giftcard/img/ciseau.png';                
                $image_path = _PS_MODULE_DIR_.$this->name.'/img/models/'.$giftcard->image_path;
                $product_quantity = $product['product_quantity'];
                $id_product = $product['product_id'];
                $card_product = new Product($id_product);
                $url = $this->getShopUrl();                
                if  ($giftcard->display_duration) {				
                    $date_from = date('Y-m-d');
                    $date_to = date('Y-m-d' , strtotime('+' .$giftcard->duration. 'month'));                    
                    $date_customer_formated = $this->displayDate($date_to, $customer->id_lang);
                    $date_gift = $this->lgc('Available until :', $customer->id_lang).' '.$date_customer_formated;
                } else {				
                    $date_from = date('Y-m-d');
                    $date_to = $giftcard->validity;
                    $date_customer_formated = $this->displayDate($date_to, $customer->id_lang);
                    $date_gift = $this->lgc('Available until :').' '.$date_customer_formated;
                }   
                $classic = (int)$product['product_quantity'] - (int)$product['customizationQuantityTotal'];
                $giftcardname = $product['product_name'];
                $currency = new Currency($id_currency);
                $price =  ($giftcard->tax == 1) ? $card_product->price *((100 + $this->getTaxRate())/100) : $card_product->price;
                $giftcardvalue = Tools::displayPrice(Tools::convertPriceFull($price, null, $currency), $currency);
                $font1 = _PS_MODULE_DIR_.'giftcard/fonts/times.ttf';
                $font2 = _PS_MODULE_DIR_.'giftcard/fonts/code.ttf'; 
                $size_code = 40; //fixed
                if(!empty($product['customizedDatas'])) { 				
                    foreach($product['customizedDatas'] as $customizationPerAddress) {					
                        foreach ($customizationPerAddress as $customizationId => $customization) {                            
                            for ($i = 0; $i < $customization ['quantity']; $i++) {                                
                                $code = $this->GenerateCardCode(8);
                                $imageView = imagecreatefrompng($image_path);
                                $codecolor = $this->hex2rgb($colorcode);
                                $textcolor = $this->hex2rgb($colortext);
                                $shadowcolor = $this->hex2rgb($colorshadow); 
                                $colorcode2 = imagecolorallocate($imageView, $codecolor[0], $codecolor[1], $codecolor[2]);
                                $colortext2 = imagecolorallocate($imageView, $textcolor[0], $textcolor[1], $textcolor[2]);
                                $colorshadow2 = imagecolorallocate($imageView, $shadowcolor[0], $shadowcolor[1], $shadowcolor[2]);                               
                                $origin_code = $this->getOrigin($font2, $code, $size_code,  $pos_code_x, $pos_code_y);
                                $origin_text = $this->getOrigin($font1, $giftcardvalue, $text_size, $pos_text_x, $pos_text_y);        
                                if($display_code)
                                    imagettftext($imageView, $size_code, 0, $origin_code['x'], $origin_code['y'], $colorcode2, $font2, $code);
                                if($display_shadow)
                                    if($color != $colortext2)
                                        imagettftext($imageView, $text_size, 0,   $origin_text['x']+2, $origin_text['y']+2, $colorshadow2, $font1, $giftcardvalue);
                                if($display_text)                      
                                    imagettftext($imageView, $text_size, 0, $origin_text['x'], $origin_text['y'], $colortext2, $font1, $giftcardvalue);                                
                                imagealphablending($imageView, false);
                                imagesavealpha($imageView, true);
                                //Création d'un fichier par custom
                                imagepng($imageView, _PS_MODULE_DIR_.'giftcard/cards/Order-'. $order->id.'-Product-'.$product['product_id'].'-Custom-'.$customizationId.'-'.$i.'.png');
                                $giftcardimage = $url.'modules/'.$this->name.'/cards/Order-'. $order->id.'-Product-'.$product['product_id'].'-Custom-'.$customizationId.'-'.$i.'.png';
                                imagedestroy($imageView);                               
                                $productLink = Context::getContext()->link->getProductLink($card_product->id);
                                $message = $customization['datas'][1][0]['value'];
                                $firstname = $customization['datas'][1][1]['value'];
                                $lastname = $customization['datas'][1][2]['value'];
                                $email = !empty($customization['datas'][1][3]['value']) ? $customization['datas'][1][3]['value'] : $customer->email;                              
                                $products_data = '';
                                $products_data .='<p>'.$message.'</p><br /><br /><p><span style="font-weight:bold;">'.$giftcard_data.'</span><a href="'.$productLink.'">  '.$product['product_name'].'</a></p><br /><br />';
                                $datas = array(
                                   '{firstname}' => $firstname,
                                   '{lastname}' => $lastname,
                                   '{GiftCardName}' => $giftcardname,
                                   '{GiftCardValue}' => $giftcardvalue,
                                   '{GiftCardCode}' => $code,
                                   '{GiftCardDate}' => $date_gift,
                                   '{GiftCardImage}' => $giftcardimage,
                                   '{GiftCardBackground}' => $giftcardbackground,
                                   '{GiftCardConditions}' => $GiftCardConditions,
                                   '{GiftCardTax}' => $tax,
                                   '{GiftCardDatas}' => $products_data,
								   '{from}' => $customer->firstname.' '.$customer->lastname
                                );                                                                
                                
                                if ($email != $customer->email) {
                                    $template1 = 'conf_card_sent'; 
                                    $title1 =  $this->lgc('Gift Card send !', $customer->id_lang);        
                                    $message_confirm = $this->lgc('Your Gift Card has been sent to:', $customer->id_lang).' '.$email;
                                    Mail::Send((int)$customer->id_lang,  $template1, $title1, array('{message_confirm}' => $message_confirm,), $customer->email, null, null, null, null, null, dirname(__FILE__) . '/mails/');
                                }
                                $order->cardimage = dirname(__FILE__).'/cards/Order-'. $order->id.'-Product-'.$product['product_id'].'-Custom-'.$customizationId.'-'.$i.'.png';
                                $order->title = $title;
                                $order->pdftextcontent = $pdftextcontent;
                                $order->pdftextfooter = $pdftextfooter;
                                $order->conditions = $GiftCardConditions;
                                $pdf = new PDF($order, 'CardPdf', Context::getContext()->smarty);
                                ob_end_clean();
                                $file_attachement['content'] = $pdf->render(false);
                                $file_attachement['name'] = 'my_giftcard.pdf';
                                $file_attachement['mime'] = 'application/pdf';
                                $template2 = 'mail_gift_card';
                                $title2 = $this->lgc('Your Gift Card', $customer->id_lang);
                                Mail::Send((int)$customer->id_lang, $template2, $title2, $datas, $email, null, null, null, $file_attachement, null, dirname(__FILE__) . '/mails/');
                                $cart_rule = new CartRule();
                                $cart_rule_name = $product['product_name'];
                                foreach (Language::getLanguages(false) as $lang) {
                                    $cart_rule->name[$lang['id_lang']] = $cart_rule_name;
                                }
                                $cart_rule->description = $cart_rule_name;
                                $cart_rule->code = $code;
                                $cart_rule->active = 1;
                                $cart_rule->date_from = $date_from;
                                $cart_rule->date_to = $date_to;
                                $cart_rule->quantity = 1;
                                $cart_rule->partial_use = $giftcard->partial_use;
                                $cart_rule->quantity_per_user = 1;
                                $voucher_price = ($giftcard->tax == 1) ? $card_product->price *((100 + $this->getTaxRate())/100) : $card_product->price;
                                $cart_rule->reduction_tax = $giftcard->tax;
                                $cart_rule->minimum_amount_currency = 1;
                                $cart_rule->reduction_currency = 1;
                                $cart_rule->reduction_amount = round((float)$voucher_price, 2);
                                $cart_rule->add();                                
                                StockAvailable::updateQuantity($product['product_id'], 0, 1, $id_shop);
                            }
                        }
                    }
                }
                if($classic > 0) { 
                    for ($i = 0; $i < $classic; $i++) {                                               
                        $code = $this->GenerateCardCode(8);
                        $imageView = imagecreatefrompng($image_path);
                        $codecolor = $this->hex2rgb($colorcode);
                        $textcolor = $this->hex2rgb($colortext);
                        $shadowcolor = $this->hex2rgb($colorshadow); 
                        $colorcode2 = imagecolorallocate($imageView, $codecolor[0], $codecolor[1], $codecolor[2]);
                        $colortext2 = imagecolorallocate($imageView, $textcolor[0], $textcolor[1], $textcolor[2]);
                        $colorshadow2 = imagecolorallocate($imageView, $shadowcolor[0], $shadowcolor[1], $shadowcolor[2]);                        
                        $origin_code = $this->getOrigin($font2, $code, $size_code,  $pos_code_x, $pos_code_y);                        
                        $origin_text = $this->getOrigin($font1, $giftcardvalue, $text_size, $pos_text_x, $pos_text_y);        
                        if($display_code)
                            imagettftext($imageView, $size_code, 0, $origin_code['x'], $origin_code['y'], $colorcode2, $font2, $code);
                        if($display_shadow)
                            if($color != $colortext2)
                                imagettftext($imageView, $text_size, 0,   $origin_text['x']+2, $origin_text['y']+2, $colorshadow2, $font1, $giftcardvalue);
                        if($display_text)                      
                            imagettftext($imageView, $text_size, 0, $origin_text['x'], $origin_text['y'], $colortext2, $font1, $giftcardvalue);                        
                        imagealphablending($imageView, false);
                        imagesavealpha($imageView, true);
                        imagepng($imageView, _PS_MODULE_DIR_.'giftcard/cards/Order-'.$order->id.'Product-'.$product['product_id'].'.png');
                        $giftcardimage = $url.'modules/'.$this->name.'/cards/Order-'.$order->id.'Product-'.$product['product_id'].'.png';
                        imagedestroy($imageView);                      
                        $productLink = Context::getContext()->link->getProductLink($card_product->id);
                        $firstname = $customer->firstname;
                        $lastname = $customer->lastname;
                        $email = $customer->email;
                        $products_data = '';
                        $products_data .='<p></p><br /><br /><p><span style="font-weight:bold;">'.$giftcard_data.'</span><a href="'.$productLink.'">'.$product['product_name'].'</a></p><br /><br />';
                        $my_vouchers = Context::getContext()->link->getPageLink('discount', true, Context::getContext()->language->id, null, false, $id_shop);
                        $datas = array(
                            '{firstname}' => $firstname,
                            '{lastname}' => $lastname,
                            '{GiftCardName}' => $giftcardname,
                            '{GiftCardValue}' => $giftcardvalue,
                            '{GiftCardCode}' => $code,
                            '{GiftCardDate}' => $date_gift,
                            '{GiftCardImage}' => $giftcardimage,
                            '{GiftCardBackground}' => $giftcardbackground,
                            '{GiftCardConditions}' => $GiftCardConditions,
                            '{GiftCardTax}' => $tax,
                            '{GiftCardDatas}' => $products_data,
                            '{my_vouchers}' => $my_vouchers
                        );
                        $order->cardimage = dirname(__FILE__).'/cards/Order-'.$order->id.'Product-'.$product['product_id'].'.png';  
                        $order->title = $title;
                        $order->pdftextcontent = $pdftextcontent;
                        $order->pdftextfooter = $pdftextfooter;
                        $order->conditions = $GiftCardConditions;
                        $pdf = new PDF($order, 'CardPdf', Context::getContext()->smarty);
                        ob_end_clean();
                        $file_attachement['content'] = $pdf->render(false);
                        $file_attachement['name'] = 'my_giftcard.pdf';
                        $file_attachement['mime'] = 'application/pdf';
                        $template3 = 'gift_card';
                        $title3 = $this->lgc('Your Gift Card', $customer->id_lang);
                        Mail::Send((int)$customer->id_lang, $template3, $title3, $datas, $email, null, null, null, $file_attachement, null, dirname(__FILE__).'/mails/');
                        $cart_rule = new CartRule();
                        $cart_rule_name = $product['product_name'];
                        foreach (Language::getLanguages(false) as $lang) {
                            $cart_rule->name[$lang['id_lang']] = $cart_rule_name;
                        }
                        $cart_rule->description = $cart_rule_name;
                        if ($giftcard->highlight) {
                            $cart_rule->id_customer = $customer->id;
                            $cart_rule->highlight = 1;
                        }
                        $cart_rule->code = $code;
                        $cart_rule->active = 1;
                        $cart_rule->date_from = $date_from;
                        $cart_rule->date_to = $date_to;
                        $cart_rule->quantity = 1;
                        $cart_rule->partial_use = $giftcard->partial_use;
                        $cart_rule->quantity_per_user = 1;
                        $voucher_price = ($giftcard->tax == 1) ? $card_product->price *((100 + $this->getTaxRate())/100) : $card_product->price;
                        $cart_rule->reduction_tax = $giftcard->tax;
                        $cart_rule->minimum_amount_currency = 1;
                        $cart_rule->reduction_currency = 1;
                        $cart_rule->reduction_amount = round((float)$voucher_price, 2);
                        $cart_rule->add();    
                        StockAvailable::updateQuantity($product['product_id'], 0, 1, $id_shop);
                    } 
                }              
            }
        }
    }
    /* translation only for email and pdf in customer language */
    public static function lgc($string, $id_lang) {
        global $_MODULE;
        $iso = Language::getIsoById((int)$id_lang);
        $name = 'giftcard';
        $filesByPriority = array(                               
            _PS_MODULE_DIR_.$name.'/translations/'.$iso.'.php',
            // Translations in theme
            _PS_THEME_DIR_.'modules/'.$name.'/translations/'.$iso.'.php'
        );
        foreach ($filesByPriority as $file)             
            include_once($file);      

        $key = md5(str_replace('\'', '\\\'', $string));
        $cache_key = $name.'|'.$string;
        $theme_key = strtolower('<{'.$name.'}'._THEME_NAME_.'>'.$name).'_'.$key;
        $module_key = strtolower('<{'.$name.'}prestashop>'.$name).'_'.$key;
        if (isset($_MODULE[$$theme_key]))
            $ret = stripslashes($_MODULE[$theme_key]);
        elseif (isset($_MODULE[$module_key]))
            $ret = stripslashes($_MODULE[$module_key]);
        else
            $ret = $string;        
        return $ret;
    }

    public static function defaultTrans() {
        /* this fonction is nerver call */
        /* add here all line with $this->lgc('....') for the normal translate fonction in BO */
        $trans = array(
            $this->l('This voucher must be used in one time'),
            $this->l('This voucher can be used several times (as long as the balance is positive)'),
            $this->l('WT'),
            $this->l('ATI'),
            $this->l('This card is only available on'),
            $this->l('For more assistance, contact Support:'),
            $this->l('See the conditions of use on the product page'),
            $this->l('Your Gift Card'),
            $this->l('Available until :'),
            $this->l('Gift Card send !'),
            $this->l('Your Gift Card has been sent to:'),
        );
    }

}