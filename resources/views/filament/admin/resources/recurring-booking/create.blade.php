<x-filament-panels::page>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
* { font-family: 'Inter', sans-serif !important; }
.fi-header,
.fi-page-header {
    display: none !important;
}

.rb-page-wrapper {
    margin: -1.5rem;
    padding: 2rem;
    min-height: 100vh;
    background: #f1f5f9;
    color-scheme: light !important;
}

/* Pastikan teks, input, dan form selalu mengikuti mode terang (override dark mode bawaan) */
.rb-page-wrapper {
    color-scheme: light !important;
}

.rb-wrap { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; padding-bottom: 2rem; }
.rb-card { background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.rb-card-header { background: linear-gradient(135deg, #013064, #024a8f) !important; padding: 1rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
.rb-card-header h3 { color: #ffffff !important; font-size: 1rem; font-weight: 700; margin: 0; }
.rb-card-header .icon { width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.rb-card-body { padding: 1.5rem; }

.rb-label { display: block; font-size: 0.8125rem; font-weight: 600; color: #475569 !important; margin-bottom: 0.4rem; letter-spacing: 0.02em; }

/* Styling Input & Select Super Kuat */
.rb-input { width: 100%; background-color: #f8fafc !important; border: 1px solid #cbd5e1 !important; border-radius: 10px; padding: 0.625rem 0.875rem; color: #0f172a !important; font-size: 0.9rem; outline: none; transition: border-color 0.2s; color-scheme: light !important; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05) !important; }
.rb-input::placeholder { color: #94a3b8 !important; opacity: 1 !important; }
.rb-input:focus { border-color: #3b82f6 !important; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05), 0 0 0 3px rgba(59,130,246,0.1) !important; }

.rb-select { appearance: none !important; background-color: #f8fafc !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569' stroke-width='2'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") !important; background-repeat: no-repeat !important; background-position: right 0.75rem center !important; background-size: 1rem !important; padding-right: 2.5rem !important; color: #0f172a !important; }
.rb-select option { color: #0f172a !important; background-color: #ffffff !important; }
.rb-textarea { resize: vertical; min-height: 80px; }
.rb-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.rb-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }

/* Modes */
.mode-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.mode-tab { padding: 0.5rem 1rem; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; font-size: 0.8125rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.mode-tab:hover { border-color: #94a3b8; color: #334155; }
.mode-tab.active { background: #eff6ff; border-color: #3b82f6; color: #1d4ed8; }

/* Slots checkbox */
.slot-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
.slot-btn { padding: 0.5rem 0.25rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 0.78rem; font-weight: 600; cursor: pointer; text-align: center; transition: all 0.2s; user-select: none; }
.slot-btn:hover { border-color: #94a3b8; }
.slot-btn.active { background: #eff6ff; border-color: #3b82f6; color: #1d4ed8; }

/* Week cards */
.week-card { background: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 12px; overflow: hidden; margin-bottom: 0.75rem; }
.week-header { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; cursor: pointer; }
.week-header h4 { color: #0f172a !important; font-size: 0.875rem; font-weight: 700; margin: 0; }
.week-header .week-count { background: #eff6ff !important; color: #1d4ed8 !important; font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 20px; border: 1px solid #bfdbfe !important; }
.week-body { padding: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem; border-top: 1px solid #e2e8f0 !important; background: #ffffff !important; }

.date-card { display: flex; flex-direction: column; align-items: center; padding: 0.625rem 0.875rem; border-radius: 10px; border: 2px solid #e2e8f0 !important; background: #ffffff !important; cursor: pointer; transition: all 0.18s; min-width: 72px; user-select: none; }
.date-card:hover:not(.past) { border-color: #3b82f6 !important; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.date-card.checked { background: #eff6ff !important; border-color: #3b82f6 !important; }
.date-card.past { opacity: 0.4; cursor: not-allowed; pointer-events: none; background: #f1f5f9 !important; border-color: #e2e8f0 !important; }
.date-card .dc-day { font-size: 0.68rem; font-weight: 700; color: #64748b !important; text-transform: uppercase; letter-spacing: 0.06em; }
.date-card.checked .dc-day { color: #1d4ed8 !important; }
.date-card .dc-num { font-size: 1.6rem; font-weight: 800; color: #0f172a !important; line-height: 1; margin: 0.15rem 0; }
.date-card .dc-month { font-size: 0.7rem; font-weight: 600; color: #64748b !important; }
.date-card.checked .dc-month { color: #1e40af; }
.date-card .dc-check { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #cbd5e1; margin-top: 0.35rem; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; }
.date-card.checked .dc-check { background: #3b82f6; border-color: #3b82f6; color: #fff; }

/* Preview */
.preview-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 1rem 1.25rem; }
.preview-row { display: flex; align-items: center; justify-content: space-between; font-size: 0.82rem; padding: 0.2rem 0; }
.preview-row .label { color: #065f46; font-weight: 600; }
.preview-row .val { color: #047857; font-weight: 700; }

/* Buttons */
.btn-primary { background: linear-gradient(135deg,#1d4ed8,#2563eb); color: #fff; border: none; border-radius: 10px; padding: 0.75rem 2rem; font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(37,99,235,0.2); }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
.btn-secondary { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.625rem 1.25rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-secondary:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; }
.btn-info { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 10px; padding: 0.625rem 1.25rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-info:hover { background: #dbeafe; }
.btn-danger-sm { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 8px; padding: 0.35rem 0.75rem; font-size: 0.75rem; cursor: pointer; font-weight: 600; }
.btn-danger-sm:hover { background: #fee2e2; }

.error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 0.875rem 1rem; margin-bottom: 1rem; }
.error-box li { color: #b91c1c; font-size: 0.83rem; font-weight: 500; }

.radio-group { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.radio-opt { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.875rem; border-radius: 8px; border: 1.5px solid #e2e8f0 !important; background: #ffffff !important; cursor: pointer; transition: all 0.2s; }
.radio-opt input { display: none; }
.radio-opt span { font-size: 0.82rem; color: #475569 !important; font-weight: 600; }
.radio-opt.checked { border-color: #3b82f6 !important; background: #eff6ff !important; }
.radio-opt.checked span { color: #1d4ed8 !important; }
</style>

<div class="rb-page-wrapper">
<div class="rb-wrap">

    <div style="margin-bottom: 1rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: #013064; display: flex; align-items: center; gap: 0.75rem;">
            <span>Buat Booking Member</span>
        </h1>
        <p style="color: #64748b; font-size: 0.95rem; margin-top: 0.25rem;">Atur jadwal recurring (berulang) untuk member The Arena.</p>
    </div>

    {{-- Validation Errors --}}
    @if(!empty($validationErrors))
        <div class="error-box">
            <ul class="list-disc ml-4">
                @foreach($validationErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── CARD 1: Customer ─────────────────────────────── --}}
    <div class="rb-card">
        <div class="rb-card-header">
            <div class="icon">👤</div>
            <h3>Informasi Customer</h3>
        </div>
        <div class="rb-card-body">
            <div class="radio-group" style="margin-bottom:1rem">
                @foreach(['manual' => 'Input Manual (Guest)', 'existing' => 'Customer Terdaftar'] as $val => $lbl)
                    <label class="radio-opt {{ $customerType === $val ? 'checked' : '' }}" wire:click="$set('customerType','{{ $val }}')">
                        <input type="radio" name="customerType" value="{{ $val }}" @if($customerType===$val) checked @endif>
                        <span>{{ $lbl }}</span>
                    </label>
                @endforeach
            </div>

            @if($customerType === 'manual')
                <div class="rb-grid-2">
                    <div>
                        <label class="rb-label">Nama Customer *</label>
                        <input type="text" class="rb-input" wire:model.lazy="customerNameManual" placeholder="Nama lengkap">
                    </div>
                    <div>
                        <label class="rb-label">No. Telepon</label>
                        <input type="tel" class="rb-input" wire:model.lazy="customerPhoneManual" placeholder="08xx">
                    </div>
                </div>
            @else
                <div>
                    <label class="rb-label">Pilih Customer *</label>
                    <select class="rb-input rb-select" wire:model="clientId">
                        <option value="">— Pilih customer —</option>
                        @foreach($clientOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    {{-- ── CARD 2: Venue & Waktu ────────────────────────── --}}
    <div class="rb-card">
        <div class="rb-card-header">
            <div class="icon">🏟️</div>
            <h3>Venue &amp; Waktu Main</h3>
        </div>
        <div class="rb-card-body" style="display:flex;flex-direction:column;gap:1.25rem">
            <div>
                <label class="rb-label">Venue *</label>
                <select class="rb-input rb-select" wire:model="venueType">
                    <option value="cibadak_a">Cibadak A</option>
                    <option value="cibadak_b">Cibadak B</option>
                    <option value="pvj">PVJ Mall</option>
                    <option value="urban">Urban</option>
                </select>
            </div>
            <div>
                <label class="rb-label">Pilih Jam Main * <span style="color:#d97706;font-weight:500">(harga dihitung otomatis per tanggal)</span></label>
                <div class="slot-grid">
                    @foreach(['06.00 - 08.00','08.00 - 10.00','10.00 - 12.00','12.00 - 14.00','14.00 - 16.00','16.00 - 18.00','18.00 - 20.00','20.00 - 22.00','22.00 - 00.00'] as $slot)
                        <div class="slot-btn {{ in_array($slot, $timeSlotsSelection) ? 'active' : '' }}"
                             wire:click="toggleTimeSlot('{{ $slot }}')">
                            {{ $slot }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── CARD 3: Pola Jadwal ─────────────────────────── --}}
    <div class="rb-card">
        <div class="rb-card-header">
            <div class="icon">📅</div>
            <h3>Pola Jadwal Booking</h3>
        </div>
        <div class="rb-card-body" style="display:flex;flex-direction:column;gap:1.5rem">

            {{-- Mode selector --}}
            <div>
                <label class="rb-label">Mode</label>
                <div class="mode-tabs">
                    @foreach(['weekly_flex' => '📆 Mingguan Fleksibel','weekly' => '🔁 Mingguan Rutin','monthly_day' => '🗓️ Bulanan per Hari','custom' => '✏️ Custom'] as $m => $ml)
                        <div class="mode-tab {{ $recurringMode === $m ? 'active' : '' }}" wire:click="$set('recurringMode','{{ $m }}')">{{ $ml }}</div>
                    @endforeach
                </div>
            </div>

            {{-- ===== MINGGUAN FLEKSIBEL ===== --}}
            @if($recurringMode === 'weekly_flex')
                <div style="display:flex;flex-direction:column;gap:1.25rem">
                    <div>
                        <label class="rb-label" style="font-size:0.75rem;color:#94a3b8;margin-bottom:.75rem;text-transform:uppercase;">LANGKAH 1 — Pilih Rentang Tanggal</label>
                        <div class="rb-grid-2" style="margin-bottom:.75rem">
                            <div>
                                <label class="rb-label">Tanggal Mulai</label>
                                <input type="date" class="rb-input" wire:model="flexRangeStart">
                            </div>
                            <div>
                                <label class="rb-label">Tanggal Selesai</label>
                                <input type="date" class="rb-input" wire:model="flexRangeEnd">
                            </div>
                        </div>
                        <button class="btn-info" wire:click="generateWeeks" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="generateWeeks">🗓️ Buat Minggu Otomatis</span>
                            <span wire:loading wire:target="generateWeeks">⏳ Generating...</span>
                        </button>
                    </div>

                    @if(!empty($weekSchedule))
                        <div>
                            <label class="rb-label" style="font-size:0.75rem;color:#94a3b8;margin-bottom:.75rem;text-transform:uppercase;">LANGKAH 2 — Centang Tanggal per Minggu</label>

                            @foreach($weekSchedule as $wi => $week)
                                @php
                                    $checkedCount = collect($week['days'])->filter(fn($d) => !empty($d['checked']))->count();
                                @endphp
                                <div class="week-card">
                                    <div class="week-header" wire:click="toggleWeekCollapse({{ $wi }})">
                                        <div style="display:flex;align-items:center;gap:.75rem">
                                            <span style="color:#94a3b8;font-size:0.9rem">{{ ($week['collapsed'] ?? false) ? '▶' : '▼' }}</span>
                                            <h4>Minggu {{ $wi + 1 }}: {{ $week['label'] }}</h4>
                                            @if($checkedCount > 0)
                                                <span class="week-count">{{ $checkedCount }} dipilih</span>
                                            @endif
                                        </div>
                                        <div style="display:flex;gap:.5rem" wire:click.stop="">
                                            <button class="btn-secondary" style="padding:.25rem .6rem;font-size:.7rem" wire:click="selectAllDaysInWeek({{ $wi }})">Semua</button>
                                            <button class="btn-secondary" style="padding:.25rem .6rem;font-size:.7rem" wire:click="clearAllDaysInWeek({{ $wi }})">Hapus</button>
                                        </div>
                                    </div>
                                    @if(!($week['collapsed'] ?? false))
                                        <div class="week-body">
                                            @foreach($week['days'] as $di => $day)
                                                <div class="date-card {{ !empty($day['checked']) ? 'checked' : '' }} {{ $day['is_past'] ? 'past' : '' }}"
                                                     @if(!$day['is_past']) wire:click="toggleDay({{ $wi }}, {{ $di }})" @endif>
                                                    <span class="dc-day">{{ $day['day_short'] }}</span>
                                                    <span class="dc-num">{{ $day['day_num'] }}</span>
                                                    <span class="dc-month">{{ $day['month_short'] }}</span>
                                                    <div class="dc-check">@if(!empty($day['checked']))✓@endif</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Preview total --}}
                        @php
                            $totalChecked = collect($weekSchedule)->flatMap(fn($w) => $w['days'])->filter(fn($d) => !empty($d['checked']) && !$d['is_past'])->count();
                        @endphp
                        @if($totalChecked > 0)
                            <div class="preview-box">
                                <div class="preview-row">
                                    <span class="label">Total Booking</span>
                                    <span class="val" style="color:#34d399">{{ $totalChecked }} sesi × {{ count($timeSlotsSelection) ?: '?' }} slot waktu</span>
                                </div>
                                <div class="preview-row">
                                    <span class="label">Venue</span>
                                    <span class="val">{{ ['cibadak_a'=>'Cibadak A','cibadak_b'=>'Cibadak B','pvj'=>'PVJ Mall','urban'=>'Urban'][$venueType] ?? $venueType }}</span>
                                </div>
                            </div>
                        @endif
                    @else
                        <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;">
                            ☝️ Pilih rentang tanggal lalu klik "Buat Minggu Otomatis"
                        </div>
                    @endif
                </div>
            @endif

            {{-- ===== MINGGUAN RUTIN ===== --}}
            @if($recurringMode === 'weekly')
                <div style="display:flex;flex-direction:column;gap:1rem">
                    <div class="rb-grid-2">
                        <div>
                            <label class="rb-label">Tanggal Mulai</label>
                            <input type="date" class="rb-input" wire:model="weeklyStartDate">
                        </div>
                        <div>
                            <label class="rb-label">Durasi</label>
                            <select class="rb-input rb-select" wire:model="weeklyDuration">
                                <option value="4">1 Bulan (4 minggu)</option>
                                <option value="8">2 Bulan (8 minggu)</option>
                                <option value="12">3 Bulan (12 minggu)</option>
                                <option value="26">6 Bulan (26 minggu)</option>
                                <option value="52">1 Tahun (52 minggu)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="rb-label">Hari Main (setiap minggu)</label>
                        <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                            @foreach([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',0=>'Minggu'] as $d => $dn)
                                <div class="slot-btn {{ in_array($d, $weeklyDays) ? 'active' : '' }}"
                                     wire:click="toggleWeeklyDay({{ $d }})">
                                    {{ $dn }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== BULANAN PER HARI ===== --}}
            @if($recurringMode === 'monthly_day')
                <div>
                    @foreach($monthlySchedule as $mi => $ms)
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;margin-bottom:.75rem">
                            <div class="rb-grid-2" style="margin-bottom:.75rem">
                                <div>
                                    <label class="rb-label">Bulan</label>
                                    <input type="date" class="rb-input" wire:model="monthlySchedule.{{ $mi }}.month_start">
                                </div>
                                <div style="display:flex;align-items:flex-end">
                                    <button class="btn-danger-sm" wire:click="removeMonthlyEntry({{ $mi }})">✕ Hapus</button>
                                </div>
                            </div>
                            <label class="rb-label">Hari yang dimainkan bulan ini</label>
                            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                                @foreach([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',0=>'Minggu'] as $d => $dn)
                                    <div class="slot-btn {{ in_array($d, $ms['days_of_week'] ?? []) ? 'active' : '' }}" wire:click="toggleMonthlyDay({{ $mi }}, {{ $d }})">{{ $dn }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <button class="btn-secondary" wire:click="addMonthlyEntry">+ Tambah Bulan</button>
                </div>
            @endif

            {{-- ===== CUSTOM ===== --}}
            @if($recurringMode === 'custom')
                <div>
                    @foreach($customDates as $ci => $cd)
                        <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:.5rem">
                            <input type="date" class="rb-input" style="flex:1" wire:model="customDates.{{ $ci }}">
                            <button class="btn-danger-sm" wire:click="removeCustomDate({{ $ci }})">✕</button>
                        </div>
                    @endforeach
                    <button class="btn-secondary" wire:click="addCustomDate">+ Tambah Tanggal</button>
                </div>
            @endif

        </div>
    </div>

    {{-- ── CARD 4: Status & Catatan ───────────────────── --}}
    <div class="rb-card">
        <div class="rb-card-header"><div class="icon">⚙️</div><h3>Status &amp; Catatan</h3></div>
        <div class="rb-card-body" style="display:flex;flex-direction:column;gap:1rem">
            <div class="rb-grid-3">
                <div>
                    <label class="rb-label">Status Booking</label>
                    <select class="rb-input rb-select" wire:model="status">
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div>
                    <label class="rb-label">Status Pembayaran</label>
                    <select class="rb-input rb-select" wire:model="paymentStatus">
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end;padding-bottom:.1rem">
                    <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer">
                        <input type="checkbox" wire:model="isPaid" style="width:18px;height:18px;accent-color:#3b82f6">
                        <span class="rb-label" style="margin:0">Sudah Dibayar</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="rb-label">Catatan (opsional)</label>
                <textarea class="rb-input rb-textarea" wire:model.lazy="notes" placeholder="Catatan tambahan..."></textarea>
            </div>
        </div>
    </div>

    {{-- ── Submit ───────────────────────────────────────── --}}
    <div style="display:flex;justify-content:flex-end;gap:1rem;padding-bottom:2rem">
        <a href="{{ $backUrl }}" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center">← Kembali</a>
        <button class="btn-primary" wire:click="save" wire:loading.attr="disabled" wire:loading.class="opacity-60">
            <span wire:loading.remove wire:target="save">✅ Simpan Booking Member</span>
            <span wire:loading wire:target="save">⏳ Menyimpan...</span>
        </button>
    </div>

</div>
</div>
</x-filament-panels::page>
