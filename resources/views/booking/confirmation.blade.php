<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Dikonfirmasi — The Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem 1rem 4rem;
        }

        .receipt-wrapper {
            width: 100%;
            max-width: 420px;
        }

        /* === MAIN CARD === */
        .receipt-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            overflow: hidden;
        }

        /* --- Card Header (logo + invoice no) --- */
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f0f2f5;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 1rem;
            color: #0f1f3d;
        }

        .brand-icon {
            width: 28px;
            height: 28px;
            background: #0f1f3d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-icon svg {
            width: 16px;
            height: 16px;
            color: white;
        }

        .invoice-no {
            text-align: right;
        }

        .invoice-label {
            font-size: 0.625rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .invoice-value {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0f1f3d;
        }

        /* --- Success Section --- */
        .success-section {
            padding: 1.75rem 1.5rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid #f0f2f5;
        }

        .check-circle {
            width: 56px;
            height: 56px;
            background: #22c55e;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .check-circle svg {
            width: 28px;
            height: 28px;
            color: white;
        }

        .success-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f1f3d;
            margin-bottom: 0.375rem;
        }

        .success-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* --- Info Section --- */
        .info-section {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f0f2f5;
        }

        .section-label {
            font-size: 0.6875rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.875rem;
        }

        .info-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.5rem 0;
        }

        .info-row + .info-row {
            border-top: 1px solid #f9fafb;
        }

        .info-label {
            font-size: 0.8125rem;
            color: #6b7280;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .info-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f1f3d;
            text-align: right;
        }

        .info-value.blue {
            color: #2563eb;
            font-weight: 500;
        }

        .info-value.bold-blue {
            color: #1d4ed8;
            font-weight: 700;
        }

        /* Time slot badge */
        .time-slots-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            justify-content: flex-end;
        }

        .time-badge {
            background: #0f1f3d;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3125rem 0.75rem;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* --- Total Section --- */
        .total-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.125rem 1.5rem;
            border-bottom: 1px solid #f0f2f5;
        }

        .total-label {
            font-size: 0.9375rem;
            color: #374151;
            font-weight: 500;
        }

        .total-value {
            font-size: 1.375rem;
            font-weight: 800;
            color: #0f1f3d;
            letter-spacing: -0.02em;
        }

        /* --- Discount row (shown only if discount > 0) --- */
        .discount-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8125rem;
            color: #6b7280;
            padding: 0.25rem 0;
        }

        .discount-original {
            text-decoration: line-through;
        }

        .discount-label {
            color: #16a34a;
            font-weight: 600;
        }

        /* --- Bottom section (light bg) --- */
        .bottom-section {
            background: #f9fafb;
            padding: 1.25rem 1.5rem;
        }

        /* Info penting box */
        .info-penting {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 0.875rem 1rem;
            margin-bottom: 1.125rem;
            display: flex;
            gap: 0.625rem;
        }

        .info-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            color: #d97706;
            margin-top: 1px;
        }

        .info-penting-content {}

        .info-penting-title {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #92400e;
            margin-bottom: 0.375rem;
        }

        .info-penting-list {
            list-style: disc;
            padding-left: 1rem;
        }

        .info-penting-list li {
            font-size: 0.8125rem;
            color: #78350f;
            line-height: 1.5;
            margin-bottom: 0.25rem;
        }

        /* --- Buttons --- */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
            margin-bottom: 1rem;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: none;
            width: 100%;
        }

        .btn svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .btn-primary {
            background: #1a5c38;
            color: white;
        }

        .btn-primary:hover {
            background: #155232;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            color: #0f1f3d;
            border: 1.5px solid #e2e8f0;
        }

        .btn-secondary:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        /* --- Footer links --- */
        .card-footer {
            text-align: center;
        }

        .footer-link {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
            margin-bottom: 0.375rem;
        }

        .footer-link:hover {
            text-decoration: underline;
        }

        .auto-redirect {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .timer-count {
            font-weight: 700;
            color: #6b7280;
        }

        @media (max-width: 480px) {
            body { padding: 1rem 0.75rem 3rem; }
            .card-header,
            .success-section,
            .info-section,
            .total-section,
            .bottom-section { padding-left: 1.25rem; padding-right: 1.25rem; }
        }
    </style>
</head>
<body>

    <div class="receipt-wrapper">
        <div class="receipt-card">

            {{-- Card Header --}}
            <div class="card-header">
                <div class="brand-logo">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c.55 0 1 .22 1.35.59C14.06 6.41 15 7.1 15 8c0 .35-.1.68-.27.97C14.07 8.36 13.07 8 12 8s-2.07.36-2.73.97C9.1 8.68 9 8.35 9 8c0-.9.94-1.59 1.65-1.41C11 6.22 11.45 5 12 5zm0 14c-2.7 0-5.09-1.36-6.55-3.44C6.89 13.64 9.3 12 12 12s5.11 1.64 6.55 3.56C17.09 17.64 14.7 19 12 19z"/>
                        </svg>
                    </div>
                    THE ARENA
                </div>
                <div class="invoice-no">
                    <div class="invoice-label">NO. TAGIHAN</div>
                    <div class="invoice-value">{{ $booking->bill_no ?? 'INV-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>

            {{-- Success Badge --}}
            <div class="success-section">
                <div class="check-circle">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="success-title">Pembayaran Berhasil!</div>
                <div class="success-subtitle">Booking Anda telah dikonfirmasi</div>
            </div>

            {{-- Informasi Pelanggan --}}
            <div class="info-section">
                <div class="section-label">Informasi Pelanggan</div>
                <div class="info-row">
                    <span class="info-label">Nama</span>
                    <span class="info-value">{{ $client->name }}</span>
                </div>
                @if($client->phone)
                <div class="info-row">
                    <span class="info-label">No. HP</span>
                    <span class="info-value">{{ $client->phone }}</span>
                </div>
                @endif
            </div>

            {{-- Detail Booking --}}
            <div class="info-section">
                <div class="section-label">Detail Booking</div>
                <div class="info-row">
                    <span class="info-label">Lapangan</span>
                    <span class="info-value bold-blue">{{ $venueName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Alamat</span>
                    <span class="info-value blue">{{ $venueAddress }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal</span>
                    <span class="info-value">{{ $bookingDateFormatted }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jam Main</span>
                    <span class="info-value">
                        <div class="time-slots-wrap">
                            @foreach($timeSlotsDisplay as $slot)
                                <span class="time-badge">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
                                        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                                    </svg>
                                    {{ $slot }}
                                </span>
                            @endforeach
                        </div>
                    </span>
                </div>
            </div>

            {{-- Total --}}
            <div class="total-section">
                @if(isset($discountAmount) && $discountAmount > 0)
                <div>
                    <div class="total-label">Total Dibayar</div>
                    <div class="discount-row">
                        <span class="discount-original">Rp {{ number_format($originalPrice, 0, ',', '.') }}</span>
                        <span class="discount-label" style="margin-left:0.5rem;">-Rp {{ number_format($discountAmount, 0, ',', '.') }}</span>
                    </div>
                </div>
                @else
                <div class="total-label">Total Dibayar</div>
                @endif
                <div class="total-value">Rp {{ number_format($totalPrice, 0, ',', '.') }}</div>
            </div>

            {{-- Bottom Section --}}
            <div class="bottom-section">

                {{-- Info Penting --}}
                <div class="info-penting">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                    </svg>
                    <div class="info-penting-content">
                        <div class="info-penting-title">Informasi Penting</div>
                        <ul class="info-penting-list">
                            <li>Harap datang 15 menit sebelum jadwal bermain.</li>
                            <li>Tunjukkan e-receipt ini ke resepsionis lapangan saat kedatangan.</li>
                        </ul>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="btn-group">
                    <a href="{{ $whatsappUrlAdmin }}" target="_blank" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                        </svg>
                        Kirim ke Admin
                    </a>

                    <button onclick="window.print()" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                        Simpan Struk
                    </button>
                </div>

                {{-- Footer --}}
                <div class="card-footer">
                    <a href="{{ route('profile', ['tab' => 'jadwal-booking']) }}" class="footer-link">Lihat Riwayat Booking</a>
                    <div class="auto-redirect">
                        Otomatis ke profil dalam <span class="timer-count" id="timer">30</span> detik
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        let seconds = 30;
        const timerEl = document.getElementById('timer');
        const interval = setInterval(() => {
            seconds--;
            timerEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = '{{ route('profile', ['tab' => 'jadwal-booking']) }}';
            }
        }, 1000);
    </script>

</body>
</html>
