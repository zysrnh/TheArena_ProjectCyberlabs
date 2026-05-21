<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected ?string $token;

    public function __construct()
    {
        $this->token = config('fonnte.token');
    }

    /**
     * Send WhatsApp message (optionally with a file attachment).
     *
     * @param string $target Recipient number(s) (comma-separated if multiple).
     * @param string $message The message body.
     * @param string|null $localFilePath Absolute path to a local file (e.g. QR code).
     * @return array
     */
    public function send(string $target, string $message, ?string $localFilePath = null): array
    {
        if (empty($this->token)) {
            Log::error('Fonnte API Token is not configured.');
            return [
                'success' => false,
                'error' => 'Fonnte token is not configured.'
            ];
        }

        // Clean target number: keep only digits and commas
        $cleanTarget = preg_replace('/[^0-9,]/', '', $target);

        // Standardize: replace leading "+" if any (already handled by preg_replace)
        // Also if number starts with "08", replace with "628" for better compatibility
        $targets = explode(',', $cleanTarget);
        foreach ($targets as &$num) {
            $num = trim($num);
            if (str_starts_with($num, '08')) {
                $num = '62' . substr($num, 1);
            }
        }
        $target = implode(',', $targets);

        $payload = [
            'target' => $target,
            'message' => $message,
            'countryCode' => '62',
        ];

        // Attach file if present and exists
        if ($localFilePath && file_exists($localFilePath)) {
            $payload['file'] = new \CURLFile($localFilePath);
            Log::info('Attaching local file to Fonnte payload', ['path' => $localFilePath]);
        }

        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $this->token,
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($err) {
                Log::error('Fonnte API connection error: ' . $err, [
                    'target' => $target,
                ]);
                return [
                    'success' => false,
                    'error' => 'Connection error: ' . $err,
                ];
            }

            Log::info('Fonnte API Response received', [
                'http_code' => $httpCode,
                'response' => $response,
            ]);

            $result = json_decode($response, true);

            // Fonnte usually returns JSON with "status" => true/false
            if (isset($result['status']) && $result['status'] === true) {
                return [
                    'success' => true,
                    'response' => $result,
                ];
            }

            $errorMessage = $result['reason'] ?? $result['message'] ?? 'Unknown error from Fonnte';
            return [
                'success' => false,
                'error' => $errorMessage,
                'response' => $result,
            ];

        } catch (Exception $e) {
            Log::error('Fonnte Service Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send automatic WhatsApp notifications to both Admin and Booker via Fonnte for a booking.
     *
     * @param \App\Models\Booking $booking
     * @return void
     */
    public function sendBookingNotifications(\App\Models\Booking $booking): void
    {
        try {
            $booking->loadMissing('client');
            $client = $booking->client;
            if (!$client) {
                Log::warning('Skip Fonnte booking notification: Client not found.', ['booking_id' => $booking->id]);
                return;
            }

            $bookingDate = $booking->booking_date;
            if (!$bookingDate) {
                Log::warning('Skip Fonnte booking notification: Booking date is empty.', ['booking_id' => $booking->id]);
                return;
            }

            $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            $dayName = $dayNames[$bookingDate->dayOfWeek];

            $monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            $bookingDateFormatted = $dayName . ', ' . $bookingDate->format('d') . ' ' . $monthNames[(int)$bookingDate->format('n')] . ' ' . $bookingDate->format('Y');

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

            $slots = $booking->time_slots;
            $timeSlotsText = '';
            if (is_array($slots)) {
                $timeSlotsText = collect($slots)->pluck('time')->join("\n• ");
            }

            $originalPrice = $booking->original_price ?? $booking->total_price;
            $discountAmount = $booking->discount_amount ?? 0;
            $totalPrice = 'Rp ' . number_format($booking->total_price, 0, ',', '.');
            $originalPriceText = 'Rp ' . number_format($originalPrice, 0, ',', '.');

            // === PESAN KE ADMIN ===
            $messageAdmin  = "*KONFIRMASI BOOKING THE ARENA*\n";
            $messageAdmin .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $messageAdmin .= "*INFORMASI PELANGGAN*\n";
            $messageAdmin .= "Nama: {$client->name}\n";
            $messageAdmin .= "No. HP: " . ($client->phone ?? '-') . "\n";
            $messageAdmin .= "Email: " . ($client->email ?? '-') . "\n\n";
            $messageAdmin .= "*DETAIL BOOKING*\n";
            $messageAdmin .= "Lapangan: {$venueName}\n";
            $messageAdmin .= "Alamat: {$venueAddress}\n";
            $messageAdmin .= "Tanggal: {$bookingDateFormatted}\n";
            $messageAdmin .= "Jam: \n• {$timeSlotsText}\n\n";
            $messageAdmin .= "*PEMBAYARAN*\n";
            $messageAdmin .= "No. Bill: {$booking->bill_no}\n";
            if ($discountAmount > 0) {
                $messageAdmin .= "Harga: ~{$originalPriceText}~\n";
                $messageAdmin .= "Diskon: -Rp " . number_format($discountAmount, 0, ',', '.') . "\n";
            }
            $messageAdmin .= "Total: *{$totalPrice}*\n";
            $messageAdmin .= "Status: *LUNAS (Paid)*\n\n";
            $messageAdmin .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $messageAdmin .= "_Booking dikonfirmasi otomatis oleh sistem The Arena._";

            // === STRUK UNTUK USER (SELF REMINDER) ===
            $messageUser  = "*STRUK BOOKING THE ARENA*\n";
            $messageUser .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $messageUser .= "Terima kasih, *{$client->name}*!\n";
            $messageUser .= "Booking Anda telah *DIKONFIRMASI*.\n\n";
            $messageUser .= "Lapangan: {$venueName}\n";
            $messageUser .= "Alamat: {$venueAddress}\n\n";
            $messageUser .= "Tanggal: {$bookingDateFormatted}\n";
            $messageUser .= "Jam:\n• {$timeSlotsText}\n\n";
            $messageUser .= "No. Bill: {$booking->bill_no}\n";
            $messageUser .= "Total: *{$totalPrice}* (Lunas)\n\n";
            $messageUser .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $messageUser .= "*Informasi Penting:*\n";
            $messageUser .= "• Harap datang 10 menit sebelum jam booking\n";
            $messageUser .= "• Gunakan sepatu olahraga/basket yang sesuai\n";
            $messageUser .= "• Tunjukkan bukti chat ini kepada petugas di lokasi\n\n";
            $messageUser .= "Sampai jumpa di lapangan!";

            // 1. Send to Admin
            $adminPhone = config('fonnte.admin_phone');
            if ($adminPhone) {
                Log::info("Sending auto-notification to admin {$adminPhone} via Fonnte.");
                $this->send($adminPhone, $messageAdmin);
            } else {
                Log::warning('Fonnte admin_phone is not configured in config/fonnte.php.');
            }

            // 2. Send to Booker (User)
            $userPhone = $client->phone;
            if ($userPhone) {
                Log::info("Sending auto-notification to user {$userPhone} via Fonnte.");
                $this->send($userPhone, $messageUser);
            } else {
                Log::warning('Fonnte User Phone is empty, cannot send user notification.');
            }

        } catch (Exception $e) {
            Log::error('Error sending Fonnte booking notifications: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

