<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Booking;
use App\Models\BookedTimeSlot;
use App\Models\Review;
use App\Models\Voucher;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * ✅ Auto-cancel expired pending bookings (dipanggil di setiap request penting)
     */
    private function cancelExpiredPendingBookings()
    {
        try {
            $expirationTime = Carbon::now()->subMinutes(10);

            $expiredBookings = Booking::where('payment_status', 'pending')
                ->where('status', 'pending')
                ->where('created_at', '<', $expirationTime)
                ->get();

            foreach ($expiredBookings as $booking) {
                DB::beginTransaction();
                try {
                    $booking->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled'
                    ]);

                    // Hapus BookedTimeSlot yang terkait
                    BookedTimeSlot::where('booking_id', $booking->id)->delete();

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to cancel expired booking: ' . $e->getMessage());
                }
            }

            return $expiredBookings->count();
        } catch (\Exception $e) {
            Log::error('Error in cancelExpiredPendingBookings: ' . $e->getMessage());
            return 0;
        }
    }

    public function index(Request $request)
    {
        // ✅ Jalankan auto-cancel sebelum render halaman
        $this->cancelExpiredPendingBookings();

        $weekOffset = $request->get('week', 0);
        $selectedVenueType = $request->get('venue', 'pvj');

        $venues = [
            'cibadak_a' => [
                'id' => 1,
                'venue_type' => 'cibadak_a',
                'name' => 'The Arena Cibadak A',
                'location' => 'GG Nyi Empok No. 8, Kota Bandung',
                'full_address' => 'Gg. Nyi Empok No.8, Cibadak, Kec. Astanaanyar, Kota Bandung, Jawa Barat 40241',
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena Cibadak berlokasi di GG Nyi Empok No. 8, Kota Bandung. The Arena Cibadak memiliki 2 lapangan basket indoor berstandar internasional dengan lantai kayu jati (Cibadak A) dan Vinyl (Cibadak B).',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan berstandar internasional dengan fasilitas lengkap dan lokasi strategis di Bandung.',
                'images' => [
                    asset('images/Lapangan/PVJ1.jpg'),
                    asset('images/Lapangan/PVJ.jpg'),
                    asset('images/Lapangan/PVJ5.jpg'),
                    asset('images/Lapangan/PVJ2.jpg'),
                    asset('images/Lapangan/PVJ4.jpg'),
                ],
                'facilities' => [
                    'Café & Resto',
                    'Tribun Penonton',
                    'Parkir Mobil & Motor',
                    'Toilet',
                    'Penjualan makanan ringan & minuman',
                ],
                'rules' => [
                    'Dilarang meludah di area lapangan',
                    'Gunakan sepatu olahraga / basket',
                    'Dilarang membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu dan dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],

            'cibadak_b' => [
                'id' => 2,
                'venue_type' => 'cibadak_b',
                'name' => 'The Arena Cibadak B',
                'location' => 'GG Nyi Empok No. 8, Kota Bandung',
                'full_address' => 'Gg. Nyi Empok No.8, Cibadak, Kec. Astanaanyar, Kota Bandung, Jawa Barat 40241',
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena Cibadak berlokasi di GG Nyi Empok No. 8, Kota Bandung. The Arena Cibadak memiliki 2 lapangan basket indoor berstandar internasional dengan lantai kayu jati (Cibadak A) dan Vinyl (Cibadak B).',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan berstandar internasional dengan fasilitas lengkap dan lokasi strategis di Bandung.',
                'images' => [
                    asset('images/Lapangan/CIBADAK1.jpg'),
                    asset('images/Lapangan/CIBADAK2.jpg'),
                    asset('images/Lapangan/CIBADAK3.jpg'),
                    asset('images/Lapangan/CIBADAK4.jpg'),
                    asset('images/Lapangan/CIBADAK5.jpg'),
                ],
                'facilities' => [
                    'Café & Resto',
                    'Tribun Penonton',
                    'Parkir Mobil & Motor',
                    'Toilet',
                    'Penjualan makanan ringan & minuman',
                ],
                'rules' => [
                    'Dilarang meludah di area lapangan',
                    'Gunakan sepatu olahraga / basket',
                    'Dilarang membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu dan dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],

            'pvj' => [
                'id' => 3,
                'venue_type' => 'pvj',
                'name' => 'The Arena PVJ',
                'location' => 'Paris Van Java Mall, Lantai P13, Bandung',
                'full_address' => 'Jl. Sukajadi No.131, Cipedes, Kec. Sukajadi, Kota Bandung, Jawa Barat 40162',
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena PVJ berlokasi di Paris Van Java Mall, Lantai P13, Bandung. Tersedia 1 lapangan basket indoor dengan material kayu jati berkualitas, memberikan pengalaman bermain yang optimal. Kami mengundang Anda untuk merasakan pengalaman berolahraga di fasilitas terbaik yang dapat disesuaikan dengan kebutuhan latihan maupun acara.',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan premium dengan material kayu jati berkualitas. Fasilitas lengkap dan lokasi strategis di pusat perbelanjaan membuat The Arena PVJ menjadi pilihan utama para pecinta basket di Bandung.',
                'images' => [
                    asset('images/Lapangan/PARIS1.jpg'),
                    asset('images/Lapangan/PARIS2.jpg'),
                    asset('images/Lapangan/PARIS3.jpg'),
                    asset('images/Lapangan/PARIS5.jpg'),
                    asset('images/Lapangan/PARIS4.jpg'),
                ],
                'facilities' => [
                    'Scoreboard',
                    'Shotclock',
                    'Sound System',
                    'Café & Resto',
                    'Tribun Penonton',
                    'Parkir Mobil & Motor',
                    'Toilet',
                    'Penjualan makanan ringan & minuman',
                ],
                'rules' => [
                    'Dilarang merokok',
                    'Dilarang meludah di area lapangan',
                    'Wajib menggunakan sepatu olahraga / basket',
                    'Dilarang membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu',
                    'Pemain harus dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],

            'urban' => [
                'id' => 4,
                'venue_type' => 'urban',
                'name' => 'The Arena Urban',
                'location' => 'Jl. Kelenteng no.41 Bandung',
                'full_address' => 'Jl. Kelenteng No.41, Ciroyom, Kec. Andir, Kota Bandung, Jawa Barat 40181',
                'description' => 'Lapangan basket semi-outdoor dengan lantai vinyl',
                'full_description' => 'The Arena Urban merupakan lapangan basket semi-outdoor dengan lantai vinyl, dilengkapi seating area luas dan suasana yang nyaman. Cocok untuk bermain menonton, maupun bersantai.',
                'invitation' => 'Nikmati pengalaman bermain basket di arena semi-outdoor dengan suasana nyaman dan fasilitas lengkap untuk aktivitas olahraga dan rekreasi.',
                'images' => [
                    asset('images/Lapangan/URBAN1.jpg'),
                    asset('images/Lapangan/URBAN2.jpg'),
                    asset('images/Lapangan/URBAN3.jpg'),
                    asset('images/Lapangan/URBAN4.jpg'),
                    asset('images/Lapangan/URBAN5.jpg'),
                ],
                'facilities' => [
                    'Scoreboard',
                    'Shotclock',
                    'Sound System',
                    'Café & Resto',
                    'Tribun Penonton',
                    'Gym & Pilates',
                    'Ruang Ganti',
                    'Musholla',
                    'Wi-Fi',
                    'Parkir Motor',
                ],
                'rules' => [
                    'Gunakan sepatu olahraga',
                    'Dilarang meludah dan membuang sampah sembarangan',
                    'Dilarang membawa alkohol, narkoba, atau barang ilegal',
                    'Pemain wajib datang tepat waktu dan dalam kondisi sehat',
                ],
                'note' => 'Segala risiko, cedera atau kecelakaan di luar tanggung jawab pengelola lapangan.',
            ],
        ];

        $venue = $venues[$selectedVenueType] ?? $venues['pvj'];
        $schedules = $this->generateSchedules($weekOffset);

        $reviews = Review::with('client:id,name,profile_image')
            ->approved()
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'client_name' => $review->client->name,
                    'client_profile_image' => $review->client->profile_image,
                    'rating' => $review->rating,
                    'rating_facilities' => $review->rating_facilities,
                    'rating_hospitality' => $review->rating_hospitality,
                    'rating_cleanliness' => $review->rating_cleanliness,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('HomePage/Booking/Booking', [
            'auth' => [
                'client' => Auth::guard('client')->user()
            ],
            'venue' => $venue,
            'venues' => $venues,
            'schedules' => $schedules,
            'currentWeek' => $weekOffset,
            'reviews' => $reviews,
        ]);
    }

    private function generateSchedules($weekOffset = 0)
    {
        $schedules = [];
        $startDate = Carbon::now()->startOfWeek()->addWeeks((int)$weekOffset);

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayName = $days[$date->dayOfWeek];

            $schedules[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $dayName,
                'date_number' => $date->format('d'),
                'month' => $date->format('F'),
                'year' => $date->format('Y'),
                'display_date' => $dayName . ', ' . $date->format('d F Y'),
                'is_past' => $date->lt(Carbon::today()),
            ];
        }

        return $schedules;
    }

    /**
     * ✅ Calculate dynamic price based on venue, date, and time
     */
    private function calculatePrice($venueType, $date, $timeSlot)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);

        preg_match('/^(\d{2})\./', $timeSlot, $matches);
        $startHour = isset($matches[1]) ? (int)$matches[1] : 0;

        if ($venueType === 'pvj') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 16) {
                    return 700000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            }
        }

        if ($venueType === 'cibadak_a') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000;
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 700000;
                }
            }
        }

        if ($venueType === 'cibadak_b') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) {
                    return 550000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 550000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000;
                }
            }
        }

        if ($venueType === 'urban') {
            if ($isWeekend) {
                return 550000;
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000;
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 550000;
                }
            }
        }

        return 350000;
    }

    /**
     * ✅ FIXED: Include manual bookings and all confirmed bookings
     */
    public function getTimeSlots(Request $request)
    {
        $this->cancelExpiredPendingBookings();

        $date = $request->input('date');
        $venueType = $request->input('venue_type', 'pvj');

        $allTimeSlots = [
            ['time' => '06.00 - 08.00', 'duration' => 120],
            ['time' => '08.00 - 10.00', 'duration' => 120],
            ['time' => '10.00 - 12.00', 'duration' => 120],
            ['time' => '12.00 - 14.00', 'duration' => 120],
            ['time' => '14.00 - 16.00', 'duration' => 120],
            ['time' => '16.00 - 18.00', 'duration' => 120],
            ['time' => '18.00 - 20.00', 'duration' => 120],
            ['time' => '20.00 - 22.00', 'duration' => 120],
            ['time' => '22.00 - 00.00', 'duration' => 120],
        ];

        // ✅ FIX: Include is_paid = true untuk manual bookings
        $bookedFromTimeSlots = BookedTimeSlot::where('date', $date)
            ->where('venue_type', $venueType)
            ->whereHas('booking', function ($query) {
                $query->where(function ($q) {
                    $q->where('payment_status', 'paid')
                        ->orWhere('status', 'confirmed')
                        ->orWhere('is_paid', true);  // ✅ TAMBAHKAN INI
                });
            })
            ->pluck('time_slot')
            ->toArray();

        $bookedFromBookings = Booking::where('booking_date', $date)
            ->where('venue_type', $venueType)
            ->where(function ($query) {
                $query->where('payment_status', 'paid')
                    ->orWhere('status', 'confirmed')
                    ->orWhere('is_paid', true);  // ✅ TAMBAHKAN INI
            })
            ->get()
            ->flatMap(function ($booking) {
                return collect($booking->time_slots)->pluck('time');
            })
            ->unique()
            ->toArray();

        $bookedSlots = array_unique(array_merge($bookedFromTimeSlots, $bookedFromBookings));

        $timeSlots = array_map(function ($slot) use ($bookedSlots, $venueType, $date) {
            $slot['price'] = $this->calculatePrice($venueType, $date, $slot['time']);
            $slot['status'] = in_array($slot['time'], $bookedSlots) ? 'booked' : 'available';
            return $slot;
        }, $allTimeSlots);

        return response()->json([
            'success' => true,
            'time_slots' => $timeSlots,
        ]);
    }

    /**
     * ✅ Validate and apply voucher
     */
    public function applyVoucher(Request $request)
    {
        $validated = $request->validate([
            'voucher_code' => 'required|string|max:50',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if (!Auth::guard('client')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        try {
            $voucher = Voucher::where('code', strtoupper($validated['voucher_code']))
                ->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher tidak ditemukan.'
                ], 404);
            }

            // Check if voucher is valid
            if (!$voucher->isValid()) {
                $reason = $this->getVoucherInvalidReason($voucher);
                return response()->json([
                    'success' => false,
                    'message' => $reason
                ], 422);
            }

            // Check if user can use this voucher
            $clientId = Auth::guard('client')->id();
            if (!$voucher->canBeUsedBy($clientId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah menggunakan voucher ini sebelumnya.'
                ], 422);
            }

            // Calculate discount
            $discountAmount = $voucher->calculateDiscount($validated['total_amount']);

            if ($discountAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal pembelian untuk voucher ini adalah Rp. ' . number_format($voucher->min_purchase, 0, ',', '.')
                ], 422);
            }

            $finalAmount = $validated['total_amount'] - $discountAmount;

            return response()->json([
                'success' => true,
                'message' => 'Voucher berhasil diterapkan!',
                'voucher' => [
                    'code' => $voucher->code,
                    'discount_type' => $voucher->discount_type,
                    'discount_value' => $voucher->discount_value,
                    'discount_amount' => $discountAmount,
                    'original_amount' => $validated['total_amount'],
                    'final_amount' => $finalAmount,
                    'description' => $voucher->description,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Apply voucher error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memvalidasi voucher.'
            ], 500);
        }
    }

    /**
     * Get reason why voucher is invalid
     */
    private function getVoucherInvalidReason($voucher): string
    {
        if (!$voucher->is_active) {
            return 'Voucher tidak aktif.';
        }

        $now = Carbon::now();
        
        if ($voucher->valid_from && $now->lt($voucher->valid_from)) {
            return 'Voucher belum dapat digunakan.';
        }

        if ($voucher->valid_until && $now->gt($voucher->valid_until)) {
            return 'Voucher sudah kadaluarsa.';
        }

        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            return 'Voucher sudah mencapai batas penggunaan.';
        }

        return 'Voucher tidak valid.';
    }

    /**
     * ✅ IMPROVED: Process booking with voucher support
     */
    public function processBooking(Request $request)
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'time_slots' => 'required|array|min:1',
            'time_slots.*.time' => 'required|string',
            'time_slots.*.price' => 'required|numeric',
            'time_slots.*.duration' => 'required|numeric',
            'venue_type' => 'required|string|in:cibadak_a,cibadak_b,pvj,urban',
            'voucher_code' => 'nullable|string|max:50',
        ]);

        if (!Auth::guard('client')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu untuk melakukan booking.'
                ], 401);
            }
            return back()->withErrors([
                'message' => 'Silakan login terlebih dahulu untuk melakukan booking.'
            ]);
        }

        try {
            $this->cancelExpiredPendingBookings();

            DB::beginTransaction();

            // Validate prices
            foreach ($validated['time_slots'] as $slot) {
                $expectedPrice = $this->calculatePrice(
                    $validated['venue_type'],
                    $validated['date'],
                    $slot['time']
                );

                if ($slot['price'] != $expectedPrice) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Harga tidak sesuai. Silakan refresh halaman dan coba lagi.'
                    ], 422);
                }
            }

            $requestedSlots = array_column($validated['time_slots'], 'time');

            // ✅ FIX: Check confirmed bookings termasuk manual bookings
            $confirmedBooked = BookedTimeSlot::where('date', $validated['date'])
                ->where('venue_type', $validated['venue_type'])
                ->whereIn('time_slot', $requestedSlots)
                ->whereHas('booking', function ($query) {
                    $query->where(function ($q) {
                        $q->where('payment_status', 'paid')
                            ->orWhere('status', 'confirmed')
                            ->orWhere('is_paid', true);  // ✅ TAMBAHKAN INI
                    });
                })
                ->exists();

            if ($confirmedBooked) {
                DB::rollBack();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maaf, slot waktu yang Anda pilih sudah dibooking dan terkonfirmasi. Silakan pilih slot waktu lain.'
                    ], 422);
                }

                return back()->withErrors([
                    'message' => 'Maaf, slot waktu yang Anda pilih sudah dibooking dan terkonfirmasi. Silakan pilih slot waktu lain.'
                ]);
            }

            // Check pending bookings
            $pendingBooked = BookedTimeSlot::where('date', $validated['date'])
                ->where('venue_type', $validated['venue_type'])
                ->whereIn('time_slot', $requestedSlots)
                ->whereHas('booking', function ($query) {
                    $query->where('status', 'pending')
                          ->where('payment_status', 'pending')
                          ->where('created_at', '>=', Carbon::now()->subMinutes(10));
                })
                ->exists();

            if ($pendingBooked) {
                DB::rollBack();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Slot waktu ini sedang dalam proses booking oleh pengguna lain. Silakan tunggu beberapa menit atau pilih slot waktu lain.'
                    ], 423);
                }

                return back()->withErrors([
                    'message' => 'Slot waktu ini sedang dalam proses booking oleh pengguna lain. Silakan tunggu beberapa menit atau pilih slot waktu lain.'
                ]);
            }

            // Calculate prices
            $originalPrice = array_sum(array_column($validated['time_slots'], 'price'));
            $totalPrice = $originalPrice;
            $discountAmount = 0;
            $voucherCode = null;
            $voucher = null;

            // ✅ APPLY VOUCHER IF PROVIDED
            if (!empty($validated['voucher_code'])) {
                $voucher = Voucher::where('code', strtoupper($validated['voucher_code']))
                    ->first();

                if ($voucher && $voucher->isValid() && $voucher->canBeUsedBy(Auth::guard('client')->id())) {
                    $discountAmount = $voucher->calculateDiscount($originalPrice);
                    if ($discountAmount > 0) {
                        $totalPrice = $originalPrice - $discountAmount;
                        $voucherCode = $voucher->code;
                    }
                }
            }

          $booking = Booking::create([
    'client_id' => Auth::guard('client')->id(),
    'venue_id' => $validated['venue_id'],
    'booking_date' => $validated['date'],
    'venue_type' => $validated['venue_type'],
    'time_slots' => $validated['time_slots'],
    'original_price' => $originalPrice,
    'discount_amount' => $discountAmount,
    'voucher_code' => $voucherCode,
    'total_price' => $totalPrice,
    'status' => 'pending',
    'payment_status' => 'pending',
]);

            // Create booked time slots
            foreach ($validated['time_slots'] as $slot) {
                BookedTimeSlot::create([
                    'booking_id' => $booking->id,
                    'date' => $validated['date'],
                    'time_slot' => $slot['time'],
                    'venue_type' => $validated['venue_type'],
                ]);
            }

            // ✅ RECORD VOUCHER USAGE
            if ($voucher && $discountAmount > 0) {
                $voucher->apply(
                    Auth::guard('client')->id(),
                    $booking->id,
                    $discountAmount
                );
            }

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Booking berhasil! Silakan lakukan pembayaran dalam 10 menit.',
                    'booking_id' => $booking->id,
                    'bill_no' => $booking->bill_no ?? null,
                    'total_price' => $booking->total_price,
                    'original_price' => $booking->original_price,
                    'discount_amount' => $booking->discount_amount,
                    'voucher_code' => $booking->voucher_code,
                    'expires_at' => $booking->created_at->addMinutes(10)->toIso8601String(),
                    'redirect_to_payment' => true,
                ]);
            }

            return back()->with([
                'flash' => [
                    'success' => true,
                    'message' => 'Booking berhasil! Silakan lanjutkan ke pembayaran dalam 10 menit.',
                    'booking_id' => $booking->id,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memproses booking: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors([
                'message' => 'Terjadi kesalahan saat memproses booking: ' . $e->getMessage()
            ]);
        }
    }

    public function storeReview(Request $request)
    {
        $validated = $request->validate([
            'rating_facilities' => 'required|integer|min:1|max:5',
            'rating_hospitality' => 'required|integer|min:1|max:5',
            'rating_cleanliness' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000|min:10',
        ]);

        if (!Auth::guard('client')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        try {
            $completedBookingWithoutReview = Booking::where('client_id', Auth::guard('client')->id())
                ->completedWithoutReview()
                ->oldest('booking_date')
                ->first();

            if (!$completedBookingWithoutReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum memiliki booking yang selesai atau semua booking sudah direview.'
                ], 422);
            }

            $averageRating = round(
                ($validated['rating_facilities'] + $validated['rating_hospitality'] + $validated['rating_cleanliness']) / 3
            );

            $review = Review::create([
                'client_id' => Auth::guard('client')->id(),
                'booking_id' => $completedBookingWithoutReview->id,
                'rating' => $averageRating,
                'rating_facilities' => $validated['rating_facilities'],
                'rating_hospitality' => $validated['rating_hospitality'],
                'rating_cleanliness' => $validated['rating_cleanliness'],
                'comment' => $validated['comment'],
                'is_approved' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Ulasan Anda akan ditampilkan setelah diverifikasi oleh admin.',
                'review' => [
                    'id' => $review->id,
                    'client_name' => Auth::guard('client')->user()->name,
                    'rating' => $review->rating,
                    'rating_facilities' => $review->rating_facilities,
                    'rating_hospitality' => $review->rating_hospitality,
                    'rating_cleanliness' => $review->rating_cleanliness,
                    'comment' => $review->comment,
                    'created_at' => 'Baru saja',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getReviews()
    {
        $reviews = Review::with('client:id,name,profile_image')
            ->approved()
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'client_name' => $review->client->name,
                    'client_profile_image' => $review->client->profile_image,
                    'rating' => $review->rating,
                    'rating_facilities' => $review->rating_facilities,
                    'rating_hospitality' => $review->rating_hospitality,
                    'rating_cleanliness' => $review->rating_cleanliness,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            });
        return response()->json([
            'success' => true,
            'reviews' => $reviews
        ]);
    }
}