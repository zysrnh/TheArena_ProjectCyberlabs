import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { ChevronDown, ChevronLeft, ChevronRight, Calendar } from "lucide-react";
import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";
import Contact from '../../Components/Contact';

export default function MatchPage({ auth, filters, dates, matches, today, weekInfo }) {
  const [selectedYear, setSelectedYear] = useState(filters.year);
  const [selectedSeries, setSelectedSeries] = useState(filters.series);
  const [selectedRegion, setSelectedRegion] = useState(filters.region);
  const [selectedDate, setSelectedDate] = useState(filters.selectedDate);
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [weekOffset, setWeekOffset] = useState(filters.week || 0);
  const [selectedMonth, setSelectedMonth] = useState(filters.month || '');

  // Handle filter changes
  const handleFilterChange = (filterName, value) => {
    const params = {
      series: filterName === 'series' ? value : selectedSeries,
      region: filterName === 'region' ? value : selectedRegion,
      date: filterName === 'date' ? value : selectedDate,
      search: searchQuery,
      week: filterName === 'week' ? value : weekOffset,
      month: filterName === 'month' ? value : selectedMonth,
    };

    // Handle year filter
    const yearValue = filterName === 'year' ? value : selectedYear;
    if (yearValue && yearValue !== '') {
      params.year = yearValue;
    }

    router.get('/jadwal-hasil', params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  // Handle week navigation
  const handleWeekChange = (offset) => {
    setWeekOffset(offset);
    setSelectedDate(null);
    handleFilterChange('week', offset);
  };

  // ✅ Handle month selection from date picker
  const handleMonthChange = (e) => {
    const month = e.target.value;
    setSelectedMonth(month);
    setWeekOffset(0); // Reset week offset
    setSelectedDate(null); // Reset selected date
    handleFilterChange('month', month);
  };

  // ✅ Handle specific date selection from date picker
  const handleDatePickerChange = (e) => {
    const date = e.target.value; // Format: YYYY-MM-DD
    setSelectedDate(date);
    setSelectedMonth(''); // Clear month when specific date is selected
    setWeekOffset(0);
    handleFilterChange('date', date);
  };

  // ✅ Reset to current month
  const resetToCurrentMonth = () => {
    setSelectedMonth('');
    setWeekOffset(0);
    setSelectedDate(null);
    handleFilterChange('month', '');
  };

  // Handle search
  const handleSearch = (e) => {
    e.preventDefault();

    const params = {
      series: selectedSeries,
      region: selectedRegion,
      date: selectedDate,
      search: searchQuery,
      week: weekOffset,
      month: selectedMonth,
    };

    if (selectedYear && selectedYear !== '') {
      params.year = selectedYear;
    }

    router.get('/jadwal-hasil', params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="THE ARENA - Jadwal & Hasil" />
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
        /* Custom date input styling */
        input[type="month"]::-webkit-calendar-picker-indicator,
        input[type="date"]::-webkit-calendar-picker-indicator {
          filter: invert(1);
          cursor: pointer;
        }
        input[type="month"],
        input[type="date"] {
          color-scheme: dark;
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
                    onChange={(e) => {
                      setSelectedYear(e.target.value);
                      handleFilterChange('year', e.target.value);
                    }}
                    className="bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm md:text-base font-semibold cursor-pointer appearance-none pr-10 rounded"
                  >
                    <option value="">Semua Tahun</option>
                    <option value={new Date().getFullYear()}>{new Date().getFullYear()}</option>
                    <option value={new Date().getFullYear() + 1}>{new Date().getFullYear() + 1}</option>
                  </select>
                  <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#013064] pointer-events-none" />
                </div>

                {/* Series Dropdown */}
                <div className="relative">
                  <select
                    value={selectedSeries}
                    onChange={(e) => {
                      setSelectedSeries(e.target.value);
                      handleFilterChange('series', e.target.value);
                    }}
                    className="bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm md:text-base font-semibold cursor-pointer appearance-none pr-10 rounded"
                  >
                    <option value="Semua Series">Semua Series</option>
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
                    onChange={(e) => {
                      setSelectedRegion(e.target.value);
                      handleFilterChange('region', e.target.value);
                    }}
                    className="bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm md:text-base font-semibold cursor-pointer appearance-none pr-10 rounded"
                  >
                    <option value="Semua Regional">Semua Regional</option>
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

        {/* ✅ Date Picker + Week Navigation */}
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

              {/* Reset Button - Show when specific date is selected */}
              {selectedDate && (
                <button
                  onClick={resetToCurrentMonth}
                  className="w-full sm:w-auto px-6 py-2.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded transition text-sm whitespace-nowrap"
                >
                  Reset
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
                    onClick={() => {
                      setSelectedDate(date.full_date);
                      handleFilterChange('date', date.full_date);
                    }}
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
                          {/* Team 1 - Logo + Category */}
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

                          {/* Match Info - Center */}
                          <div className="flex flex-col items-center justify-center min-w-[130px] md:min-w-[150px]">
                            {/* Status Badge */}
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

                            <p className="text-[11px] text-gray-600 mb-1.5 text-center italic">
                              {match.league}
                            </p>
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

                          {/* Team 2 - Logo + Category */}
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
                <p className="text-white text-xl">Pilih tanggal untuk melihat pertandingan</p>
                <p className="text-white/70 text-sm mt-2">Klik salah satu tanggal di atas</p>
              </div>
            )}
          </div>
        </div>

        <Contact />
        <Footer />
      </div>
    </>
  );
}