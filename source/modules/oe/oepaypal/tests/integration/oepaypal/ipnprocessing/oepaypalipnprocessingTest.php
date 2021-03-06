<?php
/**
 * This file is part of OXID eSales PayPal module.
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2015
 */

/**
 * Integration tests for IPN processing.
 */
class Unit_oePayPal_oePayPalIPNProcessingTest extends OxidTestCase
{
    /** @var string Command for paypal verification call. */
    const POSTBACK_CMD = 'cmd=_notify-validate';

    /** @var string PayPal transaction id */
    const PAYPAL_TRANSACTION_ID = '5H706430HM112602B';

    /** @var string PayPal id of authorization transaction */
    const PAYPAL_AUTHID = '5H706430HM1126666';

    /** @var string PayPal parent transaction id*/
    const PAYPAL_PARENT_TRANSACTION_ID = '8HF77866N86936335';

    /** @var string Amount paid*/
    const PAYMENT_AMOUNT = 30.66;

    /** @var string currency specifier*/
    const PAYMENT_CURRENCY = 'EUR';

    /** @var string PayPal correlation id for transaction*/
    const PAYMENT_CORRELATION_ID = '361b9ebf97777';

    /** @var string PayPal correlation id for authorization transaction*/
    const AUTH_CORRELATION_ID = '361b9ebf9bcee';

    /** @var string test order oxid*/
    private $testOrderId = null;

    /** @var string test user oxid */
    private $testUserId = null;

    /**
     * Set up fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->getConfig()->setConfigParam('iUtfMode', '1');

        oxDb::getDb()->execute('DROP TABLE IF EXISTS `oepaypal_order`');
        oxDb::getDb()->execute('DROP TABLE IF EXISTS `oepaypal_orderpayments`');
        oxDb::getDb()->execute('DROP TABLE IF EXISTS `oepaypal_orderpaymentcomments`');

        oePayPalEvents::addOrderPaymentsTable();
        oePayPalEvents::addOrderTable();
        oePayPalEvents::addOrderPaymentsCommentsTable();

    }

    /**
     * Tear down fixture.
     */
    protected function tearDown()
    {
        $this->cleanUpTable('oxorder');
        $this->cleanUpTable('oxuser');

        parent::tearDown();
    }

    /**
     * @return array
     */
    public function providerPayPalIPNPaymentBuilderNewCapture()
    {
        $data = array();
        $data['capture_new'][0]['ipn'] = array(
            'payment_type'         => 'instant',
            'payment_date'         => '00:54:36 Jun 03, 2015 PDT',
            'payment_status'       => 'Completed',
            'payer_status'         => 'verified',
            'first_name'           => 'Max',
            'last_name'            => 'Muster',
            'payer_email'          => 'buyer@paypalsandbox_com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'Max_Muster',
            'address_city'         => 'Freiburg',
            'address_street'       => 'Blafööstraße_123',
            'charset'              => 'UTF-8',
            'transaction_entity'   => 'payment',
            'address_country_code' => 'DE',
            'notify_version'       => '3_8',
            'custom'               => 'Bestellnummer_8',
            'parent_txn_id'        => self::PAYPAL_AUTHID,
            'txn_id'               => self::PAYPAL_TRANSACTION_ID,
            'auth_id'              => self::PAYPAL_AUTHID,
            'receiver_email'       => 'devbiz@oxid-esales_com',
            'item_name'            => 'Bestellnummer_8',
            'mc_currency'          => 'EUR',
            'test_ipn'             => '1',
            'auth_amount'          => self::PAYMENT_AMOUNT,
            'mc_gross'             => self::PAYMENT_AMOUNT,
            'correlation_id'       => self::PAYMENT_CORRELATION_ID,
            'auth_status'          => 'Completed',
            'memo'                 => 'capture_new'
        );
        $data['capture_new'][0]['expected_payment_status'] = $data['capture_new'][0]['ipn']['payment_status'];
        $data['capture_new'][0]['expected_txn_id'] = $data['capture_new'][0]['ipn']['txn_id'];
        $data['capture_new'][0]['expected_date'] = '2015-06-03 09:54:36';
        $data['capture_new'][0]['expected_correlation_id'] = $data['capture_new'][0]['ipn']['correlation_id'];

        return $data;
    }

