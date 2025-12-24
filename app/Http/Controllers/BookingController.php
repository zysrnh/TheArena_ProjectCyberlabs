<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Booking;
use App\Models\BookedTimeSlot;
use App\Models\Review;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $weekOffset = $request->get('week', 0);
        $selectedVenueType = $request->get('venue', 'pvj');

        $venues = [
            'cibadak_a' => [
                'id' => 1,
                'venue_type' => 'cibadak_a',
                'name' => 'The Arena Cibadak A',
                'location' => 'GG Nyi Empok No. 8, Kota Bandung',
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena Cibadak berlokasi di GG Nyi Empok No. 8, Kota Bandung. The Arena Cibadak memiliki 2 lapangan basket indoor berstandar internasional dengan lantai kayu jati (Cibadak A) dan Vinyl (Cibadak B).',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan berstandar internasional dengan fasilitas lengkap dan lokasi strategis di Bandung.',
                'price_per_session' => 350000,
                'member_price' => 300000,
                'images' => [
                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1200',
                    'https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1200',
                    'https://images.unsplash.com/photo-1608245449230-4ac19066d2d0?w=1200',
                    'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=1200',
                    'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?w=1200',
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
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena Cibadak berlokasi di GG Nyi Empok No. 8, Kota Bandung. The Arena Cibadak memiliki 2 lapangan basket indoor berstandar internasional dengan lantai kayu jati (Cibadak A) dan Vinyl (Cibadak B).',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan berstandar internasional dengan fasilitas lengkap dan lokasi strategis di Bandung.',
                'price_per_session' => 300000,
                'member_price' => 250000,
                'images' => [
                    'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=1200',
                    'https://images.unsplash.com/photo-1515523110800-9415d13b84a8?w=1200',
                    'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?w=1200',
                    'https://images.unsplash.com/photo-1608245449230-4ac19066d2d0?w=1200',
                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1200',
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
                'description' => 'Basketball Courts & Healthy Lifestyle Space',
                'full_description' => 'The Arena PVJ berlokasi di Paris Van Java Mall, Lantai P13, Bandung. Tersedia 1 lapangan basket indoor dengan material kayu jati berkualitas, memberikan pengalaman bermain yang optimal. Kami mengundang Anda untuk merasakan pengalaman berolahraga di fasilitas terbaik yang dapat disesuaikan dengan kebutuhan latihan maupun acara.',
                'invitation' => 'Rasakan pengalaman bermain basket di lapangan premium dengan material kayu jati berkualitas. Fasilitas lengkap dan lokasi strategis di pusat perbelanjaan membuat The Arena PVJ menjadi pilihan utama para pecinta basket di Bandung.',
                'price_per_session' => 350000,
                'member_price' => 300000, // Harga member lebih murah
                'images' => [
                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1200',
                    'https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1200',
                    'https://images.unsplash.com/photo-1608245449230-4ac19066d2d0?w=1200',
                    'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=1200',
                    'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?w=1200',
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
                'name' => 'The Arena Basketball Urban',
                'location' => 'Jl. Urban Complex No. 88, Bandung',
                'description' => 'Ultra-Modern Indoor Arena di Jantung Kota',
                'full_description' => 'The Arena Basketball Urban adalah lapangan basket indoor paling modern di Bandung. Mengusung konsep international standard dengan teknologi scoring digital, AC central, sound system premium, dan viewing deck untuk spectator. Lokasi strategis di pusat bisnis Bandung.',
                'invitation' => 'Experience basketball like never before! Fasilitas bintang 5, teknologi modern, dan atmosfer profesional untuk pemain yang menginginkan yang terbaik.',
                'price_per_session' => 400000,
                'images' => [
                    'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?w=1200',
                    'https://images.unsplash.com/photo-1515523110800-9415d13b84a8?w=1200',
                    'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=1200',
                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1200',
                    'https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1200',
                ],
                'facilities' => [
                    'Cafe Premium',
                    'Parkir Basement Security',
                    'Locker Room Premium',
                    'Shower Room',
                    'AC Central',
                    'Sound System Premium',
                    'Tribun VIP',
                    'Digital Scoreboard',
                    'WiFi High Speed',
                ],
                'rules' => [
                    'Dilarang merokok di seluruh area.',
                    'Wajib menggunakan sepatu indoor khusus basket.',
                    'Wajib sewa locker untuk barang berharga.',
                    'Dilarang membawa makanan dari luar.',
                    'Harap menjaga kebersihan dan fasilitas.',
                    'Pemain harus datang tepat waktu.',
                    'Customer wajib dalam kondisi prima.',
                    'Lapangan tidak bertanggung jawab atas cedera akibat kelalaian pribadi.',
                ],
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
     * ✅ FIXED: Deteksi booking dari customer DAN recurring booking dari admin
     */
    public function getTimeSlots(Request $request)
    {
        $date = $request->input('date');
        $venueType = $request->input('venue_type', 'indoor');

        $allTimeSlots = [
            ['time' => '06.00 - 08.00', 'duration' => 120, 'price' => 350000],
            ['time' => '08.00 - 10.00', 'duration' => 120, 'price' => 350000],
            ['time' => '10.00 - 12.00', 'duration' => 120, 'price' => 350000],
            ['time' => '12.00 - 14.00', 'duration' => 120, 'price' => 350000],
            ['time' => '14.00 - 16.00', 'duration' => 120, 'price' => 350000],
            ['time' => '16.00 - 18.00', 'duration' => 120, 'price' => 350000],
            ['time' => '18.00 - 20.00', 'duration' => 120, 'price' => 350000],
            ['time' => '20.00 - 22.00', 'duration' => 120, 'price' => 350000],
            ['time' => '22.00 - 00.00', 'duration' => 120, 'price' => 350000],
        ];

        // ✅ Ambil slot yang sudah booked dari BookedTimeSlot
        $bookedFromTimeSlots = BookedTimeSlot::where('date', $date)
            ->where('venue_type', $venueType)
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', ['pending', 'confirmed']);
            })
            ->pluck('time_slot')
            ->toArray();

        // ✅ PENTING: Ambil juga dari tabel Bookings langsung (untuk recurring booking)
        // Karena CreateRecurringBooking membuat entry di BookedTimeSlot juga,
        // kita hanya perlu pastikan query di atas sudah mencakup semua

        // Tapi untuk extra safety, kita double-check dari Bookings table juga
        $bookedFromBookings = Booking::where('booking_date', $date)
            ->where('venue_type', $venueType)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->flatMap(function ($booking) {
                // Extract time dari time_slots JSON
                return collect($booking->time_slots)->pluck('time');
            })
            ->unique()
            ->toArray();

        // ✅ Merge kedua hasil
        $bookedSlots = array_unique(array_merge($bookedFromTimeSlots, $bookedFromBookings));

        $timeSlots = array_map(function ($slot) use ($bookedSlots) {
            $slot['status'] = in_array($slot['time'], $bookedSlots) ? 'booked' : 'available';
            return $slot;
        }, $allTimeSlots);

        return response()->json([
            'success' => true,
            'time_slots' => $timeSlots,
        ]);
    }

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
            DB::beginTransaction();

            $requestedSlots = array_column($validated['time_slots'], 'time');

            // ✅ Cek konflik dari BookedTimeSlot (customer booking + recurring booking)
            $alreadyBooked = BookedTimeSlot::where('date', $validated['date'])
                ->where('venue_type', $validated['venue_type'])
                ->whereIn('time_slot', $requestedSlots)
                ->whereHas('booking', function ($query) {
                    $query->whereIn('status', ['pending', 'confirmed']);
                })
                ->exists();

            if ($alreadyBooked) {
                DB::rollBack();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maaf, ada slot waktu yang sudah dibooking oleh orang lain. Silakan pilih slot waktu lain.'
                    ], 422);
                }

                return back()->withErrors([
                    'message' => 'Maaf, ada slot waktu yang sudah dibooking oleh orang lain. Silakan pilih slot waktu lain.'
                ]);
            }

            $totalPrice = array_sum(array_column($validated['time_slots'], 'price'));

            $booking = Booking::create([
                'client_id' => Auth::guard('client')->id(),
                'venue_id' => $validated['venue_id'],
                'booking_date' => $validated['date'],
                'venue_type' => $validated['venue_type'],
                'time_slots' => $validated['time_slots'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($validated['time_slots'] as $slot) {
                BookedTimeSlot::create([
                    'booking_id' => $booking->id,
                    'date' => $validated['date'],
                    'time_slot' => $slot['time'],
                    'venue_type' => $validated['venue_type'],
                ]);
            }

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Booking berhasil! Silakan lanjutkan ke pembayaran.',
                    'booking_id' => $booking->id,
                    'redirect_to_profile' => true,
                ]);
            }

            return back()->with([
                'flash' => [
                    'success' => true,
                    'message' => 'Booking berhasil! Silakan lanjutkan ke pembayaran.',
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
                ->where('status', 'completed')
                ->whereDoesntHave('review')
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
