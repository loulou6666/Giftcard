<?php

class HTMLTemplateCardPdf extends HTMLTemplate
{
    public $image;
	public $order;

    public function __construct($giftcard_object, $smarty)
    {
        $this->image = $giftcard_object->cardimage;
		$this->order = $giftcard_object;
		$this->shop = new Shop((int)$this->order->id_shop);		
        $this->smarty = $smarty;
		$this->title = $giftcard_object->title;
		$this->conditions = $giftcard_object->conditions;

    }
	
    public function getHeader()
    {
		$shop_name = Configuration::get('PS_SHOP_NAME', null, null, (int)$this->order->id_shop);
		$path_logo = $this->getLogo();

		$width = 0;
		$height = 0;
		if (!empty($path_logo))
			list($width, $height) = getimagesize($path_logo);

		$this->smarty->assign(array(
			'logo_path' => $path_logo,
			'img_ps_dir' => 'http://'.Tools::getMediaServer(_PS_IMG_)._PS_IMG_,
			'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'),
			'title' => $this->title,
			'date' => date('d/m/Y'),
			'shop_name' => $shop_name,
			'width_logo' => $width,
			'height_logo' => $height
		));	
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'giftcard/pdf/giftcard_header.tpl');
    }
    /**
     * Returns the template's HTML content
     * @return string HTML content
     */
    public function getContent()
    {
        $this->smarty->assign(array(
            'image' => $this->image,
			'conditions' => $this->conditions
        ));

        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'giftcard/pdf/giftcard_content.tpl');
    }

	public function getFooter()
	{
		$this->smarty->assign(array(
			'shop_url' => Tools::getHttpHost(true).__PS_BASE_URI__,
			'shop_address' => $this->getShopAddress(),
			'shop_fax' => Configuration::get('PS_SHOP_FAX', null, null, (int)$this->order->id_shop),
			'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int)$this->order->id_shop),
			'shop_details' => Configuration::get('PS_SHOP_DETAILS', null, null, (int)$this->order->id_shop),
		));

		return $this->smarty->fetch(_PS_MODULE_DIR_ . 'giftcard/pdf/giftcard_footer.tpl');
	}
    /**
     * Returns the template filename
     * @return string filename
     */
    public function getFilename()
    {
        return 'giftcard.pdf';
    }

    /**
     * Returns the template filename when using bulk rendering
     * @return string filename
     */
    public function getBulkFilename()
    {
        return 'giftcard.pdf';
    }
}