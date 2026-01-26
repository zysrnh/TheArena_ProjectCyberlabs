<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\FaspayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $faspay;

    public function __construct(FaspayService $faspay)
    {
        $this->faspay = $faspay;
    }

    /**
     * ✅ Process payment untuk booking - WITH ENHANCED LOGGING
     */
    public function process(Request $request, $bookingId)
    {
        // ✅ LOG 1: START
        Log::info('🎯 ===== PAYMENT PROCESS START =====');
        Log::info('📊 Initial Data', [
            'booking_id' => $bookingId,
            'user_id' => auth('client')->id(),
            'user_name' => auth('client')->user()?->name,
            'timestamp' => now(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
        ]);

        try {
            // ✅ LOG 2: TEST DATABASE CONNECTION
            Log::info('🔌 Testing database connection...');
            try {
                DB::connection()->getPdo();
                $dbName = DB::connection()->getDatabaseName();
                Log::info('✅ Database connected successfully', [
                    'database' => $dbName,
                    'driver' => config('database.default'),
                ]);
            } catch (\Exception $e) {
                Log::error('❌ DATABASE CONNECTION FAILED', [
                    'error' => $e->getMessage(),
                    'driver' => config('database.default'),
                    'host' => config('database.connections.mysql.host'),
                    'database' => config('database.connections.mysql.database'),
                ]);
                
                return redirect()->route('profile')->with('error', 'Koneksi database gagal. Silakan hubungi admin.');
            }

            // ✅ LOG 3: FETCH BOOKING
            Log::info('📦 Fetching booking data...');
            $booking = Booking::with('client')->findOrFail($bookingId);
            
            Log::info('✅ Booking found', [
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
                'total_price' => $booking->total_price,
                'payment_status' => $booking->payment_status,
                'is_paid' => $booking->is_paid,
            ]);

            // ✅ LOG 4: AUTH CHECK
           if ((int)$booking->client_id !== auth('client')->id()) {
                Log::warning('⚠️ UNAUTHORIZED ACCESS ATTEMPT', [
                    'booking_id' => $bookingId,
                    'booking_client_id' => $booking->client_id,
                    'current_user_id' => auth('client')->id(),
                ]);
                return redirect()->route('profile')->with('error', 'Unauthorized');
            }
            Log::info('✅ Authorization passed');

            // ✅ LOG 5: PAYMENT STATUS CHECK
            if ($booking->isPaid()) {
                Log::info('ℹ️ Booking already paid', [
                    'booking_id' => $bookingId,
                    'paid_at' => $booking->paid_at,
                ]);
                return redirect()->route('profile')->with('info', 'Booking ini sudah dibayar');
            }
            Log::info('✅ Payment status check passed');

            // ✅ LOG 6: PREPARE PAYMENT DATA
            $orderId = (string) $booking->id;
            $amount = (int) $booking->total_price;

            $customerData = [
                'name'  => $booking->client->name ?? 'Customer',
                'email' => $booking->client->email ?? '',
                'phone' => $booking->client->phone ?? '',
            ];

            $venueType = match ($booking->venue_type) {
                'full_court' => 'Full Court',
                'half_court' => 'Half Court',
                default => 'Lapangan Basket',
            };

            $quantity = count($booking->time_slots ?? []);
            $pricePerItem = $quantity > 0 ? (int)($amount / $quantity) : $amount;

            $items = [
                [
                    'name'     => "Booking {$venueType} - " . $booking->booking_date->format('d/m/Y'),
                    'quantity' => $quantity,
                    'price'    => $pricePerItem,
                ],
            ];

            Log::info('🏀 Creating Faspay Payment', [
                'booking_id'   => $bookingId,
                'order_id'     => $orderId,
                'amount'       => $amount,
                'customer'     => $customerData['name'],
                'customer_email' => $customerData['email'],
                'venue_type'   => $booking->venue_type,
                'booking_date' => $booking->booking_date,
                'items_count'  => count($items),
            ]);

            // ✅ LOG 7: CALL FASPAY SERVICE
            Log::info('📞 Calling Faspay service...');
            $result = $this->faspay->createPayment($orderId, $amount, $customerData, $items);
            
            Log::info('📬 Faspay service response', [
                'success' => $result['success'] ?? false,
                'has_redirect_url' => isset($result['redirect_url']),
                'has_bill_no' => isset($result['bill_no']),
                'has_trx_id' => isset($result['trx_id']),
                'result_keys' => array_keys($result),
            ]);

            if ($result['success'] && isset($result['redirect_url'])) {
                // ✅ LOG 8: UPDATE BOOKING
                Log::info('💾 Updating booking with payment info...');
                
                $booking->update([
                    'bill_no'        => $result['bill_no'],
                    'trx_id'         => $result['trx_id'] ?? null,
                    'payment_status' => 'pending',
                ]);

                $booking->refresh();
                
                Log::info('✅✅✅ BOOKING UPDATED SUCCESSFULLY', [
                    'booking_id' => $bookingId,
                    'bill_no'    => $booking->bill_no,
                    'trx_id'     => $booking->trx_id,
                    'payment_status' => $booking->payment_status,
                    'redirect'   => $result['redirect_url'],
                ]);
                
                Log::info('🚀 Redirecting to Faspay...');
                Log::info('🎯 ===== PAYMENT PROCESS END (SUCCESS) =====');

                return redirect()->away($result['redirect_url']);
            }

            // ✅ LOG 9: FASPAY ERROR
            $errorMessage = $result['error'] ?? $result['technical_error'] ?? 'Unknown error';

            Log::error('❌ FASPAY PAYMENT CREATION FAILED', [
                'booking_id' => $bookingId,
                'error'      => $errorMessage,
                'result'     => $result,
            ]);

            Log::info('🎯 ===== PAYMENT PROCESS END (FASPAY ERROR) =====');

            return redirect()->route('profile')->with('error', 'Gagal membuat pembayaran: ' . $errorMessage);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ BOOKING NOT FOUND', [
                'booking_id' => $bookingId,
                'message' => $e->getMessage(),
            ]);
            
            Log::info('🎯 ===== PAYMENT PROCESS END (NOT FOUND) =====');
            
            return redirect()->route('profile')->with('error', 'Booking tidak ditemukan');
            
        } catch (\Exception $e) {
            Log::error('💥💥💥 PAYMENT PROCESS EXCEPTION', [
                'booking_id' => $bookingId,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            Log::info('🎯 ===== PAYMENT PROCESS END (EXCEPTION) =====');

            return redirect()->route('profile')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Callback dari Faspay (server-to-server)
     */
    public function callback(Request $request)
    {
        Log::info('🔔 ===== FASPAY CALLBACK START =====');
        Log::info('📡 Request Details', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);
        Log::info('📦 Raw Input', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        try {
            // ⭐ VALIDATE INPUT
            $validated = $request->validate([
                'bill_no' => 'required|string|max:100',
                'bill_total' => 'required|numeric',
                'trx_id' => 'required|string|max:100',
                'payment_status_code' => 'required|string|max:10',
                'payment_channel' => 'nullable|string|max:50',
                'payment_channel_uid' => 'nullable|string|max:50',
                'payment_reff' => 'nullable|string|max:100',
                'payment_date' => 'nullable|date_format:Y-m-d H:i:s',
                'payment_status_desc' => 'nullable|string|max:255',
                'signature' => 'required|string|max:255',
            ]);

            // Extract validated data
            $billNo            = $validated['bill_no'];
            $billTotal         = $validated['bill_total'];
            $paymentStatusCode = $validated['payment_status_code'];
            $paymentChannel    = $validated['payment_channel'] ?? null;
            $paymentChannelUid = $validated['payment_channel_uid'] ?? null;
            $trxId             = $validated['trx_id'];
            $paymentReff       = $validated['payment_reff'] ?? null;
            $paymentDate       = $validated['payment_date'] ?? null;
            $paymentStatusDesc = $validated['payment_status_desc'] ?? null;
            $signature         = $validated['signature'];

            Log::info('📋 Callback Data', [
                'bill_no'              => $billNo,
                'bill_total'           => $billTotal,
                'payment_status_code'  => $paymentStatusCode,
                'payment_channel'      => $paymentChannel,
                'trx_id'               => $trxId,
                'signature'            => $signature,
            ]);

            // Verify signature
            $signatureValid = $this->faspay->verifySignature($request->all());

            Log::info('🔐 Signature Check', [
                'valid' => $signatureValid,
                'received' => $signature,
            ]);

            if (!$signatureValid) {
                Log::error('❌ INVALID SIGNATURE', [
                    'bill_no' => $billNo,
                    'bill_total' => $billTotal,
                    'received_signature' => $signature,
                ]);
                
                return response()->json([
                    'response' => 'Payment Notification',
                    'response_code' => '05',
                    'response_desc' => 'Invalid signature',
                    'response_date' => now()->format('Y-m-d H:i:s'),
                ], 400);
            }

            // Map payment status
            $paymentStatus = $this->mapPaymentStatus($paymentStatusCode);

            Log::info('📋 Status Mapping', [
                'code' => $paymentStatusCode,
                'mapped' => $paymentStatus,
            ]);

            // START TRANSACTION WITH PESSIMISTIC LOCK
            DB::beginTransaction();

            try {
                // 🔒 PESSIMISTIC LOCK
                $booking = Booking::where('bill_no', $billNo)
                    ->lockForUpdate()
                    ->first();

                if (!$booking) {
                    DB::rollBack();
                    
                    Log::error('❌ BOOKING NOT FOUND', ['bill_no' => $billNo]);
                    
                    return response()->json([
                        'response' => 'Payment Notification',
                        'response_code' => '14',
                        'response_desc' => 'Booking not found',
                        'response_date' => now()->format('Y-m-d H:i:s'),
                    ], 404);
                }

                // ⭐ IDEMPOTENCY CHECK
                if ($booking->payment_status === 'paid' && $booking->trx_id === $trxId) {
                    DB::rollBack();
                    
                    Log::info('⚠️ DUPLICATE CALLBACK - Already processed', [
                        'bill_no' => $billNo,
                        'trx_id' => $trxId,
                        'existing_payment_status' => $booking->payment_status,
                        'existing_paid_at' => $booking->paid_at,
                    ]);
                    
                    // Return success untuk prevent Faspay retry
                    return response()->json([
                        'response' => 'Payment Notification',
                        'trx_id' => $trxId,
                        'merchant_id' => config('faspay.merchant_id'),
                        'merchant' => 'The Arena',
                        'bill_no' => $billNo,
                        'response_code' => '00',
                        'response_desc' => 'Already processed (idempotent)',
                        'response_date' => now()->format('Y-m-d H:i:s'),
                    ], 200);
                }

                // Validate amount
                if ($billTotal && (int)$billTotal !== (int)$booking->total_price) {
                    Log::warning('⚠️ AMOUNT MISMATCH', [
                        'bill_no' => $billNo,
                        'expected' => $booking->total_price,
                        'received' => $billTotal,
                    ]);
                }

                Log::info('🔍 BEFORE UPDATE', [
                    'booking_id' => $booking->id,
                    'current_payment_status' => $booking->payment_status,
                    'current_is_paid' => $booking->is_paid,
                    'current_status' => $booking->status,
                ]);

                // Update booking with all Faspay fields
                $booking->trx_id = $trxId;
                $booking->payment_method = $paymentChannel ?? 'Unknown';
                $booking->payment_status = $paymentStatus;
                $booking->is_paid = ($paymentStatus === 'paid') ? 1 : 0;
                $booking->paid_at = ($paymentStatus === 'paid') ? now() : null;
                $booking->status = ($paymentStatus === 'paid') ? 'confirmed' : $booking->status;
                
                // Save additional Faspay data
                $booking->payment_reff = $paymentReff;
                $booking->payment_date = $paymentDate ? \Carbon\Carbon::parse($paymentDate) : null;
                $booking->payment_status_code = $paymentStatusCode;
                $booking->payment_status_desc = $paymentStatusDesc;
                $booking->payment_channel_uid = $paymentChannelUid;
                $booking->payment_channel = $paymentChannel;
                
                $saved = $booking->save();

                Log::info('💾 Save Result', ['saved' => $saved]);

                DB::commit();

                // Refresh and verify
                $booking = $booking->fresh();

                Log::info('✅✅✅ AFTER UPDATE SUCCESS ✅✅✅', [
                    'booking_id'           => $booking->id,
                    'bill_no'              => $booking->bill_no,
                    'trx_id'               => $booking->trx_id,
                    'payment_status'       => $booking->payment_status,
                    'is_paid'              => $booking->is_paid,
                    'status'               => $booking->status,
                    'paid_at'              => $booking->paid_at,
                    'payment_reff'         => $booking->payment_reff,
                    'payment_date'         => $booking->payment_date,
                    'payment_status_code'  => $booking->payment_status_code,
                    'payment_status_desc'  => $booking->payment_status_desc,
                    'payment_channel_uid'  => $booking->payment_channel_uid,
                    'payment_channel'      => $booking->payment_channel,
                    'isPaid_method'        => $booking->isPaid(),
                ]);

                Log::info('🔔 ===== FASPAY CALLBACK END (SUCCESS) =====');

                return response()->json([
                    'response' => 'Payment Notification',
                    'trx_id' => $trxId,
                    'merchant_id' => config('faspay.merchant_id'),
                    'merchant' => 'The Arena',
                    'bill_no' => $billNo,
                    'response_code' => '00',
                    'response_desc' => 'Success',
                    'response_date' => now()->format('Y-m-d H:i:s'),
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ VALIDATION FAILED', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            Log::info('🔔 ===== FASPAY CALLBACK END (VALIDATION ERROR) =====');

            return response()->json([
                'response' => 'Payment Notification',
                'response_code' => '96',
                'response_desc' => 'Invalid request data',
                'response_date' => now()->format('Y-m-d H:i:s'),
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('💥 CALLBACK ERROR', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            Log::info('🔔 ===== FASPAY CALLBACK END (ERROR) =====');

            return response()->json([
                'response' => 'Payment Notification',
                'response_code' => '96',
                'response_desc' => 'System error',
                'response_date' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }

    /**
     * ✅ Return URL (user kembali dari Faspay) - UPDATED WITH CHANNEL INFO
     */
    public function return(Request $request)
    {
        try {
            $billNo = $request->query('bill_no');
            $status = $request->query('status');
            $trxId = $request->query('trx_id');
            $billTotal = $request->query('bill_total');
            $paymentReff = $request->query('payment_reff');
            $paymentDate = $request->query('payment_date');
            $signature = $request->query('signature');
            $merchantId = $request->query('merchant_id');
            
            // ⭐ NEW: Extract payment channel info
            $bankUserName = $request->query('bank_user_name');
            $paymentChannel = $request->query('payment_channel');
            $paymentChannelUid = $request->query('payment_channel_uid');

            Log::info('📍 User Returned from Faspay', [
                'bill_no' => $billNo,
                'status' => $status,
                'trx_id' => $trxId,
                'payment_channel' => $paymentChannel,
                'bank_user_name' => $bankUserName,
                'query' => $request->query(),
            ]);

            if (!$billNo) {
                return redirect()->route('profile')->with('info', 'Menunggu konfirmasi pembayaran.');
            }

            $booking = Booking::where('bill_no', $billNo)->first();

            if (!$booking) {
                return redirect()->route('profile')->with('error', 'Booking tidak ditemukan');
            }

            if ($booking->client_id !== auth('client')->id()) {
                return redirect()->route('profile')->with('error', 'Unauthorized');
            }

            // ✅ FALLBACK: Jika status=2 (paid) tapi callback belum masuk
            if ($status === '2' && $booking->payment_status !== 'paid') {
                Log::warning('⚠️ FALLBACK: Payment success di return URL tapi callback belum masuk', [
                    'bill_no' => $billNo,
                    'status' => $status,
                    'booking_id' => $booking->id,
                    'trx_id' => $trxId,
                ]);
                
                $signatureValid = $this->faspay->verifySignature($request->query());
                
                if (!$signatureValid) {
                    Log::error('❌ FALLBACK: Invalid signature on return URL', [
                        'bill_no' => $billNo,
                        'signature' => $signature,
                        'all_data' => $request->query(),
                    ]);
                    
                    return redirect()->route('profile', ['tab' => 'jadwal-booking'])
                        ->with('warning', '⚠️ Pembayaran sedang diverifikasi. Mohon tunggu beberapa saat atau refresh halaman.');
                }

                Log::info('✅ FALLBACK: Signature valid, proceeding with update', [
                    'bill_no' => $billNo,
                    'trx_id' => $trxId,
                ]);

                // Update booking langsung dari return URL (signature sudah valid)
                DB::beginTransaction();
                try {
                    $booking->payment_status = 'paid';
                    $booking->is_paid = true;
                    $booking->paid_at = now();
                    $booking->status = 'confirmed';
                    $booking->trx_id = $trxId;
                    $booking->payment_reff = $paymentReff;
                    $booking->payment_date = $paymentDate ? \Carbon\Carbon::parse($paymentDate) : null;
                    $booking->payment_status_code = '2';
                    $booking->payment_status_desc = 'Payment Sukses';
                    
                    // ⭐ NEW: Set payment channel info dengan fallback
                    $booking->payment_channel = $paymentChannel ?? 'Faspay Xpress';
                    $booking->payment_channel_uid = $paymentChannelUid ?? $bankUserName ?? $trxId;
                    $booking->payment_method = $paymentChannel ?? 'Faspay';
                    
                    $booking->save();
                    
                    DB::commit();
                    
                    Log::info('✅ FALLBACK: Payment updated from return URL (signature verified)', [
                        'booking_id' => $booking->id,
                        'bill_no' => $billNo,
                        'trx_id' => $trxId,
                        'payment_channel' => $booking->payment_channel,
                        'payment_channel_uid' => $booking->payment_channel_uid,
                        'payment_method' => $booking->payment_method,
                        'source' => 'return_url_fallback',
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Log::error('❌ FALLBACK: Failed to update from return URL', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return redirect()->route('profile', ['tab' => 'jadwal-booking'])
                        ->with('error', 'Gagal memproses pembayaran. Silakan hubungi admin.');
                }
            }

            // Refresh booking untuk dapat data terbaru
            $booking = $booking->fresh();
            $isPaid = $booking->isPaid();

            Log::info('📊 Payment Status on Return', [
                'booking_id' => $booking->id,
                'bill_no' => $booking->bill_no,
                'is_paid' => $isPaid,
                'payment_status' => $booking->payment_status,
                'payment_channel' => $booking->payment_channel,
                'status' => $booking->status,
            ]);

            if ($isPaid) {
                return redirect()->route('profile', ['tab' => 'jadwal-booking'])
                    ->with('success', '✅ Pembayaran berhasil! Booking Anda telah dikonfirmasi.');
            }

            // Jika belum paid, tunggu callback
            return redirect()->route('profile', ['tab' => 'jadwal-booking'])
                ->with('info', '⏳ Pembayaran sedang diproses. Refresh halaman dalam beberapa saat.');
                
        } catch (\Exception $e) {
            Log::error('💥 Payment Return Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('profile')->with('error', 'Terjadi kesalahan');
        }
    }

    /**
     * Map payment status code ke internal status
     */
    protected function mapPaymentStatus(string $statusCode): string
    {
        return match ($statusCode) {
            '2'     => 'paid',
            '1'     => 'pending',
            '3'     => 'failed',
            '7'     => 'expired',
            '8'     => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * ✅ Check payment status by transaction_id
     */
    public function checkStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'transaction_id' => 'required|string',
            ]);

            $trxId = $validated['transaction_id'];

            Log::info('🔍 Check Payment Status Request', [
                'transaction_id' => $trxId,
                'ip' => $request->ip(),
            ]);

            $booking = Booking::where('trx_id', $trxId)->first();

            if (!$booking) {
                Log::warning('❌ Transaction not found', ['trx_id' => $trxId]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Transaction not found'
                ], 404);
            }

            Log::info('✅ Transaction found', [
                'booking_id' => $booking->id,
                'bill_no' => $booking->bill_no,
                'payment_status' => $booking->payment_status,
            ]);

            return response()->json([
                'success' => true,
                'transaction_id' => $booking->trx_id,
                'bill_no' => $booking->bill_no,
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->status,
                'total_amount' => $booking->total_price,
                'booking_date' => $booking->booking_date,
                'created_at' => $booking->created_at,
                'is_paid' => $booking->isPaid(),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('💥 Check status error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }
}