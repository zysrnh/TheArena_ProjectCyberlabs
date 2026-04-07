<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use App\Models\Booking;
use App\Models\BookedTimeSlot;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CalendarBooking extends Page
{
    protected static string $resource = BookingResource::class;
    protected static string $view = 'filament.admin.resources.booking.calendar';
    protected static ?string $title = 'Kalender Booking';

    #[Url] public $selectedVenue = 'all';
    #[Url] public $startDate = null;
    #[Url] public $endDate = null;
    public $dateRangeText = null;
    public $showBookingTypeModal = false;
    public $selectedDate = null;
    public $selectedTimeSlot = null;
    public $selectedVenueForBooking = null;

    public function mount(): void
    {
        // ✅ Cancel hanya sekali saat halaman pertama dibuka
        $this->cancelExpiredPendingBookings();
        if (!$this->startDate) $this->startDate = Carbon::today()->format('Y-m-d');
        if (!$this->endDate) $this->endDate = Carbon::today()->addDays(6)->format('Y-m-d');
        $this->updateDateRangeText();
    }

    private function cancelExpiredPendingBookings()
    {
        try {
            // ✅ Naikin dari 10 menit ke 60 menit
            $expirationTime = Carbon::now()->subMinutes(60);
            $expiredBookings = Booking::where('payment_status', 'pending')
                ->where('status', 'pending')
                ->where('booking_type', '!=', 'manual')
                ->where('created_at', '<', $expirationTime)
                ->get();

            foreach ($expiredBookings as $booking) {
                DB::beginTransaction();
                try {
                    $booking->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);
                    BookedTimeSlot::where('booking_id', $booking->id)->delete();
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                }
            }
            return $expiredBookings->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function exportToCSV()
    {
        $fileName = 'laporan-matrix-booking-' . Carbon::parse($this->startDate)->format('d-M-Y') . 
                    '-sd-' . Carbon::parse($this->endDate)->format('d-M-Y') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $timeSlots = $this->getTimeSlots();
        
        $currentRow = 1;
        
        if ($this->selectedVenue !== 'all') {
            $currentRow = $this->exportSingleVenueToSheet($sheet, $startDate, $endDate, $timeSlots, $currentRow);
        } else {
            $currentRow = $this->exportAllVenuesToSheet($sheet, $startDate, $endDate, $timeSlots, $currentRow);
        }

        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function exportSingleVenueToSheet($sheet, $startDate, $endDate, $timeSlots, $startRow)
    {
        $venueName = match($this->selectedVenue) {
            'cibadak_a' => 'Cibadak A',
            'cibadak_b' => 'Cibadak B',
            'pvj' => 'PVJ Mall',
            'urban' => 'Urban',
            default => ucfirst(str_replace('_', ' ', $this->selectedVenue))
        };

        $currentRow = $startRow;
        $totalDays = $endDate->diffInDays($startDate) + 1;
        $totalColumns = max($totalDays + 1, 2);
        $lastColumn = $this->getColumnLetter($totalColumns);
        
        $sheet->setCellValue('A' . $currentRow, $venueName);
        $sheet->mergeCells('A' . $currentRow . ':' . $lastColumn . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '013064']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(30);
        $currentRow += 2;
        
        $col = 1;
        $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, 'WAKTU', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $col++;
            $dateStr = $date->format('d M') . ' (' . $date->isoFormat('ddd') . ')';
            $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $dateStr, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        
        $headerRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '024a8f']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(25);
        $currentRow++;
        
        foreach ($timeSlots as $slot) {
            $col = 1;
            $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $slot, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $col++;
                $bookings = $this->getBookingsForDateTimeVenue($date->format('Y-m-d'), $slot, $this->selectedVenue);
                
                $cellValue = '';
                $cellColor = null;
                $textColor = '000000';
                
                if ($bookings->isNotEmpty()) {
                    $cellValue = $bookings->map(fn($b) => $this->extractCustomerName($b))->join(', ');
                    $bookingType = $this->determineBookingType($bookings->first());
                    [$cellColor, $textColor] = $this->getColorByType($bookingType);
                }
                
                $cellAddress = $this->getColumnLetter($col) . $currentRow;
                $sheet->setCellValueExplicit($cellAddress, $cellValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                
                if ($cellColor) {
                    $sheet->getStyle($cellAddress)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $cellColor]],
                        'font' => ['color' => ['rgb' => $textColor], 'bold' => true],
                    ]);
                }
            }
            
            $dataRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet->getStyle('A' . $currentRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            ]);
            
            $currentRow++;
        }
        
        return $currentRow + 2;
    }

    private function exportAllVenuesToSheet($sheet, $startDate, $endDate, $timeSlots, $startRow)
    {
        $venues = [
            'cibadak_a' => 'Cibadak A',
            'cibadak_b' => 'Cibadak B',
            'pvj' => 'PVJ Mall',
            'urban' => 'Urban',
        ];

        $currentRow = $startRow;
        $totalDays = $endDate->diffInDays($startDate) + 1;
        $totalColumns = max($totalDays + 1, 2);
        $lastColumn = $this->getColumnLetter($totalColumns);

        foreach ($venues as $venueKey => $venueName) {
            $sheet->setCellValueExplicit('A' . $currentRow, $venueName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->mergeCells('A' . $currentRow . ':' . $lastColumn . $currentRow);
            $sheet->getStyle('A' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '013064']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(30);
            $currentRow += 2;
            
            $col = 1;
            $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, 'WAKTU', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $col++;
                $dateStr = $date->format('d M') . ' (' . $date->isoFormat('ddd') . ')';
                $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $dateStr, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            
            $headerRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '024a8f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(25);
            $currentRow++;
            
            foreach ($timeSlots as $slot) {
                $col = 1;
                $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $slot, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $col++;
                    $bookings = $this->getBookingsForDateTimeVenue($date->format('Y-m-d'), $slot, $venueKey);
                    
                    $cellValue = '';
                    $cellColor = null;
                    $textColor = '000000';
                    
                    if ($bookings->isNotEmpty()) {
                        $cellValue = $bookings->map(fn($b) => $this->extractCustomerName($b))->join(', ');
                        $bookingType = $this->determineBookingType($bookings->first());
                        [$cellColor, $textColor] = $this->getColorByType($bookingType);
                    }
                    
                    $cellAddress = $this->getColumnLetter($col) . $currentRow;
                    $sheet->setCellValueExplicit($cellAddress, $cellValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    
                    if ($cellColor) {
                        $sheet->getStyle($cellAddress)->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $cellColor]],
                            'font' => ['color' => ['rgb' => $textColor], 'bold' => true],
                        ]);
                    }
                }
                
                $dataRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);
                $sheet->getStyle('A' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                ]);
                
                $currentRow++;
            }
            
            $currentRow += 2;
        }
        
        return $currentRow;
    }

    public function exportToCSVByColor()
    {
        $fileName = 'laporan-booking-by-color-' . Carbon::parse($this->startDate)->format('d-M-Y') . 
                    '-sd-' . Carbon::parse($this->endDate)->format('d-M-Y') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $categories = [
            'paid'      => ['label' => 'LUNAS (Hijau)',   'color' => '059669', 'data' => []],
            'pending'   => ['label' => 'PENDING (Pink)',  'color' => 'ec4899', 'data' => []],
            'manual'    => ['label' => 'MANUAL (Kuning)', 'color' => 'FFD22F', 'data' => []],
            'recurring' => ['label' => 'MEMBER (Oranye)', 'color' => 'ea580c', 'data' => []],
        ];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $query = DB::table('bookings')
                ->leftJoin('clients', 'bookings.client_id', '=', 'clients.id')
                ->where('bookings.booking_date', $date->format('Y-m-d'))
                ->whereNotIn('bookings.status', ['cancelled'])
                ->select(
                    'bookings.id',
                    'bookings.booking_date',
                    'bookings.time_slots',
                    'bookings.venue_type',
                    'bookings.total_price',
                    'bookings.payment_status',
                    'bookings.status',
                    'bookings.is_paid',
                    'bookings.notes',
                    'bookings.booking_type',
                    'clients.name as client_name'
                );

            if ($this->selectedVenue !== 'all') {
                $query->where('bookings.venue_type', $this->selectedVenue);
            }

            $bookings = $query->get();

            foreach ($bookings as $booking) {
                $timeSlots = json_decode($booking->time_slots, true);
                
                if (is_array($timeSlots)) {
                    foreach ($timeSlots as $slot) {
                        $time = $slot['time'] ?? null;
                        
                        if ($time) {
                            $bookingType = $this->determineBookingType($booking);
                            $clientName = $this->extractCustomerName($booking);
                            
                            $venueName = match($booking->venue_type) {
                                'cibadak_a' => 'Cibadak A',
                                'cibadak_b' => 'Cibadak B',
                                'pvj' => 'PVJ Mall',
                                'urban' => 'Urban',
                                default => ucfirst(str_replace('_', ' ', $booking->venue_type))
                            };
                            
                            $paymentStatus = match($bookingType) {
                                'pending' => 'Pending',
                                'paid' => 'Lunas',
                                'manual' => 'Manual',
                                'recurring', 'member_manual' => 'Member',
                                default => 'Lunas'
                            };
                            
                            $typeLabel = match($bookingType) {
                                'recurring' => 'Member Rutin',
                                'pending' => 'Booking Pending',
                                'manual' => 'Booking Manual',
                                'member_manual' => 'Member Manual',
                                default => 'Booking Biasa'
                            };

                            $categoryKey = match($bookingType) {
                                'member_manual' => 'recurring',
                                default => $bookingType
                            };

                            if (isset($categories[$categoryKey])) {
                                $categories[$categoryKey]['data'][] = [
                                    Carbon::parse($booking->booking_date)->format('d/m/Y'),
                                    Carbon::parse($booking->booking_date)->isoFormat('dddd'),
                                    $time,
                                    $venueName,
                                    $clientName,
                                    $paymentStatus,
                                    $typeLabel,
                                    'Rp ' . number_format($booking->total_price ?? 0, 0, ',', '.'),
                                ];
                            }
                        }
                    }
                }
            }
        }

        $currentRow = 1;
        
        foreach ($categories as $key => $category) {
            if (!empty($category['data'])) {
                $currentRow++;
                
                $sheet->setCellValueExplicit('A' . $currentRow, '=== ' . $category['label'] . ' ===', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
                $sheet->getStyle('A' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $category['color']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);
                $sheet->getRowDimension($currentRow)->setRowHeight(30);
                $currentRow += 2;
                
                $headers = ['Tanggal', 'Hari', 'Jam', 'Venue', 'Nama Customer', 'Status', 'Tipe', 'Total Harga'];
                $col = 0;
                foreach ($headers as $header) {
                    $col++;
                    $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $header, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                
                $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                ]);
                $sheet->getRowDimension($currentRow)->setRowHeight(25);
                $currentRow++;
                
                foreach ($category['data'] as $rowData) {
                    $col = 0;
                    foreach ($rowData as $cellData) {
                        $col++;
                        $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $cellData, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    
                    $dataRange = 'A' . $currentRow . ':H' . $currentRow;
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                    ]);
                    
                    if ($currentRow % 2 == 0) {
                        $sheet->getStyle($dataRange)->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']]
                        ]);
                    }
                    
                    $currentRow++;
                }
            }
        }

        for ($i = 1; $i <= 8; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function getColorByType(string $bookingType): array
    {
        return match($bookingType) {
            'recurring', 'member_manual' => ['ea580c', 'FFFFFF'],
            'pending'                    => ['ec4899', 'FFFFFF'],
            'manual'                     => ['FFD22F', '1e293b'],
            default                      => ['059669', 'FFFFFF'], // paid
        };
    }

    private function getColumnLetter($columnNumber)
    {
        $letter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $letter = chr($columnNumber % 26 + 65) . $letter;
            $columnNumber = intdiv($columnNumber, 26);
        }
        return $letter;
    }

    private function getBookingsForDateTimeVenue($date, $timeSlot, $venue)
    {
        $bookings = DB::table('bookings')
            ->leftJoin('clients', 'bookings.client_id', '=', 'clients.id')
            ->where('bookings.booking_date', $date)
            ->where('bookings.venue_type', $venue)
            ->whereNotIn('bookings.status', ['cancelled'])
            ->select(
                'bookings.id',
                'bookings.time_slots',
                'bookings.venue_type',
                'bookings.total_price',
                'bookings.payment_status',
                'bookings.status',
                'bookings.is_paid',
                'bookings.notes',
                'bookings.booking_type',
                'clients.name as client_name'
            )
            ->get();

        return $bookings->filter(function($booking) use ($timeSlot) {
            $timeSlots = json_decode($booking->time_slots, true);
            if (is_array($timeSlots)) {
                foreach ($timeSlots as $slot) {
                    if (($slot['time'] ?? null) === $timeSlot) {
                        return true;
                    }
                }
            }
            return false;
        });
    }

    public function setCurrentMonth()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->updateDateRangeText();
    }

    public function openBookingTypeModal($date, $timeSlot, $venue = null)
    {
        $this->selectedDate = $date;
        $this->selectedTimeSlot = $timeSlot;
        $this->selectedVenueForBooking = $venue !== 'all' ? $venue : null;
        $this->showBookingTypeModal = true;
    }

    public function createRegularBooking()
    {
        session(['calendar_prefill' => [
            'booking_date' => $this->selectedDate,
            'time_slot' => $this->selectedTimeSlot,
            'venue_type' => $this->selectedVenueForBooking,
        ]]);
        $this->showBookingTypeModal = false;
        return redirect()->route('filament.admin.resources.bookings.create');
    }

    public function createRecurringBooking()
    {
        session(['recurring_prefill' => [
            'booking_date' => $this->selectedDate,
            'time_slot' => $this->selectedTimeSlot,
            'venue_type' => $this->selectedVenueForBooking,
        ]]);
        $this->showBookingTypeModal = false;
        return redirect()->route('filament.admin.resources.recurring-bookings.create');
    }

    public function closeBookingTypeModal()
    {
        $this->showBookingTypeModal = false;
        $this->selectedDate = null;
        $this->selectedTimeSlot = null;
        $this->selectedVenueForBooking = null;
    }

    public function applyDateRange()
    {
        $this->updateDateRangeText();
        $this->dispatch('close-modal', id: 'date-range-modal');
    }

    public function clearDateRange()
    {
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->addDays(6)->format('Y-m-d');
        $this->dateRangeText = null;
    }

    protected function updateDateRangeText()
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);
            $this->dateRangeText = $start->format('d M Y') . ' - ' . $end->format('d M Y');
        }
    }

    private function extractCustomerName($booking): string
    {
        $clientName = $booking->client_name ?? null;
        
        if (empty($clientName) && !empty($booking->notes)) {
            if (preg_match('/Customer Manual:\s*([^|]+)/', $booking->notes, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return $clientName ?? 'Guest';
    }

    public function getScheduleData()
    {
        // ✅ DIHAPUS: $this->cancelExpiredPendingBookings();
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $schedules = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $daySchedule = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->isoFormat('dddd'),
                'date_number' => $date->format('d'),
                'month' => $date->isoFormat('MMMM'),
                'is_today' => $date->isToday(),
                'bookings' => []
            ];

            $query = DB::table('bookings')
                ->leftJoin('clients', 'bookings.client_id', '=', 'clients.id')
                ->where('bookings.booking_date', $date->format('Y-m-d'))
                ->whereNotIn('bookings.status', ['cancelled'])
                ->select(
                    'bookings.id',
                    'bookings.time_slots',
                    'bookings.venue_type',
                    'bookings.total_price',
                    'bookings.payment_status',
                    'bookings.status',
                    'bookings.is_paid',
                    'bookings.notes',
                    'bookings.booking_type',
                    'clients.name as client_name'
                );

            if ($this->selectedVenue !== 'all') {
                $query->where('bookings.venue_type', $this->selectedVenue);
            }

            $bookings = $query->get();

            foreach ($bookings as $booking) {
                $timeSlots = json_decode($booking->time_slots, true);
                
                if (is_array($timeSlots)) {
                    foreach ($timeSlots as $slot) {
                        $time = $slot['time'] ?? null;
                        
                        if ($time) {
                            if (!isset($daySchedule['bookings'][$time])) {
                                $daySchedule['bookings'][$time] = collect();
                            }
                            
                            $bookingType = $this->determineBookingType($booking);
                            $clientName = $this->extractCustomerName($booking);
                            
                            $daySchedule['bookings'][$time]->push((object)[
                                'id' => $booking->id,
                                'client' => (object)['name' => $clientName],
                                'venue_type' => $booking->venue_type,
                                'total_price' => $booking->total_price,
                                'is_recurring' => $bookingType === 'recurring',
                                'booking_type' => $bookingType,
                            ]);
                        }
                    }
                }
            }

            $schedules[] = $daySchedule;
        }

        return $schedules;
    }

private function determineBookingType($booking): string
{
    // ✅ Cek status pending PERTAMA, override booking_type apapun
    if (isset($booking->status) && $booking->status === 'pending') {
        return 'pending';
    }

    if (isset($booking->payment_status) && $booking->payment_status === 'pending') {
        return 'pending';
    }

    // Baru percaya booking_type
    if (isset($booking->booking_type) && !empty($booking->booking_type)) {
        return $booking->booking_type;
    }

    // Cek notes recurring
    if (isset($booking->notes) && $booking->notes && (
        stripos($booking->notes, 'rutin') !== false ||
        stripos($booking->notes, 'recurring') !== false ||
        stripos($booking->notes, 'bulanan') !== false ||
        stripos($booking->notes, 'member') !== false
    )) {
        return 'recurring';
    }

    if ((isset($booking->is_paid) && $booking->is_paid) || 
        (isset($booking->payment_status) && $booking->payment_status === 'paid')) {
        return 'paid';
    }

    return 'pending';
}
    public function getScheduleDataPerVenue()
    {
        // ✅ DIHAPUS: $this->cancelExpiredPendingBookings();

        $venues = [
            'cibadak_a' => 'Cibadak A',
            'cibadak_b' => 'Cibadak B',
            'pvj' => 'PVJ Mall',
            'urban' => 'Urban',
        ];

        $schedulesByVenue = [];

        foreach ($venues as $venueKey => $venueName) {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            $schedules = [];

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $daySchedule = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->isoFormat('dddd'),
                    'date_number' => $date->format('d'),
                    'month' => $date->isoFormat('MMMM'),
                    'is_today' => $date->isToday(),
                    'bookings' => []
                ];

                $bookings = DB::table('bookings')
                    ->leftJoin('clients', 'bookings.client_id', '=', 'clients.id')
                    ->where('bookings.booking_date', $date->format('Y-m-d'))
                    ->where('bookings.venue_type', $venueKey)
                    ->whereNotIn('bookings.status', ['cancelled'])
                    ->select(
                        'bookings.id',
                        'bookings.time_slots',
                        'bookings.venue_type',
                        'bookings.total_price',
                        'bookings.payment_status',
                        'bookings.status',
                        'bookings.is_paid',
                        'bookings.notes',
                        'bookings.booking_type',
                        'clients.name as client_name'
                    )
                    ->get();

                foreach ($bookings as $booking) {
                    $timeSlots = json_decode($booking->time_slots, true);
                    
                    if (is_array($timeSlots)) {
                        foreach ($timeSlots as $slot) {
                            $time = $slot['time'] ?? null;
                            
                            if ($time) {
                                if (!isset($daySchedule['bookings'][$time])) {
                                    $daySchedule['bookings'][$time] = collect();
                                }
                                
                                $bookingType = $this->determineBookingType($booking);
                                $clientName = $this->extractCustomerName($booking);
                                
                                $daySchedule['bookings'][$time]->push((object)[
                                    'id' => $booking->id,
                                    'client' => (object)['name' => $clientName],
                                    'venue_type' => $booking->venue_type,
                                    'total_price' => $booking->total_price,
                                    'is_recurring' => $bookingType === 'recurring',
                                    'booking_type' => $bookingType,
                                ]);
                            }
                        }
                    }
                }

                $schedules[] = $daySchedule;
            }

            $schedulesByVenue[] = [
                'venue_key' => $venueKey,
                'venue_name' => $venueName,
                'schedules' => $schedules
            ];
        }

        return $schedulesByVenue;
    }

    public function getTimeSlots()
    {
        return [
            '06.00 - 08.00', '08.00 - 10.00', '10.00 - 12.00', '12.00 - 14.00',
            '14.00 - 16.00', '16.00 - 18.00', '18.00 - 20.00', '20.00 - 22.00', '22.00 - 00.00',
        ];
    }

    // ✅ hydrate tidak cancel booking lagi
    public function hydrate() {}

    public function refreshCalendar()
    {
        $this->dispatch('calendar-refreshed');
    }
}