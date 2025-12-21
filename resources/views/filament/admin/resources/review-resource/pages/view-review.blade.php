<div class="space-y-6">
    {{-- Status Approval Badge --}}
    <div class="p-4 rounded-lg {{ $record->is_approved ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' }}">
        <div class="flex items-center gap-3">
            @if($record->is_approved)
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-200">Review Approved</h4>
                    <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                        Review ini telah disetujui dan ditampilkan di website
                        @if($record->approved_at)
                            pada {{ $record->approved_at->format('d F Y H:i') }}
                        @endif
                    </p>
                </div>
            @else
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">Pending Approval</h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        Review ini menunggu persetujuan admin sebelum ditampilkan di website
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Client Information --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Informasi Client</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nama Client</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->client->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->client->email ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Telepon</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->client->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Alamat</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->client->address ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Review Information dengan 3 Aspek Rating --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Detail Rating</h3>
        
        {{-- Rating Fasilitas --}}
        <div class="mb-4 p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fasilitas</p>
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-2xl {{ $i <= $record->rating_facilities ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}">
                                ⭐
                            </span>
                        @endfor
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $record->rating_facilities }}</span>
                    <span class="text-lg text-gray-500 dark:text-gray-400">/5</span>
                </div>
            </div>
        </div>

        {{-- Rating Keramahan --}}
        <div class="mb-4 p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keramahan</p>
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-2xl {{ $i <= $record->rating_hospitality ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}">
                                ⭐
                            </span>
                        @endfor
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $record->rating_hospitality }}</span>
                    <span class="text-lg text-gray-500 dark:text-gray-400">/5</span>
                </div>
            </div>
        </div>

        {{-- Rating Kebersihan --}}
        <div class="mb-4 p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kebersihan</p>
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-2xl {{ $i <= $record->rating_cleanliness ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}">
                                ⭐
                            </span>
                        @endfor
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $record->rating_cleanliness }}</span>
                    <span class="text-lg text-gray-500 dark:text-gray-400">/5</span>
                </div>
            </div>
        </div>

        {{-- Rating Rata-rata --}}
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-1">Rating Rata-rata</p>
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-2xl {{ $i <= round($record->rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}">
                                ⭐
                            </span>
                        @endfor
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-4xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($record->rating, 1) }}</span>
                    <span class="text-xl text-blue-500 dark:text-blue-400">/5</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Comment - FIXED CONTRAST --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Komentar</h3>
        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <p class="text-base text-gray-900 dark:text-gray-100 leading-relaxed whitespace-pre-wrap">{{ $record->comment }}</p>
        </div>
    </div>

    {{-- Booking Information --}}
    @if($record->booking)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Informasi Booking Terkait</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ID Booking</p>
                    <p class="font-medium text-gray-900 dark:text-white">#{{ $record->booking->id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Booking</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $record->booking->booking_date->format('d F Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Venue Type</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ ucfirst(str_replace('_', ' ', $record->booking->venue_type)) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Harga</p>
                    <p class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($record->booking->total_price, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">Tidak Ada Booking Terkait</h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        Review ini tidak memiliki booking terkait atau booking telah dihapus.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Timestamps Detail --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Dibuat pada</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->created_at->format('d F Y H:i') }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $record->created_at->diffForHumans() }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Terakhir diupdate</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $record->updated_at->format('d F Y H:i') }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $record->updated_at->diffForHumans() }}</p>
            </div>
            @if($record->is_approved && $record->approved_at)
            <div>
                <p class="text-gray-500 dark:text-gray-400">Disetujui pada</p>
                <p class="font-medium text-green-700 dark:text-green-400">{{ $record->approved_at->format('d F Y H:i') }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $record->approved_at->diffForHumans() }}</p>
            </div>
            @endif
        </div>
    </div>
</div>