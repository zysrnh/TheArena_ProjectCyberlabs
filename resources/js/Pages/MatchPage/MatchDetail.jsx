import { Head, Link } from "@inertiajs/react";
import { ChevronLeft } from "lucide-react";
import { useState } from "react";
import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";
import Contact from "../../Components/Contact";

export default function MatchDetail({ auth, match, matchHighlights = [], relatedNews = [] }) {
  const [activeTab, setActiveTab] = useState('hasil');
  const [selectedTeam, setSelectedTeam] = useState('team1');

  // Safety check untuk quarters
  const quarters = match.quarters || {
    team1: [0, 0, 0, 0],
    team2: [0, 0, 0, 0]
  };

  // Safety check untuk stats
  const stats = match.stats || [];

  // Safety check untuk box scores dan sort by jersey_no
  const boxScoreTeam1 = (match.boxScoreTeam1 || []).sort((a, b) => (a.no || 0) - (b.no || 0));
  const boxScoreTeam2 = (match.boxScoreTeam2 || []).sort((a, b) => (a.no || 0) - (b.no || 0));

  // Handle click untuk redirect ke YouTube dengan timestamp
  const handleHighlightClick = (videoUrl, duration) => {
    if (videoUrl) {
      let finalUrl = videoUrl;
      
      // Parse duration format "MM:SS" ke detik
      if (duration) {
        const timeParts = duration.split(':');
        let seconds = 0;
        
        if (timeParts.length === 2) {
          // Format MM:SS
          seconds = parseInt(timeParts[0]) * 60 + parseInt(timeParts[1]);
        } else if (timeParts.length === 3) {
          // Format HH:MM:SS
          seconds = parseInt(timeParts[0]) * 3600 + parseInt(timeParts[1]) * 60 + parseInt(timeParts[2]);
        }
        
        // Tambahkan timestamp ke URL YouTube
        if (seconds > 0) {
          const separator = finalUrl.includes('?') ? '&' : '?';
          finalUrl = `${finalUrl}${separator}t=${seconds}s`;
        }
      }
      
      window.open(finalUrl, '_blank', 'noopener,noreferrer');
    }
  };

  return (
    <>
      <Head title={`THE ARENA - ${match.team1.name} vs ${match.team2.name}`} />
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
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
        {/* Navigation */}
        <Navigation activePage="jadwal-hasil" />

        {/* Match Hero Section */}
        <main className="flex-1">
          <div className="bg-[#013064] pt-12 pb-8 px-4">
            <div className="max-w-7xl mx-auto">
              <Link href="/jadwal-hasil">
                <button className="mb-6 flex items-center gap-2 text-white hover:text-[#ffd22f] transition">
                  <div className="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                    <ChevronLeft className="w-5 h-5 text-[#013064]" />
                  </div>
                </button>
              </Link>

              {/* Match Score Display */}
              <div className="bg-[#002855] p-8 mb-8">
                <div className="text-center mb-6">
                 <p className="text-gray-300 text-sm mb-2">{match.league}</p>
{(match.team1.category || match.team2.category) && (
    <p className="text-[#ffd22f] text-xs font-semibold mb-2">
        {match.team1.category?.name || match.team2.category?.name}
    </p>
)}
                  <p className="text-white text-lg font-semibold mb-1">
                    {match.date}
                  </p>
                  <p className="text-gray-400 text-sm">
                    {match.time} {match.venue && `| ${match.venue}`}
                  </p>
                </div>

                <div className="flex items-center justify-between gap-8">
                 {/* Team 1 */}
                  <div className="flex flex-col items-center flex-1">
                    <img
                      src={match.team1.logo}
                      alt={match.team1.name}
                      className="w-32 h-32 md:w-40 md:h-40 object-contain mb-4"
                    />
                    <h3 className="text-white text-xl md:text-2xl font-bold text-center">
    {match.team1.name}{match.team1.division ? ` ${match.team1.division}` : ''}
</h3>
{match.team1.category && (
    <p className="text-gray-300 text-sm mt-2 text-center">
        {match.team1.category.name}
    </p>
)}
                  </div>

                  {/* Score */}
                  <div className="flex flex-col items-center">
                    {match.score ? (
                      <p className="text-white text-5xl md:text-6xl font-bold">
                        {match.score}
                      </p>
                    ) : (
                      <p className="text-white text-2xl font-semibold">
                        VS
                      </p>
                    )}
                  </div>

             {/* Team 1 */}
                  <div className="flex flex-col items-center flex-1">
                    <img
                      src={match.team2.logo}
                      alt={match.team2.name}
                      className="w-32 h-32 md:w-40 md:h-40 object-contain mb-4"
                    />
                    <h3 className="text-white text-xl md:text-2xl font-bold text-center">
    {match.team2.name}{match.team2.division ? ` ${match.team2.division}` : ''}
</h3>
{match.team2.category && (
    <p className="text-gray-300 text-sm mt-2 text-center">
        {match.team2.category.name}
    </p>
)}
                  </div>
                </div>
              </div>

              {/* Quarter Scores Table - Only show if match has finished or is live */}
              {match.score && quarters.team1 && quarters.team2 && (
                <div className="bg-white overflow-hidden mb-8">
                  <table className="w-full">
                    <thead>
                      <tr className="bg-[#ffd22f]">
                        <th className="px-4 py-3 text-left text-[#013064] font-bold">Team</th>
                        <th className="px-4 py-3 text-center text-[#013064] font-bold">1st</th>
                        <th className="px-4 py-3 text-center text-[#013064] font-bold">2nd</th>
                        <th className="px-4 py-3 text-center text-[#013064] font-bold">3rd</th>
                        <th className="px-4 py-3 text-center text-[#013064] font-bold">4th</th>
                        <th className="px-4 py-3 text-center text-[#013064] font-bold">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr className="bg-white border-b border-gray-200">
                        <td className="px-4 py-4 font-bold text-gray-900">{match.team1.name}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team1[0]}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team1[1]}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team1[2]}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team1[3]}</td>
                        <td className="px-4 py-4 text-center font-bold text-gray-900">
                          {quarters.team1.reduce((a, b) => a + b, 0)}
                        </td>
                      </tr>
                      <tr className="bg-gray-100">
                        <td className="px-4 py-4 font-bold text-gray-900">{match.team2.name}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team2[0]}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team2[1]}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team2[2]}</td>
                        <td className="px-4 py-4 text-center text-gray-900">{quarters.team2[3]}</td>
                        <td className="px-4 py-4 text-center font-bold text-gray-900">
                          {quarters.team2.reduce((a, b) => a + b, 0)}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              )}

              {/* Stats Tabs */}
              <div className="mb-8">
                {/* Tab Headers */}
                <div className="grid grid-cols-3 bg-white overflow-hidden mb-0">
                  <button
                    onClick={() => setActiveTab('hasil')}
                    className={`px-6 py-4 text-center font-bold transition border-r border-gray-300 ${activeTab === 'hasil'
                      ? 'bg-[#ffd22f] text-[#013064]'
                      : 'bg-white text-[#013064] hover:bg-gray-100'
                      }`}
                  >
                    HASIL
                  </button>
                  <button
                    onClick={() => setActiveTab('boxscore')}
                    className={`px-6 py-4 text-center font-bold transition border-r border-gray-300 ${activeTab === 'boxscore'
                      ? 'bg-[#ffd22f] text-[#013064]'
                      : 'bg-white text-[#013064] hover:bg-gray-100'
                      }`}
                  >
                    BOX SCORE
                  </button>
                  <button
                    onClick={() => setActiveTab('berita')}
                    className={`px-6 py-4 text-center font-bold transition ${activeTab === 'berita'
                      ? 'bg-[#ffd22f] text-[#013064]'
                      : 'bg-white text-[#013064] hover:bg-gray-100'
                      }`}
                  >
                    BERITA
                  </button>
                </div>

                {/* Tab Content: HASIL */}
                {activeTab === 'hasil' && (
                  <div className="bg-white overflow-hidden">
                    {stats && stats.length > 0 ? (
                      <>
                        {/* Team Names Header */}
                        <div className="grid grid-cols-2 bg-[#013064] text-white border-b-2 border-[#ffd22f]">
                          <div className="px-4 py-3 text-center font-bold border-r-2 border-[#ffd22f]">
                            {match.team1.name}
                          </div>
                          <div className="px-4 py-3 text-center font-bold">
                            {match.team2.name}
                          </div>
                        </div>

                        {/* Stats Rows */}
                        {stats.map((stat, idx) => (
                          <div key={idx} className={`grid grid-cols-3 border-b border-gray-300 ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-100'}`}>
                            <div className="px-4 py-3 text-center text-gray-900">{stat.team1}</div>
                            <div className="px-4 py-3 text-center font-bold text-gray-900">{stat.category}</div>
                            <div className="px-4 py-3 text-center text-gray-900">{stat.team2}</div>
                          </div>
                        ))}
                      </>
                    ) : (
                      <div className="p-12 text-center">
                        <div className="mb-4">
                          <svg className="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                          </svg>
                        </div>
                        <p className="text-gray-600 text-lg font-semibold mb-2">Statistik Belum Tersedia</p>
                        <p className="text-gray-500 text-sm">Statistik pertandingan akan ditampilkan setelah pertandingan dimulai</p>
                      </div>
                    )}
                  </div>
                )}

                {/* Tab Content: BOX SCORE */}
                {activeTab === 'boxscore' && (
                  <div className="bg-white overflow-hidden">
                    {(boxScoreTeam1.length > 0 || boxScoreTeam2.length > 0) ? (
                      <>
                        {/* Team Selector */}
                        <div className="grid grid-cols-2 bg-[#013064] text-white border-b-2 border-[#ffd22f]">
                          <button
                            onClick={() => setSelectedTeam('team1')}
                            className={`px-6 py-4 text-center font-bold transition border-r-2 border-[#ffd22f] ${selectedTeam === 'team1'
                              ? 'bg-[#013064]'
                              : 'bg-[#002855] hover:bg-[#013064]'
                              }`}
                          >
                            {match.team1.name}
                          </button>
                          <button
                            onClick={() => setSelectedTeam('team2')}
                            className={`px-6 py-4 text-center font-bold transition ${selectedTeam === 'team2'
                              ? 'bg-[#013064]'
                              : 'bg-[#002855] hover:bg-[#013064]'
                              }`}
                          >
                            {match.team2.name}
                          </button>
                        </div>

                        {/* Box Score Table */}
                        <div className="overflow-x-auto">
                          <table className="w-full">
                            <thead>
                              <tr className="bg-gray-200 border-b border-gray-300">
                                <th className="px-4 py-3 text-left text-gray-900 font-bold text-sm">No</th>
                                <th className="px-4 py-3 text-left text-gray-900 font-bold text-sm">Jersey</th>
                                <th className="px-4 py-3 text-left text-gray-900 font-bold text-sm">Nama</th>
                                <th className="px-4 py-3 text-center text-gray-900 font-bold text-sm">Pos</th>
                                <th className="px-4 py-3 text-center text-gray-900 font-bold text-sm">Min</th>
                                <th className="px-4 py-3 text-center text-gray-900 font-bold text-sm">Pts</th>
                                <th className="px-4 py-3 text-center text-gray-900 font-bold text-sm">Ast</th>
                                <th className="px-4 py-3 text-center text-gray-900 font-bold text-sm">Reb</th>
                              </tr>
                            </thead>
                            <tbody>
                              {(selectedTeam === 'team1' ? boxScoreTeam1 : boxScoreTeam2).map((player, idx) => (
                                <tr key={player.id} className={`border-b border-gray-300 ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-100'}`}>
                                  <td className="px-4 py-4 text-gray-900 font-semibold">{idx + 1}</td>
                                  <td className="px-4 py-4 text-gray-900 font-semibold">{player.no || '-'}</td>
                                  <td className="px-4 py-4">
                                    <div className="flex items-center gap-3">
                                      {player.photo && (
                                        <img
                                          src={player.photo}
                                          alt={player.name}
                                          className="w-10 h-10 rounded-full object-cover"
                                        />
                                      )}
                                      <div className="flex items-center gap-2">
                                        <span className="text-gray-900 font-medium text-base">{player.name}</span>
                                        {player.isMVP && (
                                          <div className="bg-[#ffd22f] px-2 py-1 flex items-center gap-1 border-2 border-[#013064]">
                                            <span className="text-[#013064] text-xs font-extrabold">MVP</span>
                                            <img
                                              src="/images/mvp.png"
                                              alt="MVP"
                                              className="h-4 w-auto object-contain"
                                            />
                                          </div>
                                        )}
                                      </div>
                                    </div>
                                  </td>
                                  <td className="px-4 py-4 text-center text-gray-900">{player.position || '-'}</td>
                                  <td className="px-4 py-4 text-center text-gray-900">{player.minutes}</td>
                                  <td className="px-4 py-4 text-center text-gray-900 font-bold">{player.points}</td>
                                  <td className="px-4 py-4 text-center text-gray-900">{player.assists}</td>
                                  <td className="px-4 py-4 text-center text-gray-900">{player.rebounds}</td>
                                </tr>
                              ))}
                              <tr className="bg-gray-200 border-t-2 border-gray-400">
                                <td colSpan="5" className="px-4 py-4 font-bold text-gray-900">Total</td>
                                <td className="px-4 py-4 text-center font-bold text-gray-900">
                                  {(selectedTeam === 'team1' ? boxScoreTeam1 : boxScoreTeam2)
                                    .reduce((sum, p) => sum + parseInt(p.points || 0), 0)}
                                </td>
                                <td className="px-4 py-4 text-center font-bold text-gray-900">
                                  {(selectedTeam === 'team1' ? boxScoreTeam1 : boxScoreTeam2)
                                    .reduce((sum, p) => sum + parseInt(p.assists || 0), 0)}
                                </td>
                                <td className="px-4 py-4 text-center font-bold text-gray-900">
                                  {(selectedTeam === 'team1' ? boxScoreTeam1 : boxScoreTeam2)
                                    .reduce((sum, p) => sum + parseInt(p.rebounds || 0), 0)}
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </>
                    ) : (
                      <div className="p-12 text-center">
                        <div className="mb-4">
                          <svg className="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                        </div>
                        <p className="text-gray-600 text-lg font-semibold mb-2">Box Score Belum Tersedia</p>
                        <p className="text-gray-500 text-sm">Box score akan ditampilkan setelah pertandingan selesai</p>
                      </div>
                    )}
                  </div>
                )}

                {/* Tab Content: BERITA */}
                {activeTab === 'berita' && (
                  <div className="bg-white p-6">
                    {relatedNews && relatedNews.length > 0 ? (
                      <div className="grid md:grid-cols-3 gap-6">
                        {relatedNews.map((news) => (
                          <Link
                            key={news.id}
                            href={`/berita/${news.id}`}
                            className="group block"
                          >
                            <div className="relative overflow-hidden h-[380px] shadow-lg hover:shadow-2xl transition-all duration-300">
                              <img
                                src={news.image}
                                alt={news.title}
                                className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                onError={(e) => {
                                  e.target.src = 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800';
                                }}
                              />
                              <div className="absolute inset-0 bg-gradient-to-t from-black via-black/70 to-transparent" />
                              <span className="absolute top-4 left-4 bg-[#e74c3c] text-white px-3 py-1.5 text-xs font-bold uppercase z-10 shadow-lg">
                                News
                              </span>
                              <div className="absolute bottom-0 left-0 right-0 p-6 text-white z-10">
                                <p className="text-gray-200 text-xs mb-2 flex items-center gap-2">
                                  <span className="font-semibold">{news.category}</span>
                                  <span>•</span>
                                  <span>{news.date}</span>
                                </p>
                                <h3 className="text-white font-bold text-lg mb-2 leading-tight line-clamp-2 group-hover:text-[#ffd22f] transition-colors">
                                  {news.title}
                                </h3>
                                <p className="text-gray-300 text-sm mb-4 leading-relaxed line-clamp-2">
                                  {news.excerpt}
                                </p>
                                <div className="flex items-center gap-2 text-white text-sm font-semibold group-hover:text-[#ffd22f] transition-colors">
                                  <span>Lihat selengkapnya</span>
                                  <svg
                                    className="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                  >
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                  </svg>
                                </div>
                              </div>
                            </div>
                          </Link>
                        ))}
                      </div>
                    ) : (
                      <div className="col-span-full text-center py-16">
                        <div className="mb-6">
                          <svg className="mx-auto h-20 w-20 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                          </svg>
                        </div>
                        <p className="text-gray-600 text-xl font-semibold mb-2">Belum Ada Berita</p>
                        <p className="text-gray-500 text-sm">Berita terkait pertandingan ini akan segera ditampilkan</p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>

         {/* ✅ HIGHLIGHT SECTION - Dengan Timestamp YouTube */}
{matchHighlights && matchHighlights.length > 0 && (
  <div className="bg-[#013064] py-12 px-4">
    <div className="max-w-7xl mx-auto">
      <h2 className="text-white text-3xl md:text-4xl font-bold mb-8 text-center">
        Highlight Pertandingan
      </h2>
      <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8">
        {matchHighlights.map((highlight) => (
          <div 
            key={highlight.id} 
            onClick={() => handleHighlightClick(highlight.video_url, highlight.duration)}
            className="group overflow-hidden relative h-[220px] md:h-[240px] lg:h-[260px] rounded-lg cursor-pointer hover:shadow-2xl hover:scale-[1.02] transition-all duration-300"
          >
            {/* Thumbnail Image */}
            <img
              src={highlight.thumbnail || 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800'}
              alt={highlight.title}
              className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              onError={(e) => {
                e.target.src = 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800';
              }}
            />

            {/* Dark Gradient Overlay */}
            <div className="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent" />

            {/* Badge "Highlight" di kiri atas */}
            <span className="absolute top-3 left-3 bg-[#013064] text-white px-2.5 py-1 text-xs font-semibold z-10 uppercase rounded">
              Highlight
            </span>

            {/* Duration badge di kanan atas */}
            {highlight.duration && (
              <span className="absolute top-3 right-3 bg-black/80 text-white px-2 py-1 text-xs font-semibold z-10 rounded">
                {highlight.duration}
              </span>
            )}

            {/* Match Info */}
            <div className="absolute bottom-0 left-0 right-0 p-4 md:p-5 text-white z-10">
              {/* Category */}
              <p className="text-[#ffd22f] text-xs font-semibold mb-2">
                {highlight.category}
              </p>
              
              {/* Title */}
              <h3 className="text-white text-sm md:text-base font-bold mb-2 leading-tight line-clamp-2 group-hover:text-[#ffd22f] transition-colors">
                {highlight.title}
              </h3>
              
              {/* Venue & Time */}
              <div className="flex justify-between items-center text-xs mb-2">
                <span className="text-gray-300">{highlight.venue}</span>
                <span className="text-white font-bold">{highlight.time}</span>
              </div>
              
              {/* Quarter Info */}
              {highlight.quarter && (
                <p className="text-gray-400 text-xs">Quarter {highlight.quarter}</p>
              )}
            </div>

            {/* Hover Overlay */}
            <div className="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
          </div>
        ))}
      </div>
    </div>
  </div>
)}
        </main>

        {/* Contact Section */}
        <Contact />

        {/* Footer */}
        <Footer />
        <a
  href="https://wa.me/6281222977985"
  target="_blank"
  rel="noopener noreferrer"
  className="fixed bottom-6 right-6 z-50 group"
  aria-label="Chat WhatsApp"
>
  {/* Pulse Ring Effect */}
  <div className="absolute inset-0 bg-[#25D366] rounded-full animate-pulse-ring"></div>
  
  {/* Main Button */}
  <div className="relative bg-[#25D366] hover:bg-[#20BA5A] w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center shadow-2xl transition-all duration-300 hover:scale-110 animate-float">
    <img
      src="/images/whatsapp-symbol-logo-svgrepo-com.svg"
      alt="WhatsApp"
      className="w-8 h-8 md:w-9 md:h-9"
    />
  </div>
  
  {/* Tooltip */}
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