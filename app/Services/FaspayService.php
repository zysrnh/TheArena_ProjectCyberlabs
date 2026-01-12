<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FaspayService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('faspay');
    }

    /**
     * ✅ Membuat transaksi baru ke Faspay Xpress v4
     */
    public function createPayment(string $orderId, int $amount, array $customerData, array $items): array
    {
        $billNo      = $this->generateBillNo();
        $billTotal   = $amount;
        $billDate    = Carbon::now();
        $billExpired = Carbon::now()->addMinutes($this->config['payment_timeout']);

        $payload = [
            'merchant_id'   => $this->config['merchant_id'],
            'bill_no'       => $billNo,
            'bill_date'     => $billDate->format('Y-m-d H:i:s'),
            'bill_expired'  => $billExpired->format('Y-m-d H:i:s'),
            'bill_desc'     => 'Pembayaran Booking Lapangan Basketball - The Arena',
            'bill_currency' => $this->config['currency'],
            'bill_gross'    => (string) $billTotal,
            'bill_miscfee'  => '0',
            'bill_total'    => (string) $billTotal,

            // ✅ Customer
            'cust_no'    => (string) ($customerData['phone'] ?? '0'),
            'cust_name'  => (string) ($customerData['name'] ?? 'Customer'),
            'msisdn'     => (string) ($customerData['phone'] ?? ''),
            'email'      => (string) ($customerData['email'] ?? ''),
            'cust_phone' => (string) ($customerData['phone'] ?? ''),

            'bill_reff'  => $orderId,
            
            // ✅ PENTING: Callback & Return URL
            'callback_url' => $this->config['callback_url'],
            'return_url'   => $this->config['return_url'] ?? route('payment.faspay.return'),

            // ✅ Item detail
            'item' => array_map(function ($it, $i) {
                return [
                    'product' => $it['name']    ?? ('Item ' . ($i + 1)),
                    'qty'     => (string)($it['quantity'] ?? 1),
                    'amount'  => (string)($it['price'] ?? 0),
                ];
            }, $items, array_keys($items)),
        ];

        // ✅ Signature
        $payload['signature'] = $this->signCreate($billNo, (string) $billTotal);

        Log::info('📤 FASPAY REQUEST', [
            'url'     => $this->config['base_url'],
            'payload' => $payload,
        ]);

        try {
            // ✅ IMPROVED: Tambahkan error handling & retry logic
            $resp = Http::timeout(45)
                ->retry(2, 100) // Retry 2x dengan delay 100ms
                ->withOptions([
                    'verify' => false, // ⚠️ Hanya untuk sandbox! Hapus di production
                ])
                ->asJson()
                ->post($this->config['base_url'], $payload);

            // ✅ Log raw response untuk debugging
            Log::info('📥 FASPAY RAW RESPONSE', [
                'status'  => $resp->status(),
                'headers' => $resp->headers(),
                'body'    => $resp->body(),
            ]);

            // ✅ Handle HTTP errors
            if ($resp->failed()) {
                $errorBody = $resp->body();
                Log::error('❌ FASPAY HTTP ERROR', [
                    'status' => $resp->status(),
                    'body'   => $errorBody,
                ]);
                throw new \Exception("Faspay HTTP Error {$resp->status()}: {$errorBody}");
            }

            // ✅ Parse JSON response
            $result = $resp->json() ?? [];
            
            // ✅ Validate response structure
            if (empty($result)) {
                throw new \Exception('Faspay returned empty response');
            }

            Log::info('📥 FASPAY PARSED RESPONSE', ['result' => $result]);

            // ✅ Check response code
            $responseCode = $result['response_code'] ?? '';
            $responseDesc = $result['response_desc'] ?? 'Unknown error';

            if ($responseCode !== '00') {
                throw new \Exception("Faspay Error [{$responseCode}]: {$responseDesc}");
            }

            // ✅ Generate trx_id fallback
            $trxId = $result['trx_id'] ?? null;
            
            if (empty($trxId)) {
                $trxId = 'TEMP_' . $billNo;
                Log::warning('⚠️ Faspay tidak return trx_id, menggunakan temporary ID', [
                    'bill_no' => $billNo,
                    'temp_trx_id' => $trxId,
                ]);
            }

            // ✅ Return success response
            return [
                'success'          => true,
                'trx_id'           => $trxId,
                'bill_no'          => $billNo,
                'order_id'         => $orderId,
                'amount'           => $billTotal,
                'expired_at'       => $billExpired,
                'redirect_url'     => $result['redirect_url'] ?? null,
                'payment_channels' => [[
                    'channel_code' => 'XPRS',
                    'channel_name' => $this->config['is_production']
                        ? 'Faspay Xpress (Production)'
                        : 'Faspay Xpress (Sandbox)',
                    'payment_url'  => $result['redirect_url'] ?? null,
                ]],
                'is_development'   => !$this->config['is_production'],
                'raw_response'     => $result, // Untuk debugging
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // ✅ Handle connection errors specifically
            Log::error('💥 FASPAY CONNECTION ERROR', [
                'message' => $e->getMessage(),
                'url'     => $this->config['base_url'],
            ]);
            
            return [
                'success' => false,
                'error'   => 'Tidak dapat terhubung ke server Faspay. Silakan coba lagi.',
                'technical_error' => $e->getMessage(),
            ];
            
        } catch (\Throwable $e) {
            Log::error('💥 FASPAY EXCEPTION', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * ✅ Signature untuk createPayment()
     */
    protected function signCreate(string $billNo, string $billTotal): string
    {
        $raw = $this->config['user_id'] . $this->config['password'] . $billNo . $billTotal;
        $signature = sha1(md5($raw));

        Log::debug('🧾 Generated Signature (Create)', [
            'user_id'   => $this->config['user_id'],
            'password'  => str_repeat('*', strlen($this->config['password'])), // Hide password in logs
            'bill_no'   => $billNo,
            'bill_total' => $billTotal,
            'raw'       => md5($raw), // Log MD5 hash only
            'signature' => $signature,
        ]);

        return $signature;
    }

    /**
     * ✅ Verifikasi signature pada callback
     */
    public function verifySignature(array $data): bool
    {
        $billNo = (string) ($data['bill_no'] ?? '');
        $status = (string) ($data['payment_status_code'] ?? $data['payment_status'] ?? '');
        $requestSignature = (string) ($data['signature'] ?? '');

        if ($billNo === '' || $status === '' || $requestSignature === '') {
            Log::warning('⚠️ verifySignature: field kosong', compact('billNo', 'status', 'requestSignature'));
            return false;
        }

        // ✅ Formula callback: sha1(md5(user_id + password + bill_no + payment_status_code))
        $rawString = $this->config['user_id'] . 
                     $this->config['password'] . 
                     $billNo . 
                     $status;
        
        $md5Hash = md5($rawString);
        $calculated = sha1($md5Hash);

        $valid = hash_equals($calculated, $requestSignature);

        if (!$valid) {
            // ✅ LOG DETAIL DEBUG
            Log::warning('⚠️ Signature mismatch - DETAIL DEBUG', [
                'user_id'           => $this->config['user_id'],
                'password_length'   => strlen($this->config['password']),
                'bill_no'           => $billNo,
                'status'            => $status,
                'raw_string'        => $rawString,
                'md5_hash'          => $md5Hash,
                'calculated_sha1'   => $calculated,
                'received_signature' => $requestSignature,
                'all_callback_data' => $data,
            ]);
        } else {
            Log::info('✅ Signature verified', ['bill_no' => $billNo, 'status' => $status]);
        }

        return $valid;
    }

    /**
     * ✅ Generate bill number unik
     */
    protected function generateBillNo(): string
    {
        // Format: ARENA-YYYYMMDDHHMMSS-XXXX
        // Contoh: ARENA-20251205143022-5847
        return 'ARENA' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * (Opsional) Check status manual
     */
    public function checkPaymentStatus(string $trxId): array
    {
        return [
            'success' => false,
            'message' => 'Status check via API tidak tersedia; gunakan callback Faspay.',
        ];
    }

    /**
     * ✅ Ambil daftar channel pembayaran
     */
    public function getAvailableChannels(): array
    {
        return $this->config['channels'] ?? [];
    }

    /**
     * ✅ Format rupiah
     */
    public function formatAmount(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}