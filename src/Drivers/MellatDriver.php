<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\GatewayResponse;
use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Illuminate\Support\Facades\Http;

class MellatDriver extends AbstractDriver
{
    public function getDriverName(): string
    {
        return 'mellat';
    }

    protected function getTargetCurrency(): string
    {
        return 'rial';
    }

    public function request(): GatewayResponseInterface
    {
        $transaction = $this->logTransactionCreation();

        $terminalId = $this->config['terminal_id'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $orderId = rand(1000000, 9999999);
        $amount = (int) $this->getConvertedAmount();
        $localDate = date('Ymd');
        $localTime = date('His');

        $soapEnvelope = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <bpPayRequest xmlns="http://interfaces.core.section.bankmellat.ir/">
      <terminalId>' . $terminalId . '</terminalId>
      <userName>' . $username . '</userName>
      <userPassword>' . $password . '</userPassword>
      <orderId>' . $orderId . '</orderId>
      <amount>' . $amount . '</amount>
      <localDate>' . $localDate . '</localDate>
      <localTime>' . $localTime . '</localTime>
      <additionalData>' . htmlspecialchars($this->description) . '</additionalData>
      <callBackUrl>' . htmlspecialchars($this->callbackUrl) . '</callBackUrl>
      <payerId>0</payerId>
    </bpPayRequest>
  </soap:Body>
</soap:Envelope>';

        try {
            $response = Http::withBody($soapEnvelope, 'text/xml; charset=utf-8')
                ->post('https://bpm.shaparak.ir/pgwchannel/services/pgw');

            $xml = @simplexml_load_string($response->body());
            if ($xml === false) {
                throw new GatewayException("Invalid XML response received from Mellat payment gateway.");
            }

            $result = (string) ($xml->xpath('//return')[0] ?? '');
            $resParts = explode(',', $result);

            if ($resParts[0] === '0' && isset($resParts[1])) {
                $refId = $resParts[1];

                if ($transaction->exists) {
                    $transaction->update(['transaction_id' => $refId]);
                }

                return new GatewayResponse(
                    success: true,
                    transactionId: $refId,
                    authority: $refId,
                    redirectUrl: 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
                    rawResponse: ['result' => $result, 'ref_id' => $refId]
                );
            }

            $msg = "Mellat PayRequest failed with code: " . $resParts[0];
            $this->logTransactionFailure(new GatewayException($msg), ['result' => $result], $transaction);

            return new GatewayResponse(
                success: false,
                errorMessage: $msg,
                rawResponse: ['result' => $result]
            );
        } catch (\Throwable $e) {
            $this->logTransactionFailure($e, [], $transaction);
            throw new GatewayException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function verify(?array $params = null): ReceiptInterface
    {
        $params = $params ?? request()->all();
        $resCode = $params['ResCode'] ?? null;
        $refId = $params['RefId'] ?? null;
        $saleOrderId = $params['SaleOrderId'] ?? null;
        $saleReferenceId = $params['SaleReferenceId'] ?? null;

        $transaction = $this->findTransactionRecord($refId, $saleReferenceId);

        if ($resCode !== '0') {
            $msg = "Mellat payment failed with ResCode: {$resCode}";
            $this->logTransactionFailure(new VerificationFailedException($msg), $params, $transaction);
            throw new VerificationFailedException($msg);
        }

        $terminalId = $this->config['terminal_id'] ?? '';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        $soapEnvelope = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <bpVerifyRequest xmlns="http://interfaces.core.section.bankmellat.ir/">
      <terminalId>' . $terminalId . '</terminalId>
      <userName>' . $username . '</userName>
      <userPassword>' . $password . '</userPassword>
      <orderId>' . $saleOrderId . '</orderId>
      <saleOrderId>' . $saleOrderId . '</saleOrderId>
      <saleReferenceId>' . $saleReferenceId . '</saleReferenceId>
    </bpVerifyRequest>
  </soap:Body>
</soap:Envelope>';

        try {
            $response = Http::withBody($soapEnvelope, 'text/xml; charset=utf-8')
                ->post('https://bpm.shaparak.ir/pgwchannel/services/pgw');

            $xml = @simplexml_load_string($response->body());
            if ($xml === false) {
                throw new VerificationFailedException("Invalid XML response received from Mellat verification API.");
            }

            $verifyResult = (string) ($xml->xpath('//return')[0] ?? '');

            if ($verifyResult === '0' || $verifyResult === '43') {
                $amount = $transaction ? $transaction->amount : $this->amount;

                $receipt = new Receipt(
                    success: true,
                    transactionId: $refId,
                    refId: $saleReferenceId,
                    gateway: $this->getDriverName(),
                    amount: $amount,
                    message: 'Mellat payment verified successfully.',
                    rawResponse: ['verify_result' => $verifyResult, 'sale_reference_id' => $saleReferenceId]
                );

                $this->logTransactionSuccess($receipt, $transaction);
                return $receipt;
            }

            $msg = "Mellat verify failed with code: {$verifyResult}";
            $this->logTransactionFailure(new VerificationFailedException($msg), ['verify_result' => $verifyResult], $transaction);
            throw new VerificationFailedException($msg);
        } catch (\Throwable $e) {
            if (! ($e instanceof VerificationFailedException)) {
                $this->logTransactionFailure($e, [], $transaction);
                throw new VerificationFailedException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }
}
