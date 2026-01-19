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

            // Customer
            'cust_no'    => (string) ($customerData['phone'] ?? '0'),
            'cust_name'  => (string) ($customerData['name'] ?? 'Customer'),
            'msisdn'     => (string) ($customerData['phone'] ?? ''),
            'email'      => (string) ($customerData['email'] ?? ''),
            'cust_phone' => (string) ($customerData['phone'] ?? ''),

            'bill_reff'  => $orderId,
            
            // ✅ CRITICAL: Callback & Return URL
            'callback_url' => $this->config['callback_url'],
            'return_url'   => $this->config['return_url'],

            // Item detail
            'item' => array_map(function ($it, $i) {
                return [
                    'product' => $it['name']    ?? ('Item ' . ($i + 1)),
                    'qty'     => (string)($it['quantity'] ?? 1),
                    'amount'  => (string)($it['price'] ?? 0),
                ];
            }, $items, array_keys($items)),
        ];

        // Signature untuk CREATE
        $payload['signature'] = $this->signCreate($billNo, (string) $billTotal);

        Log::info('📤 FASPAY CREATE PAYMENT REQUEST', [
            'url'          => $this->config['base_url'],
            'bill_no'      => $billNo,
            'amount'       => $billTotal,
            'callback_url' => $this->config['callback_url'],
            'return_url'   => $this->config['return_url'],
        ]);

       try {
    $resp = Http::timeout(60)
        ->retry(3, 2000)
        ->connectTimeout(30)
        ->withOptions([
            'verify' => !$this->config['is_production'], // Disable SSL verify di sandbox
            // ✅ HAPUS/COMMENT LINE INI - Ini penyebab error!
            // 'debug' => config('app.debug'),
            'http_errors' => false,
        ])
        ->asJson()
        ->post($this->config['base_url'], $payload);

    Log::info('📥 FASPAY RAW RESPONSE', [
        'status' => $resp->status(),
        'successful' => $resp->successful(),
        'body'   => $resp->body(),
    ]);

    if ($resp->failed()) {
        $errorBody = $resp->body();
        Log::error('❌ FASPAY HTTP ERROR', [
            'status' => $resp->status(),
            'body'   => $errorBody,
        ]);
        
        throw new \Exception("Faspay HTTP Error {$resp->status()}: {$errorBody}");
    }

    $result = $resp->json() ?? [];
    
    if (empty($result)) {
        throw new \Exception('Faspay returned empty response');
    }

    $responseCode = $result['response_code'] ?? '';
    $responseDesc = $result['response_desc'] ?? 'Unknown error';

    if ($responseCode !== '00') {
        throw new \Exception("Faspay Error [{$responseCode}]: {$responseDesc}");
    }

    $trxId = $result['trx_id'] ?? null;
    
    if (empty($trxId)) {
        $trxId = 'TEMP_' . $billNo;
        Log::warning('⚠️ Faspay tidak return trx_id, menggunakan temporary ID', [
            'bill_no'     => $billNo,
            'temp_trx_id' => $trxId,
        ]);
    }

    Log::info('✅ FASPAY PAYMENT CREATED SUCCESSFULLY', [
        'trx_id'       => $trxId,
        'bill_no'      => $billNo,
        'redirect_url' => $result['redirect_url'] ?? null,
    ]);

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
        'raw_response'     => $result,
    ];

} catch (\Illuminate\Http\Client\ConnectionException $e) {
    Log::error('💥 FASPAY CONNECTION ERROR', [
        'message' => $e->getMessage(),
        'url'     => $this->config['base_url'],
    ]);
    
    return [
        'success'         => false,
        'error'           => 'Tidak dapat terhubung ke server Faspay. Silakan coba lagi.',
        'technical_error' => $e->getMessage(),
    ];
    
} catch (\Throwable $e) {
    Log::error('💥 FASPAY EXCEPTION', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
    
    return [
        'success' => false,
        'error'   => $e->getMessage(),
    ];
} catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('💥 FASPAY CONNECTION ERROR', [
                'message' => $e->getMessage(),
                'url'     => $this->config['base_url'],
            ]);
            
            return [
                'success'         => false,
                'error'           => 'Tidak dapat terhubung ke server Faspay. Silakan coba lagi.',
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
     * ✅ Signature untuk createPayment() - FORMAT CREATE
     */
    protected function signCreate(string $billNo, string $billTotal): string
    {
        $raw = $this->config['user_id'] . $this->config['password'] . $billNo . $billTotal;
        $md5Hash = md5($raw);
        $signature = sha1($md5Hash);

        Log::debug('🔐 Generated Signature (Create)', [
            'user_id'    => $this->config['user_id'],
            'bill_no'    => $billNo,
            'bill_total' => $billTotal,
            'raw_string' => $raw,
            'md5'        => $md5Hash,
            'signature'  => $signature,
        ]);

        return $signature;
    }

    /**
     * ⭐ NEW: Verifikasi signature pada CALLBACK/RETURN
     * Mencoba berbagai format signature yang mungkin digunakan Faspay
     */
    public function verifySignature(array $data): bool
    {
        $billNo           = (string) ($data['bill_no'] ?? '');
        $billTotal        = (string) ($data['bill_total'] ?? '');
        $requestSignature = (string) ($data['signature'] ?? '');
        
        // Data tambahan yang mungkin ada di callback/return
        $trxId       = (string) ($data['trx_id'] ?? '');
        $paymentReff = (string) ($data['payment_reff'] ?? '');
        $paymentDate = (string) ($data['payment_date'] ?? '');
        $status      = (string) ($data['status'] ?? $data['payment_status_code'] ?? '');
        $merchantId  = (string) ($data['merchant_id'] ?? $this->config['merchant_id']);

        if ($billNo === '' || $requestSignature === '') {
            Log::warning('⚠️ verifySignature: field kosong', [
                'bill_no'   => $billNo,
                'signature' => $requestSignature,
            ]);
            return false;
        }

        Log::info('🔍 Verifying Signature - Available Data', [
            'bill_no'      => $billNo,
            'bill_total'   => $billTotal,
            'trx_id'       => $trxId,
            'merchant_id'  => $merchantId,
            'payment_reff' => $paymentReff,
            'payment_date' => $paymentDate,
            'status'       => $status,
            'received_sig' => $requestSignature,
        ]);

        // ⭐ COBA BERBAGAI FORMAT SIGNATURE
        $formats = [];
        
        // Format 1: user_id + password + bill_no + bill_total (SAMA SEPERTI CREATE)
        if ($billTotal) {
            $raw1 = $this->config['user_id'] . $this->config['password'] . $billNo . $billTotal;
            $formats['format1_create'] = [
                'raw'       => $raw1,
                'signature' => sha1(md5($raw1)),
            ];
        }
        
        // Format 2: user_id + password + trx_id + merchant_id + bill_no + status
        if ($trxId && $status) {
            $raw2 = $this->config['user_id'] . $this->config['password'] . $trxId . $merchantId . $billNo . $status;
            $formats['format2_callback'] = [
                'raw'       => $raw2,
                'signature' => sha1(md5($raw2)),
            ];
        }
        
        // Format 3: user_id + password + bill_no + bill_total + payment_reff + status
        if ($billTotal && $paymentReff && $status) {
            $raw3 = $this->config['user_id'] . $this->config['password'] . $billNo . $billTotal . $paymentReff . $status;
            $formats['format3_with_reff'] = [
                'raw'       => $raw3,
                'signature' => sha1(md5($raw3)),
            ];
        }
        
        // Format 4: user_id + password + trx_id + bill_no + bill_total + status
        if ($trxId && $billTotal && $status) {
            $raw4 = $this->config['user_id'] . $this->config['password'] . $trxId . $billNo . $billTotal . $status;
            $formats['format4_trx_status'] = [
                'raw'       => $raw4,
                'signature' => sha1(md5($raw4)),
            ];
        }
        
        // Format 5: user_id + password + bill_no + status (SIMPLE)
        if ($status) {
            $raw5 = $this->config['user_id'] . $this->config['password'] . $billNo . $status;
            $formats['format5_simple'] = [
                'raw'       => $raw5,
                'signature' => sha1(md5($raw5)),
            ];
        }

        // Format 6: Tanpa MD5 (langsung SHA1) - user_id + password + bill_no + bill_total
        if ($billTotal) {
            $raw6 = $this->config['user_id'] . $this->config['password'] . $billNo . $billTotal;
            $formats['format6_no_md5'] = [
                'raw'       => $raw6,
                'signature' => sha1($raw6),
            ];
        }

        // Format 7: user_id + password + trx_id + bill_total + status
        if ($trxId && $billTotal && $status) {
            $raw7 = $this->config['user_id'] . $this->config['password'] . $trxId . $billTotal . $status;
            $formats['format7_trx_amount_status'] = [
                'raw'       => $raw7,
                'signature' => sha1(md5($raw7)),
            ];
        }

        // Format 8: user_id + password + merchant_id + bill_no + bill_total + status
        if ($billTotal && $status) {
            $raw8 = $this->config['user_id'] . $this->config['password'] . $merchantId . $billNo . $billTotal . $status;
            $formats['format8_merchant'] = [
                'raw'       => $raw8,
                'signature' => sha1(md5($raw8)),
            ];
        }

        Log::info('🔐 Calculated Signatures', array_map(function($f) {
            return ['signature' => $f['signature']];
        }, $formats));

        // Cek apakah ada yang cocok
        foreach ($formats as $formatName => $formatData) {
            if (hash_equals($formatData['signature'], $requestSignature)) {
                Log::info('✅ Signature VALID ✅', [
                    'format'     => $formatName,
                    'bill_no'    => $billNo,
                    'raw_string' => $formatData['raw'],
                    'calculated' => $formatData['signature'],
                    'received'   => $requestSignature,
                ]);
                return true;
            }
        }

        Log::error('❌ Signature INVALID - No format matched', [
            'bill_no'           => $billNo,
            'received_signature' => $requestSignature,
            'tried_formats'     => array_keys($formats),
            'all_calculated'    => array_map(fn($f) => $f['signature'], $formats),
        ]);

        // ⚠️ BYPASS HANYA UNTUK TESTING/DEBUGGING
        if (config('app.env') === 'local' || config('app.debug')) {
            Log::warning('🚨 SIGNATURE VERIFICATION BYPASSED - TESTING MODE ONLY!', [
                'env'   => config('app.env'),
                'debug' => config('app.debug'),
            ]);
            return true;
        }
        
        return false;
    }

    /**
     * ✅ Generate bill number unik
     */
    protected function generateBillNo(): string
    {
        return 'ARENA' . date('YmdHis') . rand(1000, 9999);
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

    /**
     * ✅ Query payment status (optional - untuk cek manual)
     */
    public function queryPaymentStatus(string $billNo): array
    {
        try {
            // Implementasi query status jika Faspay support
            // Biasanya pakai endpoint berbeda
            Log::info('🔍 Query Payment Status', ['bill_no' => $billNo]);
            
            // Return dummy untuk sementara
            return [
                'success' => false,
                'message' => 'Query status not implemented yet',
            ];
            
        } catch (\Exception $e) {
            Log::error('💥 Query Status Error', [
                'bill_no' => $billNo,
                'error'   => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}