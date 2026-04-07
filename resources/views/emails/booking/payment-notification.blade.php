@component('mail::message')
# 💰 Pembayaran Masuk!

@component('mail::panel')
**👤 Nama:** {{ $booking->client->name }}
**📱 No. HP:** {{ $booking->client->phone ?? '-' }}
**📧 Email:** {{ $booking->client->email ?? '-' }}

**🏟️ Lapangan:** {{ match($booking->venue_type) {
    'cibadak_a' => 'The Arena Cibadak A',
    'cibadak_b' => 'The Arena Cibadak B',
    'pvj'       => 'The Arena PVJ',
    'urban'     => 'The Arena Urban',
    default     => 'The Arena',
} }}
**📅 Tanggal:** {{ $booking->booking_date->format('d/m/Y') }}
**⏰ Jam:** {{ collect($booking->time_slots)->pluck('time')->join(', ') }}

**🧾 No. Tagihan:** {{ $booking->bill_no }}
**💳 Metode Bayar:** {{ $booking->payment_method ?? '-' }}
**💰 Total:** Rp {{ number_format($booking->total_price, 0, ',', '.') }}
**🕐 Waktu Bayar:** {{ $booking->paid_at?->format('d/m/Y H:i:s') }}
@endcomponent

The Arena - Payment Notification
@endcomponent