
import { Head, Link, usePage, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { ChevronRight, Phone, Mail, LogOut, X } from "lucide-react";
import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";
import Contact from '../../Components/Contact';

const lapanganImages = [
  "/images/HomePage/Lapangan/1CibadakA.jpg",
  "/images/HomePage/Lapangan/1CibadakB.jpg",
  "/images/HomePage/Lapangan/1PvjA.jpg",
  "/images/HomePage/Lapangan/1Urban.jpg",
  "/images/HomePage/Lapangan/2CibadakA.jpg",
  "/images/HomePage/Lapangan/2CibadakB.jpg",
  "/images/HomePage/Lapangan/2PvjB.jpg",
  "/images/HomePage/Lapangan/2Urban.jpg",
  "/images/HomePage/Lapangan/3CibadakA.jpg",
  "/images/HomePage/Lapangan/3CibadakB.jpg",
  "/images/HomePage/Lapangan/3PVjB.jpg",
  "/images/HomePage/Lapangan/3Urban.jpg"
];

const alatImages = [
  "/images/HomePage/Alat/Bola1.jpg",
  "/images/HomePage/Alat/Bola2.jpg",
  "/images/HomePage/Alat/Bola3.jpg",
  "/images/HomePage/Alat/Ring1.jpg",
  "/images/HomePage/Alat/Ring2.jpg",
  "/images/HomePage/Alat/Ring3.jpg",
  "/images/HomePage/Alat/Scoreboard1.jpg",
  "/images/HomePage/Alat/Scoreboard2.jpg",
  "/images/HomePage/Alat/Scoreboard3.jpg",
  "/images/HomePage/Alat/Shotclock.jpg",
  "/images/HomePage/Alat/Shotclock2.jpg",
  "/images/HomePage/Alat/Shotclock4.jpg"
];

const eventImages = [
  "/images/HomePage/Event/DSC_0004-2.jpg",
  "/images/HomePage/Event/DSC_0083.jpg",
  "/images/HomePage/Event/ILK_0312.jpg",
  "/images/HomePage/Event/JRP_3002.JPG"
];

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
    facilities = [],
    activeEventNotif = null,
  } = usePage().props;

  const [currentSlide, setCurrentSlide] = useState(0);
  const [isScrolled, setIsScrolled] = useState(false);
  const [lastScrollY, setLastScrollY] = useState(0);
  const [showContactBar, setShowContactBar] = useState(false);
  const [filter, setFilter] = useState(currentFilter || 'all');
  const [reviewsList, setReviewsList] = useState(reviews);
  const [showReviewModal, setShowReviewModal] = useState(false);

  const [reviewForm, setReviewForm] = useState({
    rating_facilities: 5,
    rating_hospitality: 5,
    rating_cleanliness: 5,
    comment: ''
  });
  const [isSubmittingReview, setIsSubmittingReview] = useState(false);
  const [notification, setNotification] = useState(null);
  const [currentReviewPage, setCurrentReviewPage] = useState(0);
  const [showEventNotifPopup, setShowEventNotifPopup] = useState(false);
  const [currentLapanganImgIndex, setCurrentLapanganImgIndex] = useState(0);
  const [currentAlatImgIndex, setCurrentAlatImgIndex] = useState(0);
  const [currentEventImgIndex, setCurrentEventImgIndex] = useState(0);

  // ✅ USEEFFECT AUTO-RANDOM EVENT IMAGE
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentEventImgIndex((prev) => {
        let newIndex;
        do {
          newIndex = Math.floor(Math.random() * eventImages.length);
        } while (newIndex === prev);
        return newIndex;
      });
    }, 10000);
    return () => clearInterval(interval);
  }, []);

  // ✅ USEEFFECT AUTO-RANDOM ALAT IMAGE
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentAlatImgIndex((prev) => {
        let newIndex;
        do {
          newIndex = Math.floor(Math.random() * alatImages.length);
        } while (newIndex === prev);
        return newIndex;
      });
    }, 10000);
    return () => clearInterval(interval);
  }, []);

  // ✅ USEEFFECT AUTO-RANDOM LAPANGAN IMAGE
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentLapanganImgIndex((prev) => {
        let newIndex;
        do {
          newIndex = Math.floor(Math.random() * lapanganImages.length);
        } while (newIndex === prev);
        return newIndex;
      });
    }, 10000);
    return () => clearInterval(interval);
  }, []);

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

  useEffect(() => {
    if (activeEventNotif) {
      // Cek apakah user sudah pernah melihat popup ini di sesi ini
      const hasSeenPopup = sessionStorage.getItem(`event_notif_${activeEventNotif.id}`);

      if (!hasSeenPopup) {
        setShowEventNotifPopup(true);
      }
    }

  }, [activeEventNotif]);
  // Get reviews untuk halaman saat ini
  const reviewsPerPage = 3;
  const startIndex = currentReviewPage * reviewsPerPage;
  const currentReviews = reviewsList.slice(startIndex, startIndex + reviewsPerPage);
  const totalReviewPages = Math.ceil(reviewsList.length / reviewsPerPage);

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

  const handleCloseEventNotifPopup = () => {
    // Simpan ke sessionStorage bahwa user sudah melihat popup ini
    if (activeEventNotif?.id) {
      sessionStorage.setItem(`event_notif_${activeEventNotif.id}`, 'true');
    }
    setShowEventNotifPopup(false);
  };

  const handleRegisterEvent = () => {
    if (activeEventNotif?.whatsapp_url) {
      window.open(activeEventNotif.whatsapp_url, '_blank', 'noopener,noreferrer');
      handleCloseEventNotifPopup();
    }
  };

  const getFacilityImageUrl = (url) => {
    if (!url) {
      return 'https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=800';
    }
    if (url.startsWith('http')) return url;
    return `/storage/${url}`;
  };

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

  const slides = [
    {
      title: "BOOKING LAPANGAN SEKARANG!",
      subtitle: "The Arena Basketball",
      description: (
        <>
          The Arena menghadirkan <strong>4 lapangan basket</strong> yang tersebar di Kota Bandung dengan pilihan <strong>indoor dan semi-indoor</strong>, menggunakan material <strong>berstandar FIBA</strong> (kayu & vinyl). Tidak hanya untuk bermain, The Arena juga menyediakan <strong>penyewaan perlengkapan basket</strong> serta jasa penyelenggaraan event untuk mendukung kebutuhan latihan, komunitas, hingga turnamen basket.
        </>
      ),
      image: "/images/Lapangan/SLIDELAPANG.jpg",
      buttonText: "Booking Sekarang",
      buttonAction: "internal",
      buttonLink: "/booking"
    },
    {
      title: "PENYEWAAN PERLENGKAPAN BASKET",
      subtitle: "The Arena Basketball",
      description: (
        <>
          Selain lapangan, The Arena juga menyediakan berbagai <strong>peralatan dan perlengkapan basket</strong> yang dapat disewa secara <strong>praktis dan fleksibel,</strong> sehingga pengguna tidak perlu repot menyiapkan sendiri.
        </>
      ),
      image: "/images/Lapangan/HOME1.jpg", // ✅ Ambil dari gambar PVJ
      buttonText: "Booking Peralatan",
      buttonAction: "internal",
      buttonLink: "/booking-peralatan"
    },
    {
      title: "PENYELENGGARAAN ACARA BASKET",
      subtitle: "The Arena Basketball",
      description: (
        <>
          Sebagai bagian dari ekosistem basket di Bandung, The Arena tidak hanya menjadi tempat bermain, tetapi juga <strong>ruang berkumpul dan berkompetisi bagi komunitas basket.</strong> Kami menyediakan layanan <strong>penyelenggaraan acara basket,</strong> mulai dari friendly match hingga turnamen berskala besar.
        </>
      ),
      image: "/images/Lapangan/SLIDEACARA.jpg",
      buttonText: "Hubungi Kami",
      buttonAction: "whatsapp",
      buttonLink: "https://wa.me/6281222977985"
    },
  ];

  const nextSlide = () => {
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const prevSlide = () => {
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  // ✅ USEEFFECT AUTO-SLIDE HERO CAROUSEL
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 10000);

    return () => clearInterval(interval);
  }, [currentSlide, slides.length]);

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

        .carousel-dot {
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .carousel-dot:hover {
          transform: scale(1.2);
        }

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
          animation: fade-in 0.5s ease-out;
        }

        @keyframes slide-fade-in {
          from {
            opacity: 0;
            transform: translateX(6%);
          }
          to {
            opacity: 1;
            transform: translateX(0);
          }
        }

        .animate-slide-fade-in {
          animation: slide-fade-in 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .animate-modal-appear {
          animation: modal-appear 0.4s cubic-bezier(0.16, 1, 0.3, 1);
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
            {/* Background Image */}
            <div
              className="absolute inset-0 bg-cover bg-center transition-all duration-700"
              style={{
                backgroundImage: `url('${slides[currentSlide].image}')`,
                filter: "brightness(0.4)",
              }}
            />

            {/* Content */}
            <div className="relative z-10 h-full flex items-center justify-center">
              <div key={currentSlide} className="text-center text-white px-4 max-w-4xl">
                <h2 className="text-[#FDB913] text-lg md:text-xl lg:text-2xl font-semibold mb-2">
                  {slides[currentSlide].subtitle}
                </h2>

                <h1 className="text-2xl md:text-4xl lg:text-6xl font-bold mb-4 md:mb-6 leading-tight">
                  {slides[currentSlide].title}
                </h1>

                <p className="text-sm md:text-base lg:text-lg mb-6 md:mb-8 text-gray-200 max-w-2xl mx-auto leading-relaxed">
                  {slides[currentSlide].description}
                </p>

                {/* Button */}
                {slides[currentSlide].buttonAction === "whatsapp" ? (
                  <a
                    href={slides[currentSlide].buttonLink}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit mx-auto"
                  >
                    {slides[currentSlide].buttonText}
                  </a>
                ) : (
                  <Link href={slides[currentSlide].buttonLink}>
                    <button className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit mx-auto">
                      {slides[currentSlide].buttonText}
                    </button>
                  </Link>
                )}
              </div>

              {/* Navigation Buttons - Desktop: SVG Icons, Mobile: Compact Arrows */}
              <button
                onClick={prevSlide}
                className="absolute left-2 md:left-24 lg:left-32 top-1/2 -translate-y-1/2 
                           w-9 h-9 md:w-12 md:h-12 lg:w-14 lg:h-14 
                           flex items-center justify-center 
                           bg-white/20 md:bg-transparent backdrop-blur-sm md:backdrop-blur-none
                           rounded-full md:rounded-none
                           hover:bg-white/30 md:hover:bg-transparent
                           hover:scale-110 transition-all
                           border border-white/40 md:border-0"
              >
                {/* Mobile: Simple Arrow */}
                <svg className="w-5 h-5 md:hidden text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M15 19l-7-7 7-7" />
                </svg>
                {/* Desktop: SVG Image */}
                <img
                  src="/images/Kiri.svg"
                  alt="Previous"
                  className="hidden md:block w-full h-full"
                />
              </button>

              <button
                onClick={nextSlide}
                className="absolute right-2 md:right-24 lg:right-32 top-1/2 -translate-y-1/2 
                           w-9 h-9 md:w-12 md:h-12 lg:w-14 lg:h-14 
                           flex items-center justify-center 
                           bg-white/20 md:bg-transparent backdrop-blur-sm md:backdrop-blur-none
                           rounded-full md:rounded-none
                           hover:bg-white/30 md:hover:bg-transparent
                           hover:scale-110 transition-all
                           border border-white/40 md:border-0"
              >
                {/* Mobile: Simple Arrow */}
                <svg className="w-5 h-5 md:hidden text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M9 5l7 7-7 7" />
                </svg>
                {/* Desktop: SVG Image */}
                <img
                  src="/images/Kanan.svg"
                  alt="Next"
                  className="hidden md:block w-full h-full"
                />
              </button>

              {/* Carousel Indicators - Visible on All Devices */}
              <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                {slides.map((_, index) => (
                  <button
                    key={index}
                    onClick={() => setCurrentSlide(index)}
                    className={`transition-all duration-300 carousel-dot ${index === currentSlide
                      ? 'w-8 h-2 bg-[#ffd22f]'
                      : 'w-2 h-2 bg-white/50 hover:bg-white/80'
                      } rounded-full`}
                    aria-label={`Go to slide ${index + 1}`}
                  />
                ))}
              </div>
            </div>
          </div>
        </main>

        {/* Social Media Section - RESPONSIVE */}
        <div className="bg-[#ffd22f] py-4 md:py-6">
          <div className="max-w-7xl mx-auto px-4 flex justify-center md:justify-end items-center gap-3 md:gap-4">
            <a href="https://www.instagram.com/the.arena.basketball/" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
              <img
                src="/images/instagram.png"
                alt="Instagram"
                className="w-full h-full object-contain"
              />
            </a>
            <a href="https://www.tiktok.com/@thearenapvj" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
              <img
                src="/images/tiktok.png"
                alt="TikTok"
                className="w-full h-full object-contain"
              />
            </a>
            <a href="https://www.youtube.com/@thearenapvj" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
              <img
                src="/images/youtube.png"
                alt="YouTube"
                className="w-full h-full object-contain"
              />
            </a>
            <a href="https://wa.me/6281222977985" className="w-8 h-8 md:w-9 md:h-9 flex items-center justify-center">
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
            <div className="relative h-full min-h-[300px] md:min-h-[400px] overflow-hidden">
              <img
                key={lapanganImages[currentLapanganImgIndex]}
                src={lapanganImages[currentLapanganImgIndex]}
                alt="Basketball Court"
                className="absolute inset-0 w-full h-full object-cover animate-slide-fade-in"
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
                  Booking Sekarang
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

            <div className="relative h-full min-h-[300px] md:min-h-[400px] order-1 md:order-2 overflow-hidden">
              <img
                key={alatImages[currentAlatImgIndex]}
                src={alatImages[currentAlatImgIndex]}
                alt="Alat Basket"
                className="absolute inset-0 w-full h-full object-cover animate-slide-fade-in"
              />
            </div>
          </div>

          {/* Section 3: Event Organizer */}
          <div className="grid md:grid-cols-2">
            <div className="relative h-full min-h-[300px] md:min-h-[400px] overflow-hidden">
              <img
                key={eventImages[currentEventImgIndex]}
                src={eventImages[currentEventImgIndex]}
                alt="Event Basketball"
                className="absolute inset-0 w-full h-full object-cover animate-slide-fade-in"
              />
            </div>

            <div className="bg-[#003f84] text-white p-6 md:p-12 lg:p-16 flex flex-col justify-center">
              <h3 className="text-[#ffd22f] text-lg md:text-xl lg:text-2xl font-semibold mb-3 md:mb-4">
                Event Organizer
              </h3>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 leading-tight">
                Penyelenggara Event Basket
              </h2>
              <p className="text-gray-300 text-sm md:text-base mb-6 md:mb-8 leading-relaxed">
                Sebagai bagian dari ekosistem basket di Bandung, The Arena tidak hanya menjadi tempat bermain, tetapi juga <strong>ruang berkumpul dan berkompetisi bagi komunitas basket.</strong> Kami menyediakan layanan <strong>penyelenggaraan acara basket,</strong> mulai dari friendly match hingga turnamen berskala besar.
              </p>
              <a href="https://wa.me/6281222977985" target="_blank" rel="noopener noreferrer">
  <button className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit">
    Hubungi Kami
    <ChevronRight className="w-4 h-4" />
  </button>
</a>
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

                <a
                  href="https://wa.me/6281222977985"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="bg-[#ffd22f] text-[#013064] px-5 md:px-7 py-2 md:py-3 text-xs md:text-sm lg:text-base font-bold hover:bg-[#ffe066] transition inline-flex items-center gap-2"
                >
                  Hubungi Admin
                  <ChevronRight className="w-4 h-4" />
                </a>
              </div>
            </div>
          </div>
        </div>
        {/* Facilities Section - RESPONSIVE */}
        <div className="bg-white">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            {Array.isArray(facilities) && facilities.length > 0 ? (
              facilities.slice(0, 6).map((facility) => (
                <div
                  key={facility.id}
                  className="group overflow-hidden relative h-[280px] md:h-[320px] lg:h-[350px]"
                >
                  <img
                    src={facility.image}
                    alt={facility.name}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    onError={(e) => {
                      e.target.src = 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800';
                    }}
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/50 to-transparent" />
                  <div className="absolute bottom-0 left-0 right-0 p-4 md:p-6 text-white">
                    <span className="text-[#ffd22f] text-sm md:text-base lg:text-lg font-semibold mb-1 md:mb-2 block">
                      Fasilitas
                    </span>
                    <h3 className="text-xl md:text-2xl lg:text-3xl font-bold mb-2">
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

              {/* Filter Buttons - Mobile Responsive Fixed */}
              <div className="flex flex-col sm:flex-row justify-center gap-3 sm:gap-0 mb-8 px-4">
                <button
                  onClick={() => handleFilterChange('all')}
                  className={`px-6 sm:px-8 md:px-12 py-3 md:py-3.5 text-sm md:text-base font-semibold transition-all whitespace-nowrap ${filter === 'all'
                    ? 'bg-[#ffd22f] text-[#013064]'
                    : 'bg-[#013064] text-white border border-white hover:bg-white/10'
                    }`}
                >
                  Semua
                </button>
                <button
                  onClick={() => handleFilterChange('live')}
                  className={`px-6 sm:px-8 md:px-12 py-3 md:py-3.5 text-sm md:text-base font-semibold transition-all sm:border-l-0 whitespace-nowrap ${filter === 'live'
                    ? 'bg-[#ffd22f] text-[#013064]'
                    : 'bg-[#013064] text-white border border-white hover:bg-white/10'
                    }`}
                >
                  Pertandingan Berlangsung
                </button>
                <button
                  onClick={() => handleFilterChange('upcoming')}
                  className={`px-6 sm:px-8 md:px-12 py-3 md:py-3.5 text-sm md:text-base font-semibold transition-all sm:border-l-0 whitespace-nowrap ${filter === 'upcoming'
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

      {/* Floating WhatsApp Button - FIXED */}
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
 {/* ✅ EVENT NOTIF POPUP MODAL - SIMPLIFIED */}
{showEventNotifPopup && activeEventNotif && (
  <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 animate-fade-in">
    {/* Backdrop */}
    <div
      className="absolute inset-0 bg-black/60 backdrop-blur-sm"
      onClick={handleCloseEventNotifPopup}
    />

    {/* Modal Content - RESPONSIVE LAYOUT */}
    <div className="relative bg-white w-full max-h-[90vh] overflow-hidden shadow-2xl animate-modal-appear 
                    max-w-sm sm:max-w-md md:max-w-3xl lg:max-w-4xl
                    flex flex-col md:grid md:grid-cols-7">
      
      {/* Close Button */}
      <button
        onClick={handleCloseEventNotifPopup}
        className="absolute top-3 right-3 md:top-4 md:right-4 z-50 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors bg-white/80 md:bg-transparent rounded-full md:rounded-none"
        aria-label="Close"
      >
        <X className="w-5 h-5 md:w-6 md:h-6" strokeWidth={2} />
      </button>

      {/* IMAGE SECTION */}
      <div className="relative bg-[#013064] overflow-hidden md:col-span-3 h-64 sm:h-80 md:h-auto">
        <div className="w-full h-full md:aspect-[4/5]">
          {activeEventNotif.image_url ? (
            <img
              src={activeEventNotif.image_url}
              alt={activeEventNotif.title}
              className="w-full h-full object-cover"
              onError={(e) => {
                e.target.style.display = 'none';
              }}
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <svg className="w-16 h-16 md:w-20 md:h-20 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          )}
          <div className="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-r from-[#013064]/50 md:from-transparent to-transparent md:to-[#013064]/30" />
        </div>
      </div>

      {/* CONTENT SECTION */}
      <div className="flex-1 p-5 sm:p-6 md:p-8 md:col-span-4 flex flex-col overflow-y-auto max-h-[calc(90vh-16rem)] md:max-h-[85vh]">
        
        {/* Header */}
        <div className="mb-3 md:mb-4">
          <h2 className="text-xl sm:text-2xl md:text-2xl font-light text-[#013064] mb-1 md:mb-2 leading-tight">
            {activeEventNotif.title}
          </h2>
          <p className="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wider">
            {activeEventNotif.formatted_date}
            {activeEventNotif.formatted_time && ` • ${activeEventNotif.formatted_time}`}
          </p>
        </div>

        {/* Tagline */}
        {activeEventNotif.tagline && (
          <p className="text-xs sm:text-sm text-gray-600 mb-4 md:mb-6 leading-relaxed">
            {activeEventNotif.tagline}
          </p>
        )}

        {/* Description */}
        {activeEventNotif.description && (
          <p className="text-xs sm:text-sm text-gray-600 mb-4 md:mb-6 leading-relaxed">
            {activeEventNotif.description}
          </p>
        )}

        {/* Pricing Section - SIMPLIFIED */}
        {activeEventNotif.show_pricing && (activeEventNotif.monthly_price || activeEventNotif.weekly_price) && (
          <div className="space-y-3 md:space-y-4 mb-4 md:mb-6">
            
            {/* Monthly Package */}
            {activeEventNotif.monthly_price && (
              <div className="border-l-3 md:border-l-4 border-[#ffd22f] pl-3 md:pl-4 py-2">
                <div className="flex items-center gap-2 mb-1">
                  <span className="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wider font-medium">
                    Monthly
                  </span>
                  {activeEventNotif.monthly_discount_percent && (
                    <span className="text-[9px] sm:text-[10px] bg-red-100 text-red-600 px-1.5 sm:px-2 py-0.5 rounded font-semibold">
                      - {activeEventNotif.monthly_discount_percent}%
                    </span>
                  )}
                </div>
                <p className="text-2xl sm:text-3xl font-light text-[#013064]">
                  Rp. {activeEventNotif.formatted_monthly_price}
                </p>
              </div>
            )}

            {/* Weekly Package */}
            {activeEventNotif.weekly_price && (
              <div className="border-l-3 md:border-l-4 border-gray-300 pl-3 md:pl-4 py-2">
                <span className="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wider font-medium">
                  Weekly
                </span>
                <p className="text-2xl sm:text-3xl font-light text-gray-700">
                  Rp. {activeEventNotif.formatted_weekly_price}
                </p>
              </div>
            )}
          </div>
        )}

        {/* Benefits Section */}
        {activeEventNotif.benefits_list && activeEventNotif.benefits_list.length > 0 && (
          <div className="mb-4 md:mb-6">
            <p className="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wider mb-2 md:mb-3 font-medium">
              Including
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              {activeEventNotif.benefits_list.map((benefit, idx) => (
                <div key={idx} className="flex items-start gap-2">
                  <svg className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#ffd22f] flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                  </svg>
                  <span className="text-xs sm:text-sm text-gray-600 leading-tight">
                    {benefit.label || benefit}
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Location */}
        {activeEventNotif.location && (
          <div className="flex items-center gap-2 text-xs sm:text-sm text-gray-500 mb-4 md:mb-6">
            <svg className="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span className="leading-tight">{activeEventNotif.location}</span>
          </div>
        )}

        {/* CTA Button */}
        <button
          onClick={handleRegisterEvent}
          className="w-full bg-[#013064] text-white py-2.5 sm:py-3 px-4 sm:px-6 
                     text-xs sm:text-sm uppercase tracking-wider sm:tracking-widest 
                     hover:bg-[#014a8f] transition-colors duration-300 mt-auto
                     font-medium sm:font-normal"
        >
          Register Now
        </button>
      </div>
    </div>
  </div>
)}
    </>
  );
}


