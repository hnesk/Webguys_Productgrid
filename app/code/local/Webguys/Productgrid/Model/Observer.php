<?php
/**
 * @package     Webguys_Productgrid
 * @copyright   2011 Johannes Künsebeck <jk@hdnet.de>
 */

/**
 * Observer
 */
class Webguys_Productgrid_Model_Observer {
	/**
	 * Testet auf einen product grid block 
	 * 
	 * @listen adminhtml/adminhtml_block_html_before in Mage_Adminhtml_Block_Template::_toHtml()
	 * @param Varien_Event_Observer $observer 
	 */
	public function beforeBlockToHtml(Varien_Event_Observer $observer) {
		$block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Grid) {
			$this->_modifyProductGrid($block);
        }
    }
	
	/**
	 * Fügt das Attribut "color" der Produkt-Collection hinzu
	 * 
	 * @pqaram Varien_Event_Observer $observer 
	 */
	public function beforeCatalogProductCollectionLoad(Varien_Event_Observer $observer) {
		$collection = $observer->getEvent()->getCollection();
        if ($collection instanceof Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection) {
			$collection->addAttributeToSelect('color');
			$collection->addAttributeToSelect('shirt_size');
        }
    }
	
	/**
	 * Unsere kundenspezifischen Anpassungen an das Grid
	 * 
	 * @param Mage_Adminhtml_Block_Catalog_Product_Grid $grid 
	 */
	protected function _modifyProductGrid(Mage_Adminhtml_Block_Catalog_Product_Grid $grid) {
		
		$this->_addUpdatedAtColumn($grid);
		$this->_addColorColumn($grid);
		
		$this->_removeColumn($grid, 'set_name');
		$this->_removeColumn($grid, 'visibility');		
		$this->_removeColumn($grid, 'websites');						

		// reinitialisiert die Spaltensortierung
		$grid->sortColumnsByOrder();
		// reinitialisiert die Sortierung und Filter der Collection 
		$this->_callProtectedMethod($grid, '_prepareCollection');				
		
	} 
	

	/**
	 * Fügt dem Grid die Spalte "zuletzt geändert" hinter der Spalte "Name" hinzu 
	 * 
	 * @param Mage_Adminhtml_Block_Catalog_Product_Grid $grid 
	 */
	protected function _addUpdatedAtColumn(Mage_Adminhtml_Block_Catalog_Product_Grid $grid) {		
		$grid->addColumnAfter(
			'updated_at', // interne Spalten ID
			array(
				'header' => 'l. Änderung', // Text im Header
				'index' => 'updated_at',	  // Array index der aktuellen Row				
				'type' => 'date',			  // Welcher renderer
				'format' => 'dd.MM.YYYY',	  // Datumsformat a la Zend_Date (type=date spezifisch)
				'width' => '100px',			  // Breite der Spalte (empfohlen)				
				'header_css_class' => 'updated_at', // zusätzliche css Klasse für den Header
				'sortable' => true,			 // Die Spalte ist sortierbar
				'align' => 'right'			 // rechts ausrichten 
			),
			'status' // Nach welcher Spalte einfügen
        );
	}
	
	/**
	 * Fügt dem Grid die Spalte "Farbe" hinter der Spalte "Name" hinzu 
	 * 
	 * @param Mage_Adminhtml_Block_Catalog_Product_Grid $grid 
	 */
	protected function _addColorColumn(Mage_Adminhtml_Block_Catalog_Product_Grid $grid) {		
		$grid->addColumnAfter(
			'color', // interne Spalten ID
			array(
				'header' => 'Farbe', 
				'index' => 'color',	  
                'type'  => 'options',
                'options' => $this->_getProductAttributeOptions('color')
			),
			'name' // Nach welcher Spalte einfügen
        );
	}

	
	

	/**
	 * Entfernt eine Spalte aus dem Grid
	 * 
	 * @param Mage_Adminhtml_Block_Catalog_Product_Grid $block 
	 * @param string $columnName
	 */
	protected function _removeColumn(Mage_Adminhtml_Block_Catalog_Product_Grid $block, $columnName) {
		$columns = $block->getColumns();
		// entfernt die Spalte
		unset($columns[$columnName]);		
		// Autsch, aber leider gibt es kein setColumns()
		$this->_mutateProtectedProperty($block, '_columns', $columns);
	}
	
	
	/**
	 * Holt Attibute-Options im erwarteten Format 
	 * @param string $attributeName 
	 * @return array
	 */
	protected function _getProductAttributeOptions($attributeName) {
		$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product',$attributeName);
		/* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */		
		$attributeOptions = $attribute->getSource()->getAllOptions();
        $options = array();
		// options in key => value Format bringen
		// Integer Attribute werden von Magento leider als x.0000 formatiert gespeichert
		// wir kommen Magento da entgegen und machen das auch ;)
		foreach ($attributeOptions as $option) {
			$options[number_format($option['value'], 4, '.', '')] = $option['label'];
		}		
		return $options;		
	}
	
	
	/**
	 * Modifiziert ein protected/private Attribut
	 * 
	 * und ja, das ist häßlich
	 * 
	 * @param Object $object
	 * @param string  $property
	 * @param mixed $value 
	 */
	protected function _mutateProtectedProperty($object, $propertyName, $value) {
		$reflection = new ReflectionClass($object);
		$property = $reflection->getProperty($propertyName);
		$property->setAccessible(true);
		$property->setValue($object, $value);		
	}

	/**
	 * Ruft eine protected/private Methode auf
	 * 
	 * und ja, das ist häßlich
	 * 
	 * @param Object $object
	 * @param string  $methodName
	 * @return mixed $value 
	 */
	protected function _callProtectedMethod($object, $methodName) {
		$reflection = new ReflectionClass($object);
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);
		return $method->invoke($object);
	}
	

}
?>
