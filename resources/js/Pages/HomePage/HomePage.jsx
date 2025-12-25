import { Head, Link, usePage, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { ChevronRight, Phone, Mail, LogOut, X } from "lucide-react";
import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";
import Contact from '../../Components/Contact';
export default function HomePage() {
  // Destructure props dengan default values
  const {
    auth,
    liveMatches = [],
    homeMatches = [],
    currentFilter = 'all',
    newsForHome = [],
    sponsors = [],
    partners = [],
    reviews = [],
    facilities = []
  } = usePage().props;

  const [currentSlide, setCurrentSlide] = useState(0);
  const [isScrolled, setIsScrolled] = useState(false);
  const [lastScrollY, setLastScrollY] = useState(0);
  const [showContactBar, setShowContactBar] = useState(false);
  const [filter, setFilter] = useState(currentFilter || 'all');
  const [reviewsList, setReviewsList] = useState(reviews);
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [currentFacilityIndex, setCurrentFacilityIndex] = useState(0);
  const [reviewForm, setReviewForm] = useState({
    rating_facilities: 5,
    rating_hospitality: 5,
    rating_cleanliness: 5,
    comment: ''
  });
  const [isSubmittingReview, setIsSubmittingReview] = useState(false);
  const [notification, setNotification] = useState(null);
  const [currentReviewPage, setCurrentReviewPage] = useState(0);

  // ✅ USEEFFECT AUTO-SLIDE REVIEW CAROUSEL
  useEffect(() => {
    if (reviewsList.length < 3) return;

    const interval = setInterval(() => {
      setCurrentReviewPage((prev) => {
        const maxPage = Math.ceil(reviewsList.length / 3) - 1;
        return prev >= maxPage ? 0 : prev + 1;
      });
    }, 3000);

    return () => clearInterval(interval);
  }, [reviewsList.length]);

  // ✅ USEEFFECT AUTO-ROTATE FACILITIES (TERPISAH!)
  useEffect(() => {
    if (facilities.length < 3) return;

    const interval = setInterval(() => {
      setCurrentFacilityIndex((prev) => {
        const totalSets = Math.ceil(facilities.length / 3);
        return (prev + 1) % totalSets;
      });
    }, 5000);

    return () => clearInterval(interval);
  }, [facilities.length]);

  // Get reviews untuk halaman saat ini
  const reviewsPerPage = 3;
  const startIndex = currentReviewPage * reviewsPerPage;
  const currentReviews = reviewsList.slice(startIndex, startIndex + reviewsPerPage);
  const totalReviewPages = Math.ceil(reviewsList.length / reviewsPerPage);

  // ✅ Get current facilities to display (3 items)
  const currentFacilities = facilities.length > 0
    ? facilities.slice(currentFacilityIndex * 3, currentFacilityIndex * 3 + 3)
    : [
      {
        id: 1,
        name: 'Makanan & Minuman',
        image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800',
        description: 'Nikmati berbagai pilihan makanan dan minuman'
      },
      {
        id: 2,
        name: 'Penitipan Barang',
        image: 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?w=800',
        description: 'Loker aman untuk barang berharga Anda'
      },
      {
        id: 3,
        name: 'Toilet dan Kamar Mandi',
        image: 'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?w=800',
        description: 'Fasilitas bersih dan terawat'
      }
    ];

  const handleFilterChange = (newFilter) => {
    setFilter(newFilter);
    router.get('/', { filter: newFilter }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

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

  // FUNGSI UNTUK REVIEW
  const fetchReviews = async () => {
    try {
      const response = await fetch('/api/reviews');
      const data = await response.json();
      if (data.success) {
        setReviewsList(data.reviews);
      }
    } catch (error) {
      console.error('Error fetching reviews:', error);
    }
  };

  const handleSubmitReview = async () => {
    if (!reviewForm.comment.trim() || reviewForm.comment.trim().length < 10) {
      setNotification({
        type: 'error',
        message: 'Komentar minimal 10 karakter'
      });
      setTimeout(() => setNotification(null), 3000);
      return;
    }

    setIsSubmittingReview(true);

    try {
      const response = await fetch('/api/reviews/store', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(reviewForm),
      });

      const data = await response.json();

      if (data.success) {
        setShowReviewModal(false);
        setReviewForm({
          rating_facilities: 5,
          rating_hospitality: 5,
          rating_cleanliness: 5,
          comment: ''
        });
        fetchReviews();
        setNotification({
          type: 'success',
          message: data.message
        });
        setTimeout(() => setNotification(null), 5000);
      } else {
        setNotification({
          type: 'error',
          message: data.message
        });
        setTimeout(() => setNotification(null), 5000);
      }
    } catch (error) {
      console.error('Review error:', error);
      setNotification({
        type: 'error',
        message: 'Terjadi kesalahan saat menambahkan ulasan'
      });
      setTimeout(() => setNotification(null), 5000);
    } finally {
      setIsSubmittingReview(false);
    }
  };

  const handleOpenReviewModal = () => {
    if (!auth?.client) {
      setNotification({
        type: 'error',
        message: 'Silakan login terlebih dahulu untuk memberikan ulasan'
      });
      setTimeout(() => {
        router.visit("/login");
      }, 1500);
      return;
    }
    setShowReviewModal(true);
  };

  const slides = [
    {
      title: "BOOKING LAPANGAN SEKARANG!",
      subtitle: "The Arena Basketball",
      description: (
        <>
          The Arena menghadirkan <strong>4 lapangan basket</strong> yang tersebar di Kota Bandung dengan pilihan <strong>indoor dan semi-indoor</strong>, menggunakan material <strong>berstandar FIBA</strong> (kayu & vinyl). Tidak hanya untuk bermain, The Arena juga menyediakan <strong>penyewaan perlengkapan basket</strong> serta jasa penyelenggaraan event untuk mendukung kebutuhan latihan, komunitas, hingga turnamen basket.
        </>
      ),
      image: "https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1200",
    },
    {
      title: "PENYEWAAN LAPANGAN BASKET",
      subtitle: "The Arena Basketball",
      description: (
        <>
          Lapangan basket The Arena dapat digunakan untuk <strong>latihan mandiri, aktivitas komunitas, sekolah, hingga event basket.</strong> Seluruh lapangan dirawat dengan baik dan berada di lingkungan yang aman serta nyaman.
        </>
      ),
      image: "https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1200",
    },
    {
      title: "PENYEWAAN PERLENGKAPAN BASKET",
      subtitle: "The Arena Basketball",
      description: (
        <>
          Selain lapangan, The Arena juga menyediakan berbagai <strong>peralatan dan perlengkapan basket</strong> yang dapat disewa secara <strong>praktis dan fleksibel,</strong> sehingga pengguna tidak perlu repot menyiapkan sendiri.
        </>
      ),
      image: "https://images.unsplash.com/photo-1608245449230-4ac19066d2d0?w=1200",
    },
    {
      title: "PENYELENGGARAAN ACARA BASKET",
      subtitle: "The Arena Basketball",
      description: (
        <>
          Sebagai bagian dari ekosistem basket di Bandung, The Arena tidak hanya menjadi tempat bermain, tetapi juga <strong>ruang berkumpul dan berkompetisi bagi komunitas basket.</strong> Kami menyediakan layanan <strong>penyelenggaraan acara basket,</strong> mulai dari friendly match hingga turnamen berskala besar.
        </>
      ),
      image: "https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1200",
    },
  ];

  const nextSlide = () => {
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const prevSlide = () => {
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  const handleLogout = () => {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
      router.post('/logout');
    }
  };

  return (
    <>
      <Head title="THE ARENA - Home Page" />
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
        
        @keyframes slideDown {
          from {
            opacity: 0;
            transform: translateY(-20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        @keyframes progress {
          from {
            width: 100%;
          }
          to {
            width: 0%;
          }
        }
        
        .animate-slide-down {
          animation: slideDown 0.3s ease-out;
        }
        
        .animate-progress {
          animation: progress 5s linear;
        }
          /* ✅ TAMBAHKAN INI DI DALAM <style> TAG DI HomePage.jsx */

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeInUp {
  animation: fadeInUp 0.6s ease-out;
}

/* Smooth transition untuk carousel dots */
.carousel-dot {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.carousel-dot:hover {
  transform: scale(1.2);
}
      `}</style>
      <div className="min-h-screen flex flex-col bg-[#013064]">
        {/* Navigation - RESPONSIVE & STICKY */}
        <Navigation activePage="home" />

        {/* Notification Popup */}
        {notification && (
          <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4">
            <div
              className="absolute inset-0 bg-[#013064]/80 backdrop-blur-sm"
              onClick={() => setNotification(null)}
            />
            <div className="relative bg-white max-w-md w-full animate-slide-down shadow-2xl">
              <div className="border-t-4 border-[#ffd22f]">
                <div className="bg-[#013064] px-6 py-4 flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-2 h-2 rounded-full bg-[#ffd22f]" />
                    <h3 className="font-bold text-white text-lg">
                      {notification.type === 'success' ? 'Berhasil' : 'Perhatian'}
                    </h3>
                  </div>
                  <button
                    onClick={() => setNotification(null)}
                    className="text-white/70 hover:text-white transition"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
                <div className="p-6 bg-white">
                  <p className="text-[#013064] text-base leading-relaxed">
                    {notification.message}
                  </p>
                </div>
                <div className="h-1 bg-gray-200 overflow-hidden">
                  <div className="h-full bg-[#ffd22f] animate-progress" />
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Hero Section with Carousel - RESPONSIVE */}
        <main className="flex-1 relative">
          <div className="relative h-[400px] md:h-[500px] lg:h-[600px] overflow-hidden">
            <div
              className="absolute inset-0 bg-cover bg-center"
              style={{
                backgroundImage: `url('${slides[currentSlide].image}')`,
                filter: "brightness(0.4)",
              }}
            />

            <div className="relative z-10 h-full flex items-center justify-center">
              <div className="text-center text-white px-4 max-w-4xl">
                <h2 className="text-[#FDB913] text-lg md:text-xl lg:text-2xl font-semibold mb-2">
                  {slides[currentSlide].subtitle}
                </h2>
                <h1 className="text-2xl md:text-4xl lg:text-6xl font-bold mb-4 md:mb-6 leading-tight">
                  {slides[currentSlide].title}
                </h1>
                <p className="text-sm md:text-base lg:text-lg mb-6 md:mb-8 text-gray-200 max-w-2xl mx-auto leading-relaxed">
                  {slides[currentSlide].description}
                </p>
                <Link href="/booking">
                  <button className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit">
                    Booking Lapangan
                  </button>
                </Link>
              </div>

              <button
                onClick={prevSlide}
                className="absolute left-4 md:left-24 lg:left-32 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 flex items-center justify-center"
              >
                <img
                  src="/images/Kiri.svg"
                  alt="Previous"
                  className="w-full h-full"
                />
              </button>
              <button
                onClick={nextSlide}
                className="absolute right-4 md:right-24 lg:right-32 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 flex items-center justify-center"
              >
                <img
                  src="/images/Kanan.svg"
                  alt="Next"
                  className="w-full h-full"
                />
              </button>
            </div>
          </div>
        </main>

        {/* Social Media Section - RESPONSIVE */}
        <div className="bg-[#ffd22f] py-4 md:py-6">
          <div className="max-w-7xl mx-auto px-4 flex justify-center md:justify-end items-center gap-3 md:gap-4">
            <a href="#" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
              <img
                src="/images/instagram.png"
                alt="Instagram"
                className="w-full h-full object-contain"
              />
            </a>
            <a href="#" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
              <img
                src="/images/tiktok.png"
                alt="TikTok"
                className="w-full h-full object-contain"
              />
            </a>
            <a href="#" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
              <img
                src="/images/youtube.png"
                alt="YouTube"
                className="w-full h-full object-contain"
              />
            </a>
            <a href="#" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
              <img
                src="/images/whatsapp.png"
                alt="WhatsApp"
                className="w-full h-full object-contain"
              />
            </a>
          </div>
        </div>

        {/* Content Sections - RESPONSIVE */}
        <div className="bg-white">
          {/* Section 1: Penyewaan Lapangan Basket */}
          <div className="grid md:grid-cols-2">
            <div className="relative h-full min-h-[300px] md:min-h-[400px]">
              <img
                src="https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1200"
                alt="Basketball Court"
                className="w-full h-full object-cover"
              />
            </div>

            <div className="bg-[#003f84] text-white p-6 md:p-12 lg:p-16 flex flex-col justify-center">
              <h3 className="text-[#ffd22f] text-lg md:text-xl lg:text-2xl font-semibold mb-3 md:mb-4">
                Penyewaan Lapangan Basket
              </h3>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 leading-tight">
                Penyewaan Lapangan Basket
              </h2>
              <p className="text-gray-300 text-sm md:text-base mb-6 md:mb-8 leading-relaxed">
                Lapangan basket The Arena dapat digunakan untuk <strong className="text-white">latihan mandiri, aktivitas komunitas, sekolah, hingga event basket.</strong> Seluruh lapangan dirawat dengan baik dan berada di lingkungan yang aman serta nyaman.
              </p>
              <Link href="/booking">
                <button className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit">
                  Booking Lapangan
                  <ChevronRight className="w-4 h-4" />
                </button>
              </Link>
            </div>
          </div>

          {/* Section 2: Penyewaan Perlengkapan Basket */}
          <div className="grid md:grid-cols-2">
            <div className="bg-[#003f84] text-white p-6 md:p-12 lg:p-16 flex flex-col justify-center order-2 md:order-1">
              <h3 className="text-[#ffd22f] text-lg md:text-xl lg:text-2xl font-semibold mb-3 md:mb-4">
                Perlengkapan
              </h3>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 leading-tight">
                Penyewaan Perlengkapan Basket
              </h2>
              <p className="text-gray-300 text-sm md:text-base mb-6 md:mb-8 leading-relaxed">
                Selain lapangan, The Arena juga menyediakan berbagai <strong>peralatan dan perlengkapan basket</strong> yang dapat disewa secara <strong>praktis dan fleksibel,</strong> sehingga pengguna tidak perlu repot menyiapkan sendiri.
              </p>
              <button className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit"
                onClick={() => router.visit('/booking-peralatan')}>
                Booking Peralatan
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>

            <div className="relative h-full min-h-[300px] md:min-h-[400px] order-1 md:order-2">
              <img
                src="https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1200"
                alt="Basketball Equipment"
                className="w-full h-full object-cover"
              />
            </div>
          </div>

          {/* Section 3: Event Organizer */}
          <div className="grid md:grid-cols-2">
            <div className="relative h-full min-h-[300px] md:min-h-[400px]">
              <img
                src="https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1200"
                alt="Basketball Court"
                className="w-full h-full object-cover"
              />
            </div>

            <div className="bg-[#003f84] text-white p-6 md:p-12 lg:p-16 flex flex-col justify-center">
              <h3 className="text-[#ffd22f] text-lg md:text-xl lg:text-2xl font-semibold mb-3 md:mb-4">
                Event Organizer
              </h3>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 leading-tight">
                Penyewaan Acara Basket
              </h2>
              <p className="text-gray-300 text-sm md:text-base mb-6 md:mb-8 leading-relaxed">
                Sebagai bagian dari ekosistem basket di Bandung, The Arena tidak hanya menjadi tempat bermain, tetapi juga <strong>ruang berkumpul dan berkompetisi bagi komunitas basket.</strong> Kami menyediakan layanan <strong>penyelenggaraan acara basket,</strong> mulai dari friendly match hingga turnamen berskala besar.
              </p>
              <Link href="/kontak">
                <button className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit">
                  Hubungi Kami
                  <ChevronRight className="w-4 h-4" />
                </button>
              </Link>
            </div>
          </div>
        </div>
        {/* ✅ SECTION ULASAN PELANGGAN - FIXED CAROUSEL + SMOOTH SLIDE! */}
        <div className="bg-[#013064] py-12 md:py-16 lg:py-20 px-4">
          <div className="max-w-7xl mx-auto">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 md:mb-12 gap-4">
              <div>
                <p className="text-[#ffd22f] text-base md:text-xl lg:text-2xl font-semibold mb-2">Ulasan</p>
                <h2 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold">
                  Apa Kata Pelanggan Kami
                </h2>
              </div>
              <button
                onClick={handleOpenReviewModal}
                className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-3 rounded-lg font-bold hover:bg-[#ffe066] transition text-sm md:text-base whitespace-nowrap"
              >
                Tulis Ulasan
              </button>
            </div>

            {reviewsList.length === 0 ? (
              <div className="text-center py-12 md:py-16">
                <p className="text-white/70 text-lg md:text-xl">
                  Belum ada ulasan. Jadilah yang pertama memberikan ulasan!
                </p>
              </div>
            ) : (
              <>
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                  {currentReviews.map((review, index) => (
                    <div
                      key={review.id}
                      className="bg-white/10 backdrop-blur-sm p-4 md:p-5 lg:p-6 rounded-lg border border-white/20 hover:bg-white/15 transition animate-fadeInUp"
                      style={{
                        animationDelay: `${index * 0.1}s`,
                        animationFillMode: 'both'
                      }}
                    >
                      {/* Header: Profile + Name + Time */}
                      <div className="flex items-start gap-2 md:gap-3 lg:gap-4 mb-3 md:mb-4 lg:mb-5">
                        {review.client_profile_image ? (
                          <img
                            src={`/storage/${review.client_profile_image}`}
                            alt={review.client_name}
                            className="w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 rounded-full object-cover flex-shrink-0 ring-2 ring-[#ffd22f]"
                            onError={(e) => {
                              e.target.style.display = 'none';
                              e.target.nextElementSibling.style.display = 'flex';
                            }}
                          />
                        ) : null}
                        <div
                          className="w-10 h-10 md:w-12 md:h-12 lg:w-14 lg:h-14 rounded-full bg-[#ffd22f] flex items-center justify-center flex-shrink-0"
                          style={{ display: review.client_profile_image ? 'none' : 'flex' }}
                        >
                          <span className="text-[#013064] font-bold text-base md:text-lg lg:text-xl">
                            {review.client_name.charAt(0).toUpperCase()}
                          </span>
                        </div>

                        <div className="flex-1 min-w-0">
                          <p className="text-white font-bold text-sm md:text-base lg:text-lg mb-0.5 md:mb-1 truncate">
                            {review.client_name}
                          </p>
                          <span className="text-white/50 text-[10px] md:text-xs lg:text-sm">
                            {review.created_at}
                          </span>
                        </div>
                      </div>

                      {/* Rating Details - 3 Aspek */}
                      <div className="space-y-1 md:space-y-1.5 lg:space-y-2 mb-3 md:mb-4 lg:mb-5 bg-white/5 rounded-lg p-2 md:p-2.5 lg:p-3">
                        <div className="flex items-center justify-between gap-2">
                          <span className="text-white font-semibold text-[10px] md:text-xs lg:text-sm">
                            Fasilitas
                          </span>
                          <div className="flex gap-0.5">
                            {[...Array(5)].map((_, i) => (
                              <span
                                key={i}
                                className={`text-xs md:text-sm lg:text-base ${i < review.rating_facilities
                                  ? 'text-[#ffd22f]'
                                  : 'text-white/20'
                                  }`}
                              >
                                ★
                              </span>
                            ))}
                          </div>
                        </div>

                        <div className="flex items-center justify-between gap-2">
                          <span className="text-white font-semibold text-[10px] md:text-xs lg:text-sm">
                            Keramahan
                          </span>
                          <div className="flex gap-0.5">
                            {[...Array(5)].map((_, i) => (
                              <span
                                key={i}
                                className={`text-xs md:text-sm lg:text-base ${i < review.rating_hospitality
                                  ? 'text-[#ffd22f]'
                                  : 'text-white/20'
                                  }`}
                              >
                                ★
                              </span>
                            ))}
                          </div>
                        </div>

                        <div className="flex items-center justify-between gap-2">
                          <span className="text-white font-semibold text-[10px] md:text-xs lg:text-sm">
                            Kebersihan
                          </span>
                          <div className="flex gap-0.5">
                            {[...Array(5)].map((_, i) => (
                              <span
                                key={i}
                                className={`text-xs md:text-sm lg:text-base ${i < review.rating_cleanliness
                                  ? 'text-[#ffd22f]'
                                  : 'text-white/20'
                                  }`}
                              >
                                ★
                              </span>
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* Comment */}
                      <div className="border-t border-white/10 pt-2.5 md:pt-3 lg:pt-4">
                        <p className="text-white/90 leading-relaxed text-[11px] md:text-xs lg:text-sm line-clamp-3">
                          {review.comment}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>

                {/* ✅ CAROUSEL INDICATORS - BARU! */}
                {reviewsList.length > 3 && (
                  <div className="flex justify-center gap-2 mt-6">
                    {[...Array(totalReviewPages)].map((_, idx) => (
                      <button
                        key={idx}
                        onClick={() => setCurrentReviewPage(idx)}
                        className={`w-2 h-2 rounded-full transition-all ${idx === currentReviewPage
                          ? 'bg-[#ffd22f] w-8'
                          : 'bg-white/30 hover:bg-white/50'
                          }`}
                      />
                    ))}
                  </div>
                )}
              </>
            )}
          </div>
        </div>
        {/* Berita Seputar Basket Section - RESPONSIVE */}
        <div className="bg-[#013064] py-12 md:py-16 lg:py-20 px-4">
          <div className="max-w-7xl mx-auto">
            <div className="text-center mb-10 md:mb-16">
              <p className="text-[#ffd22f] text-base md:text-xl lg:text-2xl font-semibold mb-2 md:mb-3">Berita</p>
              <h2 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold">Berita Seputar Basket</h2>
            </div>

            {newsForHome && newsForHome.length > 0 ? (
              <>
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-12">
                  {newsForHome.map((news) => (
                    <Link key={news.id} href={`/berita/${news.id}`} className="block">
                      <div className="group cursor-pointer overflow-hidden relative h-[320px] md:h-[360px] lg:h-[380px]">
                        <img
                          src={news.image}
                          alt={news.title}
                          className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                          onError={(e) => {
                            e.target.src = 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800';
                          }}
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent" />
                        <span className="absolute top-3 left-3 bg-[#e74c3c] text-white px-2.5 py-1 text-xs font-semibold z-10">
                          {news.category}
                        </span>
                        <div className="absolute bottom-0 left-0 right-0 p-4 md:p-5 text-white">
                          <p className="text-gray-300 text-xs mb-2">{news.category} - {news.date}</p>
                          <h3 className="text-white text-sm md:text-base font-bold mb-2 leading-tight line-clamp-2">
                            {news.title}
                          </h3>
                          <p className="text-gray-200 text-xs mb-3 leading-relaxed line-clamp-2">
                            {news.excerpt}
                          </p>
                          <span className="text-white text-xs font-semibold flex items-center gap-1.5 group-hover:text-[#ffd22f] transition">
                            Lihat selengkapnya
                            <ChevronRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
                          </span>
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>

                <div className="text-center">
                  <Link href="/berita">
                    <button className="bg-[#ffd22f] text-[#013064] px-8 md:px-10 py-2.5 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition">
                      Lihat Lebih Banyak
                    </button>
                  </Link>
                </div>
              </>
            ) : (
              <div className="text-center py-12">
                <p className="text-white text-xl">Belum ada berita tersedia</p>
              </div>
            )}
          </div>
        </div>
        {/* Promo Section - Hero Banner - RESPONSIVE */}
        <div className="relative h-[350px] md:h-[450px] lg:h-[500px] overflow-hidden">
          <img
            src="https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1600"
            alt="Basketball Promo"
            className="w-full h-full object-cover"
          /><div className="absolute inset-0 flex items-center">
  <div className="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 w-full">
    <div className="max-w-3xl text-white">
      <span className="text-[#ffd22f] text-base md:text-xl lg:text-2xl font-semibold mb-2 md:mb-3 block">
        Promo Member
      </span>

      <h2 className="text-2xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-5 leading-tight">
        Harga Lebih Murah untuk Member!
      </h2>

      <div className="mb-5 md:mb-6 text-sm md:text-base lg:text-lg">
        <p>Dapatkan harga spesial dan berbagai keuntungan eksklusif. Hubungi admin untuk informasi lengkap tentang paket member kami.</p>
      </div>

      <Link 
         href="https://wa.me/6281222977985" 
        className="bg-[#ffd22f] text-[#013064] px-5 md:px-7 py-2 md:py-3 text-xs md:text-sm lg:text-base font-bold hover:bg-[#ffe066] transition inline-flex items-center gap-2"
      >
        Hubungi Admin
        <ChevronRight className="w-4 h-4" />
      </Link>
    </div>
  </div>
</div>
        </div>

        {/* Fasilitas Section - RESPONSIVE & DYNAMIC */}
        <div className="bg-white">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            {currentFacilities.length > 0 ? (
              currentFacilities.map((facility) => (
                <div
                  key={facility.id}
                  className="group cursor-pointer overflow-hidden relative h-[280px] md:h-[320px] lg:h-[350px]"
                >
                  <img
                    src={facility.image || 'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=800'}
                    alt={facility.name}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    onError={(e) => {
                      e.target.onerror = null; // Prevent infinite loop
                      e.target.src = 'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=800';
                    }}
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/50 to-transparent" />
                  <div className="absolute bottom-0 left-0 right-0 p-4 md:p-6 text-white">
                    <span className="text-[#ffd22f] text-sm md:text-base lg:text-lg font-semibold mb-1 md:mb-2 block">
                      Fasilitas
                    </span>
                    <h3 className="text-xl md:text-2xl lg:text-3xl font-bold">
                      {facility.name}
                    </h3>
                    {facility.description && (
                      <p className="text-sm text-gray-300 mt-2 line-clamp-2">
                        {facility.description}
                      </p>
                    )}
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
        </div>

        <div className="bg-[#ffd22f] py-6">
          <div className="max-w-7xl mx-auto px-4 flex justify-end items-center gap-4"></div>
        </div>
        {/* Jadwal Pertandingan Section - UPDATED TO MATCH MatchPage */}
        <div className="bg-[#013064] py-12 md:py-16 px-4">
          <div className="max-w-7xl mx-auto">
            {/* Section Header with Filter Buttons */}
            <div className="text-center mb-8 md:mb-12">
              <p className="text-[#ffd22f] text-base md:text-xl lg:text-2xl font-semibold mb-2 md:mb-3">
                Jadwal
              </p>
              <h2 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold mb-6 md:mb-8">
                Jadwal Pertandingan Basket
              </h2>

              {/* Filter Buttons - Updated Design */}
              <div className="flex flex-wrap justify-center gap-0 mb-8">
                <button
                  onClick={() => handleFilterChange('all')}
                  className={`px-8 md:px-12 py-3 md:py-3.5 text-sm md:text-base font-semibold transition-all ${filter === 'all'
                    ? 'bg-[#ffd22f] text-[#013064]'
                    : 'bg-[#013064] text-white border border-white hover:bg-white/10'
                    }`}
                >
                  Semua
                </button>
                <button
                  onClick={() => handleFilterChange('live')}
                  className={`px-8 md:px-12 py-3 md:py-3.5 text-sm md:text-base font-semibold transition-all border-l-0 ${filter === 'live'
                    ? 'bg-[#ffd22f] text-[#013064]'
                    : 'bg-[#013064] text-white border border-white hover:bg-white/10'
                    }`}
                >
                  Pertandingan Berlangsung
                </button>
                <button
                  onClick={() => handleFilterChange('upcoming')}
                  className={`px-8 md:px-12 py-3 md:py-3.5 text-sm md:text-base font-semibold transition-all border-l-0 ${filter === 'upcoming'
                    ? 'bg-[#ffd22f] text-[#013064]'
                    : 'bg-[#013064] text-white border border-white hover:bg-white/10'
                    }`}
                >
                  Pertandingan Berikutnya
                </button>
              </div>
            </div>

            {/* Match Cards Grid - UPDATED TO MATCH MatchPage DESIGN */}
            {homeMatches && homeMatches.length > 0 ? (
              <div className="grid sm:grid-cols-2 gap-4 md:gap-6">
                {homeMatches.map((match) => (
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
                          {/* League/Competition - Above Badge */}
                          <p className="text-sm md:text-base font-bold text-gray-800 mb-2 text-center">
                            {match.league}
                          </p>

                          {/* Status Badge */}
                          <div className="mb-1.5">
                            <span
                              className={`px-2.5 py-1 text-xs font-bold uppercase ${match.type === 'live'
                                ? 'bg-red-600 text-white'
                                : match.type === 'upcoming'
                                  ? 'bg-green-600 text-white'
                                  : 'bg-gray-600 text-white'
                                }`}
                            >
                              {match.type === 'live'
                                ? 'Live'
                                : match.type === 'upcoming'
                                  ? 'Upcoming Match'
                                  : 'Selesai'}
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
            ) : (
              <div className="text-center py-12">
                <p className="text-white text-xl">Tidak ada pertandingan tersedia</p>
              </div>
            )}

            {/* Button Lihat Lebih Banyak */}
            <div className="text-center mt-8">
              <Link href="/jadwal-hasil">
                <button className="bg-[#ffd22f] text-[#013064] px-10 md:px-12 py-3 md:py-3.5 text-sm md:text-base font-bold hover:bg-[#ffe066] transition">
                  Lihat Lebih Banyak
                </button>
              </Link>
            </div>
          </div>
        </div>
        {/* Live Streaming Section - RESPONSIVE */}
        <div className="bg-[#002855] py-12 px-4">
          <div className="max-w-7xl mx-auto">
            <div className="text-center mb-10 md:mb-12">
              <p className="text-[#ffd22f] text-base md:text-xl lg:text-2xl font-semibold mb-2 md:mb-3">
                Siaran Langsung
              </p>
              <h2 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold mb-4">
                Pertandingan Yang Sedang Berlangsung
              </h2>
            </div>

            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8">
              {liveMatches && liveMatches.length > 0 ? (
                liveMatches.map((game) => (
                  <div
                    key={game.id}
                    onClick={() => game.stream_url && window.open(game.stream_url, '_blank', 'noopener,noreferrer')}
                    className={`group overflow-hidden relative h-[220px] md:h-[240px] lg:h-[260px] rounded-lg transition-all duration-300 ${game.stream_url
                      ? 'cursor-pointer hover:shadow-2xl hover:scale-[1.02]'
                      : 'cursor-not-allowed opacity-75'
                      }`}
                  >
                    <img
                      src={game.img}
                      alt={game.title}
                      className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                      onError={(e) => {
                        e.target.src = '/images/comingsoon.png';
                      }}
                    />

                    {/* Gradient Overlay */}
                    <div className="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent" />

                    {/* Status Badge */}
                    <span className={`absolute top-3 left-3 ${game.status === "live" ? "bg-red-600 animate-pulse" :
                      game.status === "scheduled" ? "bg-orange-600" :
                        "bg-gray-600"
                      } text-white px-2.5 py-1 text-xs font-semibold z-10 uppercase rounded`}>
                      {game.status === "live" ? "🔴 Live" :
                        game.status === "scheduled" ? "Scheduled" :
                          "✓ Selesai"}
                    </span>

                    {/* Stream Available Indicator */}
                    {game.stream_url && (
                      <div className="absolute top-3 right-3 bg-white/20 backdrop-blur-sm text-white px-2.5 py-1 text-xs font-semibold z-10 rounded flex items-center gap-1">
                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
                        </svg>
                        Watch
                      </div>
                    )}

                    {/* No Stream Warning */}
                    {!game.stream_url && (
                      <div className="absolute top-3 right-3 bg-red-600/80 backdrop-blur-sm text-white px-2.5 py-1 text-xs font-semibold z-10 rounded">
                        No Stream
                      </div>
                    )}

                    {/* Match Info - Bottom Overlay */}
                    <div className="absolute bottom-0 left-0 right-0 p-4 md:p-5 text-white">
                      <p className="text-[#ffd22f] text-xs font-semibold mb-2">
                        {game.category}
                      </p>
                      <h3 className="text-white text-sm md:text-base font-bold mb-2 leading-tight line-clamp-2">
                        {game.title}
                      </h3>
                      <div className="flex justify-between items-center text-xs mb-2">
                        <span className="text-gray-300">{game.venue}</span>
                        <span className="text-white font-bold">{game.time}</span>
                      </div>
                      <p className="text-gray-400 text-xs">{game.court}</p>
                    </div>

                    {/* Hover Overlay untuk yang ada stream */}
                    {game.stream_url && (
                      <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <div className="bg-[#ffd22f] text-[#013064] px-6 py-3 rounded-lg font-bold text-sm flex items-center gap-2">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
                          </svg>
                          Tonton Sekarang
                        </div>
                      </div>
                    )}
                  </div>
                ))
              ) : (
                <div className="col-span-full flex justify-center items-center py-12">
                  <div className="w-full max-w-2xl">
                    <img
                      src="/images/comingsoon.png"
                      alt="Coming Soon"
                      className="w-full h-auto"
                    />
                  </div>
                </div>
              )}
            </div>

            <div className="text-center">
              <Link href="/siaran-langsung">
                <button className="bg-[#ffd22f] text-[#013064] px-8 md:px-10 py-2.5 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition">
                  Lihat Lebih Banyak
                </button>
              </Link>
            </div>
          </div>
        </div>
        {/* Sponsor and Partners Section - RESPONSIVE */}
        <div className="bg-[#013064] py-12 md:py-16 lg:py-20 px-4">
          <div className="max-w-7xl mx-auto">
            <div className="text-center mb-12 md:mb-16">
              <h2 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold">
                Partner dan Sponsor Kami
              </h2>
            </div>

            {/* Presented By Section (Sponsors) */}
            {sponsors && sponsors.length > 0 && (
              <div className="mb-16 md:mb-20">
                <p className="text-[#ffd22f] text-center text-lg md:text-xl lg:text-2xl font-semibold mb-6 md:mb-8">
                  Presented By
                </p>
                <div className="flex flex-col sm:flex-row justify-center gap-6 md:gap-8 flex-wrap">
                  {sponsors.map((sponsor) => (
                    <div
                      key={sponsor.id}
                      className="bg-white p-8 md:p-12 flex items-center justify-center w-full sm:w-96 md:w-[440px] h-96 md:h-[440px] rounded-lg shadow-lg"
                    >
                      <img
                        src={sponsor.image}
                        alt={sponsor.name}
                        className="max-w-full max-h-full object-contain"
                      />
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Official Partner Section */}
            {partners && partners.length > 0 && (
              <div className="mb-16 md:mb-20">
                <p className="text-[#ffd22f] text-center text-lg md:text-xl lg:text-2xl font-semibold mb-6 md:mb-8">
                  Official Partner
                </p>
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4 lg:gap-6">
                  {partners.map((partner) => (
                    <div
                      key={partner.id}
                      className="bg-white p-3 md:p-4 lg:p-6 flex items-center justify-center w-full h-32 md:h-40 lg:h-48 hover:scale-105 transition-transform rounded-lg shadow-md"
                    >
                      <img
                        src={partner.image}
                        alt={partner.name}
                        className="max-w-full max-h-full object-contain"
                      />
                    </div>
                  ))}
                </div>
              </div>
            )}


          </div>
        </div>

        {/* Contact Section - RESPONSIVE */}
        <Contact />

        {/* Footer Section - RESPONSIVE */}
        <Footer />

        {/* Copyright Bar */}

      </div>

      {/* Modal Review */}
      {showReviewModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between mb-6">
              <h3 className="text-2xl font-bold text-[#013064]">Tulis Ulasan</h3>
              <button
                onClick={() => setShowReviewModal(false)}
                className="text-gray-500 hover:text-gray-700"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* Rating Fasilitas */}
            <div className="mb-4">
              <label className="block text-sm font-semibold text-[#013064] mb-2">
                Fasilitas
              </label>
              <div className="flex gap-2">
                {[1, 2, 3, 4, 5].map((star) => (
                  <button
                    key={star}
                    type="button"
                    onClick={() => setReviewForm({ ...reviewForm, rating_facilities: star })}
                    className="text-3xl transition hover:scale-110"
                  >
                    <span className={star <= reviewForm.rating_facilities ? 'text-[#ffd22f]' : 'text-gray-300'}>
                      ★
                    </span>
                  </button>
                ))}
              </div>
            </div>

            {/* Rating Keramahan */}
            <div className="mb-4">
              <label className="block text-sm font-semibold text-[#013064] mb-2">
                Keramahan
              </label>
              <div className="flex gap-2">
                {[1, 2, 3, 4, 5].map((star) => (
                  <button
                    key={star}
                    type="button"
                    onClick={() => setReviewForm({ ...reviewForm, rating_hospitality: star })}
                    className="text-3xl transition hover:scale-110"
                  >
                    <span className={star <= reviewForm.rating_hospitality ? 'text-[#ffd22f]' : 'text-gray-300'}>
                      ★
                    </span>
                  </button>
                ))}
              </div>
            </div>

            {/* Rating Kebersihan */}
            <div className="mb-4">
              <label className="block text-sm font-semibold text-[#013064] mb-2">
                Kebersihan
              </label>
              <div className="flex gap-2">
                {[1, 2, 3, 4, 5].map((star) => (
                  <button
                    key={star}
                    type="button"
                    onClick={() => setReviewForm({ ...reviewForm, rating_cleanliness: star })}
                    className="text-3xl transition hover:scale-110"
                  >
                    <span className={star <= reviewForm.rating_cleanliness ? 'text-[#ffd22f]' : 'text-gray-300'}>
                      ★
                    </span>
                  </button>
                ))}
              </div>
            </div>

            {/* Comment */}
            <div className="mb-6">
              <label className="block text-sm font-semibold text-[#013064] mb-2">
                Komentar (minimal 10 karakter)
              </label>
              <textarea
                value={reviewForm.comment}
                onChange={(e) => setReviewForm({ ...reviewForm, comment: e.target.value })}
                placeholder="Bagikan pengalaman Anda..."
                rows={4}
                className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-[#ffd22f] focus:outline-none resize-none"
              />
              <p className="text-xs text-gray-500 mt-1">
                {reviewForm.comment.length} karakter
              </p>
            </div>

            {/* Buttons */}
            <div className="flex gap-3">
              <button
                onClick={() => setShowReviewModal(false)}
                disabled={isSubmittingReview}
                className="flex-1 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition disabled:opacity-50"
              >
                Batal
              </button>
              <button
                onClick={handleSubmitReview}
                disabled={isSubmittingReview}
                className="flex-1 py-3 bg-[#ffd22f] text-[#013064] rounded-lg font-bold hover:bg-[#ffe066] transition disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {isSubmittingReview ? (
                  <>
                    <div className="w-5 h-5 border-2 border-[#013064] border-t-transparent rounded-full animate-spin"></div>
                    Mengirim...
                  </>
                ) : (
                  'Kirim Ulasan'
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}