    /**
     * Test IPN processing, payment building part.
     *
     * @dataProvider providerPayPalIPNPaymentBuilderNewCapture
     */
    public function testPayPalIPNPaymentBuilderNewCapture($data)
    {
        $this->prepareFullOrder();
        $orderPaymentParent = $this->createPayPalOrderPaymentParent();
        $orderPayment       = $this->getPayPalOrderPayment($data['ipn']);

        $this->assertTrue(is_a($orderPayment, 'oePayPalOrderPayment'), 'wrong type of object');
        $this->assertEquals($data['expected_payment_status'], $orderPayment->getStatus(), 'wrong payment status');
        $this->assertTrue($orderPayment->getIsValid(), 'payment not valid');
        $this->assertEquals($data['expected_txn_id'], $orderPayment->getTransactionId(), 'wrong transaction id');
        $this->assertEquals($data['expected_correlation_id'], $orderPayment->getCorrelationId(),
            'wrong correlation id');
        $this->assertEquals($data['expected_date'], $orderPayment->getDate(), 'wrong date');

        $orderPaymentParent->load();
        $this->assertEquals('Completed', $orderPaymentParent->getStatus(), 'wrong payment status');
        $this->assertEquals('0.00', $orderPaymentParent->getRefundedAmount(), 'wrong refunded amount');
    }

    /**
     * @return array
     */
    public function providerPayPalIPNPaymentBuilderExistingTransaction()
    {
        $data['exists'][0]['ipn'] = array(
            'payment_type'         => 'instant',
            'payment_date'         => '00:54:36 Jun 03, 2015 PDT',
            'payment_status'       => 'Refunded',
            'payer_status'         => 'verified',
            'first_name'           => 'Max',
            'last_name'            => 'Muster',
            'payer_email'          => 'buyer@paypalsandbox_com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'Max_Muster',
            'address_city'         => 'Freiburg',
            'address_street'       => 'Blafööstraße_123',
            'charset'              => 'UTF-8',
            'transaction_entity'   => 'payment',
            'address_country_code' => 'DE',
            'notify_version'       => '3_8',
            'custom'               => 'Bestellnummer_8',
            'parent_txn_id'        => '',
            'txn_id'               => self::PAYPAL_AUTHID,
            'auth_id'              => self::PAYPAL_AUTHID,
            'receiver_email'       => 'devbiz@oxid-esales_com',
            'item_name'            => 'Bestellnummer_8',
            'mc_currency'          => 'EUR',
            'test_ipn'             => '1',
            'auth_amount'          => self::PAYMENT_AMOUNT,
            'mc_gross'             => -self::PAYMENT_AMOUNT,
            'correlation_id'       => self::AUTH_CORRELATION_ID,
            'memo'                 => 'exists'
        );
        $data['exists'][0]['expected_payment_status'] = $data['exists'][0]['ipn']['payment_status'];
        $data['exists'][0]['expected_txn_id']         = $data['exists'][0]['ipn']['txn_id'];
        $data['exists'][0]['expected_date']           = '2015-04-01 12:12:12'; //orginal date
        $data['exists'][0]['expected_correlation_id'] = $data['exists'][0]['ipn']['correlation_id'];

        return $data;
    }

    /**
     * Test IPN processing, payment building part.
     *
     * @dataProvider providerPayPalIPNPaymentBuilderExistingTransaction
     */
    public function testPayPalIPNPaymentBuilderExistingTransaction($data)
    {
        $this->prepareFullOrder();
        $orderPaymentParent = $this->createPayPalOrderPaymentParent();
        $orderPayment       = $this->getPayPalOrderPayment($data['ipn']);

        $this->assertTrue(is_a($orderPayment, 'oePayPalOrderPayment'), 'wrong type of object');
        $this->assertEquals($data['expected_payment_status'], $orderPayment->getStatus(), 'wrong payment status');
        $this->assertTrue($orderPayment->getIsValid(), 'payment not valid');
        $this->assertEquals($data['expected_txn_id'], $orderPayment->getTransactionId(), 'wrong transaction id');
        $this->assertEquals($data['expected_correlation_id'], $orderPayment->getCorrelationId(),
            'wrong correlation id');
        $this->assertEquals($data['expected_date'], $orderPayment->getDate(), 'wrong date');

        $orderPaymentParent->load();
        $this->assertEquals('0.00', $orderPaymentParent->getRefundedAmount(), 'wrong refunded amount');
    }

