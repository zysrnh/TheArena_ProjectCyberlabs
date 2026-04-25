<?php

namespace App\Filament\Admin\Resources\RecurringBookingResource\Pages;

use App\Filament\Admin\Resources\RecurringBookingResource;
use App\Models\Booking;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateRecurringBooking extends CreateRecord
{
    protected static string $resource = RecurringBookingResource::class;
    protected static string $view     = 'filament.admin.resources.recurring-booking.create';
    protected static ?string $title   = 'Buat Booking Member';

    // ─── Customer ────────────────────────────────────────────────────────────
    public string $customerType        = 'manual';
    public string $customerNameManual  = '';
    public string $customerPhoneManual = '';
    public $clientId                   = null;
    public array  $clientOptions       = [];

    // ─── Booking detail ───────────────────────────────────────────────────────
    public string $venueType          = 'cibadak_a';
    public array  $timeSlotsSelection = [];
    public string $status             = 'confirmed';
    public string $paymentStatus      = 'paid';
    public bool   $isPaid             = true;
    public string $notes              = '';

    // ─── Mode ─────────────────────────────────────────────────────────────────
    public string $recurringMode = 'weekly_flex'; // default ke weekly_flex (fitur utama)

    // ─── Mingguan Rutin ───────────────────────────────────────────────────────
    public array  $weeklyDays      = [];
    public string $weeklyStartDate = '';
    public int    $weeklyDuration  = 4;

    // ─── Mingguan Fleksibel ───────────────────────────────────────────────────
    public string $flexRangeStart = '';
    public string $flexRangeEnd   = '';
    public array  $weekSchedule   = []; // [{label, week_start, week_end, days:[{date,day_name,day_num,month,checked,is_past}]}]

    // ─── Bulanan per Hari ─────────────────────────────────────────────────────
    public array $monthlySchedule = []; // [{month_start, days_of_week:[]}]

    // ─── Custom ───────────────────────────────────────────────────────────────
    public array $customDates = []; // ['Y-m-d', ...]

    // ─── UI State ─────────────────────────────────────────────────────────────
    public bool   $isSubmitting    = false;
    public array  $validationErrors = [];
    public string $backUrl          = '';

    // ─── Internal lookup tables (constants, bukan Livewire properties) ─────────
    protected const MONTH_SHORT = [
        1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
        7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des',
    ];
    protected const DAY_NAMES = [
        0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',
    ];
    protected const DAY_SHORT = [
        0=>'Min',1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab',
    ];

    public function mount(): void
    {
        $this->weeklyStartDate = now()->format('Y-m-d');
        $this->flexRangeStart  = now()->startOfMonth()->format('Y-m-d');
        $this->flexRangeEnd    = now()->endOfMonth()->format('Y-m-d');
        $this->backUrl         = RecurringBookingResource::getUrl('index');

        // Load client options
        $this->clientOptions = Client::orderBy('name')->get()
            ->mapWithKeys(fn($c) => [$c->id => $c->name . ($c->phone ? ' (' . $c->phone . ')' : '')])
            ->toArray();
    }

    // ✅ Required by CreateRecord — tidak dipakai karena kita punya save() sendiri
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return new Booking();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Weekly Flex: generate weeks from date range
    // ──────────────────────────────────────────────────────────────────────────
    public function generateWeeks(): void
    {
        if (!$this->flexRangeStart || !$this->flexRangeEnd) {
            Notification::make()->warning()->title('Lengkapi Tanggal')->body('Pilih tanggal mulai dan selesai.')->send();
            return;
        }

        $startDate = Carbon::parse($this->flexRangeStart);
        $endDate   = Carbon::parse($this->flexRangeEnd);

        if ($startDate->gt($endDate)) {
            Notification::make()->warning()->title('Tanggal Tidak Valid')->body('Tanggal mulai harus sebelum tanggal selesai.')->send();
            return;
        }

        $weeks     = [];
        $weekStart = $startDate->copy()->startOfWeek(Carbon::MONDAY);

        while ($weekStart->lte($endDate)) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            // Clip effective range to user's selected range
            $effStart = $weekStart->gte($startDate) ? $weekStart->copy() : $startDate->copy();
            $effEnd   = $weekEnd->lte($endDate) ? $weekEnd->copy() : $endDate->copy();

            $days = [];
            $cur  = $effStart->copy();
            while ($cur->lte($effEnd)) {
                $days[] = [
                    'date'          => $cur->format('Y-m-d'),
                    'day_name'      => self::DAY_NAMES[$cur->dayOfWeek],
                    'day_short'     => self::DAY_SHORT[$cur->dayOfWeek],
                    'day_of_week'   => $cur->dayOfWeek,
                    'day_num'       => (int)$cur->format('d'),
                    'month_short'   => self::MONTH_SHORT[$cur->month],
                    'checked'       => false,
                    'is_past'       => $cur->lt(now()->startOfDay()),
                ];
                $cur->addDay();
            }

            $wLabel = $effStart->format('d') . ' ' . (self::MONTH_SHORT[$effStart->month] ?? '')
                    . ' – ' . $effEnd->format('d') . ' ' . (self::MONTH_SHORT[$effEnd->month] ?? '')
                    . ' ' . $effEnd->format('Y');

            $weeks[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end'   => $weekEnd->format('Y-m-d'),
                'label'      => $wLabel,
                'days'       => $days,
                'collapsed'  => false,
            ];

            $weekStart->addWeek();
        }

        $this->weekSchedule = $weeks;

        Notification::make()->success()
            ->title(count($weeks) . ' minggu berhasil di-generate!')
            ->body('Sekarang centang tanggal yang ingin dibooking di setiap minggu.')
            ->send();
    }

    public function toggleDay(int $weekIdx, int $dayIdx): void
    {
        if (isset($this->weekSchedule[$weekIdx]['days'][$dayIdx])) {
            $current = $this->weekSchedule[$weekIdx]['days'][$dayIdx]['checked'] ?? false;
            $this->weekSchedule[$weekIdx]['days'][$dayIdx]['checked'] = !$current;
        }
    }

    public function toggleWeekCollapse(int $weekIdx): void
    {
        if (isset($this->weekSchedule[$weekIdx])) {
            $this->weekSchedule[$weekIdx]['collapsed'] = !($this->weekSchedule[$weekIdx]['collapsed'] ?? false);
        }
    }

    public function selectAllDaysInWeek(int $weekIdx): void
    {
        if (!isset($this->weekSchedule[$weekIdx])) return;
        foreach ($this->weekSchedule[$weekIdx]['days'] as $di => $day) {
            if (!$day['is_past']) {
                $this->weekSchedule[$weekIdx]['days'][$di]['checked'] = true;
            }
        }
    }

    public function clearAllDaysInWeek(int $weekIdx): void
    {
        if (!isset($this->weekSchedule[$weekIdx])) return;
        foreach ($this->weekSchedule[$weekIdx]['days'] as $di => $day) {
            $this->weekSchedule[$weekIdx]['days'][$di]['checked'] = false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Monthly Schedule helpers
    // ──────────────────────────────────────────────────────────────────────────
    public function addMonthlyEntry(): void
    {
        $this->monthlySchedule[] = ['month_start' => now()->startOfMonth()->format('Y-m-d'), 'days_of_week' => []];
    }

    public function removeMonthlyEntry(int $idx): void
    {
        unset($this->monthlySchedule[$idx]);
        $this->monthlySchedule = array_values($this->monthlySchedule);
    }

    public function toggleMonthlyDay(int $idx, int $day): void
    {
        $current = $this->monthlySchedule[$idx]['days_of_week'] ?? [];
        if (in_array($day, $current)) {
            $this->monthlySchedule[$idx]['days_of_week'] = array_values(array_filter($current, fn($d) => $d !== $day));
        } else {
            $this->monthlySchedule[$idx]['days_of_week'][] = $day;
        }
    }

    public function toggleTimeSlot(string $slot): void
    {
        if (in_array($slot, $this->timeSlotsSelection)) {
            $this->timeSlotsSelection = array_values(array_filter($this->timeSlotsSelection, fn($s) => $s !== $slot));
        } else {
            $this->timeSlotsSelection[] = $slot;
        }
    }

    public function toggleWeeklyDay(int $day): void
    {
        if (in_array($day, $this->weeklyDays)) {
            $this->weeklyDays = array_values(array_filter($this->weeklyDays, fn($d) => $d !== $day));
        } else {
            $this->weeklyDays[] = $day;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Custom dates helpers
    // ──────────────────────────────────────────────────────────────────────────
    public function addCustomDate(): void
    {
        $this->customDates[] = now()->format('Y-m-d');
    }

    public function removeCustomDate(int $idx): void
    {
        unset($this->customDates[$idx]);
        $this->customDates = array_values($this->customDates);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Submit / Save
    // ──────────────────────────────────────────────────────────────────────────
    public function save(): void
    {
        set_time_limit(300);

        // Build $data array compatible with existing generator methods
        $data = [
            'customer_type'          => $this->customerType,
            'customer_name_manual'   => $this->customerNameManual,
            'customer_phone_manual'  => $this->customerPhoneManual,
            'client_id'              => $this->clientId,
            'venue_type'             => $this->venueType,
            'time_slots_selection'   => $this->timeSlotsSelection,
            'status'                 => $this->status,
            'payment_status'         => $this->paymentStatus,
            'is_paid'                => $this->isPaid,
            'notes'                  => $this->notes,
            'recurring_mode'         => $this->recurringMode,
            'weekly_days'            => $this->weeklyDays,
            'weekly_start_date'      => $this->weeklyStartDate,
            'weekly_duration'        => $this->weeklyDuration,
            'weekly_flex_schedule'   => $this->buildFlexScheduleForGenerator(),
            'monthly_schedule'       => $this->monthlySchedule,
            'selected_dates'         => array_map(fn($d) => ['date' => $d], $this->customDates),
        ];

        // Validate
        $errors = $this->runValidation($data);
        if (!empty($errors)) {
            $this->validationErrors = $errors;
            return;
        }
        $this->validationErrors = [];

        DB::beginTransaction();
        try {
            $selectedDates = $this->generateRecurringDates($data);

            if (empty($selectedDates)) {
                Notification::make()->warning()->title('Tidak Ada Tanggal')->body('Tidak ada tanggal valid yang dipilih.')->send();
                return;
            }

            // Conflict check (bulk, 1 query)
            $conflicts = $this->checkConflicts($selectedDates, $data['venue_type'], $data['time_slots_selection']);
            if (!empty($conflicts)) {
                $sample = implode(', ', array_map(fn($d) => Carbon::parse($d)->format('d M'), array_slice($conflicts, 0, 5)));
                $msg    = count($conflicts) > 5 ? count($conflicts) . ' tanggal konflik. Contoh: ' . $sample . '...' : 'Tanggal konflik: ' . $sample;
                Notification::make()->danger()->title('Ada Konflik Booking!')->body($msg)->persistent()->send();
                DB::rollBack();
                return;
            }

            // Resolve client
            $clientId = $this->resolveClient($data);

            // Build notes
            $notes   = $this->buildNotes($data);
            $now     = now()->toDateTimeString();
            $slots2insert = [];
            $firstBooking = null;
            $total   = 0;
            $count   = 0;

            foreach ($selectedDates as $date) {
                $timeSlots    = $this->formatTimeSlots($data['time_slots_selection'], $data['venue_type'], $date);
                $bookingPrice = array_sum(array_column($timeSlots, 'price'));
                $total       += $bookingPrice;

                $booking = Booking::create([
                    'client_id'      => $clientId,
                    'venue_id'       => $this->getVenueId($data['venue_type']),
                    'booking_date'   => $date,
                    'venue_type'     => $data['venue_type'],
                    'time_slots'     => $timeSlots,
                    'total_price'    => $bookingPrice,
                    'status'         => $data['status'],
                    'payment_status' => $data['payment_status'],
                    'is_paid'        => $data['is_paid'],
                    'booking_type'   => 'recurring',
                    'notes'          => $notes,
                ]);

                foreach ($timeSlots as $slot) {
                    $slots2insert[] = [
                        'booking_id' => $booking->id,
                        'date'       => $date,
                        'time_slot'  => $slot['time'],
                        'venue_type' => $data['venue_type'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!$firstBooking) $firstBooking = $booking;
                $count++;
            }

            // Batch insert booked_time_slots
            foreach (array_chunk($slots2insert, 100) as $chunk) {
                DB::table('booked_time_slots')->insert($chunk);
            }

            DB::commit();

            $fDate = Carbon::parse($selectedDates[0])->locale('id')->isoFormat('D MMM Y');
            $lDate = Carbon::parse(end($selectedDates))->locale('id')->isoFormat('D MMM Y');
            $name  = $this->customerType === 'manual' ? $this->customerNameManual : 'Customer';

            Notification::make()->success()
                ->title('Booking Member Berhasil!')
                ->body("✅ {$name} | 📅 {$count} booking | 💰 Rp " . number_format($total, 0, ',', '.') . " | 📆 {$fDate} – {$lDate}")
                ->send();

            $this->redirect(RecurringBookingResource::getUrl('index'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreateRecurringBooking failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            Notification::make()->danger()->title('Gagal')->body($e->getMessage())->persistent()->send();
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────────────────
    private function buildFlexScheduleForGenerator(): array
    {
        $result = [];
        foreach ($this->weekSchedule as $week) {
            $selectedDays = collect($week['days'] ?? [])
                ->filter(fn($d) => !empty($d['checked']))
                ->pluck('day_of_week')
                ->unique()->values()->toArray();

            if (!empty($selectedDays)) {
                $result[] = ['week_of' => $week['week_start'], 'days_of_week' => $selectedDays];
            }
        }
        return $result;
    }

    private function runValidation(array $data): array
    {
        $errors = [];
        if ($data['customer_type'] === 'manual' && empty(trim($data['customer_name_manual']))) {
            $errors[] = 'Nama customer harus diisi.';
        }
        if ($data['customer_type'] === 'existing' && empty($data['client_id'])) {
            $errors[] = 'Pilih customer terlebih dahulu.';
        }
        if (empty($data['venue_type'])) $errors[] = 'Pilih venue.';
        if (empty($data['time_slots_selection'])) $errors[] = 'Pilih minimal 1 waktu main.';

        $dates = $this->generateRecurringDates($data);
        if (empty($dates)) $errors[] = 'Tidak ada tanggal yang dipilih/di-generate.';

        return $errors;
    }

    private function generateRecurringDates(array $data): array
    {
        switch ($data['recurring_mode']) {
            case 'weekly':       return $this->generateWeeklyDates($data);
            case 'weekly_flex':  return $this->generateFlexibleWeeklyDates($data);
            case 'monthly_day':  return $this->generateMonthlyDayDates($data);
            case 'custom':
            default:             return $this->generateCustomDatesFromData($data);
        }
    }

    private function generateWeeklyDates(array $data): array
    {
        $dates       = [];
        $selectedDays = $data['weekly_days'] ?? [];
        $startDate   = Carbon::parse($data['weekly_start_date'] ?? now());
        $duration    = (int)($data['weekly_duration'] ?? 4);
        if (empty($selectedDays)) return [];

        $cur = $startDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $startDate->copy()->addWeeks($duration);
        while ($cur->lt($end)) {
            if (in_array($cur->dayOfWeek, $selectedDays) && $cur->gte($startDate)) {
                $dates[] = $cur->format('Y-m-d');
            }
            $cur->addDay();
        }
        return $dates;
    }

    private function generateFlexibleWeeklyDates(array $data): array
    {
        $dates    = [];
        $schedule = $data['weekly_flex_schedule'] ?? [];
        foreach ($schedule as $item) {
            $daysOfWeek = array_map('intval', $item['days_of_week'] ?? []);
            if (empty($daysOfWeek) || empty($item['week_of'])) continue;
            $ws  = Carbon::parse($item['week_of'])->startOfWeek(Carbon::MONDAY);
            $we  = $ws->copy()->endOfWeek(Carbon::SUNDAY);
            $cur = $ws->copy();
            while ($cur->lte($we)) {
                if (in_array($cur->dayOfWeek, $daysOfWeek) && $cur->gte(now()->startOfDay())) {
                    $dates[] = $cur->format('Y-m-d');
                }
                $cur->addDay();
            }
        }
        sort($dates);
        return array_values(array_unique($dates));
    }

    private function generateMonthlyDayDates(array $data): array
    {
        $dates    = [];
        $schedule = $data['monthly_schedule'] ?? [];
        foreach ($schedule as $item) {
            $daysOfWeek = array_map('intval', $item['days_of_week'] ?? []);
            if (empty($daysOfWeek) || empty($item['month_start'])) continue;
            $start = Carbon::parse($item['month_start'])->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $cur   = $start->copy();
            while ($cur->lte($end)) {
                if (in_array($cur->dayOfWeek, $daysOfWeek) && $cur->gte(now()->startOfDay())) {
                    $dates[] = $cur->format('Y-m-d');
                }
                $cur->addDay();
            }
        }
        sort($dates);
        return array_values(array_unique($dates));
    }

    private function generateCustomDatesFromData(array $data): array
    {
        return collect($data['selected_dates'] ?? [])
            ->pluck('date')->filter()
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique()->sort()->values()->toArray();
    }

    private function resolveClient(array $data): int
    {
        if ($data['customer_type'] === 'manual') {
            $client = Client::firstOrCreate(
                ['name' => $data['customer_name_manual'], 'phone' => $data['customer_phone_manual'] ?? null],
                ['email' => 'manual_' . time() . '_' . rand(100, 999) . '@thearena.local', 'password' => bcrypt('dummy_' . time())]
            );
            return $client->id;
        }
        return (int)$data['client_id'];
    }

    private function buildNotes(array $data): string
    {
        $notes = $data['notes'] ?? '';
        if ($data['customer_type'] === 'manual') {
            $notes = 'Customer Manual: ' . $data['customer_name_manual']
                . ($data['customer_phone_manual'] ? ' | Phone: ' . $data['customer_phone_manual'] : '')
                . ($notes ? ' | ' . $notes : '');
        }
        $notes .= ' | Booking Member | Generated: ' . Carbon::now()->format('d M Y H:i');
        return $notes;
    }

    private function checkConflicts(array $dates, string $venueType, array $timeSlots): array
    {
        return DB::table('booked_time_slots')
            ->join('bookings', 'booked_time_slots.booking_id', '=', 'bookings.id')
            ->whereIn('booked_time_slots.date', $dates)
            ->where('booked_time_slots.venue_type', $venueType)
            ->whereIn('booked_time_slots.time_slot', $timeSlots)
            ->where(function ($q) {
                $q->where('bookings.payment_status', 'paid')
                  ->orWhere('bookings.status', 'confirmed')
                  ->orWhere('bookings.is_paid', true);
            })
            ->pluck('booked_time_slots.date')->unique()->values()->toArray();
    }

    private function calculatePrice(string $venueType, string $date, string $timeSlot): int
    {
        $isWeekend = in_array(Carbon::parse($date)->dayOfWeek, [0, 6]);
        preg_match('/^(\d{2})\./', $timeSlot, $m);
        $h = isset($m[1]) ? (int)$m[1] : 0;

        if ($venueType === 'pvj') {
            if ($isWeekend)  return ($h >= 6 && $h < 20) ? 700000 : 500000;
            else             return ($h >= 6 && $h < 16) ? 350000 : (($h < 20) ? 700000 : 500000);
        }
        if ($venueType === 'cibadak_a') {
            if ($isWeekend)  return ($h >= 6 && $h < 20) ? 700000 : 500000;
            else             return ($h >= 6 && $h < 16) ? 350000 : 700000;
        }
        if ($venueType === 'cibadak_b') {
            if ($isWeekend)  return ($h >= 6 && $h < 20) ? 550000 : 450000;
            else             return ($h >= 6 && $h < 16) ? 300000 : (($h < 20) ? 550000 : 450000);
        }
        if ($venueType === 'urban') {
            if ($isWeekend)  return 550000;
            else             return ($h >= 6 && $h < 16) ? 300000 : 550000;
        }
        return 350000;
    }

    private function formatTimeSlots(array $selectedSlots, string $venueType, string $date): array
    {
        return array_map(fn($t) => ['time' => $t, 'duration' => 120, 'price' => $this->calculatePrice($venueType, $date, $t)], $selectedSlots);
    }

    private function getVenueId(string $venueType): int
    {
        return ['cibadak_a' => 1, 'cibadak_b' => 2, 'pvj' => 3, 'urban' => 4][$venueType] ?? 1;
    }
}