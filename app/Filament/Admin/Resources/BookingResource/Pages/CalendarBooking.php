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
        $this->cancelExpiredPendingBookings();
        if (!$this->startDate) $this->startDate = Carbon::today()->format('Y-m-d');
        if (!$this->endDate) $this->endDate = Carbon::today()->addDays(6)->format('Y-m-d');
        $this->updateDateRangeText();
    }

    private function cancelExpiredPendingBookings()
    {
        try {
            $expirationTime = Carbon::now()->subMinutes(10);
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

    /**
     * ✅ EXPORT TO EXCEL - Format Matrix dengan Border & Warna
     */
    public function exportToCSV()
    {
        $this->cancelExpiredPendingBookings();
        
        $fileName = 'laporan-matrix-booking-' . Carbon::parse($this->startDate)->format('d-M-Y') . 
                    '-sd-' . Carbon::parse($this->endDate)->format('d-M-Y') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $timeSlots = $this->getTimeSlots();
        
        $currentRow = 1;
        
        // Jika filter per venue
        if ($this->selectedVenue !== 'all') {
            $currentRow = $this->exportSingleVenueToSheet($sheet, $startDate, $endDate, $timeSlots, $currentRow);
        } else {
            // Export semua venue
            $currentRow = $this->exportAllVenuesToSheet($sheet, $startDate, $endDate, $timeSlots, $currentRow);
        }

        // Auto-size columns
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

    /**
     * Export single venue dalam format matrix dengan styling
     */
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
        
        // Hitung jumlah kolom (1 untuk WAKTU + jumlah hari)
        $totalDays = $endDate->diffInDays($startDate) + 1;
        $totalColumns = $totalDays + 1; // +1 for WAKTU column
        
        // Validate minimum columns
        if ($totalColumns < 2) {
            $totalColumns = 2; // Minimum 2 columns (WAKTU + at least 1 date)
        }
        
        $lastColumn = $this->getColumnLetter($totalColumns);
        
        // Header venue dengan styling
        $sheet->setCellValue('A' . $currentRow, $venueName);
        $sheet->mergeCells('A' . $currentRow . ':' . $lastColumn . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '013064']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(30);
        $currentRow += 2;
        
        // Header tanggal
        $col = 1;
        $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, 'WAKTU', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $col++;
            $dateStr = $date->format('d M') . ' (' . $date->isoFormat('ddd') . ')';
            $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $dateStr, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        
        // Styling header tanggal
        $headerRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '024a8f']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(25);
        $currentRow++;
        
        // Isi data per time slot
        foreach ($timeSlots as $slot) {
            $col = 1;
            $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $slot, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $col++;
                $bookings = $this->getBookingsForDateTimeVenue(
                    $date->format('Y-m-d'), 
                    $slot, 
                    $this->selectedVenue
                );
                
                $cellValue = '';
                $cellColor = null;
                $textColor = '000000'; // Default black text
                
                if ($bookings->isNotEmpty()) {
                    $names = $bookings->map(function($booking) {
                        return $this->extractCustomerName($booking);
                    })->join(', ');
                    $cellValue = $names;
                    
                    // Determine color based on first booking type
                    $firstBooking = $bookings->first();
                    $bookingType = $this->determineBookingType($firstBooking);
                    
                    // Set colors based on booking type
                    if ($bookingType === 'recurring' || $bookingType === 'member_manual') {
                        $cellColor = 'ea580c'; // Orange for Member
                        $textColor = 'FFFFFF'; // White text
                    } elseif ($bookingType === 'pending') {
                        $cellColor = 'ec4899'; // Pink for Pending
                        $textColor = 'FFFFFF'; // White text
                    } elseif ($bookingType === 'manual') {
                        $cellColor = 'FFD22F'; // Yellow for Manual
                        $textColor = '1e293b'; // Dark text
                    } else {
                        $cellColor = '059669'; // Green for Paid/Lunas
                        $textColor = 'FFFFFF'; // White text
                    }
                }
                
                $cellAddress = $this->getColumnLetter($col) . $currentRow;
                $sheet->setCellValueExplicit($cellAddress, $cellValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                
                // Apply color if booking exists
                if ($cellColor) {
                    $sheet->getStyle($cellAddress)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $cellColor]],
                        'font' => ['color' => ['rgb' => $textColor], 'bold' => true],
                    ]);
                }
            }
            
            // Styling data row
            $dataRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            
            // Bold untuk kolom waktu
            $sheet->getStyle('A' . $currentRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            ]);
            
            $currentRow++;
        }
        
        return $currentRow + 2;
    }

    /**
     * Export semua venue dalam format matrix terpisah dengan styling
     */
    private function exportAllVenuesToSheet($sheet, $startDate, $endDate, $timeSlots, $startRow)
    {
        $venues = [
            'cibadak_a' => 'Cibadak A',
            'cibadak_b' => 'Cibadak B',
            'pvj' => 'PVJ Mall',
            'urban' => 'Urban',
        ];

        $currentRow = $startRow;
        
        // Hitung jumlah kolom (1 untuk WAKTU + jumlah hari)
        $totalDays = $endDate->diffInDays($startDate) + 1;
        $totalColumns = $totalDays + 1; // +1 for WAKTU column
        
        // Validate minimum columns
        if ($totalColumns < 2) {
            $totalColumns = 2; // Minimum 2 columns
        }
        
        $lastColumn = $this->getColumnLetter($totalColumns);

        foreach ($venues as $venueKey => $venueName) {
            // Header venue
            $sheet->setCellValueExplicit('A' . $currentRow, $venueName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->mergeCells('A' . $currentRow . ':' . $lastColumn . $currentRow);
            $sheet->getStyle('A' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '013064']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(30);
            $currentRow += 2;
            
            // Header tanggal
            $col = 1;
            $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, 'WAKTU', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $col++;
                $dateStr = $date->format('d M') . ' (' . $date->isoFormat('ddd') . ')';
                $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $dateStr, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            
            // Styling header tanggal
            $headerRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '024a8f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(25);
            $currentRow++;
            
            // Isi data per time slot
            foreach ($timeSlots as $slot) {
                $col = 1;
                $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $slot, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $col++;
                    $bookings = $this->getBookingsForDateTimeVenue(
                        $date->format('Y-m-d'), 
                        $slot, 
                        $venueKey
                    );
                    
                    $cellValue = '';
                    $cellColor = null;
                    $textColor = '000000'; // Default black text
                    
                    if ($bookings->isNotEmpty()) {
                        $names = $bookings->map(function($booking) {
                            return $this->extractCustomerName($booking);
                        })->join(', ');
                        $cellValue = $names;
                        
                        // Determine color based on first booking type
                        $firstBooking = $bookings->first();
                        $bookingType = $this->determineBookingType($firstBooking);
                        
                        // Set colors based on booking type
                        if ($bookingType === 'recurring' || $bookingType === 'member_manual') {
                            $cellColor = 'ea580c'; // Orange for Member
                            $textColor = 'FFFFFF'; // White text
                        } elseif ($bookingType === 'pending') {
                            $cellColor = 'ec4899'; // Pink for Pending
                            $textColor = 'FFFFFF'; // White text
                        } elseif ($bookingType === 'manual') {
                            $cellColor = 'FFD22F'; // Yellow for Manual
                            $textColor = '1e293b'; // Dark text
                        } else {
                            $cellColor = '059669'; // Green for Paid/Lunas
                            $textColor = 'FFFFFF'; // White text
                        }
                    }
                    
                    $cellAddress = $this->getColumnLetter($col) . $currentRow;
                    $sheet->setCellValueExplicit($cellAddress, $cellValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    
                    // Apply color if booking exists
                    if ($cellColor) {
                        $sheet->getStyle($cellAddress)->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $cellColor]],
                            'font' => ['color' => ['rgb' => $textColor], 'bold' => true],
                        ]);
                    }
                }
                
                // Styling data row
                $dataRange = 'A' . $currentRow . ':' . $this->getColumnLetter($col) . $currentRow;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);
                
                // Bold untuk kolom waktu
                $sheet->getStyle('A' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                ]);
                
                $currentRow++;
            }
            
            // Spacing antar venue
            $currentRow += 2;
        }
        
        return $currentRow;
    }

    /**
     * Helper function untuk convert nomor kolom ke huruf
     */
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

    /**
     * Get bookings untuk tanggal, waktu, dan venue tertentu
     */
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

    /**
     * ✅ EXPORT TO EXCEL DENGAN PEMISAHAN WARNA (Alternative - Detailed Report)
     */
    public function exportToCSVByColor()
    {
        $this->cancelExpiredPendingBookings();
        
        $fileName = 'laporan-booking-by-color-' . Carbon::parse($this->startDate)->format('d-M-Y') . 
                    '-sd-' . Carbon::parse($this->endDate)->format('d-M-Y') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        // Group by category
        $categories = [
            'paid' => ['label' => 'LUNAS (Hijau)', 'color' => '059669', 'data' => []],
            'pending' => ['label' => 'PENDING (Pink)', 'color' => 'ec4899', 'data' => []],
            'manual' => ['label' => 'MANUAL (Kuning)', 'color' => 'FFD22F', 'data' => []],
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
                                'recurring' => 'Member',
                                'member_manual' => 'Member',
                                default => 'Lunas'
                            };
                            
                            $typeLabel = match($bookingType) {
                                'recurring' => 'Member Rutin',
                                'pending' => 'Booking Pending',
                                'manual' => 'Booking Manual',
                                'member_manual' => 'Member Manual',
                                default => 'Booking Biasa'
                            };

                            // Normalize booking type for grouping
                            $categoryKey = match($bookingType) {
                                'member_manual' => 'recurring',
                                default => $bookingType
                            };

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

        // Output grouped by category
        $currentRow = 1;
        
        foreach ($categories as $key => $category) {
            if (!empty($category['data'])) {
                // Spacing
                $currentRow++;
                
                // Category header
                $sheet->setCellValueExplicit('A' . $currentRow, '=== ' . $category['label'] . ' ===', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
                $sheet->getStyle('A' . $currentRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $category['color']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);
                $sheet->getRowDimension($currentRow)->setRowHeight(30);
                $currentRow += 2;
                
                // Column headers
                $headers = ['Tanggal', 'Hari', 'Jam', 'Venue', 'Nama Customer', 'Status', 'Tipe', 'Total Harga'];
                $col = 0;
                foreach ($headers as $header) {
                    $col++;
                    $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $header, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                
                // Styling header columns
                $headerRange = 'A' . $currentRow . ':H' . $currentRow;
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
                ]);
                $sheet->getRowDimension($currentRow)->setRowHeight(25);
                $currentRow++;
                
                // Data rows
                foreach ($category['data'] as $rowData) {
                    $col = 0;
                    foreach ($rowData as $cellData) {
                        $col++;
                        $sheet->setCellValueExplicit($this->getColumnLetter($col) . $currentRow, $cellData, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    
                    // Styling data row
                    $dataRange = 'A' . $currentRow . ':H' . $currentRow;
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                    ]);
                    
                    // Zebra striping
                    if ($currentRow % 2 == 0) {
                        $sheet->getStyle($dataRange)->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']]
                        ]);
                    }
                    
                    $currentRow++;
                }
            }
        }

        // Auto-size columns
        for ($i = 1; $i <= 8; $i++) { // We have 8 columns (A to H)
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * ✅ SET CURRENT MONTH DATE RANGE
     */
    public function setCurrentMonth()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->updateDateRangeText();
        $this->cancelExpiredPendingBookings();
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
        $this->cancelExpiredPendingBookings();
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
        $this->cancelExpiredPendingBookings();
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
        $this->cancelExpiredPendingBookings();
        $this->updateDateRangeText();
        $this->dispatch('close-modal', id: 'date-range-modal');
    }

    public function clearDateRange()
    {
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->addDays(6)->format('Y-m-d');
        $this->dateRangeText = null;
        $this->cancelExpiredPendingBookings();
    }

    protected function updateDateRangeText()
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);
            $this->dateRangeText = $start->format('d M Y') . ' - ' . $end->format('d M Y');
        }
    }

    /**
     * ✅ Helper function to extract customer name
     */
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
        $this->cancelExpiredPendingBookings();
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
    // ✅ Cek booking_type field dulu
    if (isset($booking->booking_type) && !empty($booking->booking_type)) {
        return $booking->booking_type;
    }

    // ✅ Cek pending SEBELUM cek paid
    if (isset($booking->status) && $booking->status === 'pending') {
        return 'pending';
    }

    if (isset($booking->payment_status) && $booking->payment_status === 'pending') {
        return 'pending';
    }

    // ✅ Cek notes untuk recurring
    if (isset($booking->notes) && $booking->notes && (
        stripos($booking->notes, 'rutin') !== false ||
        stripos($booking->notes, 'recurring') !== false ||
        stripos($booking->notes, 'bulanan') !== false ||
        stripos($booking->notes, 'member') !== false ||
        stripos($booking->notes, 'Booking Member') !== false
    )) {
        return 'recurring';
    }

    // ✅ Baru cek paid
    if ((isset($booking->is_paid) && $booking->is_paid) || 
        (isset($booking->payment_status) && $booking->payment_status === 'paid')) {
        return 'paid';
    }

    return 'pending'; // ✅ Default pending, bukan paid
}

    public function getScheduleDataPerVenue()
    {
        $this->cancelExpiredPendingBookings();

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

    public function hydrate() { $this->cancelExpiredPendingBookings(); }
    public function refreshCalendar() { $this->cancelExpiredPendingBookings(); $this->dispatch('calendar-refreshed'); }
}