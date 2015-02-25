<?php

namespace Drufony\CoreBundle\Model;

class Product extends Content
{
    protected $dateCalendar;
    protected $showInCalendar;
    protected $sgu;
    protected $sku;
    protected $priceSubtotalNoVat;
    protected $priceVatPercentage = DEFAULT_VAT_TYPE;
    protected $price;
    protected $stock;
    protected $currency=DEFAULT_CURRENCY;
    protected $varieties=array();
    protected $firstPrice;
    protected $cares;
    protected $pricePVP;
    protected $color;
    protected $composition;
    protected $weight = 0;
    protected $brand;


    function __construct($nid = null, $lang = DEFAULT_LANG) {
        $this->contentType = self::TYPE_PRODUCT;
        if (isset($nid)) {
            $this->_loadNode($nid, $lang);
        }
    }

    public function __get($attr) {
        $value = FALSE;

        if (property_exists($this, $attr)) {
            $value = $this->$attr;
        }

        return $value;
    }

    public function getStock($varietyId = null) {
        $stock = 0;

        if ($varietyId) {
            $stock = $this->_getStockByVariety($varietyId);
        }
        else {
            $stock = $this->stock;
        }

        return $stock;
    }

    public function getPrice() {
    	$price = 0;
        if(!$this->price) {
        	$vatPercentages = unserialize(VAT_TYPES);
		$vatPercentage = $vatPercentage[$this->priceVatPercentage];
        	$this->price = $this->priceSubtotalNoVat * (1 + $vatPercentage);
        }
        $price = $this->price;
       

        return $price;
    }
    
    public function getPricePVP() {
    	$price = 0;
        
	$price = $this->pricePVP;
       

        return $price;
    }

    public function getCategory(){

    	return $this->category;
    }


    public function getColor(){

    	return $this->color;
    }


    public function getFirstPrice(){


        $price = $this->firstPrice;
       

        return $price;
    }


    public function getCares(){

    	return $this->cares;
    }


    public function getComposition(){

    	return $this->composition;
    }

    public function getWeight() {
        return $this->weight;
    }

    public function getBrand() {
        return $this->brand;
    }

    public function isAvailable($varietyId = null) {
        return ($this->getStock($varietyId) > 0);
    }

    public function lock($quantity) {
        //TODO
    }

    public function unlock($quantity) {
        //TODO
    }

    public function getVarieties() {
        if (!$this->varieties) {

            $sql = 	'SELECT  varietiesByProduct.id AS varProdId,varietiesByProduct.varietyId AS varietyId,value,stock FROM ((varietiesByProduct 
			INNER JOIN variety ON variety.id=varietiesByProduct.varietyId) 
			INNER JOIN stockByVariety ON varietiesByProduct.id=stockByVariety.productId) 
			WHERE varietiesByProduct.productId = ?';


            $varieties = db_fetchAll($sql, array($this->id));
	    $this->varieties = $varieties;
        }

        return $this->varieties;
    }

    private function _getPriceByVariety($varietyId) {
        //TODO
    }

    private function _getStockByVarieties() {
        //TODO
    	    
    }

    private function _getStockByVariety($varietyId) {
 	$sql= 'SELECT stock FROM stockByVariety WHERE stock productId=?';
 	$result = db_fechColumn($sql,$varietyId);
	if(isset($result)){
		return $result;
	}
	else{
		return FALSE;
	}
    }

    /**
     * Gets defined currencies for products
     * @return array keyed by currency code
     */


    static public  function getStockVariety($varietyId = null) {

 	$sql= 'SELECT stock,productId AS varietyId FROM stockByVariety WHERE productId=?';
 	$result = db_fetchColumn($sql,array($varietyId));
	if(isset($result)){
		$stock=$result;
	}
	else{
		$stock=FALSE;
	}	
	return $stock;

    }


    static public  function updateStockVariety($value,$varietyId) {

	$newValue=self::getStockVariety($varietyId)+$value;
	l('INFO','ACTUALIZAR '.$newValue);
	db_update('stockByVariety',array('stock'=>$newValue),array('productId'=>$varietyId));
	//	l('INFO',$varietyId.' not found');	
	//}
	//else{
	//	l('INFO',$varietyId.' stock modified');	
	//}

	return $newValue;

    }

    static public function getProductAJAX($varietyId){

 	$sql= 'SELECT *,variety.value AS size,varietiesByProduct.id AS varProdId  FROM product 
		INNER JOIN varietiesByProduct ON varietiesByProduct.productId=product.id
		INNER JOIN variety ON variety.id=varietiesByProduct.varietyId
		INNER JOIN url_friendly ON product.id=url_friendly.oid 

		WHERE varietiesByProduct.id=?';
 	$result = db_fetchAll($sql,array($varietyId));

        return $result[0];

    }


    static public function getDefinedCurrencies() {
        return array(
            'EUR',
            'USD',
            'GBP',
        );
    }
}
