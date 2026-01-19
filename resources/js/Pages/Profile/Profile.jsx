import { Head, Link, useForm, usePage, router } from "@inertiajs/react";
import { useState, useEffect, useRef } from "react"; // ✅ Tambah useRef
import { Phone, Mail, Calendar, User, MapPin, LogOut, Video, Clock, X, CreditCard, CheckCircle, AlertCircle, Star, MessageSquare } from "lucide-react";

import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";
// ✅ GANTI PaymentTimer Component (baris 7-52)
function PaymentTimer({ createdAt, onExpired, onAlert }) {
  const [timeLeft, setTimeLeft] = useState(null);
  const alertShownRef = useRef({
    fiveMin: false,
    twoMin: false,
    oneMin: false
  });

  useEffect(() => {
    const calculateTimeLeft = () => {
      const created = new Date(createdAt);
      const expiry = new Date(created.getTime() + 10 * 60 * 1000);
      const now = new Date();
      const diff = expiry - now;

      if (diff <= 0) {
        if (onExpired && typeof onExpired === 'function') {
          onExpired();
        }
        return null;
      }

      const minutes = Math.floor(diff / 60000);
      const seconds = Math.floor((diff % 60000) / 1000);
      const totalSeconds = Math.floor(diff / 1000);

      // Alert notifications dengan type checking
      if (onAlert && typeof onAlert === 'function') {
        if (totalSeconds <= 300 && !alertShownRef.current.fiveMin) {
          alertShownRef.current.fiveMin = true;
          onAlert('⚠️ Sisa 5 menit lagi! Segera selesaikan pembayaran Anda.');
        }

        if (totalSeconds <= 120 && !alertShownRef.current.twoMin) {
          alertShownRef.current.twoMin = true;
          onAlert('🚨 Sisa 2 menit lagi! Booking akan otomatis dibatalkan jika belum dibayar.');
        }

        if (totalSeconds <= 60 && !alertShownRef.current.oneMin) {
          alertShownRef.current.oneMin = true;
          onAlert('🔴 SISA 1 MENIT! Segera lakukan pembayaran sekarang!');
        }
      }

      return { minutes, seconds, total: diff };
    };

    setTimeLeft(calculateTimeLeft());

    const timer = setInterval(() => {
      setTimeLeft(calculateTimeLeft());
    }, 1000);

    return () => clearInterval(timer);
  }, [createdAt, onExpired, onAlert]);

  if (!timeLeft) return null;

  const isUrgent = timeLeft.total < 3 * 60 * 1000;

  return (
    <div className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold ${isUrgent ? 'bg-red-100 text-red-800 animate-pulse' : 'bg-orange-100 text-orange-800'
      }`}>
      <Clock className="w-3 h-3" />
      <span>
        Bayar dalam: {timeLeft.minutes}:{String(timeLeft.seconds).padStart(2, '0')}
      </span>
    </div>
  );
}
export default function Profile() {


  const { auth, upcomingBookings = [], historyBookings = {}, reviewHistory = [], flash,
    shouldShowReviewReminder, completedBookingCount } = usePage().props;

  const [showPaymentCheckModal, setShowPaymentCheckModal] = useState(false);
  const [checkingBillNo, setCheckingBillNo] = useState(null);

  const [activeTab, setActiveTab] = useState('data-profil');
  const [selectedImage, setSelectedImage] = useState(null);
  const [previewImage, setPreviewImage] = useState(null);
  const [imageFileName, setImageFileName] = useState("No file chosen");
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [showNotification, setShowNotification] = useState(false);
  const [notificationMessage, setNotificationMessage] = useState('');
  const [notificationType, setNotificationType] = useState('success');
  const [expiredBookings, setExpiredBookings] = useState(new Set()); // ✅ TAMBAH INI


  useEffect(() => {
    console.log('📊 Profile Page Props:', {
      shouldShowReviewReminder,
      completedBookingCount,
      typeOfShouldShow: typeof shouldShowReviewReminder,
      typeOfCount: typeof completedBookingCount,
      authClient: auth.client?.id
    });
  }, [shouldShowReviewReminder, completedBookingCount]);

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const checkPayment = urlParams.get('check_payment');

    if (checkPayment) {
      setCheckingBillNo(checkPayment);
      setShowPaymentCheckModal(true);

      // Auto refresh setelah 3 detik
      setTimeout(() => {
        router.reload({ only: ['upcomingBookings'] });
        setShowPaymentCheckModal(false);
      }, 3000);
    }
  }, []);


  const { data, setData, post, processing, errors } = useForm({
    name: auth.client?.name || "",
    email: auth.client?.email || "",  // ✅ TAMBAH INI
    province: auth.client?.province || "",
    city: auth.client?.city || "",
    address: auth.client?.address || "",
    phone: auth.client?.phone || "",
    gender: auth.client?.gender || "",
    birth_date: auth.client?.birth_date || "",
    profile_image: null,
  });



  // Tampilkan notifikasi jika ada flash message
  useEffect(() => {
    if (flash?.success) {
      setNotificationMessage(flash.success);
      setNotificationType('success');
      setShowNotification(true);
      setTimeout(() => setShowNotification(false), 3000);
    } else if (flash?.error) {
      setNotificationMessage(flash.error);
      setNotificationType('error');
      setShowNotification(true);
      setTimeout(() => setShowNotification(false), 3000);
    }
  }, [flash]);

  useEffect(() => {
    if (shouldShowReviewReminder && completedBookingCount > 0) {
      setNotificationMessage(
        `Anda belum menambahkan ulasan untuk ${completedBookingCount} booking sebelumnya`
      );
      setNotificationType('error');
      setShowNotification(true);

      setTimeout(() => {
        router.visit("/#ulasan");
      }, 1500);
    }
  }, [shouldShowReviewReminder, completedBookingCount]);

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setSelectedImage(file);
      setData('profile_image', file);
      setImageFileName(file.name);

      const reader = new FileReader();
      reader.onloadend = () => {
        setPreviewImage(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    // ✅ DEBUGGING
    console.log('📤 Data yang dikirim:', {
      name: data.name,
      email: data.email,
      has_image: !!data.profile_image,
      image_name: data.profile_image?.name,
    });

    post('/profile/update', {
      forceFormData: true,
      onSuccess: () => {
        console.log('✅ Update berhasil');
        setNotificationMessage('Profil berhasil diperbarui!');
        setNotificationType('success');
        setShowNotification(true);
        setTimeout(() => setShowNotification(false), 3000);

        // ✅ Reset preview image
        setSelectedImage(null);
        setPreviewImage(null);
        setImageFileName("No file chosen");
      },
      onError: (errors) => {
        console.error('❌ Update gagal:', errors);
        setNotificationMessage('Gagal memperbarui profil: ' + Object.values(errors).join(', '));
        setNotificationType('error');
        setShowNotification(true);
        setTimeout(() => setShowNotification(false), 5000);
      }
    });
  };

  const handleCancelBooking = (booking) => {
    setSelectedBooking(booking);
    setShowCancelModal(true);
  };

  const confirmCancelBooking = () => {
    if (selectedBooking) {
      router.post(`/profile/booking/${selectedBooking.id}/cancel`, {
        booking_ids: selectedBooking.booking_ids || [selectedBooking.id]
      }, {
        onSuccess: () => {
          setShowCancelModal(false);
          setSelectedBooking(null);
        },
        onError: (errors) => {
          console.error('Cancellation failed:', errors);
          setShowCancelModal(false);
          setSelectedBooking(null);
        }
      });
    }
  };

  const handleLogout = () => {
    setShowLogoutModal(true);
  };

  const confirmLogout = () => {
    router.post('/logout');
  };

  const getStatusBadge = (color) => {
    const colors = {
      yellow: 'bg-yellow-500',
      green: 'bg-green-600',
      blue: 'bg-blue-600',
      red: 'bg-red-600',
    };
    return colors[color] || 'bg-gray-500';
  };

  const handleBookingExpired = (bookingId) => {
    setExpiredBookings(prev => new Set([...prev, bookingId]));
    setTimeout(() => {
      router.reload();
    }, 2000);
  };
  const handlePaymentAlert = (message) => {
    setNotificationMessage(message);
    setNotificationType('error'); // Merah untuk urgent
    setShowNotification(true);
    setTimeout(() => setShowNotification(false), 5000); // 5 detik
  };

  return (
    <>
      <Head title="THE ARENA - Profile" />
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
        @keyframes slide-in {
          from {
            transform: translateX(100%);
            opacity: 0;
          }
          to {
            transform: translateX(0);
            opacity: 1;
          }
        }
        .animate-slide-in {
          animation: slide-in 0.3s ease-out;
        }
          @keyframes slide-in {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
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

.animate-slide-in {
  animation: slide-in 0.3s ease-out;
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
      {/* Notification Toast - HARUS ADA INI! */}
      {showNotification && (
        <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4">
          <div
            className="absolute inset-0 bg-[#013064]/80 backdrop-blur-sm"
            onClick={() => setShowNotification(false)}
          />

          <div className="relative bg-white max-w-md w-full animate-slide-in shadow-2xl">
            <div className={`border-t-4 ${notificationType === 'success' ? 'border-green-500' : 'border-red-500'}`}>
              <div className="bg-[#013064] px-6 py-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className={`w-2 h-2 rounded-full ${notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'}`} />
                  <h3 className="font-bold text-white text-lg">
                    {notificationType === 'success' ? 'Berhasil!' : 'Perhatian'}
                  </h3>
                </div>
                <button
                  onClick={() => setShowNotification(false)}
                  className="text-white/70 hover:text-white transition"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <div className="p-6 bg-white">
                <p className="text-[#013064] text-base leading-relaxed">
                  {notificationMessage}
                </p>
              </div>

              <div className="h-1 bg-gray-200 overflow-hidden">
                <div
                  className={`h-full ${notificationType === 'success' ? 'bg-green-500' : 'bg-red-500'}`}
                  style={{ animation: 'progress 3s linear' }}
                />
              </div>
            </div>
          </div>
        </div>
      )}
      <div className="min-h-screen flex flex-col bg-[#013064]">
        <Navigation activePage="profile" />

        <main className="flex-1 py-8 px-4">
          <div className="max-w-7xl mx-auto">
            <div className="mb-6">
              <h1 className="text-[#ffd22f] text-2xl font-bold mb-1">Profil</h1>
              <h2 className="text-white text-5xl font-bold">Halo, {auth.client?.name}</h2>
            </div>

            <div className="grid grid-cols-12 gap-6">
              {/* Sidebar */}
              <div className="col-span-12 lg:col-span-3">
                <div className="bg-[#024b8a]/40 rounded overflow-hidden">
                  <button
                    onClick={() => setActiveTab('data-profil')}
                    className={`w-full flex items-center gap-3 px-6 py-4 text-left transition ${activeTab === 'data-profil'
                      ? 'bg-[#ffd22f] text-[#013064]'
                      : 'text-white hover:bg-[#035a9e]'
                      }`}
                  >
                    <User className="w-5 h-5" />
                    <span className="font-semibold">Data Profil</span>
                  </button>

                  <button
                    onClick={() => setActiveTab('jadwal-booking')}
                    className={`w-full flex items-center gap-3 px-6 py-4 text-left transition ${activeTab === 'jadwal-booking'
                      ? 'bg-[#ffd22f] text-[#013064]'
                      : 'text-white hover:bg-[#035a9e]'
                      }`}
                  >
                    <Calendar className="w-5 h-5" />
                    <span className="font-semibold">Jadwal Booking</span>
                  </button>

                  <button
                    onClick={() => setActiveTab('history')}
                    className={`w-full flex items-center gap-3 px-6 py-4 text-left transition ${activeTab === 'history'
                      ? 'bg-[#ffd22f] text-[#013064]'
                      : 'text-white hover:bg-[#035a9e]'
                      }`}
                  >
                    <Video className="w-5 h-5" />
                    <span className="font-semibold">History</span>
                  </button>
                  <button
                    onClick={() => setActiveTab('history-ulasan')}
                    className={`w-full flex items-center gap-3 px-6 py-4 text-left transition ${activeTab === 'history-ulasan'
                      ? 'bg-[#ffd22f] text-[#013064]'
                      : 'text-white hover:bg-[#035a9e]'
                      }`}
                  >
                    <Star className="w-5 h-5" />
                    <span className="font-semibold">History Ulasan</span>
                  </button>

                  <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-3 px-6 py-4 text-left text-white hover:bg-[#035a9e] transition"
                  >
                    <LogOut className="w-5 h-5" />
                    <span className="font-semibold">Keluar Akun</span>
                  </button>
                </div>
              </div>


              {/* Main Content */}

              <div className="col-span-12 lg:col-span-9">
                {activeTab === 'data-profil' && (
                  <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Profile Image Section */}
                    <div className="flex items-start gap-6 mb-8">
                      <div className="flex-shrink-0">
                        <img
                          src={previewImage || (auth.client?.profile_image ? `/storage/${auth.client.profile_image}` : "/images/default-avatar.jpg")}
                          alt="Profile"
                          className="w-44 h-44 rounded object-cover border-4 border-white/20"
                        />
                      </div>
                      <div className="flex-1 pt-4">
                        <p className="text-gray-300 text-sm mb-3">{imageFileName}</p>
                        <label className="inline-block bg-[#ffd22f] text-[#013064] px-8 py-3 font-bold cursor-pointer hover:bg-[#ffe066] transition">
                          Ubah Gambar Profil
                          <input
                            type="file"
                            accept="image/*"
                            onChange={handleImageChange}
                            className="hidden"
                          />
                        </label>
                      </div>
                    </div>

                    {/* Form Fields */}
                    <div className="space-y-5">
                      {/* NAMA */}
                      <div>
                        <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                          Nama
                        </label>
                        <input
                          type="text"
                          value={data.name}
                          onChange={(e) => setData('name', e.target.value)}
                          className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f]"
                        />
                        {errors.name && (
                          <p className="text-red-400 text-xs mt-1">{errors.name}</p>
                        )}
                      </div>

                      {/* EMAIL */}
                      <div>
                        <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                          Email
                        </label>
                        <input
                          type="email"
                          value={data.email}
                          onChange={(e) => setData('email', e.target.value)}
                          className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f]"
                          placeholder="email@example.com"
                        />
                        {errors.email && (
                          <p className="text-red-400 text-xs mt-1">{errors.email}</p>
                        )}
                      </div>

                      {/* PROVINSI & KOTA */}
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                            Provinsi
                          </label>
                          <select
                            value={data.province}
                            onChange={(e) => setData('province', e.target.value)}
                            className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] appearance-none"
                          >
                            <option value="">Pilih Provinsi</option>
                            <option value="Jawa Barat">Jawa Barat</option>
                            <option value="Jawa Tengah">Jawa Tengah</option>
                            <option value="Jawa Timur">Jawa Timur</option>
                            <option value="DKI Jakarta">DKI Jakarta</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                            Kota
                          </label>
                          <select
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                            className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] appearance-none"
                          >
                            <option value="">Pilih Kota</option>
                            <option value="Bandung">Bandung</option>
                            <option value="Jakarta">Jakarta</option>
                            <option value="Surabaya">Surabaya</option>
                          </select>
                        </div>
                      </div>

                      {/* ALAMAT */}
                      <div>
                        <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                          Alamat
                        </label>
                        <textarea
                          value={data.address}
                          onChange={(e) => setData('address', e.target.value)}
                          rows="4"
                          className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] resize-none"
                          placeholder="Jl Terusan Mars Utara III No. 8D Kota Bandung, 40292"
                        />
                      </div>

                      {/* TELEPON & JENIS KELAMIN */}
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                            Telepon
                          </label>
                          <input
                            type="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f]"
                            placeholder="0812-3456-789"
                          />
                        </div>
                        <div>
                          <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                            Jenis Kelamin
                          </label>
                          <select
                            value={data.gender}
                            onChange={(e) => setData('gender', e.target.value)}
                            className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] appearance-none"
                          >
                            <option value="">Pilih</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                          </select>
                        </div>
                      </div>

                      {/* TANGGAL LAHIR */}
                      <div className="max-w-md">
                        <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                          Tanggal Lahir
                        </label>
                        <div className="relative">
                          <input
                            type="date"
                            value={data.birth_date}
                            onChange={(e) => setData('birth_date', e.target.value)}
                            className="w-full px-4 py-3 bg-white/95 text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#ffd22f]"
                          />
                        </div>
                      </div>
                    </div>

                    <div className="pt-4">
                      <button
                        type="submit"
                        disabled={processing}
                        className="bg-[#ffd22f] text-[#013064] px-12 py-3 font-bold text-base hover:bg-[#ffe066] transition disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {processing ? 'Menyimpan...' : 'Ubah Data'}
                      </button>
                    </div>
                  </form>
                )}



                {activeTab === 'jadwal-booking' && (
                  <div>
                    <div className="mb-6">
                      <h3 className="text-[#ffd22f] text-2xl font-bold mb-2">Jadwal Booking Anda</h3>
                      <p className="text-white/70">Daftar booking yang akan datang</p>
                    </div>

                    {upcomingBookings.length > 0 ? (
                      <div className="space-y-4">
                        {upcomingBookings.map((booking, index) => {
                          const isExpired = expiredBookings.has(booking.id);

                          return (
                            <div key={booking.id} className={`bg-white rounded-lg p-6 ${isExpired ? 'opacity-50' : ''}`}>
                              <div className="flex justify-between items-start mb-4">
                                <div className="flex-1">
                                  <div className="flex items-center gap-3 mb-2 flex-wrap">
                                    <span className="text-2xl font-bold text-[#013064]">
                                      {index + 1}.
                                    </span>
                                    <span className={`px-4 py-1.5 rounded text-white text-xs font-black tracking-wider ${getStatusBadge(booking.status_color)}`}>
                                      {isExpired ? 'EXPIRED' : booking.status_label}
                                    </span>

                                    {/* Payment Status Badge */}
                                    {!isExpired && (
                                      <>
                                        {booking.is_paid ? (
                                          <span className="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                            <CheckCircle className="w-3 h-3" />
                                            Terbayar
                                          </span>
                                        ) : booking.payment_status === 'pending' ? (
                                          <>
                                            <span className="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                              <AlertCircle className="w-3 h-3" />
                                              Belum Bayar
                                            </span>
                                            {/* ✅ Payment Timer */}
                                            <PaymentTimer
                                              createdAt={booking.created_at}
                                              onExpired={() => handleBookingExpired(booking.id)}
                                              onAlert={(message) => handlePaymentAlert(message)}
                                            />
                                          </>
                                        ) : null}
                                      </>
                                    )}
                                  </div>
                                </div>

                                {/* Action Buttons */}
{!isExpired && (
  <div className="flex gap-2">
    {booking.can_pay && (
      <button
        onClick={() => {
          router.post(`/payment/process/${booking.id}`, {}, {
            onError: (errors) => {
              console.error('Payment error:', errors);
              setNotificationMessage('Gagal memproses pembayaran: ' + (errors.message || 'Unknown error'));
              setNotificationType('error');
              setShowNotification(true);
            }
          });
        }}
        className="w-full flex items-center justify-center gap-2 px-3 md:px-4 py-2 bg-[#ffd22f] text-[#013064] rounded hover:bg-[#ffe066] transition font-semibold text-sm"
      >
        <CreditCard className="w-4 h-4" />
        <span>Bayar</span>
      </button>
    )}
    {booking.can_cancel && (
      <button
        onClick={() => handleCancelBooking(booking)}
        className="flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition"
      >
        <X className="w-4 h-4" />
        <span className="text-sm font-semibold">Batalkan</span>
      </button>
    )}
  </div>
)}
                              </div>

                              <div className="space-y-3 mb-4">
                                <div className="flex items-start gap-3">
                                  <Clock className="w-5 h-5 text-[#ffd22f] flex-shrink-0 mt-0.5" />
                                  <div className="flex-1">
                                    <p className="text-sm text-gray-600">Jam Sewa</p>
                                    <p className="font-bold text-lg text-[#013064]">{booking.time_slot}</p>
                                  </div>
                                </div>

                                <div className="flex items-start gap-3">
                                  <Calendar className="w-5 h-5 text-[#ffd22f] flex-shrink-0 mt-0.5" />
                                  <div className="flex-1">
                                    <p className="text-sm text-gray-600">Tanggal</p>
                                    <p className="font-bold text-lg text-[#013064]">{booking.booking_date}</p>
                                  </div>
                                </div>

                                <div className="flex items-start gap-3">
                                  <MapPin className="w-5 h-5 text-[#ffd22f] flex-shrink-0 mt-0.5" />
                                  <div className="flex-1">
                                    <p className="text-sm text-gray-600">Jenis Lapangan</p>
                                    <p className="font-bold text-lg text-[#013064]">{booking.venue_type}</p>
                                  </div>
                                </div>

                                {booking.bill_no && (
                                  <div className="flex items-start gap-3">
                                    <CreditCard className="w-5 h-5 text-[#ffd22f] flex-shrink-0 mt-0.5" />
                                    <div className="flex-1">
                                      <p className="text-sm text-gray-600">No. Tagihan</p>
                                      <p className="font-bold text-sm text-[#013064]">{booking.bill_no}</p>
                                    </div>
                                  </div>
                                )}
                                {booking.payment_method && (
                                  <div className="flex items-start gap-3">
                                    <CreditCard className="w-5 h-5 text-[#ffd22f] flex-shrink-0 mt-0.5" />
                                    <div className="flex-1">
                                      <p className="text-sm text-gray-600">Metode Pembayaran</p>
                                      <p className="font-bold text-sm text-[#013064]">{booking.payment_method}</p>
                                    </div>
                                  </div>
                                )}
                              </div>

                              <div className="mt-4 pt-4 border-t flex justify-between items-center">
                                <span className="text-gray-600 font-medium">Total Pembayaran</span>
                                <span className="text-2xl font-bold text-[#013064]">
                                  Rp. {booking.total_price}
                                </span>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    ) : (
                      <div className="bg-white/10 rounded-lg p-12 text-center">
                        <Calendar className="w-16 h-16 text-white/30 mx-auto mb-4" />
                        <p className="text-white text-lg">Belum ada jadwal booking</p>
                        <Link
                          href="/booking"
                          className="inline-block mt-4 px-6 py-3 bg-[#ffd22f] text-[#013064] font-bold rounded hover:bg-[#ffe066] transition"
                        >
                          Booking Sekarang
                        </Link>
                      </div>
                    )}
                  </div>
                )}

                {activeTab === 'history' && (
                  <div>
                    <div className="mb-6">
                      <h3 className="text-[#ffd22f] text-2xl font-bold mb-2">History Booking Anda</h3>
                      <p className="text-white/70">Riwayat semua booking</p>
                    </div>

                    {historyBookings.data && historyBookings.data.length > 0 ? (
                      <div className="space-y-4">
                        {/* Table View */}
                        <div className="bg-white rounded-lg overflow-hidden">
                          <table className="w-full">
                            <thead>
                              <tr className="bg-[#ffd22f]">
                                <th className="px-6 py-4 text-left text-[#013064] font-bold">No</th>
                                <th className="px-6 py-4 text-left text-[#013064] font-bold">Jam Sewa</th>
                                <th className="px-6 py-4 text-left text-[#013064] font-bold">Tanggal</th>
                                <th className="px-6 py-4 text-left text-[#013064] font-bold">Status</th>
                              </tr>
                            </thead>
                            <tbody>
                              {historyBookings.data.map((booking, index) => (
                                <tr
                                  key={booking.id}
                                  className={index % 2 === 0 ? 'bg-gray-50' : 'bg-white'}
                                >
                                  <td className="px-6 py-4 text-[#013064] font-medium">
                                    {index + 1}.
                                  </td>
                                  <td className="px-6 py-4 text-[#013064] font-medium">
                                    {booking.time_slot}
                                  </td>
                                  <td className="px-6 py-4 text-[#013064] font-medium">
                                    {booking.booking_date}
                                  </td>
                                  <td className="px-6 py-4">
                                    <span className={`inline-block px-4 py-1.5 rounded text-white text-xs font-black tracking-wider ${getStatusBadge(booking.status_color)}`}>
                                      {booking.status_label}
                                    </span>
                                  </td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>

                        {/* Pagination */}
                        {historyBookings.last_page > 1 && (
                          <div className="flex justify-center gap-2 mt-6">
                            {Array.from({ length: historyBookings.last_page }, (_, i) => i + 1).map(page => (
                              <Link
                                key={page}
                                href={`/profile?page=${page}`}
                                className={`px-4 py-2 rounded font-semibold transition ${page === historyBookings.current_page
                                  ? 'bg-[#ffd22f] text-[#013064]'
                                  : 'bg-white/10 text-white hover:bg-white/20'
                                  }`}
                              >
                                {page}
                              </Link>
                            ))}
                          </div>
                        )}
                      </div>
                    ) : (
                      <div className="bg-white/10 rounded-lg p-12 text-center">
                        <Video className="w-16 h-16 text-white/30 mx-auto mb-4" />
                        <p className="text-white text-lg">Belum ada riwayat booking</p>
                      </div>
                    )}
                  </div>
                )}
                {activeTab === 'history-ulasan' && (
                  <div>
                    <div className="mb-6">
                      <h3 className="text-[#ffd22f] text-2xl font-bold mb-2">History Ulasan Anda</h3>
                      <p className="text-white/70">Riwayat ulasan yang telah Anda berikan</p>
                    </div>

                    {reviewHistory && reviewHistory.length > 0 ? (
                      <div className="space-y-4">
                        {reviewHistory.map((review, index) => (
                          <div key={review.id} className="bg-white rounded-lg p-6">
                            <div className="flex justify-between items-start mb-4">
                              <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                  <span className="text-2xl font-bold text-[#013064]">
                                    {index + 1}.
                                  </span>
                                  <div className="flex items-center gap-2">
                                    {[1, 2, 3, 4, 5].map((star) => (
                                      <Star
                                        key={star}
                                        className={`w-5 h-5 ${star <= review.average_rating
                                          ? 'fill-[#ffd22f] text-[#ffd22f]'
                                          : 'text-gray-300'
                                          }`}
                                      />
                                    ))}
                                    <span className="text-lg font-bold text-[#013064] ml-2">
                                      {review.average_rating}/5
                                    </span>
                                  </div>
                                </div>
                                <p className="text-sm text-gray-500">
                                  {review.created_at}
                                </p>
                              </div>

                              {review.is_approved ? (
                                <span className="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                  <CheckCircle className="w-3 h-3" />
                                  Disetujui
                                </span>
                              ) : (
                                <span className="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                  <AlertCircle className="w-3 h-3" />
                                  Menunggu Persetujuan
                                </span>
                              )}
                            </div>

                            <div className="space-y-3 mb-4">
                              <div className="grid grid-cols-3 gap-4">
                                <div className="text-center p-3 bg-gray-50 rounded">
                                  <p className="text-xs text-gray-600 mb-1">Fasilitas</p>
                                  <div className="flex items-center justify-center gap-1">
                                    <Star className="w-4 h-4 fill-[#ffd22f] text-[#ffd22f]" />
                                    <span className="font-bold text-[#013064]">
                                      {review.rating_facilities}
                                    </span>
                                  </div>
                                </div>
                                <div className="text-center p-3 bg-gray-50 rounded">
                                  <p className="text-xs text-gray-600 mb-1">Keramahan</p>
                                  <div className="flex items-center justify-center gap-1">
                                    <Star className="w-4 h-4 fill-[#ffd22f] text-[#ffd22f]" />
                                    <span className="font-bold text-[#013064]">
                                      {review.rating_hospitality}
                                    </span>
                                  </div>
                                </div>
                                <div className="text-center p-3 bg-gray-50 rounded">
                                  <p className="text-xs text-gray-600 mb-1">Kebersihan</p>
                                  <div className="flex items-center justify-center gap-1">
                                    <Star className="w-4 h-4 fill-[#ffd22f] text-[#ffd22f]" />
                                    <span className="font-bold text-[#013064]">
                                      {review.rating_cleanliness}
                                    </span>
                                  </div>
                                </div>
                              </div>

                              {review.comment && (
                                <div className="flex items-start gap-3 mt-4 p-4 bg-gray-50 rounded">
                                  <MessageSquare className="w-5 h-5 text-[#ffd22f] flex-shrink-0 mt-0.5" />
                                  <div className="flex-1">
                                    <p className="text-sm text-gray-600 mb-1 font-semibold">Komentar</p>
                                    <p className="text-gray-800">{review.comment}</p>
                                  </div>
                                </div>
                              )}

                              {review.booking_info && (
                                <div className="mt-3 pt-3 border-t">
                                  <p className="text-xs text-gray-500 mb-2">Untuk Booking:</p>
                                  <div className="flex items-center gap-4 text-sm">
                                    <span className="text-gray-700">
                                      <Calendar className="w-4 h-4 inline mr-1" />
                                      {review.booking_info.date}
                                    </span>
                                    <span className="text-gray-700">
                                      <MapPin className="w-4 h-4 inline mr-1" />
                                      {review.booking_info.venue}
                                    </span>
                                  </div>
                                </div>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="bg-white/10 rounded-lg p-12 text-center">
                        <Star className="w-16 h-16 text-white/30 mx-auto mb-4" />
                        <p className="text-white text-lg mb-2">Belum ada ulasan yang diberikan</p>
                        <p className="text-white/70 text-sm">
                          Selesaikan booking dan berikan ulasan pengalaman Anda!
                        </p>
                      </div>
                    )}
                  </div>
                )}

              </div>
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

      {/* Logout Confirmation Modal */}
      {showLogoutModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <h3 className="text-xl font-bold text-[#013064] mb-4">Konfirmasi Keluar</h3>
            <p className="text-gray-600 mb-6">Apakah Anda yakin ingin keluar dari akun?</p>
            <div className="flex gap-3 justify-end">
              <button
                onClick={() => setShowLogoutModal(false)}
                className="px-6 py-2 bg-gray-200 text-gray-700 rounded font-semibold hover:bg-gray-300 transition"
              >
                Batal
              </button>
              <button
                onClick={confirmLogout}
                className="px-6 py-2 bg-red-500 text-white rounded font-semibold hover:bg-red-600 transition"
              >
                Keluar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 2️⃣ ✅ TAMBAHKAN MODAL INI DI SINI - Payment Checking Modal */}
      {showPaymentCheckModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-8 text-center">
            <div className="mb-4">
              <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-[#ffd22f] mx-auto"></div>
            </div>
            <h3 className="text-xl font-bold text-[#013064] mb-2">Mengecek Status Pembayaran</h3>
            <p className="text-gray-600 mb-4">
              Sedang memverifikasi pembayaran Anda...
            </p>
            <p className="text-sm text-gray-500">
              Bill No: {checkingBillNo}
            </p>
          </div>
        </div>
      )}


      {/* Cancel Booking Modal */}
      {showCancelModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <h3 className="text-xl font-bold text-[#013064] mb-4">Konfirmasi Pembatalan</h3>
            <p className="text-gray-600 mb-6">Apakah Anda yakin ingin membatalkan booking ini?</p>
            <div className="flex gap-3 justify-end">
              <button
                onClick={() => {
                  setShowCancelModal(false);
                  setSelectedBooking(null);
                }}
                className="px-6 py-2 bg-gray-200 text-gray-700 rounded font-semibold hover:bg-gray-300 transition"
              >
                Tidak
              </button>
              <button
                onClick={confirmCancelBooking}
                className="px-6 py-2 bg-red-500 text-white rounded font-semibold hover:bg-red-600 transition"
              >
                Ya, Batalkan
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}