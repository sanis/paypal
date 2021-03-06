<?php
/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2014
 */


/**
 * Testing oePayPalOrderActionFactory class.
 */
class Unit_oePayPal_Models_Actions_oePayPalOrderActionFactoryTest extends OxidTestCase
{

    /**
     * Data provider for testCreateAction
     */
    public function providerCreateAction()
    {
        return array(
            array('capture', 'oePayPalOrderCaptureAction'),
            array('refund', 'oePayPalOrderRefundAction'),
            array('void', 'oePayPalOrderVoidAction'),
        );
    }

    /**
     * Test case for oePayPalOrderActionFactory::createAction
     * Testing action object creation with correct actions
     *
     * @dataProvider providerCreateAction
     */
    public function testCreateAction($sAction, $sClass)
    {
        $oOrder = new oePayPalOxOrder();
        $oRequest = new oePayPalRequest();
        $oActionFactory = new oePayPalOrderActionFactory($oRequest, $oOrder);

        $this->assertTrue($oActionFactory->createAction($sAction) instanceof $sClass);
    }

    /**
     * Test case for oePayPalOrderActionFactory::createAction
     * Testing action object creation with incorrect actions
     *
     * @expectedException oePayPalInvalidActionException
     */
    public function testCreateActionWithInvalidData()
    {
        $oOrder = new oePayPalOxOrder();
        $oRequest = new oePayPalRequest();
        $oActionFactory = new oePayPalOrderActionFactory($oRequest, $oOrder);

        $oActionFactory->createAction('some_non_existing_action');
    }
}