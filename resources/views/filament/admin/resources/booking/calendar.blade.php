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

        /* ✅ VENUE SECTION - Improved spacing and sizing */
        .venue-section {
            margin-bottom: 3.5rem; /* Increased from 2.5rem */
            page-break-inside: avoid;
        }

        .venue-header {
            background: linear-gradient(135deg, #013064 0%, #024a8f 100%);
            padding: 1.5rem 2rem; /* Increased from 1.25rem */
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .venue-title {
            font-size: 1.65rem; /* Increased from 1.5rem */
            font-weight: 800;
            color: white;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 1rem; /* Increased from 0.75rem */
        }

        .venue-icon {
            width: 2.5rem; /* Increased from 2rem */
            height: 2.5rem;
            background: rgba(255, 210, 47, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .venue-icon svg {
            width: 1.5rem; /* Explicit size for icon */
            height: 1.5rem;
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
            background: linear-gradient(135deg, #FFD22F 0%, #FFA500 100%);
            box-shadow: 0 3px 12px 0 rgba(255, 210, 47, 0.3);
            color: #1e293b !important;
        }

        .booking-recurring {
            background: linear-gradient(135deg, #ea580c 0%, #ea580c 100%);
            box-shadow: 0 3px 12px 0 rgba(168, 85, 247, 0.25);
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

        .booking-manual .booking-badge {
            background: rgba(30, 41, 59, 0.15);
            color: #1e293b;
            font-weight: 800;
        }

        /* ✅ LEGEND - Improved spacing and layout */
        .legend-container {
            padding-top: 1.75rem; /* Increased from 1.25rem */
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* More flexible */
            gap: 1rem; /* Increased from 0.75rem */
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.875rem; /* Increased from 0.625rem */
            padding: 0.875rem 1.25rem; /* Increased padding */
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px; /* Increased from 10px */
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.2s ease;
        }

        .legend-item:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .legend-box {
            width: 24px; /* Increased from 20px */
            height: 24px;
            border-radius: 6px; /* Increased from 5px */
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .legend-text {
            color: white;
            font-weight: 600;
            font-size: 0.9375rem; /* Slightly increased */
            letter-spacing: 0.01em;
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
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            min-height: 48px;
        }

        select.filter-button {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23013064' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.125rem;
            padding-right: 3rem;
        }

        .filter-button:hover {
            background: #f8fafc;
            border-color: #013064;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 48, 100, 0.12);
        }

        /* ✅ Export Button Styling */
        .export-button {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
            min-height: 48px;
        }

        .export-button:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(34, 197, 94, 0.35);
        }

        /* ✅ Export By Color Button Styling */
        .export-color-button {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
            min-height: 48px;
        }

        .export-color-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(59, 130, 246, 0.35);
        }

        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            padding: 0.625rem 1rem;
            border-radius: 24px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .active-filter-tag:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.35);
        }

        .remove-filter-btn {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .remove-filter-btn:hover {
            background: rgba(239, 68, 68, 0.9);
            transform: scale(1.15);
        }

        .booking-type-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }

        .booking-type-modal {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 540px;
            width: 90%;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .modal-subtitle {
            font-size: 0.95rem;
            color: #64748b;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .booking-type-option {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.5rem 1.75rem;
            border: 2.5px solid #e2e8f0;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 1rem;
            background: white;
        }

        .booking-type-option:hover {
            border-color: #013064;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            transform: translateX(6px) scale(1.01);
            box-shadow: 0 8px 20px rgba(1, 48, 100, 0.12);
        }

        .booking-type-icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .booking-type-icon.regular {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .booking-type-icon.recurring {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
        }

        .booking-type-info h3 {
            font-size: 1.125rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.375rem;
            letter-spacing: -0.01em;
        }

        .booking-type-info p {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            line-height: 1.4;
        }

        .modal-cancel-btn {
            width: 100%;
            margin-top: 1.25rem;
            padding: 1rem 1.5rem;
            background: #f1f5f9;
            color: #475569;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-cancel-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .date-picker-modal .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            margin-top: 16px;
        }

        .date-picker-modal .calendar-day-header {
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            padding: 10px 4px;
            letter-spacing: 0.6px;
        }

        .date-picker-modal .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            border: 2px solid #e5e7eb;
            color: #1e293b;
        }

        .date-picker-modal .calendar-day:hover:not(.other-month):not(:disabled) {
            border-color: #013064;
            background: #dbeafe;
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(1, 48, 100, 0.15);
        }

        .date-picker-modal .calendar-day.other-month {
            color: #cbd5e1;
            background: #f8fafc;
            border-color: #f1f5f9;
            cursor: default;
        }

        .date-picker-modal .calendar-day.today {
            border-color: #ffd22f;
            background: #fffbeb;
            color: #013064;
            font-weight: 900;
            box-shadow: 0 0 0 3px rgba(255, 210, 47, 0.2);
        }

        .date-picker-modal .calendar-day.selected-start,
        .date-picker-modal .calendar-day.selected-end {
            background: linear-gradient(135deg, #013064 0%, #024a8f 100%);
            border-color: #013064;
            color: white;
            font-weight: 900;
            box-shadow: 0 4px 16px rgba(1, 48, 100, 0.3);
        }

        .date-picker-modal .calendar-day.in-range {
            background: #dbeafe;
            border-color: #93c5fd;
            color: #013064;
            font-weight: 700;
        }

        .date-picker-modal .calendar-nav-btn {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: white;
            border: 2.5px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .date-picker-modal .calendar-nav-btn:hover {
            border-color: #013064;
            background: #dbeafe;
            transform: scale(1.05);
        }

        .date-picker-modal .quick-sidebar-btn {
            padding: 14px 18px;
            text-align: left;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            background: white;
            border: 2.5px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .date-picker-modal .quick-sidebar-btn:hover {
            border-color: #013064;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #013064;
            transform: translateX(6px);
            box-shadow: 0 4px 12px rgba(1, 48, 100, 0.1);
        }

        .date-picker-modal .modal-footer-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 1.5rem;
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

            .booking-type-modal {
                padding: 2rem 1.5rem;
            }

            .modal-title {
                font-size: 1.5rem;
            }

            .legend-grid {
                grid-template-columns: 1fr;
            }

            .venue-section {
                margin-bottom: 2.5rem;
            }
        }
    </style>

    <div class="full-calendar-wrapper">
        {{-- HEADER SECTION --}}
        <div class="calendar-header-section" style="position: relative; z-index: 1;">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-5">
                <div>
                    <p class="page-subtitle">
                        {{ now()->isoFormat('DD MMMM YYYY') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex gap-2 w-full lg:w-auto">
                    <select 
                        wire:model.live="selectedVenue"
                        class="filter-button"
                        style="min-width: 200px;"
                    >
                        <option value="all">Semua Venue</option>
                        <option value="cibadak_a">Cibadak A</option>
                        <option value="cibadak_b">Cibadak B</option>
                        <option value="pvj">PVJ Mall</option>
                        <option value="urban">Urban</option>
                    </select>

                    <button 
                        x-data
                        x-on:click="$dispatch('open-modal', { id: 'date-range-modal' })"
                        class="filter-button whitespace-nowrap"
                    >
                        <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Pilih Tanggal</span>
                    </button>

                    <button 
                        wire:click="setCurrentMonth"
                        class="filter-button whitespace-nowrap"
                    >
                        <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Bulan Ini</span>
                    </button>

                    {{-- Export Matrix Button --}}
                    <button 
                        wire:click="exportToCSV"
                        class="export-button whitespace-nowrap"
                        title="Export format matrix seperti kalender"
                    >
                        <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span>Export Matrix</span>
                    </button>

                    {{-- Export By Color Button --}}
                    <button 
                        wire:click="exportToCSVByColor"
                        class="export-color-button whitespace-nowrap"
                        title="Export dengan pemisahan berdasarkan warna/kategori"
                    >
                        <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                        <span>Export by Color</span>
                    </button>
                </div>
            </div>

            {{-- ✅ IMPROVED LEGEND SECTION --}}
            <div class="legend-container">
                <div class="legend-grid">
                    <div class="legend-item">
                        <div class="legend-box" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);"></div>
                        <span class="legend-text">Lunas</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);"></div>
                        <span class="legend-text">Pending</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: linear-gradient(135deg, #FFD22F 0%, #FFA500 100%);"></div>
                        <span class="legend-text">Manual</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: linear-gradient(135deg, #ea580c 0%, #ea580c 100%);"></div>
                        <span class="legend-text">Member</span>
                    </div>
                </div>
            </div>

            @if($selectedVenue !== 'all' || $dateRangeText)
                <div class="mt-4 flex flex-wrap gap-2.5">
                    @if($selectedVenue !== 'all')
                        <span class="active-filter-tag">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if($dateRangeText)
                        <span class="active-filter-tag">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ $dateRangeText }}
                            <button wire:click="clearDateRange" class="remove-filter-btn">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Rest of the calendar content remains the same --}}
        <div style="padding: 2rem;">
            @if($selectedVenue === 'all')
                @foreach($schedulesByVenue as $venueData)
                    <div class="venue-section">
                        <div class="venue-header">
                            <div class="venue-title">
                                <div class="venue-icon">
                                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                {{ $venueData['venue_name'] }}
                            </div>
                        </div>

                        <div class="calendar-table-wrapper" style="border-radius: 0 0 12px 12px; overflow: hidden;">
                            <div class="overflow-x-auto">
                                <table class="calendar-table">
                                    <thead>
                                        <tr>
                                            <th class="time-cell text-left" style="min-width: 120px;">WAKTU</th>
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

                                    <tbody>
                                        @foreach($timeSlots as $slot)
                                            <tr>
                                                <td class="time-cell">{{ $slot }}</td>

                                                @foreach($venueData['schedules'] as $schedule)
                                                    @php
                                                        $booking = $schedule['bookings'][$slot] ?? null;
                                                        $isBooked = $booking && $booking->isNotEmpty();
                                                    @endphp

                                                    <td>
                                                        @if($isBooked)
                                                            <div class="booking-stack">
                                                                @foreach($booking as $item)
                                                                    @php
                                                                        if ($item->booking_type === 'recurring') {
                                                                            $colorClass = 'booking-recurring';
                                                                        } elseif ($item->booking_type === 'pending') {
                                                                            $colorClass = 'booking-unpaid';
                                                                        } elseif ($item->booking_type === 'member_manual') {
                                                                            $colorClass = 'booking-member';
                                                                        } elseif ($item->booking_type === 'manual') {
                                                                            $colorClass = 'booking-manual';
                                                                        } else {
                                                                            $colorClass = 'booking-paid';
                                                                        }
                                                                    @endphp
                                                                    
                                                                    <a 
                                                                        href="{{ $item->is_recurring ? route('filament.admin.resources.recurring-bookings.edit', $item->id) : route('filament.admin.resources.bookings.edit', $item->id) }}"
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
                                                                            @if($item->booking_type === 'recurring')
                                                                                <span class="booking-badge">MEMBER</span>
                                                                            @elseif($item->booking_type === 'pending')
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
                                                            <button 
                                                                wire:click="openBookingTypeModal('{{ $schedule['date'] }}', '{{ $slot }}', '{{ $venueData['venue_key'] }}')"
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
                {{-- Single venue view --}}
                <div class="calendar-table-wrapper">
                    <div class="overflow-x-auto">
                        <table class="calendar-table">
                            <thead>
                                <tr>
                                    <th class="time-cell text-left" style="min-width: 120px;">WAKTU</th>
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

                            <tbody>
                                @foreach($timeSlots as $slot)
                                    <tr>
                                        <td class="time-cell">{{ $slot }}</td>

                                        @foreach($schedules as $schedule)
                                            @php
                                                $booking = $schedule['bookings'][$slot] ?? null;
                                                $isBooked = $booking && $booking->isNotEmpty();
                                            @endphp

                                            <td>
                                                @if($isBooked)
                                                    <div class="booking-stack">
                                                        @foreach($booking as $item)
                                                            @php
                                                                if ($item->booking_type === 'recurring') {
                                                                    $colorClass = 'booking-recurring';
                                                                } elseif ($item->booking_type === 'pending') {
                                                                    $colorClass = 'booking-unpaid';
                                                                } elseif ($item->booking_type === 'member_manual') {
                                                                    $colorClass = 'booking-member';
                                                                } elseif ($item->booking_type === 'manual') {
                                                                    $colorClass = 'booking-manual';
                                                                } else {
                                                                    $colorClass = 'booking-paid';
                                                                }
                                                            @endphp
                                                            
                                                            <a 
                                                                href="{{ $item->is_recurring ? route('filament.admin.resources.recurring-bookings.edit', $item->id) : route('filament.admin.resources.bookings.edit', $item->id) }}"
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
                                                                    @if($item->booking_type === 'recurring')
                                                                        <span class="booking-badge">RUTIN</span>
                                                                    @elseif($item->booking_type === 'pending')
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
                                                    <button 
                                                        wire:click="openBookingTypeModal('{{ $schedule['date'] }}', '{{ $slot }}')"
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

    {{-- Modals remain the same --}}
    @if($showBookingTypeModal)
        <div class="booking-type-modal-overlay" wire:click="closeBookingTypeModal">
            <div class="booking-type-modal" wire:click.stop>
                <h2 class="modal-title">Pilih Tipe Booking</h2>
                <p class="modal-subtitle">Tentukan jenis booking yang ingin dibuat</p>

                <div class="booking-type-option" wire:click="createRegularBooking">
                    <div class="booking-type-icon regular">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="booking-type-info flex-1">
                        <h3>Booking Biasa</h3>
                        <p>Booking untuk satu kali main saja</p>
                    </div>
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>

                <div class="booking-type-option" wire:click="createRecurringBooking">
                    <div class="booking-type-icon recurring">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div class="booking-type-info flex-1">
                        <h3>Member</h3>
                        <p>Booking otomatis untuk beberapa minggu/bulan</p>
                    </div>
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>

                <button wire:click="closeBookingTypeModal" class="modal-cancel-btn">
                    Batal
                </button>
            </div>
        </div>
    @endif

    {{-- DATE RANGE MODAL --}}
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
            <div class="flex flex-col lg:flex-row gap-6">
                <div class="lg:w-56 flex-shrink-0">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 px-1">Pilihan Cepat</label>
                    <div class="space-y-2">
                        <button @click="quickSelect('today')" class="quick-sidebar-btn w-full">Hari Ini</button>
                        <button @click="quickSelect('yesterday')" class="quick-sidebar-btn w-full">Kemarin</button>
                        <button @click="quickSelect('last7days')" class="quick-sidebar-btn w-full">7 Hari Terakhir</button>
                        <button @click="quickSelect('thismonth')" class="quick-sidebar-btn w-full">Bulan Ini</button>
                        <button @click="quickSelect('lastmonth')" class="quick-sidebar-btn w-full">Bulan Lalu</button>
                    </div>
                </div>

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

                    <div class="calendar-grid">
                        <template x-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']">
                            <div class="calendar-day-header" x-text="day"></div>
                        </template>

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
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'date-range-modal' })" outlined size="lg">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Batal
                </x-filament::button>
                
                <x-filament::button wire:click="applyDateRange" size="lg">
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