    /**
     * @return array
     */
    public function providerPayPalIPNPaymentBuilderRefund()
    {
        $data['refund_new'][0]['ipn'] = array(
            'payment_type'         => 'instant',
            'payment_date'         => '00:54:36 Jun 03, 2015 PDT',
            'payment_status'       => 'Refunded',
            'payer_status'         => 'verified',
            'first_name'           => 'Max',
            'last_name'            => 'Muster',
            'payer_email'          => 'buyer@paypalsandbox_com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'Max_Muster',
            'address_city'         => 'Freiburg',
            'address_street'       => 'Blafööstraße_123',
            'charset'              => 'UTF-8',
            'transaction_entity'   => 'payment',
            'address_country_code' => 'DE',
            'notify_version'       => '3_8',
            'custom'               => 'Bestellnummer_8',
            'parent_txn_id'        => self::PAYPAL_AUTHID,
            'txn_id'               => self::PAYPAL_TRANSACTION_ID,
            'auth_id'              => self::PAYPAL_AUTHID,
            'receiver_email'       => 'devbiz@oxid-esales_com',
            'item_name'            => 'Bestellnummer_8',
            'mc_currency'          => 'EUR',
            'test_ipn'             => '1',
            'auth_amount'          => self::PAYMENT_AMOUNT,
            'mc_gross'             => -self::PAYMENT_AMOUNT,
            'correlation_id'       => self::AUTH_CORRELATION_ID,
            'memo'                 => 'refund_new'
        );
        $data['refund_new'][0]['expected_payment_status'] = $data['refund_new'][0]['ipn']['payment_status'];
        $data['refund_new'][0]['expected_txn_id']         = $data['refund_new'][0]['ipn']['txn_id'];
        $data['refund_new'][0]['expected_date']           = '2015-06-03 09:54:36'; //orginal date
        $data['refund_new'][0]['expected_correlation_id'] = $data['refund_new'][0]['ipn']['correlation_id'];

        return $data;
    }

    /**
     * Test IPN processing, payment building part.
     *
     * @dataProvider providerPayPalIPNPaymentBuilderRefund
     */
    public function testPayPalIPNPaymentBuilderRefund($data)
    {
        $this->prepareFullOrder();
        $orderPaymentParent = $this->createPayPalOrderPaymentParent();
        $orderPayment       = $this->getPayPalOrderPayment($data['ipn']);

        $this->assertTrue(is_a($orderPayment, 'oePayPalOrderPayment'), 'wrong type of object');
        $this->assertEquals($data['expected_payment_status'], $orderPayment->getStatus(), 'wrong payment status');
        $this->assertTrue($orderPayment->getIsValid(), 'payment not valid');
        $this->assertEquals($data['expected_txn_id'], $orderPayment->getTransactionId(), 'wrong transaction id');
        $this->assertEquals($data['expected_correlation_id'], $orderPayment->getCorrelationId(),
            'wrong correlation id');
        $this->assertEquals($data['expected_date'], $orderPayment->getDate(), 'wrong date');

        $orderPaymentParent->load();

        //in case of refund, parent transaction should now have set a refunded amount
        $this->assertEquals('refund', $orderPayment->getAction(), 'wrong action');
        $this->assertEquals(-$data['ipn']['mc_gross'], $orderPayment->getAmount(), 'wrong amount');
        $this->assertEquals($orderPayment->getAmount(), $orderPaymentParent->getRefundedAmount(),
            'wrong refunded amount');
    }

    /**
     * @return array
     */
    public function providerPayPalIPNPaymentBuilderWrongEntity()
    {
        $data['wrong_entity'][0]['ipn']                     = array('transaction_entity' => 'no_payment');
        $data['wrong_entity'][0]['expected_payment_status'] = '';
        $data['wrong_entity'][0]['expected_txn_id']         = '';
        $data['wrong_entity'][0]['expected_date']           = '';
        $data['wrong_entity'][0]['expected_correlation_id'] = '';

        return $data;
    }

