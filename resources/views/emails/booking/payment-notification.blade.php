<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Pembayaran - The Arena</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: #f0f2f5;
            font-family: 'Poppins', Arial, sans-serif;
            color: #1a1a2e;
            padding: 40px 16px;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper {
            max-width: 580px;
            margin: 0 auto;
        }

        .header {
            background-color: #013064;
            border-radius: 16px 16px 0 0;
            padding: 36px 48px 32px;
            text-align: center;
        }

        .logo {
            height: 100px;
            width: auto;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .header-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #ffd230;
            margin-bottom: 8px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.3px;
        }

        .strip {
            height: 5px;
            background: linear-gradient(90deg, #ffd230 60%, #f3684d 100%);
        }

        .body {
            background-color: #ffffff;
            padding: 40px 48px;
        }

        .intro-text {
            font-size: 13px;
            font-weight: 400;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.7;
            border-left: 3px solid #ffd230;
            padding-left: 14px;
        }

        .section-title {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #013064;
            margin-bottom: 10px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .card table {
            width: 100%;
            border-collapse: collapse;
        }

        .card table tr {
            border-bottom: 1px solid #f3f4f6;
        }

        .card table tr:last-child {
            border-bottom: none;
        }

        .card table td {
            padding: 13px 18px;
            font-size: 13px;
            vertical-align: middle;
            line-height: 1.5;
        }

        .card table td:first-child {
            color: #9ca3af;
            font-weight: 500;
            width: 40%;
            background-color: #fafafa;
            border-right: 1px solid #f3f4f6;
        }

        .card table td:last-child {
            color: #111827;
            font-weight: 600;
        }

        .total-amount {
            font-size: 18px;
            font-weight: 700;
            color: #013064 !important;
        }

        .status-paid {
            display: inline-block;
            background-color: #ecfdf5;
            color: #065f46;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 6px;
            border: 1px solid #a7f3d0;
        }

        .footer {
            background-color: #013064;
            border-radius: 0 0 16px 16px;
            padding: 24px 48px;
            text-align: center;
            border-top: 5px solid #ffd230;
        }

        .footer-brand {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .footer p {
            font-size: 11px;
            color: rgba(255,255,255,0.45);
            line-height: 1.7;
        }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <img src="https://i.imgur.com/WV7vdyu.png"
             alt="The Arena Basketball"
             class="logo">
        <div class="header-label">Payment Notification</div>
        <h1>Pembayaran Berhasil Diterima</h1>
    </div>
    <div class="strip"></div>

    <div class="body">

        <p class="intro-text">
            Transaksi baru telah berhasil diproses. Berikut ringkasan detail pembayaran dari pelanggan.
        </p>

        <div class="section-title">Data Pelanggan</div>
        <div class="card">
            <table>
                <tr>
                    <td>Nama</td>
                    <td>{{ $booking->client->name }}</td>
                </tr>
                <tr>
                    <td>No. HP</td>
                    <td>{{ $booking->client->phone ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td>{{ $booking->client->email ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-title">Detail Booking</div>
        <div class="card">
            <table>
                <tr>
                    <td>Lapangan</td>
                    <td>
                        @php
                            echo match($booking->venue_type) {
                                'cibadak_a' => 'The Arena Cibadak A',
                                'cibadak_b' => 'The Arena Cibadak B',
                                'pvj'       => 'The Arena PVJ',
                                'urban'     => 'The Arena Urban',
                                default     => 'The Arena',
                            };
                        @endphp
                    </td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>{{ $booking->booking_date->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td>Jam</td>
                    <td>{{ collect($booking->time_slots)->pluck('time')->join(', ') }}</td>
                </tr>
            </table>
        </div>

        <div class="section-title">Informasi Pembayaran</div>
        <div class="card">
            <table>
                <tr>
                    <td>No. Tagihan</td>
                    <td>{{ $booking->bill_no }}</td>
                </tr>
                <tr>
                    <td>Metode Bayar</td>
                    <td>{{ $booking->payment_method ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Total</td>
                    <td class="total-amount">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Waktu Bayar</td>
                    <td>{{ $booking->paid_at?->format('d F Y, H:i') }} WIB</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span class="status-paid">Lunas</span></td>
                </tr>
            </table>
        </div>

    </div>

    <div class="footer">
        <div class="footer-brand">The Arena Basketball</div>
        <p>
            Email otomatis dari sistem. Mohon tidak membalas email ini.<br>
            &copy; {{ date('Y') }} The Arena Basketball. All rights reserved.
        </p>
    </div>

</div>
</body>
</html>