<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: BuyerCountry.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_Source_BuyerCountry {
  
  /**
   * Buyer country supported by mPAY24
   *
   * @var array
   */
  protected $_supportedBuyerCountryCodes = array(
      'AF ', 'AX ', 'AL ', 'DZ ', 'AS ', 'AD ', 'AO ', 'AI ', 'AQ ', 'AG ', 'AR ', 'AM ', 'AW ', 'AU ', 'AT ', 'AZ ',
      'BS ', 'BH ', 'BD ', 'BB ', 'BY ', 'BE ', 'BZ ', 'BJ ', 'BM ', 'BT ', 'BO ', 'BA ', 'BW ', 'BV ', 'BR ', 'IO ',
      'BN ', 'BG ', 'BF ', 'BI ', 'KH ', 'CM ', 'CA ', 'CV ', 'KY ', 'CF ', 'TD ', 'CL ', 'CN ', 'CX ', 'CC ', 'CO ',
      'KM ', 'CG ', 'CD ', 'CK ', 'CR ', 'CI ', 'HR ', 'CU ', 'CY ', 'CZ ', 'DK ', 'DJ ', 'DM ', 'DO ', 'EC ', 'EG ',
      'SV ', 'GQ ', 'ER ', 'EE ', 'ET ', 'FK ', 'FO ', 'FJ ', 'FI ', 'FR ', 'GF ', 'PF ', 'TF ', 'GA ', 'GM ', 'GE ',
      'DE ', 'GH ', 'GI ', 'GR ', 'GL ', 'GD ', 'GP ', 'GU ', 'GT ', 'GG ', 'GN ', 'GW ', 'GY ', 'HT ', 'HM ', 'VA ',
      'HN ', 'HK ', 'HU ', 'IS ', 'IN ', 'ID ', 'IR ', 'IQ ', 'IE ', 'IM ', 'IL ', 'IT ', 'JM ', 'JP ', 'JE ', 'JO ',
      'KZ ', 'KE ', 'KI ', 'KP ', 'KR ', 'KW ', 'KG ', 'LA ', 'LV ', 'LB ', 'LS ', 'LR ', 'LY ', 'LI ', 'LT ', 'LU ',
      'MO ', 'MK ', 'MG ', 'MW ', 'MY ', 'MV ', 'ML ', 'MT ', 'MH ', 'MQ ', 'MR ', 'MU ', 'YT ', 'MX ', 'FM ', 'MD ',
      'MC ', 'MN ', 'MS ', 'MA ', 'MZ ', 'MM ', 'NA ', 'NR ', 'NP ', 'NL ', 'AN ', 'NC ', 'NZ ', 'NI ', 'NE ', 'NG ',
      'NU ', 'NF ', 'MP ', 'NO ', 'OM ', 'PK ', 'PW ', 'PS ', 'PA ', 'PG ', 'PY ', 'PE ', 'PH ', 'PN ', 'PL ', 'PT ',
      'PR ', 'QA ', 'RE ', 'RO ', 'RU ', 'RW ', 'SH ', 'KN ', 'LC ', 'PM ', 'VC ', 'WS ', 'SM ', 'ST ', 'SA ', 'SN ',
      'CS ', 'SC ', 'SL ', 'SG ', 'SK ', 'SI ', 'SB ', 'SO ', 'ZA ', 'GS ', 'ES ', 'LK ', 'SD ', 'SR ', 'SJ ', 'SZ ',
      'SE ', 'CH ', 'SY ', 'TW ', 'TJ ', 'TZ ', 'TH ', 'TL ', 'TG ', 'TK ', 'TO ', 'TT ', 'TN ', 'TR ', 'TM ', 'TC ',
      'TV ', 'UG ', 'UA ', 'AE ', 'GB ', 'US ', 'UM ', 'UY ', 'UZ ', 'VU ', 'VE ', 'VN ', 'VG ', 'VI ', 'WF ', 'EH ',
      'YE ', 'ZM ', 'ZW'
  );
  
  public function toOptionArray($isMultiselect = false)
  {
      $supported = $this->getSupportedBuyerCountryCodes();
      $options = Mage::getResourceModel('directory/country_collection')
          ->addCountryCodeFilter($supported, 'iso2')
          ->loadData()
          ->toOptionArray($isMultiselect ? false : Mage::helper('adminhtml')->__('--Please Select--'));

      return $options;
  }
  
  public function getSupportedBuyerCountryCodes()
  {
    return $this->_supportedBuyerCountryCodes;
  }
}