    /**
     * Test IPN processing, payment building part.
     *
     * @dataProvider providerPayPalIPNPaymentBuilderExistingTransaction
     */
    public function testPayPalIPNPaymentBuilderWrongEntity($data)
    {
        $this->prepareFullOrder();
        $orderPayment = $this->getPayPalOrderPayment($data['ipn']);
        $this->assertNull($orderPayment, 'did not expect order payment object');
    }

    /**
     * @return array
     */
    public function providerPayPalIPNProcessor()
    {
        $data                                           = array();
        $data['complete_capture'][0]['capture']         = array(
            'payment_type'         => 'instant',
            'payment_date'         => '00:54:36 Jun 03, 2015 PDT',
            'payment_status'       => 'Completed',
            'payer_status'         => 'verified',
            'first_name'           => 'Max',
            'last_name'            => 'Muster',
            'payer_email'          => 'buyer@paypalsandbox_com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'Max_Muster',
            'address_city'         => 'Freiburg',
            'address_street'       => 'Blafööstraße_123',
            'charset'              => 'UTF-8',
            'transaction_entity'   => 'payment',
            'address_country_code' => 'DE',
            'notify_version'       => '3_8',
            'custom'               => 'Bestellnummer_8',
            'parent_txn_id'        => self::PAYPAL_AUTHID,
            'txn_id'               => self::PAYPAL_TRANSACTION_ID,
            'auth_id'              => self::PAYPAL_AUTHID,
            'receiver_email'       => 'devbiz@oxid-esales_com',
            'item_name'            => 'Bestellnummer_8',
            'mc_currency'          => 'EUR',
            'test_ipn'             => '1',
            'auth_amount'          => self::PAYMENT_AMOUNT,
            'mc_gross'             => self::PAYMENT_AMOUNT,
            'correlation_id'       => '361b9ebf99999',
            'auth_status'          => 'Completed'
        );
        $data['complete_capture'][1]['expected_payment_count']   = 2;
        $data['complete_capture'][1]['expected_capture_amount']  = self::PAYMENT_AMOUNT;
        $data['complete_capture'][1]['expected_refunded_amount'] = 0.0;
        $data['complete_capture'][1]['expected_voided_amount']   = 0.0;

        $data['capture_and_refund'][0]['capture'] = array(
            'payment_type'         => 'instant',
            'payment_date'         => '00:54:36 Jun 03, 2015 PDT',
            'payment_status'       => 'Completed',
            'payer_status'         => 'verified',
            'first_name'           => 'Max',
            'last_name'            => 'Muster',
            'payer_email'          => 'buyer@paypalsandbox_com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'Max_Muster',
            'address_city'         => 'Freiburg',
            'address_street'       => 'Blafööstraße_123',
            'charset'              => 'UTF-8',
            'transaction_entity'   => 'payment',
            'address_country_code' => 'DE',
            'notify_version'       => '3_8',
            'custom'               => 'Bestellnummer_8',
            'parent_txn_id'        => self::PAYPAL_AUTHID,
            'txn_id'               => self::PAYPAL_TRANSACTION_ID,
            'auth_id'              => self::PAYPAL_AUTHID,
            'receiver_email'       => 'devbiz@oxid-esales_com',
            'item_name'            => 'Bestellnummer_8',
            'mc_currency'          => 'EUR',
            'test_ipn'             => '1',
            'auth_amount'          => self::PAYMENT_AMOUNT,
            'mc_gross'             => 0.7 * self::PAYMENT_AMOUNT,
            'ipn_track_id'         => '361b9ebf99999',
            'auth_status'          => 'In_Progress'
        );

        $data['capture_and_refund'][0]['refund'] = array(
            'payment_type'         => 'instant',
            'payment_date'         => '00:54:36 Jun 03, 2015 PDT',
            'payment_status'       => 'Refunded',
            'payer_status'         => 'verified',
            'first_name'           => 'Max',
            'last_name'            => 'Muster',
            'payer_email'          => 'buyer@paypalsandbox_com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'Max_Muster',
            'address_city'         => 'Freiburg',
            'address_street'       => 'Blafööstraße_123',
            'charset'              => 'UTF-8',
            'transaction_entity'   => 'payment',
            'address_country_code' => 'DE',
            'notify_version'       => '3_8',
            'custom'               => 'Bestellnummer_8',
            'parent_txn_id'        => self::PAYPAL_TRANSACTION_ID,
            'txn_id'               => '5H706430HM1127777',
            'auth_id'              => self::PAYPAL_AUTHID,
            'receiver_email'       => 'devbiz@oxid-esales_com',
            'item_name'            => 'Bestellnummer_8',
            'mc_currency'          => 'EUR',
            'test_ipn'             => '1',
            'auth_amount'          => self::PAYMENT_AMOUNT,
            'mc_gross'             => -0.5 * self::PAYMENT_AMOUNT,
            'ipn_track_id'         => '361b9ebf99988',
            'auth_status'          => 'In_Progress'
        );

        $data['capture_and_refund'][0]['void'] = array(
            'payment_type'         => 'instant',
            'payment_date'         => '00:54:36 Jun 03, 2015 PDT',
            'payment_status'       => 'Voided',
            'payer_status'         => 'verified',
            'first_name'           => 'Max',
            'last_name'            => 'Muster',
            'payer_email'          => 'buyer@paypalsandbox_com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'Max_Muster',
            'address_city'         => 'Freiburg',
            'address_street'       => 'Blafööstraße_123',
            'charset'              => 'UTF-8',
            'transaction_entity'   => 'payment',
            'address_country_code' => 'DE',
            'notify_version'       => '3_8',
            'custom'               => 'Bestellnummer_8',
            'parent_txn_id'        => '',
            'txn_id'               => self::PAYPAL_AUTHID,
            'auth_id'              => self::PAYPAL_AUTHID,
            'receiver_email'       => 'devbiz@oxid-esales_com',
            'item_name'            => 'Bestellnummer_8',
            'mc_currency'          => 'EUR',
            'test_ipn'             => '1',
            'auth_amount'          => self::PAYMENT_AMOUNT,
            'mc_gross'             => self::PAYMENT_AMOUNT,
            'ipn_track_id'         => '361b9ebf99955',
            'auth_status'          => 'Voided'
        );

        $data['capture_and_refund'][1]['expected_payment_count']   = 3;
        $data['capture_and_refund'][1]['expected_capture_amount']  = round(0.7 * self::PAYMENT_AMOUNT, 2);
        $data['capture_and_refund'][1]['expected_refunded_amount'] = round(0.5 * self::PAYMENT_AMOUNT, 2);
        $data['capture_and_refund'][1]['expected_voided_amount']   = round(0.3 * self::PAYMENT_AMOUNT, 2);

        return $data;
    }

