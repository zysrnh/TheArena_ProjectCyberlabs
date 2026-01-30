import { Head, Link, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { ChevronDown, ChevronLeft, ChevronRight, Calendar, X } from "lucide-react";
import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";

export default function MatchPage({ auth, filters, dates, matches, today, weekInfo, leagues, activeEventNotif = null }) {
  const [selectedYear, setSelectedYear] = useState(filters.year || '');
  const [selectedLeague, setSelectedLeague] = useState(filters.league || '');
  const [selectedSeries, setSelectedSeries] = useState(filters.series || '');
  const [selectedRegion, setSelectedRegion] = useState(filters.region || '');
  const [selectedDate, setSelectedDate] = useState(filters.selectedDate || null);
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [weekOffset, setWeekOffset] = useState(filters.week || 0);
  const [selectedMonth, setSelectedMonth] = useState(filters.month || '');
  const [showEventNotifPopup, setShowEventNotifPopup] = useState(false);

  // ✅ SYNC STATE WITH PROPS
  useEffect(() => {
    setSelectedYear(filters.year || '');
    setSelectedLeague(filters.league || '');
    setSelectedSeries(filters.series || '');
    setSelectedRegion(filters.region || '');
    setSelectedDate(filters.selectedDate || null);
    setSearchQuery(filters.search || '');
    setWeekOffset(filters.week || 0);
    setSelectedMonth(filters.month || '');
  }, [filters]);

  // ✅ SHOW EVENT NOTIF POPUP
  useEffect(() => {
    if (activeEventNotif) {
      setShowEventNotifPopup(true);
    }
  }, [activeEventNotif]);

  // ✅ CRITICAL FIX: Don't send empty string parameters
  const handleFilterChange = (filterName, value) => {
    const params = {};

    // Build base params - ONLY if they have values
    if (selectedLeague && selectedLeague !== '') params.league = selectedLeague;
    if (selectedSeries && selectedSeries !== '') params.series = selectedSeries;
    if (selectedRegion && selectedRegion !== '') params.region = selectedRegion;
    if (searchQuery && searchQuery !== '') params.search = searchQuery;
    if (selectedYear && selectedYear !== '') params.year = selectedYear;

    // Apply the changed filter
    if (filterName === 'year') {
      if (value && value !== '') {
        params.year = value;
      } else {
        delete params.year; // Remove year if empty
      }
    } else if (filterName === 'league') {
      if (value && value !== '') {
        params.league = value;
      } else {
        delete params.league;
      }
    } else if (filterName === 'series') {
      if (value && value !== '') {
        params.series = value;
      } else {
        delete params.series;
      }
    } else if (filterName === 'region') {
      if (value && value !== '') {
        params.region = value;
      } else {
        delete params.region;
      }
    }

    // Date navigation logic
    if (filterName === 'date') {
      params.date = value;
    } else if (filterName === 'week') {
      params.week = value;
    } else if (filterName === 'month') {
      params.month = value;
    } else {
      // Preserve current date state for other filters
      if (selectedDate) {
        params.date = selectedDate;
      } else if (weekOffset !== 0) {
        params.week = weekOffset;
      } else if (selectedMonth) {
        params.month = selectedMonth;
      }
    }

    router.get('/jadwal-hasil', params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleWeekChange = (offset) => {
    const params = { week: offset };

    if (selectedLeague && selectedLeague !== '') params.league = selectedLeague;
    if (selectedSeries && selectedSeries !== '') params.series = selectedSeries;
    if (selectedRegion && selectedRegion !== '') params.region = selectedRegion;
    if (selectedYear && selectedYear !== '') params.year = selectedYear;
    if (searchQuery && searchQuery !== '') params.search = searchQuery;

    router.get('/jadwal-hasil', params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleDatePickerChange = (e) => {
    const date = e.target.value;
    if (!date) return;
    
    const params = { date: date };

    if (selectedLeague && selectedLeague !== '') params.league = selectedLeague;
    if (selectedSeries && selectedSeries !== '') params.series = selectedSeries;
    if (selectedRegion && selectedRegion !== '') params.region = selectedRegion;
    if (selectedYear && selectedYear !== '') params.year = selectedYear;
    if (searchQuery && searchQuery !== '') params.search = searchQuery;

    router.get('/jadwal-hasil', params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const resetToCurrentMonth = () => {
    const params = {};
    
    if (selectedLeague && selectedLeague !== '') params.league = selectedLeague;
    if (selectedSeries && selectedSeries !== '') params.series = selectedSeries;
    if (selectedRegion && selectedRegion !== '') params.region = selectedRegion;
    if (selectedYear && selectedYear !== '') params.year = selectedYear;
    if (searchQuery && searchQuery !== '') params.search = searchQuery;

    router.get('/jadwal-hasil', params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleSearch = (e) => {
    e.preventDefault();

    const params = {};
    
    if (searchQuery && searchQuery !== '') params.search = searchQuery;
    if (selectedLeague && selectedLeague !== '') params.league = selectedLeague;
    if (selectedSeries && selectedSeries !== '') params.series = selectedSeries;
    if (selectedRegion && selectedRegion !== '') params.region = selectedRegion;
    if (selectedYear && selectedYear !== '') params.year = selectedYear;
    
    if (selectedDate) {
      params.date = selectedDate;
    } else if (weekOffset !== 0) {
      params.week = weekOffset;
    } else if (selectedMonth) {
      params.month = selectedMonth;
    }

    router.get('/jadwal-hasil', params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleCloseEventNotifPopup = () => {
    setShowEventNotifPopup(false);
  };

  const handleRegisterEvent = () => {
    if (activeEventNotif?.whatsapp_url) {
      window.open(activeEventNotif.whatsapp_url, '_blank', 'noopener,noreferrer');
      handleCloseEventNotifPopup();
    }
  };

  return (
    <>
      <Head title="THE ARENA - Jadwal & Hasil" />
      <style>{`
        @keyframes fade-in {
          from { opacity: 0; }
          to { opacity: 1; }
        }

        @keyframes modal-appear {
          from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
          }
          to {
            opacity: 1;
            transform: translateY(0) scale(1);
          }
        }

        .animate-fade-in {
          animation: fade-in 0.3s ease-out;
        }

        .animate-modal-appear {
          animation: modal-appear 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
        input[type="month"]::-webkit-calendar-picker-indicator,
        input[type="date"]::-webkit-calendar-picker-indicator {
          filter: invert(1);
          cursor: pointer;
        }
        input[type="month"],
        input[type="date"] {
          color-scheme: dark;
        }
        @keyframes float {
          0%, 100% {
            transform: translateY(0px);
          }
          50% {
            transform: translateY(-10px);
          }
        }

        @keyframes pulse-ring {
          0% {
            transform: scale(1);
            opacity: 1;
          }
          100% {
            transform: scale(1.5);
            opacity: 0;
          }
        }

        .animate-float {
          animation: float 3s ease-in-out infinite;
        }

        .animate-pulse-ring {
          animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
      `}</style>
      <div className="min-h-screen flex flex-col bg-[#013064]">
        <Navigation activePage="jadwal-hasil" />

        {/* Hero Section */}
        <div className="bg-[#013064] py-12 md:py-16 px-4">
          <div className="max-w-7xl mx-auto">
            <div className="mb-8">
              <p className="text-[#ffd22f] text-3xl md:text-4xl mb-3">
                Pertandingan
              </p>
              <h1 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold">
                Lihat Jadwal & Hasil<br />Pertandingan!
              </h1>
            </div>

            {/* Filter Dropdowns & Search Row */}
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
              <div className="flex flex-wrap gap-2">
                {/* Year Dropdown */}
                <div className="relative">
                  <select
                    value={selectedYear}
                    onChange={(e) => handleFilterChange('year', e.target.value)}
                    className="bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm md:text-base font-semibold cursor-pointer appearance-none pr-10 rounded"
                  >
                    <option value="">Semua Tahun</option>
                    {Array.from({ length: new Date().getFullYear() - 2020 + 2 }, (_, i) => 2020 + i).reverse().map(year => (
                      <option key={year} value={year}>{year}</option>
                    ))}
                  </select>
                  <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#013064] pointer-events-none" />
                </div>

                {/* League/Competition Dropdown */}
                <div className="relative">
                  <select
                    value={selectedLeague}
                    onChange={(e) => handleFilterChange('league', e.target.value)}
                    className="bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm md:text-base font-semibold cursor-pointer appearance-none pr-10 rounded"
                  >
                    <option value="">Semua Liga</option>
                    {leagues && leagues.map((league, index) => (
                      <option key={index} value={league}>{league}</option>
                    ))}
                  </select>
                  <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#013064] pointer-events-none" />
                </div>

                {/* Series Dropdown */}
                <div className="relative">
                  <select
                    value={selectedSeries}
                    onChange={(e) => handleFilterChange('series', e.target.value)}
                    className="bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm md:text-base font-semibold cursor-pointer appearance-none pr-10 rounded"
                  >
                    <option value="">Semua Series</option>
                    <option value="Regular Season">Regular Season</option>
                    <option value="Playoff">Playoff</option>
                    <option value="Finals">Finals</option>
                  </select>
                  <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#013064] pointer-events-none" />
                </div>

                {/* Region Dropdown */}
                <div className="relative">
                  <select
                    value={selectedRegion}
                    onChange={(e) => handleFilterChange('region', e.target.value)}
                    className="bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm md:text-base font-semibold cursor-pointer appearance-none pr-10 rounded"
                  >
                    <option value="">Semua Regional</option>
                    <option value="Jakarta">Jakarta</option>
                    <option value="Bandung">Bandung</option>
                    <option value="Surabaya">Surabaya</option>
                  </select>
                  <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#013064] pointer-events-none" />
                </div>
              </div>

              {/* Search Box */}
              <div className="w-full md:w-auto md:min-w-[400px]">
                <form onSubmit={handleSearch} className="relative">
                  <input
                    type="text"
                    placeholder="Cari Pertandingan"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full px-6 py-2.5 pr-12 text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#ffd22f] rounded"
                  />
                  <button type="submit" className="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        {/* Date Picker + Week Navigation */}
        <div className="bg-[#013064] pt-2 pb-6 px-2">
          <div className="max-w-7xl mx-auto">
            {/* Date Picker - Mobile Friendly */}
            <div className="flex flex-col sm:flex-row items-center justify-center gap-3 mb-6">
              {/* Specific Date Picker */}
              <div className="relative w-full sm:w-auto">
                <input
                  type="date"
                  value={selectedDate || ''}
                  onChange={handleDatePickerChange}
                  placeholder="Pilih Tanggal"
                  className="w-full sm:w-auto px-6 py-2.5 bg-[#ffd22f] text-[#013064] font-semibold rounded cursor-pointer text-center appearance-none focus:outline-none focus:ring-2 focus:ring-white"
                />
                <Calendar className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-[#013064] pointer-events-none" />
              </div>

              {/* Reset Button */}
              {(selectedDate || selectedMonth || weekOffset !== 0) && (
                <button
                  onClick={resetToCurrentMonth}
                  className="w-full sm:w-auto px-6 py-2.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded transition text-sm whitespace-nowrap"
                >
                  Reset ke Minggu Ini
                </button>
              )}
            </div>

            {/* Week Navigation */}
            <div className="flex items-center justify-center gap-3 mb-6">
              <button
                onClick={() => handleWeekChange(weekOffset - 1)}
                className="p-2 bg-white/20 hover:bg-white/30 text-white rounded-full transition"
                title="Minggu Sebelumnya"
              >
                <ChevronLeft className="w-6 h-6" />
              </button>

              {weekInfo.is_current ? (
                <p className="text-[#ffd22f] text-base font-semibold px-4">Minggu Ini</p>
              ) : (
                <button
                  onClick={() => handleWeekChange(0)}
                  className="text-white text-base font-medium hover:text-[#ffd22f] transition px-4"
                >
                  Kembali ke Minggu Ini
                </button>
              )}

              <button
                onClick={() => handleWeekChange(weekOffset + 1)}
                className="p-2 bg-white/20 hover:bg-white/30 text-white rounded-full transition"
                title="Minggu Selanjutnya"
              >
                <ChevronRight className="w-6 h-6" />
              </button>
            </div>

            {/* Date Cards */}
            {dates && dates.length > 0 ? (
              <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-7 gap-4">
                {dates.map((date) => (
                  <div
                    key={date.full_date}
                    onClick={() => handleFilterChange('date', date.full_date)}
                    className={`cursor-pointer transition-all overflow-hidden ${selectedDate === date.full_date
                        ? 'ring-4 ring-[#ffd22f] shadow-lg'
                        : 'hover:ring-2 hover:ring-white/50'
                      }`}
                  >
                    <div className="py-3 px-4 bg-[#ffd22f]">
                      <p className="text-xs md:text-sm font-semibold text-[#013064] text-center">
                        {date.name}
                      </p>
                    </div>

                    <div className={`py-4 px-4 ${selectedDate === date.full_date
                        ? 'bg-[#ffd22f]'
                        : 'bg-white'
                      }`}>
                      <p className="text-5xl font-bold text-[#013064] text-center mb-2">
                        {date.day}
                      </p>
                      <p className="text-xs text-[#013064] text-center mb-1">
                        {date.month}
                      </p>
                      <p className={`text-[10px] md:text-xs font-semibold text-center ${date.matches > 0 ? 'text-green-600' : 'text-gray-500'
                        }`}>
                        {date.matches > 0 ? `${date.matches} Pertandingan` : 'Tidak ada'}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <p className="text-white text-lg">Tidak ada jadwal pertandingan tersedia</p>
              </div>
            )}
          </div>
        </div>

        {/* Matches List */}
        <div className="bg-[#013064] py-12 px-4">
          <div className="max-w-7xl mx-auto">
            {matches.data && matches.data.length > 0 ? (
              <>
                <div className="grid sm:grid-cols-2 gap-6">
                  {matches.data.map((match) => (
                    <Link key={match.id} href={`/jadwal-hasil/${match.id}`}>
                      <div className="bg-white py-5 px-5 md:py-6 md:px-6 relative hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer min-h-[250px] md:min-h-[300px] flex flex-col">
                        <div className="flex items-center justify-center gap-4 md:gap-6 lg:gap-8 flex-1">
                          {/* Team 1 */}
                          <div className="flex flex-col items-center justify-center flex-1">
                            <img
                              src={match.team1.logo}
                              alt={match.team1.name}
                              className="w-24 h-24 md:w-32 md:h-32 lg:w-36 lg:h-36 object-contain mb-2"
                              onError={(e) => {
                                e.target.src = '/images/default-team-logo.png';
                              }}
                            />
                            <p className="text-xs md:text-sm font-bold text-[#013064] text-center px-2">
                              {match.team1.name}
                            </p>
                            {match.team1.category && (
                              <p className="text-[10px] md:text-xs text-gray-600 text-center mt-1">
                                {match.team1.category.name}
                              </p>
                            )}
                          </div>

                          {/* Match Info */}
                          <div className="flex flex-col items-center justify-center min-w-[130px] md:min-w-[150px]">
                            <p className="text-sm md:text-base font-bold text-gray-800 mb-2 text-center">
                              {match.league}
                            </p>

                            <div className="mb-1.5">
                              <span className={`px-2.5 py-1 text-xs font-bold uppercase ${match.type === 'live'
                                  ? 'bg-red-600 text-white'
                                  : match.type === 'upcoming'
                                    ? 'bg-green-600 text-white'
                                    : 'bg-gray-600 text-white'
                                }`}>
                                {match.type === 'live' ? 'Live' : match.type === 'upcoming' ? 'Upcoming Match' : 'Selesai'}
                              </span>
                            </div>
                            <p className="text-sm md:text-base font-bold text-gray-900 text-center">
                              {match.date}
                            </p>
                            <p className="text-[11px] md:text-xs text-gray-600 mb-2.5 tracking-wider">
                              {match.time}
                            </p>
                            {match.score ? (
                              <p className="text-2xl md:text-3xl font-bold text-[#013064]">
                                {match.score}
                              </p>
                            ) : (
                              <p className="text-base md:text-lg font-medium text-gray-400">
                                - vs -
                              </p>
                            )}
                          </div>

                          {/* Team 2 */}
                          <div className="flex flex-col items-center justify-center flex-1">
                            <img
                              src={match.team2.logo}
                              alt={match.team2.name}
                              className="w-24 h-24 md:w-32 md:h-32 lg:w-36 lg:h-36 object-contain mb-2"
                              onError={(e) => {
                                e.target.src = '/images/default-team-logo.png';
                              }}
                            />
                            <p className="text-xs md:text-sm font-bold text-[#013064] text-center px-2">
                              {match.team2.name}
                            </p>
                            {match.team2.category && (
                              <p className="text-[10px] md:text-xs text-gray-600 text-center mt-1">
                                {match.team2.category.name}
                              </p>
                            )}
                          </div>
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>

                {/* Pagination */}
                {matches.links && matches.links.length > 3 && (
                  <div className="flex justify-center items-center gap-2 mt-8">
                    {matches.links.map((link, index) => {
                      let label = link.label;
                      if (label.includes('&laquo;')) label = '‹';
                      if (label.includes('&raquo;')) label = '›';

                      return (
                        <button
                          key={index}
                          onClick={() => {
                            if (link.url) {
                              router.get(link.url, {}, {
                                preserveState: true,
                                preserveScroll: false,
                              });
                            }
                          }}
                          disabled={!link.url}
                          className={`min-w-[40px] h-10 px-3 flex items-center justify-center rounded transition ${link.active
                              ? 'bg-[#ffd22f] text-[#013064] font-bold'
                              : link.url
                                ? 'bg-white/20 hover:bg-white/30 text-white'
                                : 'bg-white/10 text-white/50 cursor-not-allowed'
                            }`}
                        >
                          {label}
                        </button>
                      );
                    })}
                  </div>
                )}
              </>
            ) : (
              <div className="text-center py-12">
                <p className="text-white text-xl">
                  {selectedDate ? 'Tidak ada pertandingan di tanggal ini' : 'Pilih tanggal untuk melihat pertandingan'}
                </p>
                <p className="text-white/70 text-sm mt-2">
                  {selectedDate ? 'Coba pilih tanggal lain atau ubah filter' : 'Klik salah satu tanggal di atas'}
                </p>
              </div>
            )}
          </div>
      
        </div>

        <Footer />
        <a
          href="https://wa.me/6281222977985"
          target="_blank"
          rel="noopener noreferrer"
          className="fixed bottom-6 right-6 z-50 group"
          aria-label="Chat WhatsApp"
        >
          <div className="absolute inset-0 bg-[#25D366] rounded-full animate-pulse-ring"></div>
          <div className="relative bg-[#25D366] hover:bg-[#20BA5A] w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center shadow-2xl transition-all duration-300 hover:scale-110 animate-float">
            <img
              src="/images/whatsapp-symbol-logo-svgrepo-com.svg"
              alt="WhatsApp"
              className="w-8 h-8 md:w-9 md:h-9"
            />
          </div>
          <div className="absolute right-full mr-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
            <div className="bg-gray-900 text-white px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap shadow-xl">
              Chat dengan Kami
              <div className="absolute right-0 top-1/2 -translate-y-1/2 translate-x-full">
                <div className="border-8 border-transparent border-l-gray-900"></div>
              </div>
            </div>
          </div>
        </a>
      </div>
    </>
  );
}