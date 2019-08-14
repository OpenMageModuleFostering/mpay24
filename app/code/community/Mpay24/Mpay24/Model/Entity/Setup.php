<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Setup.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_Entity_Setup extends Mage_Eav_Model_Entity_Setup {
  public function getDefaultEntities() {
    return array(
                'order_payment' => array(
                                          'entity_model' => 'sales/order_payment',
                                          'table'=>'sales/order_entity',
                                          'attributes' => array(
                                                                'parent_id' => array(
                                                                                    'type'=>'static',
                                                                                    'backend'=>'sales_entity/order_attribute_backend_child'
                                                                                     ),
                                                                'mpay_tid' => array(),
                                                                )
                                        )
                );
  }
}
?>