    /**
     * Test IPN processing in case that the incoming transaction is not yet known in the shop database.
     * This happens when actions are done via PayPal backend and not via shop.
     * Test case :
     * - authorization mode, authorization was done by shop
     * - 'capture_for_existing_auth' => incoming IPN is for successful capture of the complete amount
     * - 'capture_and_refund' => captrue, partial refund and void of the remaining auth amount
     *
     * @param array $data
     * @param array $expectations
     *
     * @dataProvider providerPayPalIPNProcessor
     */
    public function testPayPalIPNProcessing($data, $expectations)
    {
        $paypalOrder = $this->prepareFullOrder();
        $this->createPayPalOrderPayment('authorization');
        $this->processIpn($data);

        //after
        $paypalOrder->load();
        $this->assertEquals('completed', $paypalOrder->getPaymentStatus(), 'payment status');
        $this->assertEquals($expectations['expected_payment_count'], count($paypalOrder->getPaymentList()), 'payment count');
        $this->assertEquals($expectations['expected_capture_amount'], $paypalOrder->getCapturedAmount(), 'captured amount');
        $this->assertEquals($expectations['expected_refunded_amount'], $paypalOrder->getRefundedAmount(), 'refunded amount');
        $this->assertEquals($expectations['expected_voided_amount'], $paypalOrder->getVoidedAmount(), 'voided amount');

        // status of order in table oxorder
        $order = oxNew('oxOrder');
        $order->load($this->testOrderId);
        $this->assertEquals('OK', $order->oxorder__oxtransstatus->value, 'oxorder status');
        $this->assertNotNull($order->oxorder__oxpaid->value, 'oxpaid date');
        $this->assertNotEquals('0000-00-00 00:00:00', $order->oxorder__oxpaid->value);
    }

