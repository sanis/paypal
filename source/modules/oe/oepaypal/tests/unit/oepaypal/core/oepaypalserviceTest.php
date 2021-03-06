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
 * Testing oePayPalService class.
 */
class Unit_oePayPal_core_oePayPalServiceTest extends OxidTestCase
{
    /**
     * oePayPalConfig setter getter test
     */
    public function testGetConfig_configSet_config()
    {
        $oService = new oePayPalService();
        $oService->setPayPalConfig(new oePayPalConfig());

        $this->assertTrue($oService->getPayPalConfig() instanceof oePayPalConfig);
    }

    /**
     * oePayPalConfig getter test
     */
    public function testGetConfig_notSet_config()
    {
        $oService = new oePayPalService();
        $this->assertTrue($oService->getPayPalConfig() instanceof oePayPalConfig);
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testGetCaller_callerSet_definedCaller()
    {
        $oCaller = new oePayPalCaller();
        $oCaller->setParameter('parameter', 'value');

        $oService = new oePayPalService();
        $oService->setCaller($oCaller);

        $this->assertTrue($oService->getCaller() instanceof oePayPalCaller);
        $aParameters = $oService->getCaller()->getParameters();
        $this->assertEquals('value', $aParameters['parameter']);
        $this->assertNull($aParameters['notDefinedParameter']);
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testGetCaller_defaultCaller_callerWithPreparedData()
    {
        $this->getConfig()->setConfigParam('blOEPayPalSandboxMode', false);
        $this->getConfig()->setConfigParam('sOEPayPalPassword', 'pwd');
        $this->getConfig()->setConfigParam('sOEPayPalUsername', 'usr');
        $this->getConfig()->setConfigParam('sOEPayPalSignature', 'signature');

        $oService = new oePayPalService();

        $this->assertTrue($oService->getCaller() instanceof oePayPalCaller);

        $aParameters = $oService->getCaller()->getParameters();
        $this->assertEquals('84.0', $aParameters['VERSION']);
        $this->assertEquals('pwd', $aParameters['PWD']);
        $this->assertEquals('usr', $aParameters['USER']);
        $this->assertEquals('signature', $aParameters['SIGNATURE']);
        $this->assertNull($aParameters['notDefinedParameter']);

        $oCurl = $oService->getCaller()->getCurl();
        $this->assertTrue($oCurl instanceof oePayPalCurl);
        $this->assertEquals('api-3t.paypal.com', $oCurl->getHost());
        $this->assertEquals('https://api-3t.paypal.com/nvp', $oCurl->getUrlToCall());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testGetCallerWithLogger_LoggingOff_loggerNotSet()
    {
        $this->getConfig()->setConfigParam('blPayPalLoggerEnabled', false);

        $oService = new oePayPalService();
        $this->assertTrue($oService->getCaller() instanceof oePayPalCaller);
        $this->assertNull($oService->getCaller()->getLogger());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testGetCallerWithLogger_LoggingOn_loggerIsSet()
    {
        $this->getConfig()->setConfigParam('blPayPalLoggerEnabled', true);

        $oService = new oePayPalService();
        $this->assertTrue($oService->getCaller() instanceof oePayPalCaller);
        $this->assertTrue($oService->getCaller()->getLogger() instanceof oePayPalLogger);
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testSetExpressCheckout_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), 'SetExpressCheckout'));

        $oResponse = $oService->setExpressCheckout($this->_prepareRequest());

        $this->assertTrue($oResponse instanceof oePayPalResponseSetExpressCheckout);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testGetExpressCheckoutDetails_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), 'GetExpressCheckoutDetails'));

        $oResponse = $oService->getExpressCheckoutDetails($this->_prepareRequest());

        $this->assertTrue($oResponse instanceof oePayPalResponseGetExpressCheckoutDetails);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testDoExpressCheckoutPayment_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), 'DoExpressCheckoutPayment'));

        $oResponse = $oService->doExpressCheckoutPayment($this->_prepareRequest());

        $this->assertTrue($oResponse instanceof oePayPalResponseDoExpressCheckoutPayment);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testDoVoid_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), 'DoVoid'));

        $oResponse = $oService->doVoid($this->_prepareRequest());

        $this->assertTrue($oResponse instanceof oePayPalResponseDoVoid);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testRefundTransaction_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), 'RefundTransaction'));

        $oResponse = $oService->refundTransaction($this->_prepareRequest());

        $this->assertTrue($oResponse instanceof oePayPalResponseDoRefund);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testDoReAuthorization_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), 'DoReauthorization'));

        $oResponse = $oService->doReAuthorization($this->_prepareRequest());

        $this->assertTrue($oResponse instanceof oePayPalResponseDoReAuthorize);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testDoCapture_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), 'DoCapture'));

        $oResponse = $oService->doCapture($this->_prepareRequest());

        $this->assertTrue($oResponse instanceof oePayPalResponseDoCapture);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * oePayPalCaller setter getter test
     */
    public function testDoVerifyWithPayPal_setRequest_getResponse()
    {
        $oService = new oePayPalService();
        $oService->setCaller($this->_prepareCallerMock($this->_prepareRequest(), null));

        $oResponse = $oService->doVerifyWithPayPal($this->_prepareRequest(), 'UTF-8');
        $this->assertTrue($oResponse instanceof oePayPalResponseDoVerifyWithPayPal);
        $this->assertEquals(array('parameter' => 'value'), $oResponse->getData());
    }

    /**
     * data provider
     *
     * @return array
     */
    public function ipnPostbackEncodingProvider()
    {
        $data = array();

        $data['ascii_IPN'][0]['ipn'] = array(
            'payment_type'   => 'instant',
            'payment_date'   => 'Tue_May_26_2015_21:57:49_GMT_0200_(CEST)',
            'payment_status' => 'Completed',
            'payer_status'   => 'verified',
            'first_name'     => 'Bla',
            'last_name'      => 'Foo',
            'payer_email'    => 'buyer@paypalsandbox_com',
            'payer_id'       => 'TESTBUYERID01',
            'address_name'   => 'Bla_Foo',
            'address_city'   => 'Hamburg',
            'address_street' => 'meinestrasse_123',
            'charset'        => 'ISO-8859-1'
        );
        $expectedPostback = 'payment_type=instant&payment_date=Tue_May_26_2015_21%3A57%3A49_GMT_0200_%28CEST%29&' .
                            'payment_status=Completed&payer_status=verified&first_name=Bla&last_name=Foo&payer_email=' .
                            'buyer%40paypalsandbox_com&payer_id=TESTBUYERID01&address_name=Bla_Foo&address_city=' .
                            'Hamburg&address_street=meinestrasse_123&charset=ISO-8859-1';

        $data['ascii_IPN'][0]['expected_postback'] = $expectedPostback;
        $data['ascii_IPN'][0]['expected_data_encoding'] = 'ascii';

        $data['utf8_IPN'][0]['ipn'] = array(
            'payment_type'   => 'instant',
            'payment_date'   => 'Tue_May_26_2015_21:57:49_GMT_0200_(CEST)',
            'payment_status' => 'Completed',
            'payer_status'   => 'verified',
            'first_name'     => 'Bla',
            'last_name'      => 'Foo',
            'payer_email'    => 'buyer@paypalsandbox_com',
            'payer_id'       => 'TESTBUYERID01',
            'address_name'   => 'Bla_Foo',
            'address_city'   => 'Литовские',
            'address_street' => 'Blafööstraße_123',
            'charset'        => 'UTF-8'
        );
        $expectedPostback = 'payment_type=instant&payment_date=Tue_May_26_2015_21%3A57%3A49_GMT_0200_%28CEST%29&' .
                            'payment_status=Completed&payer_status=verified&first_name=Bla&last_name=Foo&payer_email=' .
                            'buyer%40paypalsandbox_com&payer_id=TESTBUYERID01&address_name=Bla_Foo&address_city=' .
                            '%D0%9B%D0%B8%D1%82%D0%BE%D0%B2%D1%81%D0%BA%D0%B8%D0%B5&address_street=' .
                            'Blaf%C3%B6%C3%B6stra%C3%9Fe_123&charset=UTF-8';
        $data['utf8_IPN'][0]['expected_postback'] = $expectedPostback;
        $data['utf8_IPN'][0]['expected_data_encoding'] = 'utf-8';

        //see http://en.wikipedia.org/wiki/Windows-1252
        $data['windows-1252_IPN'][0]['ipn'] = array(
            'payment_type'   => 'instant',
            'payment_date'   => 'Tue_May_26_2015_21:57:49_GMT_0200_(CEST)',
            'payment_status' => 'Completed',
            'payer_status'   => 'verified',
            'first_name'     => 'Bla',
            'last_name'      => 'Foo',
            'payer_email'    => 'buyer@paypalsandbox_com',
            'payer_id'       => 'TESTBUYERID01',
            'address_name'   => 'Bla_Foo',
            'address_city'   => 'Hamburg',
            'address_street' => 'Blafööstraße_123',
            'charset'        => 'windows-1252'
        );
        $expectedPostback = 'payment_type=instant&payment_date=Tue_May_26_2015_21%3A57%3A49_GMT_0200_%28CEST%29&' .
                            'payment_status=Completed&payer_status=verified&first_name=Bla&last_name=Foo&payer_email=' .
                            'buyer%40paypalsandbox_com&payer_id=TESTBUYERID01&address_name=Bla_Foo&address_city=' .
                            'Hamburg&address_street=Blaf%C3%B6%C3%B6stra%C3%9Fe_123&charset=windows-1252';
        $data['windows-1252_IPN'][0]['expected_postback'] = $expectedPostback;
        $data['windows-1252_IPN'][0]['expected_data_encoding'] = 'windows-1252';

        return $data;
    }

    /**
     * Check encoding of test data.
     * Test data will be reused later.
     *
     * @dataProvider ipnPostbackEncodingProvider
     */
    public function testTestDataEncoding($data)
    {
        foreach ($data['ipn'] as $key => $value) {
            $this->assertTrue(mb_check_encoding($value, $data['expected_data_encoding']));
        }
    }

    /**
     * Test PayPal caller service for IPN verification.
     * Verify that charsets are set as required.
     *
     * @dataProvider ipnPostbackEncodingProvider
     */
    public function testPayPalIPNConnectionCharset($data)
    {
        $curl = $this->prepareCurlDoVerifyWithPayPal($data);
        $this->assertEquals($data['charset'], $curl->getConnectionCharset());
    }

    /**
     * Test PayPal caller service for IPN verification.
     * Verify that charsets are set as required.
     *
     * @dataProvider ipnPostbackEncodingProvider
     */
    public function testPayPalIPNDataCharset($data)
    {
        $curl = $this->prepareCurlDoVerifyWithPayPal($data);
        $this->assertEquals($data['charset'], $curl->getDataCharset());
    }


    /**
     * Test PayPal caller service for IPN verification.
     * Have a look what the curl object does with parameters,
     * data should not have been changed so far
     *
     * @dataProvider ipnPostbackEncodingProvider
     */
    public function testPayPalIPNCurlParameters($data)
    {
        $curl = $this->prepareCurlDoVerifyWithPayPal($data);
        $curlParameters = $curl->getParameters();
        $this->assertEquals($data['ipn'], $curlParameters );

    }

    /**
     * Test PayPal caller service for IPN verification.
     * oePayPalService::doVerifyWithPayPal
     *
     * @dataProvider ipnPostbackEncodingProvider
     */
    public function testPayPalIPNPostbackEncoding($data)
    {
        //Rule for PayPal IPN verification is to give them back whatever comes in and prepend
        //cmd=_notify-validate. As long as the shop does not try to reencode the original
        //request, all is well.
        //The encoding in PayPal backend should be set according to shop nevertheless.
        //see http://blog.scrobbld.com/paypal/change-encoding-in-your-paypal-account/
        //As the address data from IPN requests is not used for shop currently, wrong encoding
        // does not matter here. It might matter for PayPalExpress checkout, as that one sets the
        // delivery address that's stored at PayPal.

        $curl = $this->prepareCurlDoVerifyWithPayPal($data);

        $query = $curl->getQuery();
        $this->assertEquals($data['expected_postback'], $query);
    }

    /**
     * Prepare oePayPalCaller stub
     *
     * @param oePayPalPayPalRequest $oRequest    request
     * @param string                $sMethodName method name
     *
     * @return oePayPalCaller
     */
    protected function _prepareCallerMock($oRequest, $sMethodName = null)
    {
        $oCaller = $this->getMock("oePayPalCaller", array("setRequest", 'call'));
        $oCaller->expects($this->once())->method("setRequest")->with($this->equalTo($oRequest));
        if (!is_null($sMethodName)) {
            $oCaller->expects($this->once())->method("call")
                ->with($this->equalTo($sMethodName))
                ->will($this->returnValue(array('parameter' => 'value')));
        } else {
            $oCaller->expects($this->once())->method("call")
                ->will($this->returnValue(array('parameter' => 'value')));
        }

        return $oCaller;
    }

    /**
     * Prepare PayPal request
     *
     * @return oePayPalPayPalRequest
     */
    protected function _prepareRequest()
    {
        $oRequest = new oePayPalPayPalRequest();
        $oRequest->setData(array('rParameter' => 'rValue'));

        return $oRequest;
    }

    /**
     * Provide a mocked oePayPalConfig
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPayPalConfigMock()
    {
        $mocks = array( 'getUserEmail'                         => 'devbiz_api1.oxid-efire.com',
                        'isExpressCheckoutInMiniBasketEnabled' => '1',
                        'isStandardCheckoutEnabled'            => '1',
                        'isExpressCheckoutEnabled'             => '1',
                        'isLoggingEnabled'                     => '1',
                        'finalizeOrderOnPayPalSide'            => '1',
                        'isSandboxEnabled'                     => '1',
                        'getPassword'                          => '1382082575',
                        'getSignature'                         => 'AoRXRr2UPUu8BdpR8rbnhMMeSk9rAmMNTW2T1o9INg0KUgsqW4qcuhS5',
                        'getTransactionMode'                   => 'AUTHORIZATION',
        );

        $paypalConfig = $this->getMock('oePayPalConfig',
            array_keys($mocks));

        foreach ($mocks as $method => $returnValue) {
            $paypalConfig->expects($this->any())->method($method)->will($this->returnValue($returnValue));
        }

        return $paypalConfig;
    }

    /**
     * Test helper for preparing curl object that
     * ran IPN verification with PayPal.
     *
     * @param $data
     *
     * @return mixed
     */
    private function prepareCurlDoVerifyWithPayPal($data)
    {
        $paypalPayPalRequest = oxNew('oePayPalPayPalRequest');
        foreach ($data['ipn'] as $key => $value) {
            $paypalPayPalRequest->setParameter($key, $value);
        }

        $caller = $this->getMock('oePayPalCaller', array('call'));
        $caller->expects($this->once())->method('call')->will($this->returnValue(array()));
        $caller->setRequest($paypalPayPalRequest);

        $curl = $caller->getCurl();
        $curl->setParameters($caller->getParameters());

        $paypalService = oxNew('oePayPalService');
        $paypalService->setCaller($caller);
        $paypalService->setPayPalConfig($this->getPayPalConfigMock());
        $paypalService->doVerifyWithPayPal($paypalPayPalRequest, $data['charset']);

        return $curl;
    }
}
