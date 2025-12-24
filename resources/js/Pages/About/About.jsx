import { Head, Link, usePage, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { Phone, Mail } from "lucide-react";
import Footer from "../../Components/Footer";
import Navigation from "../../Components/Navigation";

export default function About() {
  const { auth, aboutData, facilities } = usePage().props;
  const [isScrolled, setIsScrolled] = useState(false);
  const [lastScrollY, setLastScrollY] = useState(0);
  const [showContactBar, setShowContactBar] = useState(false);

  // Debug facilities data
  useEffect(() => {
    console.log('Facilities data:', facilities);
    console.log('Facilities length:', facilities?.length);
    console.log('Is Array?', Array.isArray(facilities));
  }, [facilities]);

  useEffect(() => {
    const handleScroll = () => {
      const currentScrollY = window.scrollY;
      
      setIsScrolled(currentScrollY > 50);
      
      if (currentScrollY > lastScrollY && currentScrollY > 50) {
        setShowContactBar(true);
      } else if (currentScrollY < lastScrollY || currentScrollY <= 50) {
        setShowContactBar(false);
      }
      
      setLastScrollY(currentScrollY);
    };
    
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, [lastScrollY]);

  const handleLogout = () => {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
      router.post('/logout');
    }
  };

  // Helper function untuk gambar About Content
  const getImageUrl = (url) => {
    if (!url) return 'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=1200';
    if (url.startsWith('http')) return url;
    return `/storage/${url}`;
  };

  // Helper function untuk gambar Fasilitas
  const getFacilityImageUrl = (url) => {
    if (!url) {
      return 'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=800';
    }
    if (url.startsWith('http')) return url;
    // Cek apakah file exists di storage
    return `/storage/${url}`;
  };

  // Default images untuk setiap jenis fasilitas
  const getDefaultFacilityImage = (facilityName) => {
    const name = facilityName?.toLowerCase() || '';
    if (name.includes('cafe') || name.includes('resto')) {
      return 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=800';
    } else if (name.includes('makanan')) {
      return 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=800';
    } else if (name.includes('minuman')) {
      return 'https://images.unsplash.com/photo-1534353436294-0dbd4bdac845?w=800';
    } else if (name.includes('ganti')) {
      return 'https://images.unsplash.com/photo-1534349762230-e0cadf78f5da?w=800';
    } else if (name.includes('parkir')) {
      return 'https://images.unsplash.com/photo-1590674899484-d5640e854abe?w=800';
    } else if (name.includes('wifi')) {
      return 'https://images.unsplash.com/photo-1551808525-51a94da548ce?w=800';
    } else if (name.includes('tribun')) {
      return 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800';
    }
    return 'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=800';
  };

  // Helper function untuk render HTML dari RichEditor
  const renderHTML = (htmlContent) => {
    if (!htmlContent) return null;
    return (
      <div 
        className="prose prose-invert max-w-none prose-headings:text-white prose-p:text-gray-200 prose-strong:text-white prose-ul:text-gray-200 prose-ol:text-gray-200"
        dangerouslySetInnerHTML={{ __html: htmlContent }}
      />
    );
  };

  return (
    <>
      <Head title="THE ARENA - About" />
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
        
        .prose-invert h2, .prose-invert h3 {
          color: #fff;
          font-weight: 700;
        }
        .prose-invert p {
          color: #e5e7eb;
          word-wrap: break-word;
          overflow-wrap: break-word;
          hyphens: auto;
        }
        .prose-invert strong {
          color: #fff;
          font-weight: 600;
        }
        .prose-invert ul, .prose-invert ol {
          color: #e5e7eb;
        }
        .prose-invert a {
          color: #ffd22f;
          word-break: break-word;
        }
        .prose-invert a:hover {
          color: #ffc107;
        }
        
        /* Mobile text optimization */
        @media (max-width: 640px) {
          .prose-invert p {
            font-size: 14px;
            line-height: 1.6;
          }
          .prose-invert h2 {
            font-size: 20px;
          }
          .prose-invert h3 {
            font-size: 18px;
          }
        }
      `}</style>
      
      <div className="min-h-screen flex flex-col bg-white">
        {/* Navigation */}
        <Navigation activePage="tentang" />

        {/* Hero Title Section - DYNAMIC */}
        <div className="bg-[#013064] py-12 md:py-16 lg:py-20 px-4 md:px-8 lg:px-16">
          <div className="max-w-7xl mx-auto">
            <p className="text-[#ffd22f] text-lg md:text-xl lg:text-2xl font-semibold mb-3 md:mb-4">
              {aboutData?.hero?.subtitle || 'Tentang'}
            </p>
            <h1 className="text-white text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold leading-tight">
              {aboutData?.hero?.title || 'The Arena History'}
            </h1>
          </div>
        </div>

        {/* Main Content Section - The Arena (DYNAMIC) */}
        <div className="flex-1">
          <div className="grid md:grid-cols-2">
            {/* Left Section - Image */}
            <div className="relative h-[350px] md:h-[400px] lg:h-[450px]">
              <img
                src={getImageUrl(aboutData?.arena?.image_url)}
                alt={aboutData?.arena?.title || "The Arena Basketball Court"}
                className="w-full h-full object-cover"
              />
            </div>

            {/* Right Section - Content */}
            <div className="bg-[#003f84] text-white p-6 md:p-10 lg:p-14 flex flex-col justify-center">
              <h2 className="text-white text-2xl md:text-3xl lg:text-4xl font-bold mb-4 md:mb-6 leading-tight">
                {aboutData?.arena?.title || 'The Arena'}
              </h2>
              
              <div className="space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm lg:text-base leading-relaxed">
                {aboutData?.arena?.description_1 && renderHTML(aboutData.arena.description_1)}
                {aboutData?.arena?.description_2 && renderHTML(aboutData.arena.description_2)}
                {aboutData?.arena?.description_3 && renderHTML(aboutData.arena.description_3)}
              </div>
            </div>
          </div>
        </div>

        {/* Komunitas Section (DYNAMIC) */}
        <div className="grid md:grid-cols-2">
          {/* Left Section - Content */}
          <div className="bg-[#003f84] text-white p-6 md:p-10 lg:p-14 flex flex-col justify-center order-2 md:order-1">
            <h2 className="text-white text-2xl md:text-3xl lg:text-4xl font-bold mb-4 md:mb-6 leading-tight">
              {aboutData?.komunitas?.title || 'Komunitas'}
            </h2>
            
            <div className="space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm lg:text-base leading-relaxed">
              {aboutData?.komunitas?.description_1 && renderHTML(aboutData.komunitas.description_1)}
              {aboutData?.komunitas?.description_2 && renderHTML(aboutData.komunitas.description_2)}
              {aboutData?.komunitas?.description_3 && renderHTML(aboutData.komunitas.description_3)}
            </div>
          </div>

          {/* Right Section - Image */}
          <div className="relative h-[350px] md:h-[400px] lg:h-[450px] order-1 md:order-2">
            <img
              src={getImageUrl(aboutData?.komunitas?.image_url)}
              alt={aboutData?.komunitas?.title || "Basketball Community"}
              className="w-full h-full object-cover"
            />
          </div>
        </div>

        {/* Lapangan Kami Section - STATIC */}
        <div className="grid md:grid-cols-2">
          {/* Left Section - Image */}
          <div className="relative h-[350px] md:h-[400px] lg:h-[450px]">
            <img
              src="https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1200"
              alt="Lapangan Basket The Arena"
              className="w-full h-full object-cover"
            />
          </div>

          {/* Right Section - Content */}
          <div className="bg-[#003f84] text-white p-6 md:p-10 lg:p-14 flex flex-col justify-center">
            <h2 className="text-white text-2xl md:text-3xl lg:text-4xl font-bold mb-4 md:mb-6 leading-tight">
              Lapangan Kami
            </h2>
            
            <div className="space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm lg:text-base leading-relaxed">
              <p>
                The Arena Basketball memiliki 4 lapangan basket berstandar internasional FIBA dengan jam operasional setiap hari:
              </p>
              <p className="text-lg md:text-xl font-semibold text-[#ffd22f]">
                06.00 – 22.00 WIB
              </p>
            </div>
          </div>
        </div>

        {/* The Arena PVJ Section - STATIC */}
        <div className="grid md:grid-cols-2">
          {/* Left Section - Content */}
          <div className="bg-[#003f84] text-white p-6 md:p-10 lg:p-14 flex flex-col justify-center order-2 md:order-1">
            <h2 className="text-white text-2xl md:text-3xl lg:text-4xl font-bold mb-4 md:mb-6 leading-tight">
              The Arena PVJ
            </h2>
            <p className="text-[#ffd22f] text-base md:text-lg font-semibold mb-3 md:mb-4">
              Basketball Courts & Healthy Lifestyle Space
            </p>
            
            <div className="space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm lg:text-base leading-relaxed">
              <p>
                The Arena PVJ berlokasi di Paris Van Java Mall, Lantai P13, Bandung. Tersedia 1 lapangan basket indoor dengan material kayu jati berkualitas, memberikan pengalaman bermain yang optimal.
              </p>
              <p>
                Kami mengundang Anda untuk merasakan pengalaman berolahraga di fasilitas terbaik yang dapat disesuaikan dengan kebutuhan latihan maupun acara.
              </p>
            </div>
          </div>

          {/* Right Section - Image */}
          <div className="relative h-[350px] md:h-[400px] lg:h-[450px] order-1 md:order-2">
            <img
              src="https://images.unsplash.com/photo-1515523110800-9415d13b84a8?w=1200"
              alt="The Arena PVJ"
              className="w-full h-full object-cover"
            />
          </div>
        </div>

        {/* Fasilitas Section - Grid DYNAMIC dari Database */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          {Array.isArray(facilities) && facilities.length > 0 ? (
            facilities
              .filter(facility => facility.name.toLowerCase() !== 'tribun penonton')
              .map((facility) => (
                <div 
                  key={facility.id}
                  className="group cursor-pointer overflow-hidden relative h-[280px] md:h-[320px] lg:h-[350px]"
                >
                  <img
                    src={facility.image_url 
                      ? (facility.image_url.startsWith('http') 
                          ? facility.image_url 
                          : facility.image_url.startsWith('images/') 
                            ? `/${facility.image_url}` 
                            : `/storage/${facility.image_url}`)
                      : getDefaultFacilityImage(facility.name)
                    }
                    alt={facility.name}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    onError={(e) => {
                      e.target.src = getDefaultFacilityImage(facility.name);
                    }}
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />
                  <div className="absolute bottom-0 left-0 right-0 p-4 md:p-6 text-white">
                    <span className="text-[#ffd22f] text-sm md:text-base font-semibold mb-1 md:mb-2 block">
                      Fasilitas
                    </span>
                    <h3 className="text-xl md:text-2xl lg:text-3xl font-bold">
                      {facility.name}
                    </h3>
                  </div>
                </div>
              ))
          ) : (
            <div className="col-span-full bg-gray-50 py-16 px-4">
              <div className="text-center max-w-md mx-auto">
                <div className="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </div>
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Belum Ada Fasilitas</h3>
                <p className="text-sm text-gray-500">Data fasilitas akan ditampilkan di sini</p>
              </div>
            </div>
          )}
        </div>

        {/* Tribun Penonton Section - 3 Columns Grid (DYNAMIC) */}
        <div className="grid grid-cols-1 md:grid-cols-3">
          {(() => {
            // Cari data Tribun Penonton dari facilities atau aboutData
            const tribunData = facilities?.find(f => f.name.toLowerCase().includes('tribun')) || aboutData?.tribun;
            const hasData = tribunData !== undefined;

            return (
              <>
                {/* Left - Image Card (1 column) */}
                <div className="group cursor-pointer overflow-hidden relative h-[280px] md:h-[320px] lg:h-[350px]">
                  <img
                    src={
                      tribunData?.image_url
                        ? (tribunData.image_url.startsWith('http') 
                            ? tribunData.image_url 
                            : tribunData.image_url.startsWith('images/') 
                              ? `/${tribunData.image_url}` 
                              : `/storage/${tribunData.image_url}`)
                        : getDefaultFacilityImage('tribun penonton')
                    }
                    alt={tribunData?.title || tribunData?.name || "Tribun Penonton"}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    onError={(e) => {
                      e.target.src = getDefaultFacilityImage('tribun penonton');
                    }}
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />
                  <div className="absolute bottom-0 left-0 right-0 p-4 md:p-6 text-white">
                    <span className="text-[#ffd22f] text-sm md:text-base font-semibold mb-1 md:mb-2 block">
                      {tribunData?.subtitle || 'Fasilitas'}
                    </span>
                    <h3 className="text-xl md:text-2xl lg:text-3xl font-bold">
                      {tribunData?.title || tribunData?.name || 'Tribun Penonton'}
                    </h3>
                  </div>
                </div>

                {/* Right - Text Content (2 columns) */}
                <div className="md:col-span-2 bg-[#003f84] text-white p-4 md:p-6 lg:p-8 flex flex-col justify-center h-[280px] md:h-[320px] lg:h-[350px]">
                  <div className="space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm leading-relaxed">
                    {/* Hanya tampilkan jika ada data dari aboutData (AboutContent) */}
                    {aboutData?.tribun?.description_1 && renderHTML(aboutData.tribun.description_1)}
                    {aboutData?.tribun?.description_2 && renderHTML(aboutData.tribun.description_2)}
                    {aboutData?.tribun?.description_3 && renderHTML(aboutData.tribun.description_3)}
                  </div>
                </div>
              </>
            );
          })()}
        </div>

        {/* Full Width Description Section (DYNAMIC) */}
        <div className="bg-[#003f84] text-white py-8 md:py-12 lg:py-16 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-20">
          <div className="max-w-7xl mx-auto">
            <div className="space-y-3 sm:space-y-4 md:space-y-6 text-gray-200 text-sm sm:text-base md:text-lg leading-relaxed break-words">
              {aboutData?.full_description?.description_1 && renderHTML(aboutData.full_description.description_1)}
              {aboutData?.full_description?.description_2 && renderHTML(aboutData.full_description.description_2)}
              {aboutData?.full_description?.description_3 && renderHTML(aboutData.full_description.description_3)}
            </div>
          </div>
        </div>

        <Footer />
      </div>
    </>
  );
}