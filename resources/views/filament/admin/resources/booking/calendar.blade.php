<x-filament-panels::page>
    @php
        $schedules = $this->selectedVenue === 'all' ? [] : $this->getScheduleData();
        $schedulesByVenue = $this->selectedVenue === 'all' ? $this->getScheduleDataPerVenue() : [];
        $timeSlots = $this->getTimeSlots();
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        
        .full-calendar-wrapper {
            margin: -2rem -2rem 0 -2rem;
            min-height: 100vh;
            background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .calendar-header-section {
            background: linear-gradient(135deg, #013064 0%, #024a8f 100%);
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .calendar-header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 210, 47, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 210, 47, 0.06) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .calendar-table-wrapper {
            background: white;
            padding: 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .venue-section {
            margin-bottom: 2.5rem;
        }

        .venue-header {
            background: linear-gradient(135deg, #013064 0%, #024a8f 100%);
            padding: 1.25rem 2rem;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .venue-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .venue-icon {
            width: 2rem;
            height: 2rem;
            background: rgba(255, 210, 47, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .booking-cell {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 85px;
            max-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        
        .booking-cell::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: rgba(255, 255, 255, 0.4);
            transition: width 0.3s ease;
        }

        .booking-cell:hover::before {
            width: 6px;
        }

        .booking-cell:hover {
            transform: translateY(-3px) scale(1.015);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        }

        /* Premium Color Schemes */
        .booking-paid {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 3px 12px 0 rgba(5, 150, 105, 0.2);
            color: #ffffff !important;
        }

        .booking-unpaid {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
            box-shadow: 0 3px 12px 0 rgba(236, 72, 153, 0.2);
            color: #ffffff !important;
        }

        .booking-manual {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 3px 12px 0 rgba(245, 158, 11, 0.2);
            color: #ffffff !important;
        }

        .booking-member {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            box-shadow: 0 3px 12px 0 rgba(139, 92, 246, 0.2);
            color: #ffffff !important;
        }

        .booking-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 220px;
            overflow-y: auto;
        }

        .booking-stack::-webkit-scrollbar {
            width: 4px;
        }

        .booking-stack::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }

        .empty-cell {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            border: 2px solid #e5e7eb;
            position: relative;
            min-height: 85px;
            padding: 1rem;
        }

        .empty-cell::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #ffd22f 0%, #fbbf24 100%);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .empty-cell:hover::after {
            opacity: 0.12;
        }

        .empty-cell:hover {
            border-color: #ffd22f;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(255, 210, 47, 0.15);
        }

        .empty-cell > * {
            position: relative;
            z-index: 1;
        }
        
        .calendar-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .calendar-table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, #013064 0%, #024a8f 100%);
            padding: 1.25rem 0.75rem;
            border: none;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .calendar-table thead th:last-child {
            border-right: none;
        }

        .calendar-table tbody td {
            border-right: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem;
            vertical-align: top;
            min-height: 100px;
            max-height: 240px;
            background: white;
        }

        .calendar-table tbody td:last-child {
            border-right: none;
        }

        .time-cell {
            position: sticky;
            left: 0;
            z-index: 5;
            background: linear-gradient(to right, #f8fafc 0%, #f1f5f9 100%);
            font-weight: 700;
            font-size: 13px;
            color: #013064;
            padding: 1rem 1.25rem !important;
            border-right: 3px solid #013064 !important;
            white-space: nowrap;
            box-shadow: 4px 0 6px -1px rgba(0, 0, 0, 0.05);
        }

        .today-header {
            background: linear-gradient(135deg, #ffd22f 0%, #fbbf24 100%) !important;
            position: relative;
        }

        .today-header * {
            color: #013064 !important;
        }

        .booking-name {
            font-weight: 700;
            font-size: 13px;
            line-height: 1.3;
            margin-bottom: 4px;
            letter-spacing: -0.01em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .booking-venue {
            font-size: 10px;
            font-weight: 600;
            opacity: 0.85;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .booking-badge {
            font-size: 8px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.25);
            letter-spacing: 0.3px;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.625rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .date-header {
            text-align: center;
            padding: 1rem 0.5rem;
        }

        .day-name {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 8px;
            opacity: 0.8;
        }

        .date-number {
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
        }

        .month-name {
            font-size: 11px;
            font-weight: 500;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-button {
            background: white;
            color: #013064;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            border: 2px solid #ffd22f;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23013064' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.25rem;
            padding-right: 2.5rem;
        }
        
        .filter-button:not(select) {
            background-image: none;
            padding-right: 1.25rem;
        }

        .filter-button:hover {
            background: linear-gradient(135deg, #ffd22f 0%, #fbbf24 100%);
            color: #013064;
            border-color: #ffd22f;
            transform: translateY(-2px);
            box-shadow: 0 5px 14px rgba(255, 210, 47, 0.25);
        }

        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 0.875rem;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .active-filter-tag:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .remove-filter-btn {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .remove-filter-btn:hover {
            background: rgba(239, 68, 68, 0.8);
            transform: scale(1.1);
        }

        .calendar-table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .calendar-table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .calendar-table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .calendar-table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .quick-sidebar-btn {
            text-align: left;
            padding: 0.875rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            color: #4b5563;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .quick-sidebar-btn:hover {
            background: linear-gradient(135deg, #013064 0%, #024a8f 100%);
            color: white;
            border-color: #013064;
            transform: translateX(4px);
        }

        .calendar-nav-btn {
            padding: 0.625rem;
            border-radius: 10px;
            background: #f3f4f6;
            color: #374151;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid #e5e7eb;
        }

        .calendar-nav-btn:hover {
            background: #013064;
            color: white;
            border-color: #013064;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }

        .calendar-day-header {
            text-align: center;
            padding: 0.875rem 0;
            font-weight: 700;
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            color: #1f2937;
            background: white;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .calendar-day:hover:not(:disabled) {
            border-color: #013064;
            background: #f0f9ff;
            transform: scale(1.05);
        }

        .calendar-day.other-month {
            color: #d1d5db;
            background: #f9fafb;
            cursor: not-allowed;
        }

        .calendar-day.today {
            border-color: #ffd22f;
            background: #fef3c7;
            font-weight: 800;
        }

        .calendar-day.selected-start,
        .calendar-day.selected-end {
            background: linear-gradient(135deg, #013064 0%, #024a8f 100%);
            color: white;
            border-color: #013064;
            font-weight: 800;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(1, 48, 100, 0.3);
        }

        .calendar-day.in-range {
            background: #e0f2fe;
            border-color: #bae6fd;
        }

        .modal-footer-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 2px solid #f3f4f6;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 0.01em;
        }

        .filter-icon {
            width: 1.125rem;
            height: 1.125rem;
            flex-shrink: 0;
        }

        @media (max-width: 640px) {
            .calendar-header-section {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .date-number {
                font-size: 28px;
            }
        }
    </style>

    <div class="full-calendar-wrapper">
        {{-- HEADER SECTION --}}
        <div class="calendar-header-section" style="position: relative; z-index: 1;">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-5">
                {{-- Title --}}
                <div>
                    <p class="page-subtitle">
                        {{ now()->isoFormat('DD MMMM YYYY') }}
                    </p>
                </div>

                {{-- Controls --}}
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    {{-- Venue Filter --}}
                    <select 
                        wire:model.live="selectedVenue"
                        class="filter-button w-full sm:w-auto"
                        style="min-width: 200px;"
                    >
                        <option value="all">Semua Venue</option>
                        <option value="cibadak_a">Cibadak A</option>
                        <option value="cibadak_b">Cibadak B</option>
                        <option value="pvj">PVJ Mall</option>
                        <option value="urban">Urban</option>
                    </select>

                    {{-- Date Range Button --}}
                    <button 
                        x-data
                        x-on:click="$dispatch('open-modal', { id: 'date-range-modal' })"
                        class="filter-button w-full sm:w-auto whitespace-nowrap"
                    >
                        <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">Pilih Tanggal</span>
                        <span class="sm:hidden">Tanggal</span>
                    </button>
                </div>
            </div>

            {{-- Legend --}}
            <div class="pt-5 border-t border-white/10">
                <div class="grid grid-cols-2 lg:flex lg:flex-wrap gap-2 lg:gap-3">
                    <div class="legend-item text-xs lg:text-sm">
                        <div class="legend-box" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);"></div>
                        <span class="text-white font-semibold">Lunas</span>
                    </div>
                    <div class="legend-item text-xs lg:text-sm">
                        <div class="legend-box" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);"></div>
                        <span class="text-white font-semibold">Pending</span>
                    </div>
                    <div class="legend-item text-xs lg:text-sm">
                        <div class="legend-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"></div>
                        <span class="text-white font-semibold">Manual</span>
                    </div>
                    <div class="legend-item text-xs lg:text-sm">
                        <div class="legend-box" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);"></div>
                        <span class="text-white font-semibold">Member</span>
                    </div>
                </div>
            </div>

            {{-- Active Filters --}}
            @if($selectedVenue !== 'all' || $dateRangeText)
                <div class="mt-3 flex flex-wrap gap-2">
                    @if($selectedVenue !== 'all')
                        <span class="active-filter-tag text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ match($selectedVenue) {
                                'cibadak_a' => 'Cibadak A',
                                'cibadak_b' => 'Cibadak B',
                                'pvj' => 'PVJ Mall',
                                'urban' => 'Urban',
                                default => ucfirst(str_replace('_', ' ', $selectedVenue))
                            } }}
                            <button wire:click="$set('selectedVenue', 'all')" class="remove-filter-btn">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if($dateRangeText)
                        <span class="active-filter-tag text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ $dateRangeText }}
                            <button wire:click="clearDateRange" class="remove-filter-btn">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- CALENDAR CONTENT --}}
        <div style="padding: 2rem;">
            @if($selectedVenue === 'all')
                {{-- TAMPILAN SEMUA VENUE - MULTIPLE TABLES --}}
                @foreach($schedulesByVenue as $venueData)
                    <div class="venue-section">
                        {{-- Venue Header --}}
                        <div class="venue-header">
                            <div class="venue-title">
                                <div class="venue-icon">
                                    <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                {{ $venueData['venue_name'] }}
                            </div>
                        </div>

                        {{-- Calendar Table untuk Venue ini --}}
                        <div class="calendar-table-wrapper" style="border-radius: 0 0 12px 12px; overflow: hidden;">
                            <div class="overflow-x-auto">
                                <table class="calendar-table">
                                    {{-- Header: Dates --}}
                                    <thead>
                                        <tr>
                                            <th class="time-cell text-left" style="min-width: 120px;">
                                                WAKTU
                                            </th>
                                            @foreach($venueData['schedules'] as $schedule)
                                                <th class="date-header {{ $schedule['is_today'] ? 'today-header' : '' }}" style="min-width: 160px;">
                                                    <div class="day-name {{ $schedule['is_today'] ? 'text-[#1e293b]' : 'text-white/80' }}">
                                                        {{ $schedule['day_name'] }}
                                                    </div>
                                                    <div class="date-number {{ $schedule['is_today'] ? 'text-[#1e293b]' : 'text-white' }}">
                                                        {{ $schedule['date_number'] }}
                                                    </div>
                                                    <div class="month-name {{ $schedule['is_today'] ? 'text-[#1e293b]/70' : 'text-white/60' }}">
                                                        {{ $schedule['month'] }}
                                                    </div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>

                                    {{-- Body: Time Slots --}}
                                    <tbody>
                                        @foreach($timeSlots as $slot)
                                            <tr>
                                                {{-- Time Label --}}
                                                <td class="time-cell">
                                                    {{ $slot }}
                                                </td>

                                                {{-- Booking Cells --}}
                                                @foreach($venueData['schedules'] as $schedule)
                                                    @php
                                                        $booking = $schedule['bookings'][$slot] ?? null;
                                                        $isBooked = $booking && $booking->isNotEmpty();
                                                    @endphp

                                                    <td>
                                                        @if($isBooked)
                                                            {{-- CELL ADA BOOKING --}}
                                                            <div class="booking-stack">
                                                                @foreach($booking as $item)
                                                                    @php
                                                                        $colorClass = 'booking-paid';
                                                                        if ($item->booking_type === 'pending') {
                                                                            $colorClass = 'booking-unpaid';
                                                                        } elseif ($item->booking_type === 'member_manual') {
                                                                            $colorClass = 'booking-member';
                                                                        } elseif ($item->booking_type === 'manual') {
                                                                            $colorClass = 'booking-manual';
                                                                        }
                                                                    @endphp
                                                                    
                                                                    <a 
                                                                        href="{{ route('filament.admin.resources.bookings.edit', $item->id) }}"
                                                                        class="block {{ $colorClass }} rounded-lg px-3 py-2.5 booking-cell cursor-pointer"
                                                                    >
                                                                        <div>
                                                                            <div class="booking-name" title="{{ $item->client ? $item->client->name : 'Guest' }}">
                                                                                {{ $item->client ? $item->client->name : 'Guest' }}
                                                                            </div>
                                                                            <div class="booking-venue">
                                                                                {{ $venueData['venue_name'] }}
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            @if($item->booking_type === 'pending')
                                                                                <span class="booking-badge">PENDING</span>
                                                                            @elseif($item->booking_type === 'member_manual')
                                                                                <span class="booking-badge">MEMBER</span>
                                                                            @elseif($item->booking_type === 'manual')
                                                                                <span class="booking-badge">MANUAL</span>
                                                                            @else
                                                                                <span class="booking-badge">LUNAS</span>
                                                                            @endif
                                                                        </div>
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            {{-- CELL KOSONG --}}
                                                            <button 
                                                                wire:click="createBooking('{{ $schedule['date'] }}', '{{ $slot }}', '{{ $venueData['venue_key'] }}')"
                                                                class="empty-cell w-full flex flex-col items-center justify-center rounded-lg transition group cursor-pointer"
                                                            >
                                                                <svg class="w-6 h-6 text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                {{-- TAMPILAN SINGLE VENUE --}}
                <div class="calendar-table-wrapper">
                    <div class="overflow-x-auto">
                        <table class="calendar-table">
                            {{-- Header: Dates --}}
                            <thead>
                                <tr>
                                    <th class="time-cell text-left" style="min-width: 120px;">
                                        WAKTU
                                    </th>
                                    @foreach($schedules as $schedule)
                                        <th class="date-header {{ $schedule['is_today'] ? 'today-header' : '' }}" style="min-width: 160px;">
                                            <div class="day-name {{ $schedule['is_today'] ? 'text-[#1e293b]' : 'text-white/80' }}">
                                                {{ $schedule['day_name'] }}
                                            </div>
                                            <div class="date-number {{ $schedule['is_today'] ? 'text-[#1e293b]' : 'text-white' }}">
                                                {{ $schedule['date_number'] }}
                                            </div>
                                            <div class="month-name {{ $schedule['is_today'] ? 'text-[#1e293b]/70' : 'text-white/60' }}">
                                                {{ $schedule['month'] }}
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            {{-- Body: Time Slots --}}
                            <tbody>
                                @foreach($timeSlots as $slot)
                                    <tr>
                                        {{-- Time Label --}}
                                        <td class="time-cell">
                                            {{ $slot }}
                                        </td>

                                        {{-- Booking Cells --}}
                                        @foreach($schedules as $schedule)
                                            @php
                                                $booking = $schedule['bookings'][$slot] ?? null;
                                                $isBooked = $booking && $booking->isNotEmpty();
                                            @endphp

                                            <td>
                                                @if($isBooked)
                                                    {{-- CELL ADA BOOKING --}}
                                                    <div class="booking-stack">
                                                        @foreach($booking as $item)
                                                            @php
                                                                $colorClass = 'booking-paid';
                                                                if ($item->booking_type === 'pending') {
                                                                    $colorClass = 'booking-unpaid';
                                                                } elseif ($item->booking_type === 'member_manual') {
                                                                    $colorClass = 'booking-member';
                                                                } elseif ($item->booking_type === 'manual') {
                                                                    $colorClass = 'booking-manual';
                                                                }
                                                            @endphp
                                                            
                                                            <a 
                                                                href="{{ route('filament.admin.resources.bookings.edit', $item->id) }}"
                                                                class="block {{ $colorClass }} rounded-lg px-3 py-2.5 booking-cell cursor-pointer"
                                                            >
                                                                <div>
                                                                    <div class="booking-name" title="{{ $item->client ? $item->client->name : 'Guest' }}">
                                                                        {{ $item->client ? $item->client->name : 'Guest' }}
                                                                    </div>
                                                                    <div class="booking-venue">
                                                                        {{ match($item->venue_type) {
                                                                            'cibadak_a' => 'CIBADAK A',
                                                                            'cibadak_b' => 'CIBADAK B',
                                                                            'pvj' => 'PVJ MALL',
                                                                            'urban' => 'URBAN',
                                                                            default => strtoupper(str_replace('_', ' ', $item->venue_type))
                                                                        } }}
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    @if($item->booking_type === 'pending')
                                                                        <span class="booking-badge">PENDING</span>
                                                                    @elseif($item->booking_type === 'member_manual')
                                                                        <span class="booking-badge">MEMBER</span>
                                                                    @elseif($item->booking_type === 'manual')
                                                                        <span class="booking-badge">MANUAL</span>
                                                                    @else
                                                                        <span class="booking-badge">LUNAS</span>
                                                                    @endif
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    {{-- CELL KOSONG --}}
                                                    <button 
                                                        wire:click="createBooking('{{ $schedule['date'] }}', '{{ $slot }}')"
                                                        class="empty-cell w-full flex flex-col items-center justify-center rounded-lg transition group cursor-pointer"
                                                    >
                                                        <svg class="w-6 h-6 text-gray-300 group-hover:text-gray-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- VISUAL CALENDAR DATE RANGE MODAL --}}
    <x-filament::modal id="date-range-modal" width="5xl">
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-[#013064] to-[#024a8f] rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">Pilih Rentang Tanggal</div>
                    <div class="text-sm text-gray-500 font-medium mt-0.5">Tentukan periode yang ingin ditampilkan</div>
                </div>
            </div>
        </x-slot>

        <div class="space-y-6 date-picker-modal py-2" x-data="dateRangePicker()" x-init="
            $watch('selectedStart', value => {
                if (value) {
                    $wire.set('startDate', formatDateForBackend(value), false);
                }
            });
            $watch('selectedEnd', value => {
                if (value) {
                    $wire.set('endDate', formatDateForBackend(value), false);
                }
            });
        ">
            {{-- Quick Shortcuts Sidebar --}}
            <div class="flex flex-col lg:flex-row gap-6">
                {{-- Left Sidebar: Quick Options --}}
                <div class="lg:w-56 flex-shrink-0">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 px-1">Pilihan Cepat</label>
                    <div class="space-y-2">
                        <button 
                            @click="quickSelect('today')"
                            class="quick-sidebar-btn w-full"
                        >
                            Hari Ini
                        </button>
                        <button 
                            @click="quickSelect('yesterday')"
                            class="quick-sidebar-btn w-full"
                        >
                            Kemarin
                        </button>
                        <button 
                            @click="quickSelect('last7days')"
                            class="quick-sidebar-btn w-full"
                        >
                            7 Hari Terakhir
                        </button>
                        <button 
                            @click="quickSelect('thismonth')"
                            class="quick-sidebar-btn w-full"
                        >
                            Bulan Ini
                        </button>
                        <button 
                            @click="quickSelect('lastmonth')"
                            class="quick-sidebar-btn w-full"
                        >
                            Bulan Lalu
                        </button>
                    </div>
                </div>

                {{-- Right: Calendar Grid --}}
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="previousMonth" class="calendar-nav-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div class="text-lg font-bold text-gray-800">
                            <span x-text="monthNames[currentMonth]"></span> <span x-text="currentYear"></span>
                        </div>
                        <button @click="nextMonth" class="calendar-nav-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Calendar Grid --}}
                    <div class="calendar-grid">
                        {{-- Day Headers --}}
                        <template x-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']">
                            <div class="calendar-day-header" x-text="day"></div>
                        </template>

                        {{-- Calendar Days --}}
                        <template x-for="day in calendarDays" :key="day.date">
                            <button 
                                @click="selectDate(day.date)"
                                :class="{
                                    'calendar-day': true,
                                    'other-month': day.isOtherMonth,
                                    'selected-start': isStartDate(day.date),
                                    'selected-end': isEndDate(day.date),
                                    'in-range': isInRange(day.date),
                                    'today': isToday(day.date)
                                }"
                                :disabled="day.isOtherMonth"
                            >
                                <span x-text="day.day"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Selected Range Display --}}
                    <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-[#013064] rounded-xl" x-show="selectedStart && selectedEnd">
                        <div class="text-center">
                            <span class="text-xs font-bold text-[#013064] uppercase tracking-wider block mb-1.5">Periode Terpilih</span>
                            <div class="text-lg font-bold text-[#013064]">
                                <span x-text="formatDate(selectedStart)"></span> 
                                <span class="mx-2">—</span> 
                                <span x-text="formatDate(selectedEnd)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-slot name="footerActions">
            <div class="modal-footer-actions w-full">
                <x-filament::button 
                    color="gray" 
                    x-on:click="$dispatch('close-modal', { id: 'date-range-modal' })"
                    outlined
                    size="lg"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Batal
                </x-filament::button>
                
                <x-filament::button 
                    wire:click="applyDateRange"
                    size="lg"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Terapkan Filter
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    <script>
        function dateRangePicker() {
            return {
                currentMonth: new Date().getMonth(),
                currentYear: new Date().getFullYear(),
                selectedStart: null,
                selectedEnd: null,
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                
                get calendarDays() {
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                    const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                    const prevLastDay = new Date(this.currentYear, this.currentMonth, 0);
                    
                    const days = [];
                    const firstDayOfWeek = firstDay.getDay();
                    
                    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                        days.push({
                            day: prevLastDay.getDate() - i,
                            date: new Date(this.currentYear, this.currentMonth - 1, prevLastDay.getDate() - i),
                            isOtherMonth: true
                        });
                    }
                    
                    for (let i = 1; i <= lastDay.getDate(); i++) {
                        days.push({
                            day: i,
                            date: new Date(this.currentYear, this.currentMonth, i),
                            isOtherMonth: false
                        });
                    }
                    
                    const remainingDays = 42 - days.length;
                    for (let i = 1; i <= remainingDays; i++) {
                        days.push({
                            day: i,
                            date: new Date(this.currentYear, this.currentMonth + 1, i),
                            isOtherMonth: true
                        });
                    }
                    
                    return days;
                },
                
                previousMonth() {
                    if (this.currentMonth === 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    } else {
                        this.currentMonth--;
                    }
                },
                
                nextMonth() {
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                },
                
                selectDate(date) {
                    if (!this.selectedStart || (this.selectedStart && this.selectedEnd)) {
                        this.selectedStart = date;
                        this.selectedEnd = null;
                    } else {
                        if (date < this.selectedStart) {
                            this.selectedEnd = this.selectedStart;
                            this.selectedStart = date;
                        } else {
                            this.selectedEnd = date;
                        }
                    }
                },

                quickSelect(type) {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    switch(type) {
                        case 'today':
                            this.selectedStart = new Date(today);
                            this.selectedEnd = new Date(today);
                            break;
                        case 'yesterday':
                            const yesterday = new Date(today);
                            yesterday.setDate(yesterday.getDate() - 1);
                            this.selectedStart = yesterday;
                            this.selectedEnd = yesterday;
                            break;
                        case 'last7days':
                            const last7 = new Date(today);
                            last7.setDate(last7.getDate() - 6);
                            this.selectedStart = last7;
                            this.selectedEnd = new Date(today);
                            break;
                        case 'thismonth':
                            this.selectedStart = new Date(today.getFullYear(), today.getMonth(), 1);
                            this.selectedEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                            break;
                        case 'lastmonth':
                            this.selectedStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                            this.selectedEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                            break;
                    }
                    
                    this.currentMonth = this.selectedStart.getMonth();
                    this.currentYear = this.selectedStart.getFullYear();
                },
                
                isStartDate(date) {
                    return this.selectedStart && date.toDateString() === this.selectedStart.toDateString();
                },
                
                isEndDate(date) {
                    return this.selectedEnd && date.toDateString() === this.selectedEnd.toDateString();
                },
                
                isInRange(date) {
                    if (!this.selectedStart || !this.selectedEnd) return false;
                    return date > this.selectedStart && date < this.selectedEnd;
                },
                
                isToday(date) {
                    const today = new Date();
                    return date.toDateString() === today.toDateString();
                },
                
                formatDate(date) {
                    if (!date) return '';
                    const day = date.getDate();
                    const month = this.monthNames[date.getMonth()].substring(0, 3);
                    const year = date.getFullYear();
                    return `${day} ${month} ${year}`;
                },
                
                formatDateForBackend(date) {
                    if (!date) return '';
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }
            }
        }
    </script>
</x-filament-panels::page>