    /**
     * Test IPN processing.
     */
    public function testPayPalOrderManager()
    { //hier weiter
        $this->insertUser();
        $this->createOrder();
        $this->createPayPalOrder();
        $orderPaymentAuthorization = $this->createPayPalOrderPayment('authorization');
        $orderPayment              = $this->createPayPalOrderPayment('capture');

        $orderPaymentAuthorization->setStatus('Completed');
        $orderPaymentAuthorization->save();
        $this->assertEquals('Completed', $orderPaymentAuthorization->getStatus());

        // status of order in table oxorder
        $order = oxNew('oxOrder');
        $order->load($this->testOrderId);
        $this->assertEquals('NOT_FINISHED', $order->oxorder__oxtransstatus->value);

        //we have the capture paypal transactions that completes the payment
        //in table oepaypal_orderpayments and need to update oepaypal_order and oxorder__oxtransstatus

        $orderManager = $this->getProxyClass('oePayPalOrderManager');
        $orderManager->setOrderPayment($orderPayment);
        $paypalOrder = $orderManager->getOrder();
        $this->assertTrue(is_a($paypalOrder, 'oePayPalPayPalOrder'));
        $this->assertEquals($this->testOrderId, $paypalOrder->getId());
        $this->assertEquals(0.0, $paypalOrder->getCapturedAmount());
        $this->assertEquals('pending', $paypalOrder->getPaymentStatus());

        //check PayPal transactions for this order
        $paymentList = $paypalOrder->getPaymentList();
        $this->assertEquals(2, count($paymentList));
        $this->assertFalse($paymentList->hasPendingPayment());

        $processSuccess = $orderManager->updateOrderStatus();
        $this->assertTrue($processSuccess);
        $this->assertEquals('completed', $paypalOrder->getPaymentStatus());
        $this->assertEquals(self::PAYMENT_AMOUNT, $paypalOrder->getCapturedAmount());

        $order->load($this->testOrderId);
        $this->assertEquals('OK', $order->oxorder__oxtransstatus->value);
    }

    /**
     * Test order amount recalculation.
     */
    public function testPayPalOrderManagerOrderRecalculation()
    {
        $this->insertUser();
        $this->createOrder();
        $this->createPayPalOrder();
        $orderPaymentAuthorization = $this->createPayPalOrderPayment('authorization');
        $orderPayment              = $this->createPayPalOrderPayment('capture');

        $orderPaymentAuthorization->setStatus('In_Progress');
        $orderPaymentAuthorization->save();

        $orderPayment->setStatus('Completed');
        $orderPayment->setAmount(self::PAYMENT_AMOUNT - 10.0);
        $orderPayment->save();

        $orderManager = $this->getProxyClass('oePayPalOrderManager');
        $orderManager->setOrderPayment($orderPayment);
        $paypalOrder = $orderManager->getOrder();

        $paypalOrder = $orderManager->recalculateAmounts($paypalOrder);
        $this->assertEquals(self::PAYMENT_AMOUNT - 10.0, $paypalOrder->getCapturedAmount());
    }

