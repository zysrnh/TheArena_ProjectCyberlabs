import { Head, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { ArrowLeft, Calendar, Clock, Check, ChevronLeft, ChevronRight, CheckCircle, X, MapPin } from "lucide-react";
import Navigation from "../../../Components/Navigation";
import Footer from "../../../Components/Footer";

export default function Booking({ auth, venue, venues = {}, schedules = [], currentWeek = 0, reviews = [] }) {
  const validSchedules = Array.isArray(schedules) ? schedules : [];
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [selectedDate, setSelectedDate] = useState('');
  const [timeSlots, setTimeSlots] = useState([]);
  const [selectedTimeSlots, setSelectedTimeSlots] = useState([]);
  const [loading, setLoading] = useState(false);
  const [weekOffset, setWeekOffset] = useState(currentWeek);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [notification, setNotification] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [reviewsList, setReviewsList] = useState(reviews);
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [mobileStartIndex, setMobileStartIndex] = useState(0);
  const MOBILE_DATES_SHOWN = 5;
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [showImagePreview, setShowImagePreview] = useState(false);
  const [previewImageIndex, setPreviewImageIndex] = useState(0);

  const openImagePreview = (index) => {
    setPreviewImageIndex(index);
    setShowImagePreview(true);
  };

  const handlePreviewNavigation = (direction) => {
    if (direction === 'next') {
      setPreviewImageIndex((prev) =>
        prev < venue.images.length - 1 ? prev + 1 : 0
      );
    } else {
      setPreviewImageIndex((prev) =>
        prev > 0 ? prev - 1 : venue.images.length - 1
      );
    }
  };

  const [reviewForm, setReviewForm] = useState({
    rating_facilities: 5,
    rating_hospitality: 5,
    rating_cleanliness: 5,
    comment: ''
  });
  const [isSubmittingReview, setIsSubmittingReview] = useState(false);

  const getVisibleDates = () => {
    if (window.innerWidth >= 640) { // sm breakpoint
      return validSchedules;
    }
    return validSchedules.slice(mobileStartIndex, mobileStartIndex + MOBILE_DATES_SHOWN);
  };

  const handleMobileDateNav = (direction) => {
    if (direction === 'next') {
      if (mobileStartIndex + MOBILE_DATES_SHOWN < validSchedules.length) {
        setMobileStartIndex(mobileStartIndex + 1);
      }
    } else {
      if (mobileStartIndex > 0) {
        setMobileStartIndex(mobileStartIndex - 1);
      }
    }
  };


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

  useEffect(() => {
    const handleKeyPress = (e) => {
      if (showImagePreview) {
        if (e.key === 'Escape') {
          setShowImagePreview(false);
        } else if (e.key === 'ArrowLeft') {
          handlePreviewNavigation('prev');
        } else if (e.key === 'ArrowRight') {
          handlePreviewNavigation('next');
        }
      }
    };

    window.addEventListener('keydown', handleKeyPress);
    return () => window.removeEventListener('keydown', handleKeyPress);
  }, [showImagePreview, previewImageIndex]);

  // Update date when schedules change (misalnya saat ganti minggu)
  useEffect(() => {
    if (validSchedules.length > 0 && !selectedDate) {
      const firstAvailableDate = validSchedules.find(s => !s.is_past);
      const newDate = firstAvailableDate?.date || validSchedules[0]?.date;
      console.log('📅 Setting date from schedules:', newDate);
      if (newDate) {
        setSelectedDate(newDate);
      }
    }
  }, [validSchedules.length]);

  // Fetch time slots on mount and when date/venue changes
  useEffect(() => {
    console.log('useEffect triggered:', { selectedDate, venueType: venue?.venue_type });
    if (selectedDate && venue?.venue_type) {
      fetchTimeSlots();
    }
  }, [selectedDate, venue?.venue_type]);

  useEffect(() => {
    setMobileStartIndex(0);
  }, [weekOffset]);

  // ✅ DITAMBAHKAN: Close date picker when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (showDatePicker && !event.target.closest('.date-picker-container')) {
        setShowDatePicker(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [showDatePicker]);

  const fetchTimeSlots = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `/api/booking/time-slots?date=${selectedDate}&venue_type=${venue.venue_type}`
      );
      const data = await response.json();
      if (data.success) {
        setTimeSlots(data.time_slots || []);
      }
    } catch (error) {
      console.error("Error fetching time slots:", error);
      setTimeSlots([]);
    } finally {
      setLoading(false);
    }
  };

  const handleTimeSlotToggle = (slot) => {
    if (slot.status === "booked") return;

    const isSelected = selectedTimeSlots.some((s) => s.time === slot.time);
    if (isSelected) {
      setSelectedTimeSlots(selectedTimeSlots.filter((s) => s.time !== slot.time));
    } else {
      setSelectedTimeSlots([...selectedTimeSlots, {
        time: slot.time,
        duration: slot.duration,
        price: slot.price
      }]);
    }
  };

  const calculateTotal = () => {
    return selectedTimeSlots.reduce((total, slot) => total + slot.price, 0);
  };

  const handleWeekChange = (direction) => {
    const newWeek = direction === 'next' ? weekOffset + 1 : weekOffset - 1;

    if (newWeek < 0) return;

    setSelectedTimeSlots([]);

    router.visit(`/booking?venue=${venue.venue_type}&week=${newWeek}`, {
      preserveScroll: false,
      preserveState: false,
      replace: false,
    });
  };

  // ✅ DITAMBAHKAN: Handle jump ke minggu tertentu
  const handleJumpToWeek = (weekNumber) => {
    if (weekNumber < 0) return;

    setSelectedTimeSlots([]);
    setShowDatePicker(false);

    router.visit(`/booking?venue=${venue.venue_type}&week=${weekNumber}`, {
      preserveScroll: false,
      preserveState: false,
      replace: false,
    });
  };

  // ✅ DITAMBAHKAN: Generate list minggu untuk dropdown
  const getWeeksInMonth = () => {
    const weeks = [];
    // Generate 12 minggu ke depan (sekitar 3 bulan)
    for (let i = 0; i <= 12; i++) {
      const date = new Date();
      date.setDate(date.getDate() + (i * 7));
      const endDate = new Date(date);
      endDate.setDate(endDate.getDate() + 6);

      weeks.push({
        weekNumber: i,
        label: i === 0 ? 'Minggu Ini' : `Minggu +${i}`,
        dateRange: `${date.getDate()} - ${endDate.getDate()} ${date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })}`
      });
    }
    return weeks;
  };

  // ✅ DITAMBAHKAN: Get current month and year for display
  const getCurrentMonthYear = () => {
    const date = new Date();
    date.setDate(date.getDate() + (weekOffset * 7));
    return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
  };

  const handleBooking = () => {
    if (selectedTimeSlots.length === 0) {
      alert("Silakan pilih minimal 1 slot waktu");
      return;
    }

    if (!auth?.client) {
      setNotification({
        type: 'error',
        message: 'Silakan login terlebih dahulu untuk melakukan booking'
      });
      setTimeout(() => {
        router.visit("/login");
      }, 1500);
      return;
    }

    const selectedSchedule = validSchedules.find(s => s.date === selectedDate);
    if (selectedSchedule?.is_past) {
      alert("Tidak dapat booking untuk tanggal yang sudah lewat");
      return;
    }

    setShowConfirmModal(true);
  };

  const confirmBooking = async () => {
  setIsProcessing(true);

  try {
    const response = await fetch("/api/booking/process", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        venue_id: venue?.id,
        date: selectedDate,
        time_slots: selectedTimeSlots,
        venue_type: venue.venue_type,
      }),
    });

    const data = await response.json();

    if (data.success) {
      setShowConfirmModal(false);
      setShowSuccessModal(true);
      fetchTimeSlots();
    } else {
      // ✅ Close modal terlebih dahulu
      setShowConfirmModal(false);
      
      // ✅ Tampilkan error sebagai notification popup (sama seperti notif login)
      setNotification({
        type: 'error',
        message: data.message || "Terjadi kesalahan saat melakukan booking"
      });
      
      // ✅ Auto dismiss after 5 seconds
      setTimeout(() => setNotification(null), 5000);
      
      // ✅ Refresh time slots untuk update status booking
      fetchTimeSlots();
    }
  } catch (error) {
    console.error("Booking error:", error);
    
    setShowConfirmModal(false);
    
    // ✅ Tampilkan error sebagai notification popup
    setNotification({
      type: 'error',
      message: "Terjadi kesalahan saat melakukan booking. Silakan coba lagi."
    });
    
    setTimeout(() => setNotification(null), 5000);
  } finally {
    setIsProcessing(false);
  }
};


  const handleSuccessClose = () => {
    setShowSuccessModal(false);
    setSelectedTimeSlots([]);
    router.visit("/profile");
  };

  const selectedSchedule = validSchedules.find((s) => s.date === selectedDate) || null;

  if (!venue) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#013064]">
        <p className="text-white text-xl">Loading...</p>
      </div>
    );
  }

  return (
    <>
      <Head title={`Booking - ${venue.name}`} />
      <style>{`
  @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
  * {
    font-family: 'Montserrat', sans-serif;
  }
  
/* ✅ TAMBAHKAN ANIMASI INI di dalam <style> */
@keyframes zoomIn {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-zoom-in {
  animation: zoomIn 0.3s ease-out;
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
    @keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-slide-up {
  animation: slideUp 0.6s ease-out forwards;
  opacity: 0;
}

.animate-slide-in-left {
  animation: slideInLeft 0.8s ease-out forwards;
}

.animate-slide-in-right {
  animation: slideInRight 0.8s ease-out forwards;
  opacity: 0;
}

.animate-fade-in {
  animation: fadeIn 0.6s ease-out;
}

.animate-bounce-in {
  animation: bounceIn 0.5s ease-out;
}

.hover\:scale-102:hover {
  transform: scale(1.02);
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
        <Navigation activePage="booking" />

        {/* Notification Popup - Elegant Design */}
        {notification && (
          <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4">
            {/* Backdrop */}
            <div
              className="absolute inset-0 bg-[#013064]/80 backdrop-blur-sm"
              onClick={() => setNotification(null)}
            />

            {/* Popup */}
            <div className="relative bg-white max-w-md w-full animate-slide-down shadow-2xl">
              <div className="border-t-4 border-[#ffd22f]">
                {/* Header */}
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

                {/* Content */}
                <div className="p-6 bg-white">
                  <p className="text-[#013064] text-base leading-relaxed">
                    {notification.message}
                  </p>
                </div>

                {/* Progress Bar */}
                <div className="h-1 bg-gray-200 overflow-hidden">
                  <div className="h-full bg-[#ffd22f] animate-progress" />
                </div>
              </div>
            </div>
          </div>
        )}

        <main className="flex-1">
          <div className="bg-[#013064] py-6 px-4">
            <div className="max-w-7xl mx-auto">
              <div className="flex items-start gap-4 mb-4">
                <button
                  onClick={() => router.visit("/")}
                  className="flex items-center gap-2 text-white hover:text-[#ffd22f] transition flex-shrink-0 mt-1"
                >
                  <div className="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                    <ArrowLeft className="w-6 h-6 text-[#013064]" />
                  </div>
                </button>
                <h1 className="text-3xl md:text-4xl lg:text-5xl font-bold text-white">
                  {venue.name}
                </h1>
              </div>

              <div className="mb-8 animate-fade-in">
                <h2 className="text-2xl font-bold text-white mb-6">Pilihan Lapangan The Arena</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {Object.values(venues).map((v, index) => {
                    const isSelected = venue.venue_type === v.venue_type;

                    return (
                      <button
                        key={v.venue_type}
                        onClick={() => {
                          setSelectedTimeSlots([]);
                          router.visit(`/booking?venue=${v.venue_type}&week=${weekOffset}`, {
                            preserveScroll: true,
                          });
                        }}
                        style={{ animationDelay: `${index * 100}ms` }}
                        className={`p-6 rounded-lg relative transition-all duration-300 animate-slide-up min-h-[140px] ${isSelected
                            ? 'bg-[#ffd22f] border-2 border-[#ffd22f] shadow-lg'
                            : 'bg-white border-2 border-white hover:border-[#ffd22f] hover:shadow-md hover:-translate-y-1'
                          }`}
                      >
                        {isSelected && (
                          <div className="absolute top-4 right-4 w-8 h-8 bg-[#013064] rounded-full flex items-center justify-center shadow-md animate-bounce-in">
                            <Check className="w-5 h-5 text-white" />
                          </div>
                        )}

                        <div className="text-left pr-10">
                          <p className={`text-xs uppercase tracking-wide mb-3 font-medium ${isSelected ? 'text-[#013064]/70' : 'text-gray-500'
                            }`}>
                            Lapangan
                          </p>

                          <h3 className={`text-xl font-bold mb-4 ${isSelected ? 'text-[#013064]' : 'text-gray-900'
                            }`}>
                            {v.name.replace('The Arena Basketball ', '')}
                          </h3>

                          <div className="flex items-center gap-2">
                            <MapPin className={`w-4 h-4 flex-shrink-0 ${isSelected ? 'text-[#013064]/60' : 'text-gray-500'
                              }`} />
                            <p className={`text-sm ${isSelected ? 'text-[#013064]/80' : 'text-gray-600'
                              }`}>
                              {v.location}
                            </p>
                          </div>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* ✅ GANTI SELURUH SECTION INI */}
              <div className="hidden md:flex flex-row gap-4 justify-center items-start mt-8">
                <div className="flex-shrink-0 w-[550px] animate-slide-in-left">
                  <div
                    className="aspect-square rounded-lg overflow-hidden group cursor-pointer relative"
                    onClick={() => openImagePreview(0)}
                  >
                    <img
                      src={venue.images?.[0] || '/placeholder.jpg'}
                      alt={`${venue.name} - Main`}
                      className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                    />
                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 flex items-center justify-center">
                      <span className="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 font-semibold text-lg">
                        Klik untuk melihat detail
                      </span>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4 w-[550px] h-[550px]">
                  {venue.images?.slice(1, 5).map((img, idx) => (
                    <div
                      key={idx}
                      className="w-full h-full rounded-lg overflow-hidden group cursor-pointer animate-slide-in-right relative"
                      style={{ animationDelay: `${idx * 100}ms` }}
                      onClick={() => openImagePreview(idx + 1)}
                    >
                      <img
                        src={img}
                        alt={`${venue.name} - ${idx + 2}`}
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                      />
                      <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 flex items-center justify-center">
                        <span className="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 font-semibold text-sm">
                          Lihat detail
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>


              {/* Mobile Slider */}
              <div className="md:hidden animate-fade-in-up">
                <div className="relative slider-container">
                  {/* Image Container */}
                  <div
                    className="aspect-square overflow-hidden rounded-lg relative cursor-pointer"
                    onClick={() => openImagePreview(currentImageIndex)}
                  >
                    <div
                      className="flex transition-transform duration-500 ease-out h-full"
                      style={{ transform: `translateX(-${currentImageIndex * 100}%)` }}
                    >
                      {venue.images?.slice(0, 5).map((img, idx) => (
                        <div key={idx} className="w-full h-full flex-shrink-0">
                          <img
                            src={img}
                            alt={`${venue.name} - ${idx + 1}`}
                            className="w-full h-full object-cover"
                          />
                        </div>
                      ))}
                    </div>

                    {/* Navigation Arrows */}
                    {currentImageIndex > 0 && (
                      <button
                        onClick={(e) => {
                          e.stopPropagation(); // ✅ TAMBAHKAN INI biar gak trigger preview
                          setCurrentImageIndex(prev => prev - 1);
                        }}
                        className="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-white transition animate-fade-in z-10"
                      >
                        <ChevronLeft className="w-6 h-6 text-[#013064]" />
                      </button>
                    )}

                    {currentImageIndex < (venue.images?.length - 1 || 0) && (
                      <button
                        onClick={(e) => {
                          e.stopPropagation(); // ✅ TAMBAHKAN INI biar gak trigger preview
                          setCurrentImageIndex(prev => prev + 1);
                        }}
                        className="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-white transition animate-fade-in z-10"
                      >
                        <ChevronRight className="w-6 h-6 text-[#013064]" />
                      </button>
                    )}

                    {/* Image Counter */}
                    <div className="absolute bottom-4 right-4 bg-[#013064]/80 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-sm font-semibold">
                      {currentImageIndex + 1} / {venue.images?.length || 0}
                    </div>

                    {/* ✅ TAMBAHKAN Hint Text */}
                    <div className="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs font-semibold text-[#013064]">
                      Tap untuk zoom
                    </div>
                  </div>

                  {/* Dots Indicator */}
                  <div className="flex justify-center gap-2 mt-4">
                    {venue.images?.slice(0, 5).map((_, idx) => (
                      <button
                        key={idx}
                        onClick={() => setCurrentImageIndex(idx)}
                        className={`transition-all duration-300 rounded-full ${idx === currentImageIndex
                          ? 'w-8 h-2 bg-[#ffd22f]'
                          : 'w-2 h-2 bg-white/40 hover:bg-white/60'
                          }`}
                      />
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-[#013064]">
            <div className="max-w-7xl mx-auto px-4 py-12">
              <div className="grid lg:grid-cols-3 gap-8">
                <div className="lg:col-span-2 space-y-8">
                  <div>
                    <h2 className="text-2xl font-bold text-white mb-4">Deskripsi</h2>
                    <p className="text-white mb-4">{venue.description}</p>
                    <p className="text-white mb-4">{venue.full_description}</p>
                    <p className="text-white">{venue.invitation}</p>
                  </div>

                  <div>
                    <h2 className="text-2xl font-bold text-white mb-4">Aturan Venue</h2>
                    <p className="text-white mb-4">Peraturan lapangan di {venue.name}:</p>
                    <ul className="space-y-2 mb-6">
                      {venue.rules?.map((rule, idx) => (
                        <li key={idx} className="flex gap-2 text-white">
                          <span className="text-[#ffd22f]">-</span>
                          <span>{rule}</span>
                        </li>
                      ))}
                    </ul>

                    {/* Note Section */}
                    {venue.note && (
                      <div className="bg-[#ffd22f]/10 border-l-4 border-[#ffd22f] p-4 rounded-lg">
                        <p className="text-white text-sm leading-relaxed">
                          <span className="font-bold text-[#ffd22f]">Catatan Penting: </span>
                          <span className="italic">{venue.note}</span>
                        </p>
                      </div>
                    )}
                  </div>

                  {/* Fasilitas Termasuk */}
                  <div>
                    <h2 className="text-2xl font-bold text-white mb-4">Fasilitas</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                      {venue.facilities
                        ?.filter(facility =>
                          !['Scoreboard', 'Shotclock', 'Sound System', 'Score Board', 'Shot Clock'].includes(facility)
                        )
                        .map((facility, idx) => (
                          <div
                            key={idx}
                            className="flex items-center gap-3 px-4 py-3 border-2 border-white text-white rounded-lg hover:bg-white/10 transition"
                          >
                            <span className="text-sm font-medium">{facility}</span>
                          </div>
                        ))}
                    </div>
                  </div>

                  {/* Add-ons Section */}
                  <div>
                    <h2 className="text-2xl font-bold text-white mb-2">Add-ons</h2>
                    <p className="text-white/70 text-sm mb-4">Peralatan tambahan yang tidak termasuk dalam paket standar</p>

                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                      {[
                        { name: "Score Board", status: "not_included" },
                        { name: "Shot Clock", status: "not_included" },
                        { name: "Sound System", status: "not_included" }
                      ].map((addon, idx) => (
                        <div
                          key={idx}
                          className="flex items-center gap-3 px-4 py-3 border-2 border-white/30 bg-white/5 text-white rounded-lg"
                        >

                          <div className="flex-1">
                            <span className="text-sm font-medium block">{addon.name}</span>
                            <span className="text-xs text-white/50">Not Included</span>
                          </div>
                        </div>
                      ))}
                    </div>

                    {/* Info Note */}
                    <div className="mt-4 bg-white/10 border-l-4 border-[#ffd22f] p-4 rounded-lg">
                      <p className="text-white text-sm leading-relaxed">
                        <span className="font-bold text-[#ffd22f]">Catatan: </span>
                        <span className="italic">
                          Untuk menggunakan Score Board, Shot Clock, atau Sound System, silakan hubungi admin untuk informasi ketersediaan dan biaya tambahan.
                        </span>
                      </p>
                    </div>
                  </div>
                  {validSchedules.length > 0 && (
                    <>


                      {/* ✅ SECTION PILIH TANGGAL - DIUBAH */}
                      <div>
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3">
                          <h2 className="text-xl sm:text-2xl font-bold text-white">Pilih Tanggal</h2>
                          <div className="flex items-center gap-2">
                            <button
                              onClick={() => handleWeekChange('prev')}
                              disabled={weekOffset === 0}
                              className={`p-2 rounded-full transition ${weekOffset === 0
                                ? 'bg-white/20 cursor-not-allowed'
                                : 'bg-white hover:bg-[#ffd22f]'
                                }`}
                            >
                              <ChevronLeft className={`w-4 h-4 sm:w-5 sm:h-5 ${weekOffset === 0 ? 'text-white/50' : 'text-[#013064]'}`} />
                            </button>

                            {/* Date Picker Button */}
                            <div className="relative date-picker-container">
                              <button
                                onClick={() => setShowDatePicker(!showDatePicker)}
                                className="px-3 sm:px-4 py-2 bg-white hover:bg-[#ffd22f] rounded-full transition flex items-center gap-2"
                              >
                                <Calendar className="w-4 h-4 text-[#013064]" />
                                <span className="text-[#013064] font-semibold text-sm sm:text-base">
                                  {getCurrentMonthYear()}
                                </span>
                              </button>

                              {/* Dropdown Date Picker */}
                              {showDatePicker && (
                                <div className="absolute top-full mt-2 right-0 bg-white rounded-lg shadow-xl border-2 border-[#013064] z-50 w-64 max-h-80 overflow-y-auto">
                                  <div className="p-2">
                                    <div className="bg-[#013064] text-white px-3 py-2 rounded-t-lg font-bold text-sm mb-2">
                                      Pilih Minggu
                                    </div>
                                    {getWeeksInMonth().map((week) => (
                                      <button
                                        key={week.weekNumber}
                                        onClick={() => handleJumpToWeek(week.weekNumber)}
                                        className={`w-full text-left px-4 py-3 rounded-lg transition hover:bg-[#ffd22f]/20 ${weekOffset === week.weekNumber
                                          ? 'bg-[#ffd22f] text-[#013064] font-bold'
                                          : 'text-[#013064]'
                                          }`}
                                      >
                                        <div className="flex justify-between items-center">
                                          <span className="font-semibold text-xs">{week.label}</span>
                                          <span className="text-xs opacity-70">{week.dateRange}</span>
                                        </div>
                                      </button>
                                    ))}
                                  </div>
                                </div>
                              )}
                            </div>

                            <button
                              onClick={() => handleWeekChange('next')}
                              className="p-2 rounded-full bg-white hover:bg-[#ffd22f] transition"
                            >
                              <ChevronRight className="w-4 h-4 sm:w-5 sm:h-5 text-[#013064]" />
                            </button>
                          </div>
                        </div>

                        {/* Date Selector dengan Navigation untuk Mobile */}
                        <div className="relative">
                          {/* Mobile Navigation - Tampil hanya di mobile */}
                          <div className="sm:hidden">
                            {mobileStartIndex > 0 && (
                              <button
                                onClick={() => handleMobileDateNav('prev')}
                                className="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-lg"
                              >
                                <ChevronLeft className="w-5 h-5 text-[#013064]" />
                              </button>
                            )}

                            {mobileStartIndex + MOBILE_DATES_SHOWN < validSchedules.length && (
                              <button
                                onClick={() => handleMobileDateNav('next')}
                                className="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-lg"
                              >
                                <ChevronRight className="w-5 h-5 text-[#013064]" />
                              </button>
                            )}
                          </div>

                          {/* Date Buttons */}
                          <div className="flex gap-2 sm:gap-3 overflow-hidden sm:overflow-x-auto pb-4 px-10 sm:px-0">
                            {getVisibleDates().map((schedule) => (
                              <button
                                key={schedule.date}
                                onClick={() => !schedule.is_past && setSelectedDate(schedule.date)}
                                disabled={schedule.is_past}
                                className={`flex-shrink-0 flex flex-col items-center justify-center w-16 h-16 sm:w-24 sm:h-24 rounded-none transition ${schedule.is_past
                                  ? 'bg-gray-400 cursor-not-allowed opacity-50'
                                  : selectedDate === schedule.date
                                    ? "bg-[#ffd22f] text-[#013064]"
                                    : "bg-white text-[#013064] hover:bg-[#ffd22f]"
                                  }`}
                              >
                                <span className="text-[10px] sm:text-xs mb-1">{schedule.day_name}</span>
                                <span className="text-xl sm:text-3xl font-bold">{schedule.date_number}</span>
                              </button>
                            ))}
                          </div>

                          {/* Mobile Indicator - Tampil hanya di mobile */}
                          <div className="sm:hidden flex justify-center gap-1 mt-2">
                            {Array.from({ length: Math.ceil(validSchedules.length / MOBILE_DATES_SHOWN) }).map((_, idx) => (
                              <div
                                key={idx}
                                className={`h-1 rounded-full transition-all ${idx === Math.floor(mobileStartIndex / MOBILE_DATES_SHOWN)
                                  ? 'w-6 bg-[#ffd22f]'
                                  : 'w-1 bg-white/30'
                                  }`}
                              />
                            ))}
                          </div>
                        </div>
                      </div>

                      <div>
                        <h2 className="text-2xl font-bold text-white mb-4">Pilih Jadwal Lapangan</h2>

                        {loading ? (
                          <div className="text-center py-8">
                            <div className="inline-block w-8 h-8 border-4 border-white border-t-transparent rounded-full animate-spin"></div>
                            <p className="mt-4 text-white">Memuat jadwal...</p>
                          </div>
                        ) : (
                          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            {timeSlots.map((slot, idx) => {
                              const isSelected = selectedTimeSlots.some((s) => s.time === slot.time);
                              const isBooked = slot.status === "booked";

                              return (
                                <button
                                  key={idx}
                                  onClick={() => handleTimeSlotToggle(slot)}
                                  disabled={isBooked}
                                  className={`p-4 rounded-none transition relative ${isBooked
                                    ? "bg-[#8B9BB3] border-2 border-[#8B9BB3] cursor-not-allowed"
                                    : isSelected
                                      ? "bg-[#ffd22f] border-2 border-[#ffd22f]"
                                      : "bg-white border-2 border-white hover:border-[#ffd22f]"
                                    }`}
                                >
                                  {isSelected && !isBooked && (
                                    <div className="absolute top-2 right-2 w-6 h-6 bg-[#013064] rounded-full flex items-center justify-center">
                                      <Check className="w-4 h-4 text-white" />
                                    </div>
                                  )}
                                  <div className="text-center">
                                    <p className={`text-xs mb-1 ${isBooked ? 'text-white/70' : 'text-[#013064]/70'}`}>
                                      {slot.duration} Menit
                                    </p>
                                    <p className={`text-lg font-bold mb-2 ${isBooked ? 'text-white/90' : 'text-[#013064]'}`}>
                                      {slot.time}
                                    </p>
                                    {isBooked ? (
                                      <p className="text-sm font-semibold text-white/90">Booked</p>
                                    ) : (
                                     <p className="text-sm font-semibold text-[#013064] notranslate">
  Rp. {slot.price.toLocaleString("id-ID")}
</p>
                                    )}
                                  </div>
                                </button>
                              );
                            })}
                          </div>
                        )}
                      </div>
                      {/* Note Harga Member */}
                      <div className="mb-4 bg-[#ffd22f]/10 border-l-4 border-[#ffd22f] p-4 rounded-lg">
                        <p className="text-white text-sm leading-relaxed">
                          <span className="font-bold text-[#ffd22f]">Info: </span>
                          <span>Harga yang ditampilkan adalah harga reguler. Member The Arena mendapatkan harga spesial lebih murah untuk setiap sesi booking.</span>
                        </p>
                      </div>
                    </>
                  )}
                </div>

                <div className="lg:col-span-1">
                  <div className="bg-[#013064] text-white p-4 sm:p-6 rounded-lg border-2 border-white/30 lg:sticky lg:top-4">
                    <h3 className="text-xl sm:text-2xl font-bold mb-4 sm:mb-6">
                      Rp. {venue.price_per_session?.toLocaleString("id-ID") || 0}/sesi
                    </h3>

                    <div className="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                      <div className="flex items-center gap-3 p-3 bg-white/10 rounded">
                        <Calendar className="w-4 h-4 sm:w-5 sm:h-5 text-[#ffd22f] flex-shrink-0" />
                        <div className="min-w-0">
                          <p className="text-xs text-white/70">Tanggal</p>
                          <p className="font-semibold text-sm sm:text-base truncate">
                            {selectedSchedule?.display_date || "Pilih tanggal"}
                          </p>
                        </div>
                      </div>
                      <div className="flex items-center gap-3 p-3 bg-white/10 rounded">
                        <MapPin className="w-4 h-4 sm:w-5 sm:h-5 text-[#ffd22f] flex-shrink-0" />
                        <div className="min-w-0">
                          <p className="text-xs text-white/70">Venue</p>
                          <p className="font-semibold text-sm sm:text-base truncate">
                            {venue.name}
                          </p>
                        </div>
                      </div>


                      {selectedTimeSlots.length > 0 && (
                        <div className="space-y-2">
                          {selectedTimeSlots.map((slot, idx) => (
                            <div key={idx} className="flex items-center gap-2 sm:gap-3 p-3 bg-white/10 rounded">
                              <Clock className="w-4 h-4 sm:w-5 sm:h-5 text-[#ffd22f] flex-shrink-0" />
                              <div className="flex-1 min-w-0">
                                <p className="text-xs text-white/70">Waktu</p>
                                <p className="font-semibold text-sm sm:text-base">{slot.time}</p>
                              </div>
                              <button
                                onClick={() => handleTimeSlotToggle(slot)}
                                className="text-red-400 hover:text-red-300 text-xs flex-shrink-0"
                              >
                                Hapus
                              </button>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>

                    <div className="border-t border-white/20 pt-4 mb-4 sm:mb-6">
                      <div className="flex justify-between items-center mb-2">
                        <span className="text-[#ffd22f] text-sm sm:text-base">Total Pembayaran</span>
                        <span className="text-xl sm:text-2xl font-bold notranslate">
  Rp. {calculateTotal().toLocaleString("id-ID")}
</span>
                      </div>
                    </div>

                    <button
                      onClick={handleBooking}
                      disabled={selectedTimeSlots.length === 0}
                      className={`w-full py-3 rounded-lg font-bold text-base sm:text-lg transition ${selectedTimeSlots.length === 0
                        ? "bg-white/20 cursor-not-allowed"
                        : "bg-[#ffd22f] text-[#013064] hover:bg-[#ffe066]"
                        }`}
                    >
                      Booking
                    </button>

                    {selectedTimeSlots.length === 0 && (
                      <p className="text-xs text-white/70 mt-3 text-center">
                        Pilih minimal 1 slot waktu untuk melanjutkan
                      </p>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* SECTION ULASAN PELANGGAN */}
          <div id="ulasan" className="bg-[#013064] py-12">
            <div className="max-w-7xl mx-auto px-4">
              <div className="flex justify-between items-center mb-8">
                <h2 className="text-3xl font-bold text-white">Ulasan Pelanggan</h2>
                <button
                  onClick={handleOpenReviewModal}
                  className="bg-[#ffd22f] text-[#013064] px-6 py-3 rounded-lg font-bold hover:bg-[#ffe066] transition"
                >
                  Tulis Ulasan
                </button>
              </div>

              {reviewsList.length === 0 ? (
                <div className="text-center py-12">
                  <p className="text-white/70 text-lg">Belum ada ulasan. Jadilah yang pertama memberikan ulasan!</p>
                </div>
              ) : (
                <div className="grid md:grid-cols-2 gap-6">
                  {reviewsList.map((review) => (
                    <div key={review.id} className="bg-white/10 backdrop-blur-sm p-6 rounded-lg border border-white/20">
                      <div className="flex items-start gap-4 mb-5">
                        {review.client_profile_image ? (
                          <img
                            src={`/storage/${review.client_profile_image}`}
                            alt={review.client_name}
                            className="w-14 h-14 rounded-full object-cover flex-shrink-0 ring-2 ring-[#ffd22f]"
                            onError={(e) => {
                              e.target.style.display = 'none';
                              e.target.nextElementSibling.style.display = 'flex';
                            }}
                          />
                        ) : null}
                        <div
                          className="w-14 h-14 rounded-full bg-[#ffd22f] flex items-center justify-center flex-shrink-0"
                          style={{ display: review.client_profile_image ? 'none' : 'flex' }}
                        >
                          <span className="text-[#013064] font-bold text-xl">
                            {review.client_name.charAt(0).toUpperCase()}
                          </span>
                        </div>

                        <div className="flex-1 min-w-0">
                          <p className="text-white font-bold text-lg mb-1">{review.client_name}</p>
                          <span className="text-white/50 text-sm">{review.created_at}</span>
                        </div>
                      </div>

                      <div className="space-y-3 mb-5 bg-white/5 rounded-lg p-4">
                        <div className="flex items-center justify-between">
                          <span className="text-white font-semibold text-sm min-w-[90px]">Fasilitas</span>
                          <div className="flex gap-1">
                            {[...Array(5)].map((_, i) => (
                              <span
                                key={i}
                                className={`text-xl ${i < review.rating_facilities ? 'text-[#ffd22f]' : 'text-white/20'}`}
                              >
                                ★
                              </span>
                            ))}
                          </div>
                        </div>

                        <div className="flex items-center justify-between">
                          <span className="text-white font-semibold text-sm min-w-[90px]">Keramahan</span>
                          <div className="flex gap-1">
                            {[...Array(5)].map((_, i) => (
                              <span
                                key={i}
                                className={`text-xl ${i < review.rating_hospitality ? 'text-[#ffd22f]' : 'text-white/20'}`}
                              >
                                ★
                              </span>
                            ))}
                          </div>
                        </div>

                        <div className="flex items-center justify-between">
                          <span className="text-white font-semibold text-sm min-w-[90px]">Kebersihan</span>
                          <div className="flex gap-1">
                            {[...Array(5)].map((_, i) => (
                              <span
                                key={i}
                                className={`text-xl ${i < review.rating_cleanliness ? 'text-[#ffd22f]' : 'text-white/20'}`}
                              >
                                ★
                              </span>
                            ))}
                          </div>
                        </div>
                      </div>

                      <div className="border-t border-white/10 pt-4">
                        <p className="text-white/90 leading-relaxed text-sm">
                          {review.comment}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </main>

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

      {/* Modal Konfirmasi Booking */}
      {showConfirmModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6 shadow-xl">
            <h3 className="text-2xl font-bold text-[#013064] mb-4">Konfirmasi Booking</h3>

            <div className="space-y-3 mb-6">
              <div className="flex items-center gap-2 text-[#013064]">
                <Calendar className="w-5 h-5 text-[#ffd22f]" />
                <div>
                  <p className="text-xs text-gray-600">Tanggal</p>
                  <p className="font-semibold">{selectedSchedule?.display_date}</p>
                </div>
              </div>
              <div className="flex items-center gap-2 text-[#013064]">
                <MapPin className="w-5 h-5 text-[#ffd22f]" />
                <div>
                  <p className="text-xs text-gray-600">Venue</p>
                  <p className="font-semibold">{venue.name}</p>
                </div>
              </div>
              <div className="border-t pt-3">
                <p className="text-xs text-gray-600 mb-2">Slot Waktu:</p>
                {selectedTimeSlots.map((slot, idx) => (
                  <div key={idx} className="flex justify-between items-center py-2 px-3 bg-gray-50 rounded mb-2">
                    <div className="flex items-center gap-2">
                      <Clock className="w-4 h-4 text-[#ffd22f]" />
                      <span className="font-medium text-[#013064]">{slot.time}</span>
                    </div>
                   <span className="text-sm font-semibold text-[#013064] notranslate">
  Rp. {slot.price.toLocaleString("id-ID")}
</span>
                  </div>
                ))}
              </div>

              <div className="border-t pt-3">
                <div className="flex justify-between items-center">
                  <span className="text-lg font-bold text-[#013064]">Total Pembayaran</span>
                  <span className="text-2xl font-bold text-[#ffd22f] notranslate">
  Rp. {calculateTotal().toLocaleString("id-ID")}
</span>
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => setShowConfirmModal(false)}
                disabled={isProcessing}
                className="flex-1 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Batal
              </button>
              <button
                onClick={confirmBooking}
                disabled={isProcessing}
                className="flex-1 py-3 bg-[#ffd22f] text-[#013064] rounded-lg font-bold hover:bg-[#ffe066] transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {isProcessing ? (
                  <>
                    <div className="w-5 h-5 border-2 border-[#013064] border-t-transparent rounded-full animate-spin"></div>
                    Memproses...
                  </>
                ) : (
                  'Konfirmasi Booking'
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Sukses */}
      {showSuccessModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-8 shadow-xl text-center">
            <div className="w-20 h-20 bg-[#ffd22f] rounded-full flex items-center justify-center mx-auto mb-6">
              <CheckCircle className="w-12 h-12 text-[#013064]" />
            </div>

            <h3 className="text-3xl font-bold text-[#013064] mb-3">Booking Dalam Prosess</h3>
            <p className="text-gray-600 mb-6">
              Lakukan Pembayaran di Halaman Profile Jadwal Booking Anda.
            </p>

            <div className="bg-[#013064]/5 rounded-lg p-4 mb-6 text-left">
              <div className="flex items-center gap-2 text-[#013064] mb-3">
                <Calendar className="w-5 h-5 text-[#ffd22f]" />
                <div>
                  <p className="text-xs text-gray-600">Tanggal</p>
                  <p className="font-semibold">{selectedSchedule?.display_date}</p>
                </div>
              </div>

              <div className="flex items-center gap-2 text-[#013064]">
                <MapPin className="w-5 h-5 text-[#ffd22f]" />
                <div>
                  <p className="text-xs text-gray-600">Venue</p>
                  <p className="font-semibold">{venue.name}</p>
                </div>
              </div>
            </div>

            <button
              onClick={handleSuccessClose}
              className="w-full py-3 bg-[#ffd22f] text-[#013064] rounded-lg font-bold hover:bg-[#ffe066] transition"
            >
              Menuju Jadwal Booking Anda
            </button>
          </div>
        </div>
      )}

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
      {/* ✅ TAMBAHKAN MODAL PREVIEW INI */}
      {showImagePreview && (
        <div
          className="fixed inset-0 bg-black/95 z-[100] flex items-center justify-center p-4"
          onClick={() => setShowImagePreview(false)}
        >
          {/* Close Button */}
          <button
            onClick={() => setShowImagePreview(false)}
            className="absolute top-4 right-4 z-10 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center transition"
          >
            <X className="w-6 h-6 text-white" />
          </button>

          {/* Image Counter */}
          <div className="absolute top-4 left-4 z-10 bg-white/10 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold">
            {previewImageIndex + 1} / {venue.images?.length || 0}
          </div>

          {/* Navigation Arrows */}
          <button
            onClick={(e) => {
              e.stopPropagation();
              handlePreviewNavigation('prev');
            }}
            className="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center transition"
          >
            <ChevronLeft className="w-6 h-6 text-white" />
          </button>

          <button
            onClick={(e) => {
              e.stopPropagation();
              handlePreviewNavigation('next');
            }}
            className="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center transition"
          >
            <ChevronRight className="w-6 h-6 text-white" />
          </button>

          {/* Main Image */}
          <div
            className="max-w-6xl max-h-[90vh] w-full h-full flex items-center justify-center animate-zoom-in"
            onClick={(e) => e.stopPropagation()}
          >
            <img
              src={venue.images?.[previewImageIndex]}
              alt={`${venue.name} - Preview ${previewImageIndex + 1}`}
              className="max-w-full max-h-full object-contain rounded-lg shadow-2xl"
            />
          </div>

          {/* Thumbnail Navigation */}
          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-2 bg-white/10 backdrop-blur-sm p-2 rounded-full max-w-[90vw] overflow-x-auto">
            {venue.images?.map((img, idx) => (
              <button
                key={idx}
                onClick={(e) => {
                  e.stopPropagation();
                  setPreviewImageIndex(idx);
                }}
                className={`flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition ${idx === previewImageIndex
                    ? 'border-[#ffd22f] scale-110'
                    : 'border-white/30 hover:border-white/60'
                  }`}
              >
                <img
                  src={img}
                  alt={`Thumbnail ${idx + 1}`}
                  className="w-full h-full object-cover"
                />
              </button>
            ))}
          </div>

          {/* Keyboard Hint */}
          <div className="absolute bottom-24 left-1/2 -translate-x-1/2 text-white/50 text-xs flex items-center gap-4">
            <span>← → untuk navigasi</span>
            <span>ESC untuk tutup</span>
          </div>
        </div>
      )}
    </>
  );
} 