<?php

class CornerDrop_Collect_Block_Adminhtml_Form_Field_Account_Balance extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Return the account balance or "Unknown" if it's not known.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (is_numeric($element->getEscapedValue())) {
            return $element->getEscapedValue();
        }

        return "Unknown";
    }

}
