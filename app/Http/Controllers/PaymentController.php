<?php

namespace App\Http\Controllers;

use App\Mail\BookingPaymentNotification;
use App\Models\Booking;
use App\Services\FaspayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    protected $faspay;

    public function __construct(FaspayService $faspay)
    {
        $this->faspay = $faspay;
    }

    /**
     * ✅ Process payment untuk booking
     */
    public function process(Request $request, $bookingId)
    {
        Log::info('🎯 ===== PAYMENT PROCESS START =====');
        Log::info('📊 Initial Data', [
            'booking_id' => $bookingId,
            'user_id' => auth('client')->id(),
            'user_name' => auth('client')->user()->name ?? 'Unknown',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        try {
            $booking = Booking::with('client')->findOrFail($bookingId);

            Log::info('✅ Booking found', [
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
                'auth_id' => auth('client')->id(),
                'total_price' => $booking->total_price,
                'payment_status' => $booking->payment_status,
                'is_paid' => $booking->is_paid,
            ]);

            // 🔒 AUTHORIZATION CHECK
            if ((int)$booking->client_id !== (int)auth('client')->id()) {
                Log::warning('🚫 Authorization failed', [
                    'booking_client_id' => $booking->client_id,
                    'auth_client_id' => auth('client')->id(),
                ]);
                return redirect()->route('profile')->with('error', 'Unauthorized access to this booking');
            }
            Log::info('✅ Authorization passed');

            // ✅ CHECK IF ALREADY PAID
            if ($booking->isPaid()) {
                Log::info('ℹ️ Booking already paid');
                return redirect()->route('profile')->with('info', 'Booking ini sudah dibayar');
            }
            Log::info('✅ Payment status check passed');

            // 📝 PREPARE PAYMENT DATA
            $orderId = (string) $booking->id;
            $amount  = (int) $booking->total_price;

            $customerData = [
                'name'  => $booking->client->name ?? 'Customer',
                'email' => $booking->client->email ?? '',
                'phone' => $booking->client->phone ?? '',
            ];

            $venueType = match ($booking->venue_type) {
                'full_court' => 'Full Court',
                'half_court' => 'Half Court',
                default      => 'Lapangan Basket',
            };

            $quantity     = count($booking->time_slots ?? []);
            $pricePerItem = $quantity > 0 ? (int)($amount / $quantity) : $amount;

            $items = [
                [
                    'name'     => "Booking {$venueType} - " . $booking->booking_date->format('d/m/Y'),
                    'quantity' => $quantity,
                    'price'    => $pricePerItem,
                ],
            ];

            Log::info('📞 Calling Faspay service...');
            $result = $this->faspay->createPayment($orderId, $amount, $customerData, $items);

            Log::info('📬 Faspay service response', [
                'success'         => $result['success'] ?? false,
                'has_redirect_url' => isset($result['redirect_url']),
                'redirect_url'    => $result['redirect_url'] ?? 'not_set',
            ]);

            if ($result['success'] && isset($result['redirect_url'])) {

                $booking->update([
                    'bill_no'        => $result['bill_no'],
                    'trx_id'         => $result['trx_id'] ?? null,
                    'payment_status' => 'pending',
                ]);

                Log::info('✅ Booking updated, redirecting to Faspay...');
                Log::info('🎯 ===== PAYMENT PROCESS END (SUCCESS) =====');

                return redirect()->away($result['redirect_url']);
            }

            $errorMessage = $result['error'] ?? $result['technical_error'] ?? 'Unknown error';

            Log::error('❌ Faspay Payment Creation Failed', [
                'booking_id'  => $bookingId,
                'error'       => $errorMessage,
                'full_result' => $result,
            ]);

            Log::info('🎯 ===== PAYMENT PROCESS END (FAILED) =====');
            return redirect()->route('profile')->with('error', 'Gagal membuat pembayaran: ' . $errorMessage);

        } catch (\Exception $e) {
            Log::error('💥 Payment Process Exception', [
                'booking_id' => $bookingId,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
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
            'url'    => $request->fullUrl(),
            'ip'     => $request->ip(),
        ]);

        try {
            $validated = $request->validate([
                'bill_no'              => 'required|string|max:100',
                'bill_total'           => 'required|numeric',
                'trx_id'               => 'required|string|max:100',
                'payment_status_code'  => 'required|string|max:10',
                'payment_channel'      => 'nullable|string|max:50',
                'payment_channel_uid'  => 'nullable|string|max:50',
                'payment_reff'         => 'nullable|string|max:100',
                'payment_date'         => 'nullable|date_format:Y-m-d H:i:s',
                'payment_status_desc'  => 'nullable|string|max:255',
                'signature'            => 'required|string|max:255',
            ]);

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
                'bill_no'             => $billNo,
                'bill_total'          => $billTotal,
                'payment_status_code' => $paymentStatusCode,
                'payment_channel'     => $paymentChannel,
                'trx_id'              => $trxId,
            ]);

            $signatureValid = $this->faspay->verifySignature($request->all());

            Log::info('🔐 Signature Check', ['valid' => $signatureValid]);

            if (!$signatureValid) {
                Log::error('❌ INVALID SIGNATURE', ['bill_no' => $billNo]);

                return response()->json([
                    'response'      => 'Payment Notification',
                    'response_code' => '05',
                    'response_desc' => 'Invalid signature',
                    'response_date' => now()->format('Y-m-d H:i:s'),
                ], 400);
            }

            $paymentStatus = $this->mapPaymentStatus($paymentStatusCode);

            DB::beginTransaction();

            try {
                $booking = Booking::where('bill_no', $billNo)
                    ->lockForUpdate()
                    ->first();

                if (!$booking) {
                    DB::rollBack();
                    Log::error('❌ BOOKING NOT FOUND', ['bill_no' => $billNo]);

                    return response()->json([
                        'response'      => 'Payment Notification',
                        'response_code' => '14',
                        'response_desc' => 'Booking not found',
                        'response_date' => now()->format('Y-m-d H:i:s'),
                    ], 404);
                }

                // Cek duplicate callback
                if ($booking->payment_status === 'paid' && $booking->trx_id === $trxId) {
                    DB::rollBack();

                    Log::info('⚠️ DUPLICATE CALLBACK - Already processed', [
                        'bill_no' => $billNo,
                        'trx_id'  => $trxId,
                    ]);

                    return response()->json([
                        'response'      => 'Payment Notification',
                        'trx_id'        => $trxId,
                        'merchant_id'   => config('faspay.merchant_id'),
                        'merchant'      => 'The Arena',
                        'bill_no'       => $billNo,
                        'response_code' => '00',
                        'response_desc' => 'Already processed',
                        'response_date' => now()->format('Y-m-d H:i:s'),
                    ], 200);
                }

                if ($billTotal && (int)$billTotal !== (int)$booking->total_price) {
                    Log::warning('⚠️ AMOUNT MISMATCH', [
                        'expected' => $booking->total_price,
                        'received' => $billTotal,
                    ]);
                }

                $booking->trx_id               = $trxId;
                $booking->payment_method        = $paymentChannel ?? 'Unknown';
                $booking->payment_status        = $paymentStatus;
                $booking->is_paid               = ($paymentStatus === 'paid') ? 1 : 0;
                $booking->paid_at               = ($paymentStatus === 'paid') ? now() : null;
                $booking->status                = ($paymentStatus === 'paid') ? 'confirmed' : $booking->status;
                $booking->payment_reff          = $paymentReff;
                $booking->payment_date          = $paymentDate ? \Carbon\Carbon::parse($paymentDate) : null;
                $booking->payment_status_code   = $paymentStatusCode;
                $booking->payment_status_desc   = $paymentStatusDesc;
                $booking->payment_channel_uid   = $paymentChannelUid;
                $booking->payment_channel       = $paymentChannel;

                $booking->save();

                DB::commit();

                // ✅ KIRIM EMAIL NOTIFIKASI KE ADMIN JIKA PAYMENT SUKSES
                if ($paymentStatus === 'paid') {
                    try {
                        $adminEmails = array_filter(
                            explode(',', env('ADMIN_EMAIL', 'admin@thearena.id'))
                        );

                        Mail::to($adminEmails)
                            ->send(new BookingPaymentNotification($booking->fresh(['client'])));

                        Log::info('📧 Admin notification email sent', [
                            'to'      => $adminEmails,
                            'bill_no' => $billNo,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('❌ Admin email failed (callback)', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $booking = $booking->fresh();

                Log::info('✅✅✅ CALLBACK UPDATE SUCCESS', [
                    'booking_id'     => $booking->id,
                    'payment_status' => $booking->payment_status,
                    'is_paid'        => $booking->is_paid,
                ]);

                Log::info('🔔 ===== FASPAY CALLBACK END (SUCCESS) =====');

                return response()->json([
                    'response'      => 'Payment Notification',
                    'trx_id'        => $trxId,
                    'merchant_id'   => config('faspay.merchant_id'),
                    'merchant'      => 'The Arena',
                    'bill_no'       => $billNo,
                    'response_code' => '00',
                    'response_desc' => 'Success',
                    'response_date' => now()->format('Y-m-d H:i:s'),
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ VALIDATION FAILED', ['errors' => $e->errors()]);

            return response()->json([
                'response'      => 'Payment Notification',
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
            ]);

            return response()->json([
                'response'      => 'Payment Notification',
                'response_code' => '96',
                'response_desc' => 'System error',
                'response_date' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }

    /**
     * ✅ Return URL (user kembali dari Faspay)
     */
    public function return(Request $request)
    {
        try {
            $billNo            = $request->query('bill_no');
            $status            = $request->query('status');
            $trxId             = $request->query('trx_id');
            $paymentReff       = $request->query('payment_reff');
            $paymentDate       = $request->query('payment_date');
            $signature         = $request->query('signature');
            $bankUserName      = $request->query('bank_user_name');
            $paymentChannel    = $request->query('payment_channel');
            $paymentChannelUid = $request->query('payment_channel_uid');

            Log::info('📍 User Returned from Faspay', [
                'bill_no'         => $billNo,
                'status'          => $status,
                'trx_id'          => $trxId,
                'payment_channel' => $paymentChannel,
            ]);

            if (!$billNo) {
                return redirect()->route('profile')->with('info', 'Menunggu konfirmasi pembayaran.');
            }

            $booking = Booking::where('bill_no', $billNo)->with('client')->first();

            if (!$booking) {
                return redirect()->route('profile')->with('error', 'Booking tidak ditemukan');
            }

            // Authorization check
            if ((int)$booking->client_id !== (int)auth('client')->id()) {
                return redirect()->route('profile')->with('error', 'Unauthorized');
            }

            // FALLBACK: Update dari return URL jika callback belum masuk
            if ($status === '2' && $booking->payment_status !== 'paid') {
                Log::warning('⚠️ FALLBACK: Updating from return URL');

                $signatureValid = $this->faspay->verifySignature($request->query());

                if ($signatureValid) {
                    DB::beginTransaction();
                    try {
                        $booking->payment_status      = 'paid';
                        $booking->is_paid             = true;
                        $booking->paid_at             = now();
                        $booking->status              = 'confirmed';
                        $booking->trx_id              = $trxId;
                        $booking->payment_reff        = $paymentReff;
                        $booking->payment_date        = $paymentDate ? \Carbon\Carbon::parse($paymentDate) : null;
                        $booking->payment_status_code = '2';
                        $booking->payment_status_desc = 'Payment Sukses';
                        $booking->payment_channel     = $paymentChannel ?? 'Faspay Xpress';
                        $booking->payment_channel_uid = $paymentChannelUid ?? $bankUserName ?? $trxId;
                        $booking->payment_method      = $paymentChannel ?? 'Faspay';

                        $booking->save();
                        DB::commit();

                        Log::info('✅ FALLBACK: Payment updated from return URL');

                        // ✅ KIRIM EMAIL NOTIFIKASI KE ADMIN (FALLBACK)
                        try {
                            $adminEmails = array_filter(
                                explode(',', env('ADMIN_EMAIL', 'admin@thearena.id'))
                            );

                            Mail::to($adminEmails)
                                ->send(new BookingPaymentNotification($booking->fresh(['client'])));

                            Log::info('📧 Admin fallback email sent', [
                                'to'      => $adminEmails,
                                'bill_no' => $billNo,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('❌ Admin email failed (fallback)', [
                                'error' => $e->getMessage(),
                            ]);
                        }

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('❌ FALLBACK failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            $booking = $booking->fresh(['client']);
            $isPaid  = $booking->isPaid();

            Log::info('📊 Return Status', [
                'is_paid'        => $isPaid,
                'payment_status' => $booking->payment_status,
            ]);

            if ($isPaid) {
                $client      = $booking->client;
                $bookingDate = $booking->booking_date->format('d/m/Y');

                $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                $dayName = $dayNames[$booking->booking_date->dayOfWeek];

                $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $bookingDateFormatted = $dayName . ', ' . $booking->booking_date->format('d') . ' ' . $monthNames[(int)$booking->booking_date->format('n')] . ' ' . $booking->booking_date->format('Y');

                $venueName = match ($booking->venue_type) {
                    'cibadak_a' => 'The Arena Cibadak A',
                    'cibadak_b' => 'The Arena Cibadak B',
                    'pvj'       => 'The Arena PVJ (Paris Van Java)',
                    'urban'     => 'The Arena Urban',
                    default     => 'The Arena',
                };

                $venueAddress = match ($booking->venue_type) {
                    'cibadak_a', 'cibadak_b' => 'Gg. Nyi Empok No.8, Cibadak, Bandung',
                    'pvj'                    => 'Paris Van Java Mall, Lantai P13, Bandung',
                    'urban'                  => 'Jl. Kelenteng No.41, Bandung',
                    default                  => 'Bandung',
                };

                $timeSlotsList = collect($booking->time_slots);
                $timeSlotsText = $timeSlotsList->pluck('time')->join("\n⏱️ ");

                $originalPrice = $booking->original_price ?? $booking->total_price;
                $discountAmount = $booking->discount_amount ?? 0;
                $totalPrice = 'Rp ' . number_format($booking->total_price, 0, ',', '.');
                $originalPriceText = 'Rp ' . number_format($originalPrice, 0, ',', '.');

                $separator = str_repeat('─', 30);

                // === PESAN KE ADMIN ===
                $messageAdmin  = "🏀 *KONFIRMASI BOOKING THE ARENA* 🏀\n";
                $messageAdmin .= $separator . "\n\n";
                $messageAdmin .= "👤 *INFORMASI PELANGGAN*\n";
                $messageAdmin .= "Nama     : *{$client->name}*\n";
                $messageAdmin .= "No. HP   : " . ($client->phone ?? '-') . "\n";
                $messageAdmin .= "Email    : " . ($client->email ?? '-') . "\n\n";
                $messageAdmin .= "🏟️ *DETAIL BOOKING*\n";
                $messageAdmin .= "Lapangan : *{$venueName}*\n";
                $messageAdmin .= "Alamat   : {$venueAddress}\n";
                $messageAdmin .= "Tanggal  : *{$bookingDateFormatted}*\n";
                $messageAdmin .= "Jam      :\n⏱️ {$timeSlotsText}\n\n";
                $messageAdmin .= "💳 *PEMBAYARAN*\n";
                $messageAdmin .= "No. Bill : `{$booking->bill_no}`\n";
                if ($discountAmount > 0) {
                    $messageAdmin .= "Harga    : ~~{$originalPriceText}~~\n";
                    $messageAdmin .= "Diskon   : -Rp " . number_format($discountAmount, 0, ',', '.') . "\n";
                }
                $messageAdmin .= "Total    : *{$totalPrice}*\n";
                $messageAdmin .= "Status   : ✅ *LUNAS*\n\n";
                $messageAdmin .= $separator . "\n";
                $messageAdmin .= "_Booking dikonfirmasi otomatis oleh sistem The Arena._";

                $adminWaNumber = env('ADMIN_WA_NUMBER', '6281222977985');
                $whatsappUrlAdmin = "https://wa.me/{$adminWaNumber}?text=" . urlencode($messageAdmin);

                // === STRUK UNTUK USER (SELF REMINDER) ===
                $messageUser  = "🏀 *STRUK BOOKING THE ARENA* 🏀\n";
                $messageUser .= $separator . "\n\n";
                $messageUser .= "Terima kasih, *{$client->name}*! 🎉\n";
                $messageUser .= "Booking Anda telah *DIKONFIRMASI*.\n\n";
                $messageUser .= "🏟️ *{$venueName}*\n";
                $messageUser .= "📍 {$venueAddress}\n\n";
                $messageUser .= "📅 *{$bookingDateFormatted}*\n";
                $messageUser .= "⏰ Jam:\n⏱️ {$timeSlotsText}\n\n";
                $messageUser .= "🧾 No. Bill: `{$booking->bill_no}`\n";
                $messageUser .= "💰 Total: *{$totalPrice}* ✅ Lunas\n\n";
                $messageUser .= $separator . "\n";
                $messageUser .= "📌 *Informasi Penting:*\n";
                $messageUser .= "• Harap datang 10 menit sebelum jam booking\n";
                $messageUser .= "• Gunakan sepatu olahraga/basket\n";
                $messageUser .= "• Tunjukkan bukti ini kepada petugas\n\n";
                $messageUser .= "Sampai jumpa di lapangan! 🏀🔥";

                Log::info('✅✅✅ PAYMENT SUCCESS - Redirecting with WhatsApp URL');

                return redirect()->route('booking.confirmation', ['booking' => $booking->id]);
            }

            return redirect()->route('profile', ['tab' => 'jadwal-booking'])
                ->with('info', '⏳ Pembayaran sedang diproses. Refresh halaman dalam beberapa saat.');

        } catch (\Exception $e) {
            Log::error('💥 Payment Return Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('profile')->with('error', 'Terjadi kesalahan');
        }
    }

    /**
     * ✅ Halaman konfirmasi booking setelah payment sukses
     */
    public function confirmation(Request $request, $bookingId)
    {
        $booking = Booking::with('client')->findOrFail($bookingId);

        // Authorization - only the booking owner can see confirmation
        if ((int)$booking->client_id !== (int)auth('client')->id()) {
            return redirect()->route('profile')->with('error', 'Unauthorized');
        }

        if (!$booking->isPaid()) {
            return redirect()->route('profile')->with('info', 'Pembayaran belum dikonfirmasi.');
        }

        $client = $booking->client;

        $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $dayName = $dayNames[$booking->booking_date->dayOfWeek];
        $bookingDateFormatted = $dayName . ', ' . $booking->booking_date->format('d') . ' ' . $monthNames[(int)$booking->booking_date->format('n')] . ' ' . $booking->booking_date->format('Y');

        $venueName = match ($booking->venue_type) {
            'cibadak_a' => 'The Arena Cibadak A',
            'cibadak_b' => 'The Arena Cibadak B',
            'pvj'       => 'The Arena PVJ (Paris Van Java)',
            'urban'     => 'The Arena Urban',
            default     => 'The Arena',
        };

        $venueAddress = match ($booking->venue_type) {
            'cibadak_a', 'cibadak_b' => 'Gg. Nyi Empok No.8, Cibadak, Bandung',
            'pvj'                    => 'Paris Van Java Mall, Lantai P13, Bandung',
            'urban'                  => 'Jl. Kelenteng No.41, Bandung',
            default                  => 'Bandung',
        };

        $timeSlotsCollection = collect($booking->time_slots);
        $timeSlotsText = $timeSlotsCollection->pluck('time')->join("\n⏱️ ");
        $timeSlotsDisplay = $timeSlotsCollection->pluck('time')->toArray();

        $originalPrice = $booking->original_price ?? $booking->total_price;
        $discountAmount = $booking->discount_amount ?? 0;
        $separator = str_repeat('─', 30);
        $totalPriceText = 'Rp ' . number_format($booking->total_price, 0, ',', '.');
        $originalPriceText = 'Rp ' . number_format($originalPrice, 0, ',', '.');

        // === PESAN KE ADMIN ===
        $messageAdmin  = "🏀 *KONFIRMASI BOOKING THE ARENA* 🏀\n";
        $messageAdmin .= $separator . "\n\n";
        $messageAdmin .= "👤 *INFORMASI PELANGGAN*\n";
        $messageAdmin .= "Nama     : *{$client->name}*\n";
        $messageAdmin .= "No. HP   : " . ($client->phone ?? '-') . "\n";
        $messageAdmin .= "Email    : " . ($client->email ?? '-') . "\n\n";
        $messageAdmin .= "🏟️ *DETAIL BOOKING*\n";
        $messageAdmin .= "Lapangan : *{$venueName}*\n";
        $messageAdmin .= "Alamat   : {$venueAddress}\n";
        $messageAdmin .= "Tanggal  : *{$bookingDateFormatted}*\n";
        $messageAdmin .= "Jam      :\n⏱️ {$timeSlotsText}\n\n";
        $messageAdmin .= "💳 *PEMBAYARAN*\n";
        $messageAdmin .= "No. Bill : `{$booking->bill_no}`\n";
        if ($discountAmount > 0) {
            $messageAdmin .= "Harga    : ~~{$originalPriceText}~~\n";
            $messageAdmin .= "Diskon   : -Rp " . number_format($discountAmount, 0, ',', '.') . "\n";
        }
        $messageAdmin .= "Total    : *{$totalPriceText}*\n";
        $messageAdmin .= "Status   : ✅ *LUNAS*\n\n";
        $messageAdmin .= $separator . "\n";
        $messageAdmin .= "_Booking dikonfirmasi otomatis oleh sistem The Arena._";

        // === STRUK UNTUK USER (simpan ke no sendiri) ===
        $messageUser  = "🏀 *STRUK BOOKING THE ARENA* 🏀\n";
        $messageUser .= $separator . "\n\n";
        $messageUser .= "Terima kasih, *{$client->name}*! 🎉\n";
        $messageUser .= "Booking Anda telah *DIKONFIRMASI*.\n\n";
        $messageUser .= "🏟️ *{$venueName}*\n";
        $messageUser .= "📍 {$venueAddress}\n\n";
        $messageUser .= "📅 *{$bookingDateFormatted}*\n";
        $messageUser .= "⏰ Jam:\n⏱️ {$timeSlotsText}\n\n";
        $messageUser .= "🧾 No. Bill: `{$booking->bill_no}`\n";
        $messageUser .= "💰 Total: *{$totalPriceText}* ✅ Lunas\n\n";
        $messageUser .= $separator . "\n";
        $messageUser .= "📌 *Informasi Penting:*\n";
        $messageUser .= "• Harap datang 10 menit sebelum jam booking\n";
        $messageUser .= "• Gunakan sepatu olahraga/basket\n";
        $messageUser .= "• Tunjukkan bukti ini kepada petugas\n\n";
        $messageUser .= "Sampai jumpa di lapangan! 🏀🔥";

        $adminWaNumber = env('ADMIN_WA_NUMBER', '6281222977985');
        $userPhone = preg_replace('/[^0-9]/', '', $client->phone ?? '');
        if (str_starts_with($userPhone, '0')) {
            $userPhone = '62' . substr($userPhone, 1);
        }

        $whatsappUrlAdmin = "https://wa.me/{$adminWaNumber}?text=" . urlencode($messageAdmin);
        $whatsappUrlUser  = $userPhone ? "https://wa.me/{$userPhone}?text=" . urlencode($messageUser) : null;

        return view('booking.confirmation', [
            'booking'           => $booking,
            'client'            => $client,
            'venueName'         => $venueName,
            'venueAddress'      => $venueAddress,
            'bookingDateFormatted' => $bookingDateFormatted,
            'timeSlotsDisplay'  => $timeSlotsDisplay,
            'originalPrice'     => $originalPrice,
            'discountAmount'    => $discountAmount,
            'totalPrice'        => $booking->total_price,
            'whatsappUrlAdmin'  => $whatsappUrlAdmin,
            'whatsappUrlUser'   => $whatsappUrlUser,
        ]);
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

            $booking = Booking::where('trx_id', $trxId)->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Transaction not found',
                ], 404);
            }

            return response()->json([
                'success'        => true,
                'transaction_id' => $booking->trx_id,
                'bill_no'        => $booking->bill_no,
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->status,
                'total_amount'   => $booking->total_price,
                'booking_date'   => $booking->booking_date,
                'is_paid'        => $booking->isPaid(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('💥 Check status error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error'   => 'Internal server error',
            ], 500);
        }
    }
}