    private function getPayPalConfigMock()
    {
        $mocks = array(
            'getUserEmail'                         => 'devbiz_api1.oxid-efire.com',
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
     * Create order in database by given ID.
     *
     * @param string $status
     *
     * @return oxOrder
     */
    private function createOrder($status = oePayPalOxOrder::OEPAYPAL_TRANSACTION_STATUS_NOT_FINISHED)
    {
        if (empty($this->testUserId)) {
            $this->fail('please create related oxuser first');
        }

        $this->testOrderId = substr_replace(oxUtilsObject::getInstance()->generateUId(), '_', 0, 1);
        $order             = $this->getMock('oxOrder', array('validateDeliveryAddress'));
        $order->setId($this->testOrderId);
        $order->oxorder__oxshopid        = new oxField('oxbaseshop');
        $order->oxorder__oxuserid        = new oxField($this->testUserId);
        $order->oxorder__oxorderdate     = new oxField('2015-05-29 10:41:03');
        $order->oxorder__oxbillemail     = new oxField('not@thepaypalmail.com');
        $order->oxorder__oxbillfname     = new oxField('Max');
        $order->oxorder__oxbillname      = new oxField('Muster');
        $order->oxorder__oxbillstreet    = new oxField('Blafööstraße');
        $order->oxorder__oxbillstreetnr  = new oxField('123');
        $order->oxorder__oxbillcity      = new oxField('Литовские');
        $order->oxorder__oxbillcountryid = new oxField('a7c40f631fc920687.20179984');
        $order->oxorder__oxbillzip       = new oxField('22769');
        $order->oxorder__oxbillsal       = new oxField('MR');
        $order->oxorder__oxpaymentid     = new oxField('95700b639e4ef5e759bf6e3be4aabd44');
        $order->oxorder__oxpaymenttype   = new oxField('oxidpaypal');
        $order->oxorder__oxtotalnetsum   = new oxField(self::PAYMENT_AMOUNT / 1.19);
        $order->oxorder__oxtotalbrutsum  = new oxField(self::PAYMENT_AMOUNT);
        $order->oxorder__oxtotalordersum = new oxField(self::PAYMENT_AMOUNT);
        $order->oxorder__oxartvat        = new oxField('19');
        $order->oxorder__oxvatartprice1  = new oxField('4.77');
        $order->oxorder__oxcurrency      = new oxField('EUR');
        $order->oxorder__oxfolder        = new oxField('ORDERFOLDER_NEW');
        $order->oxorder__oxdeltype       = new oxField('standard');
        $order->oxorder__oxtransstatus   = new oxField($status);
        $order->oxorder__oxtransid       = new oxField(self::PAYPAL_AUTHID, oxField::T_RAW);
        $order->save();

        //mocked to circumvent delivery address change md5 check from requestParameter
        $order->expects($this->any())->method('validateDeliveryAddress')->will($this->returnValue(0));

        return $order;
    }

    /**
     * Create order in database by given ID.
     *
     * @return oePayPalPayPalOrder
     */
    private function createPayPalOrder()
    {
        if (empty($this->testOrderId)) {
            $this->fail('please create related oxorder first');
        }

        $paypalOrder = oxNew('oePayPalPayPalOrder');
        $paypalOrder->setOrderId($this->testOrderId);
        $paypalOrder->setPaymentStatus('pending');
        $paypalOrder->setCapturedAmount(0.0);
        $paypalOrder->setTotalOrderSum(self::PAYMENT_AMOUNT);
        $paypalOrder->setCurrency(self::PAYMENT_CURRENCY);
        $paypalOrder->setTransactionMode('Authorization');
        $paypalOrder->save();

        return $paypalOrder;
    }

    /**
     * Create order payment authorization or capture.
     *
     * @param string $mode Chose type of orderpayment (authorization or capture)
     *
     * @return oePayPalOrderPayment
     */
    private function createPayPalOrderPayment($mode = 'authorization')
    {
        if (empty($this->testOrderId)) {
            $this->fail('please create related oxorder first');
        }

        $data                  = array();
        $data['authorization'] = array(
            'setAction'        => 'authorization',
            'setTransactionId' => self::PAYPAL_AUTHID,
            'setAmount'        => self::PAYMENT_AMOUNT,
            'setCurrency'      => self::PAYMENT_CURRENCY,
            'setStatus'        => 'Pending',
            'setCorrelationId' => self::AUTH_CORRELATION_ID,
            'setDate'          => '2015-04-01 12:12:12'
        );
        $data['capture']       = array(
            'setAction'        => 'capture',
            'setTransactionId' => self::PAYPAL_TRANSACTION_ID,
            'setAmount'        => self::PAYMENT_AMOUNT,
            'setCurrency'      => self::PAYMENT_CURRENCY,
            'setStatus'        => 'Completed',
            'setCorrelationId' => self::PAYMENT_CORRELATION_ID,
            'setDate'          => '2015-04-01 13:13:13'
        );

        $paypalOrderPayment = oxNew('oePayPalOrderPayment');
        $paypalOrderPayment->setOrderid($this->testOrderId);

        foreach ($data[$mode] as $function => $argument) {
            $paypalOrderPayment->$function($argument);
        }
        $paypalOrderPayment->save();

        return $paypalOrderPayment;
    }

    /**
     * insert test user
     */
    private function insertUser()
    {
        $this->testUserId = substr_replace(oxUtilsObject::getInstance()->generateUId(), '_', 0, 1);
        $user             = oxNew('oxUser');
        $user->setId($this->testUserId);
        $user->oxuser__oxactive    = new oxField('1', oxField::T_RAW);
        $user->oxuser__oxrights    = new oxField('user', oxField::T_RAW);
        $user->oxuser__oxshopid    = new oxField('oxbaseshop', oxField::T_RAW);
        $user->oxuser__oxusername  = new oxField('testuser@oxideshop.dev', oxField::T_RAW);
        //password is asdfasdf
        $user->oxuser__oxpassword  = new oxField('c630e7f6dd47f9ad60ece4492468149bfed3da3429940181464baae99941d0ffa5562' .
                                                 'aaecd01eab71c4d886e5467c5fc4dd24a45819e125501f030f61b624d7d',
                                                 oxField::T_RAW);
        $user->oxuser__oxpasssalt  = new oxField('3ddda7c412dbd57325210968cd31ba86', oxField::T_RAW);
        $user->oxuser__oxcustnr    = new oxField('666', oxField::T_RAW);
        $user->oxuser__oxfname     = new oxField('Max', oxField::T_RAW);
        $user->oxuser__oxlname     = new oxField('Muster', oxField::T_RAW);
        $user->oxuser__oxstreet    = new oxField('blafoostreet', oxField::T_RAW);
        $user->oxuser__oxstreetnr  = new oxField('123', oxField::T_RAW);
        $user->oxuser__oxcity      = new oxField('Freiburg', oxField::T_RAW);
        $user->oxuser__oxcountryid = new oxField('a7c40f631fc920687.20179984', oxField::T_RAW);
        $user->oxuser__oxzip       = new oxField('22769', oxField::T_RAW);
        $user->oxuser__oxsal       = new oxField('MR', oxField::T_RAW);
        $user->oxuser__oxactive    = new oxField('1', oxField::T_RAW);
        $user->oxuser__oxboni      = new oxField('1000', oxField::T_RAW);
        $user->oxuser__oxcreate    = new oxField('2015-05-20 22:10:51', oxField::T_RAW);
        $user->oxuser__oxregister  = new oxField('2015-05-20 22:10:51', oxField::T_RAW);
        $user->oxuser__oxboni      = new oxField('1000', oxField::T_RAW);
        $user->save();
    }

    /**
     * Test helper for creating order with PayPal.
     *
     * @param array test data
     *
     * @return oePayPalOrderPayment
     */
    private function getPayPalOrderPayment($data)
    {
        $paypalConfig = $this->getPayPalConfigMock();
        $lang         = $paypalConfig->getLang();

        //simulates IPN for capture
        $paypalRequest = $this->getMock('oePayPalRequest', array('getPost'));
        $paypalRequest->expects($this->any())->method('getPost')->will($this->returnValue($data));

        $paymentBuilder = oxNew('oePayPalIPNPaymentBuilder');
        $paymentBuilder->setLang($lang);
        $paymentBuilder->setRequest($paypalRequest);

        //expect the capture transaction to be stored in table oepaypal_orderpayments
        $orderPayment = $paymentBuilder->buildPayment();

        return $orderPayment;
    }

    /**
     * Test helper for creating order payment parent transaction with PayPal.
     *
     * @return oePayPalOrderPayment
     */
    private function createPayPalOrderPaymentParent()
    {
        $orderPaymentParent = $this->createPayPalOrderPayment('authorization');
        $this->assertEquals('Pending', $orderPaymentParent->getStatus());
        return $orderPaymentParent;
    }

    /**
     * Test helper, creates order with paypal payment and all connected database entries.
     * @return oePayPalPayPalOrder
     */
    private function prepareFullOrder()
    {
        $this->insertUser();
        $this->createOrder();
        $paypalOrder = $this->createPayPalOrder();
        return $paypalOrder;
    }

    /**
     * Test helper, processes IPN data.
     *
     * @param $data
     */
    private function processIpn($data)
    {
        $paypalConfig = $this->getPayPalConfigMock();
        $lang         = $paypalConfig->getLang();

        foreach ($data as $post) {
            $paypalRequest = $this->getMock('oePayPalRequest', array('getPost'));
            $paypalRequest->expects($this->any())->method('getPost')->will($this->returnValue($post));
            $processor = oxNew('oePayPalIPNProcessor');
            $processor->setLang($lang);
            $processor->setRequest($paypalRequest);
            $processor->process();
        }
    }
}
