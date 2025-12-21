import { Head, Link, usePage, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { ArrowLeft, ChevronRight, ChevronLeft as ChevronLeftIcon, X } from "lucide-react";
import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";
import Contact from '../../Components/Contact';

export default function EquipmentDetail() {
  const { auth, equipment, otherEquipments, errors, flash } = usePage().props;
  
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [notification, setNotification] = useState(null);
  const [showAdminModal, setShowAdminModal] = useState(false);

  // Gunakan images dari backend (array gambar)
  const images = equipment.images && equipment.images.length > 0 
    ? equipment.images 
    : [equipment.image || "https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800"];

  // Pastikan selalu ada 5 gambar untuk layout
  const displayImages = [...images];
  while (displayImages.length < 5) {
    displayImages.push(displayImages[0]);
  }

  // Admin contacts
  const adminContacts = [
    { name: "Admin 1", phone: "6283861669565" },
    { name: "Admin 2", phone: "6281234567890" },
    { name: "Admin 3", phone: "6289876543210" }
  ];

  // Show notification from flash messages or errors
  useEffect(() => {
    if (errors?.message) {
      setNotification({ type: 'error', message: errors.message });
      setTimeout(() => setNotification(null), 5000);
    }
    if (flash?.message) {
      setNotification({ type: flash.success ? 'success' : 'error', message: flash.message });
      setTimeout(() => setNotification(null), 5000);
    }
  }, [errors, flash]);

  // Auto slide untuk mobile
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentImageIndex((prev) => (prev + 1) % displayImages.length);
    }, 3000);

    return () => clearInterval(interval);
  }, [displayImages.length]);

  const truncateDescription = (text, maxLength = 100) => {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  };

  const handleWhatsAppClick = (e) => {
    e.preventDefault();
    
    // Cek apakah user sudah login
    if (!auth.client) {
      setNotification({ 
        type: 'error', 
        message: 'Silakan login terlebih dahulu untuk melakukan booking' 
      });
      setTimeout(() => {
        router.visit("/login");
      }, 1500);
      return;
    }

    // Jika sudah login, tampilkan modal pilihan admin
    setShowAdminModal(true);
  };

  const handleAdminSelect = (phone) => {
    const message = `Halo, saya ingin menyewa peralatan:%0A%0A` +
      `Atas Nama: ${auth.client.name}%0A` +
      `Barang yang akan dibooking: ${equipment.name}%0A%0A` +
      `Saya ingin mendapatkan informasi lebih lanjut tentang cara booking dan ketersediaan.`;
    window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
    setShowAdminModal(false);
  };

  return (
    <>
      <Head title={`THE ARENA - ${equipment.name}`} />
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

        .break-word {
          word-wrap: break-word;
          word-break: break-word;
          overflow-wrap: break-word;
        }
      `}</style>
      <div className="min-h-screen flex flex-col bg-[#013064]">
        <Navigation activePage="equipment" />

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
              <div className={`${
                notification.type === 'success' 
                  ? 'border-t-4 border-[#ffd22f]' 
                  : 'border-t-4 border-[#ffd22f]'
              }`}>
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

        {/* Admin Selection Modal */}
        {showAdminModal && (
          <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4">
            {/* Backdrop */}
            <div 
              className="absolute inset-0 bg-[#013064]/80 backdrop-blur-sm"
              onClick={() => setShowAdminModal(false)}
            />
            
            {/* Modal */}
            <div className="relative bg-white max-w-md w-full animate-slide-down shadow-2xl">
              <div className="border-t-4 border-[#ffd22f]">
                {/* Header */}
                <div className="bg-[#013064] px-6 py-4 flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-2 h-2 rounded-full bg-[#ffd22f]" />
                    <h3 className="font-bold text-white text-lg">
                      Pilih Admin
                    </h3>
                  </div>
                  <button
                    onClick={() => setShowAdminModal(false)}
                    className="text-white/70 hover:text-white transition"
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>
                
                {/* Content */}
                <div className="p-6 bg-white">
                  <p className="text-[#013064] text-sm mb-4">
                    Silakan pilih admin yang ingin Anda hubungi:
                  </p>
                  
                  <div className="space-y-3">
                    {adminContacts.map((admin, index) => (
                      <button
                        key={index}
                        onClick={() => handleAdminSelect(admin.phone)}
                        className="w-full bg-[#013064] hover:bg-[#002952] text-white px-6 py-3 transition flex items-center justify-between group"
                      >
                        <span className="font-semibold">{admin.name}</span>
                        <svg className="w-5 h-5 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        <div className="flex-1 bg-[#013064]">
          <div className="max-w-7xl mx-auto px-4 py-8 md:py-12">
            {/* Header with Back Button */}
            <div className="flex items-start gap-4 mb-8">
              <Link
                href="/booking-peralatan"
                className="inline-flex items-center gap-2 text-white hover:text-[#ffd22f] transition flex-shrink-0 mt-1"
              >
                <div className="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                  <ArrowLeft className="w-5 h-5 text-[#013064]" />
                </div>
              </Link>
              
              <div>
                <h1 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold">
                  {equipment.name}
                </h1>
              </div>
            </div>

            {/* Image Gallery - 1 Besar + 4 Kecil */}
            <div className="mb-12">
              {/* Desktop Layout - Simetris */}
              <div className="hidden lg:flex gap-4 justify-center">
                {/* Main Image (Gambar 1) - 550px */}
                <div className="w-[550px] h-[550px]">
                  <img
                    src={displayImages[0]}
                    alt={`${equipment.name} - Main`}
                    className="w-full h-full object-cover"
                    onError={(e) => {
                      e.target.src = "https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800";
                    }}
                  />
                </div>
                
                {/* Grid 4 Images (Gambar 2-5) - 550px total */}
                <div className="grid grid-cols-2 gap-4 w-[550px] h-[550px]">
                  {displayImages.slice(1, 5).map((img, idx) => (
                    <div key={idx} className="w-full h-full">
                      <img
                        src={img}
                        alt={`${equipment.name} - ${idx + 2}`}
                        className="w-full h-full object-cover"
                        onError={(e) => {
                          e.target.src = "https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800";
                        }}
                      />
                    </div>
                  ))}
                </div>
              </div>

              {/* Mobile Layout - Slider/Carousel */}
              <div className="lg:hidden relative">
                <div className="relative w-full aspect-square overflow-hidden">
                  <img
                    src={displayImages[currentImageIndex]}
                    alt={`${equipment.name} - ${currentImageIndex + 1}`}
                    className="w-full h-full object-cover"
                    onError={(e) => {
                      e.target.src = "https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800";
                    }}
                  />
                  
                  {/* Navigation Arrows */}
                  <button
                    onClick={() => setCurrentImageIndex((prev) => (prev - 1 + displayImages.length) % displayImages.length)}
                    className="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/70 transition"
                  >
                    <ChevronLeftIcon className="w-6 h-6" />
                  </button>
                  <button
                    onClick={() => setCurrentImageIndex((prev) => (prev + 1) % displayImages.length)}
                    className="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/70 transition"
                  >
                    <ChevronRight className="w-6 h-6" />
                  </button>

                  {/* Dots Indicator */}
                  <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                    {displayImages.map((_, idx) => (
                      <button
                        key={idx}
                        onClick={() => setCurrentImageIndex(idx)}
                        className={`w-2 h-2 rounded-full transition ${
                          idx === currentImageIndex ? 'bg-[#ffd22f] w-6' : 'bg-white/50'
                        }`}
                      />
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Description & Booking Section */}
            <div className="grid lg:grid-cols-2 gap-8">
              {/* Description */}
              <div>
                <h2 className="text-white text-2xl font-bold mb-4">Deskripsi Peralatan</h2>
                <div className="text-white leading-relaxed space-y-4 text-sm lg:text-base">
                  <p className="break-words whitespace-normal" style={{ maxWidth: '60ch' }}>
                    {equipment.description}
                  </p>
                  
                  <div className="mt-6 pt-6 border-t border-white/20">
                    <h3 className="font-semibold text-base mb-3">Informasi Tambahan:</h3>
                    <ul className="space-y-2 text-gray-200">
                      <li>• Peralatan dalam kondisi terawat dan siap pakai</li>
                      <li>• Tersedia untuk sewa harian dengan durasi fleksibel</li>
                      <li>• Harga sudah termasuk pemeliharaan standar</li>
                      <li>• Stok terbatas, booking terlebih dahulu disarankan</li>
                    </ul>
                  </div>

                  <div className="mt-6 pt-6 border-t border-white/20">
                    <h3 className="font-semibold text-base mb-3">Syarat & Ketentuan:</h3>
                    <ul className="space-y-2 text-gray-200">
                      <li>• Wajib menunjukkan identitas valid saat pengambilan</li>
                      <li>• Kerusakan di luar keausan normal menjadi tanggung jawab penyewa</li>
                      <li>• Pengembalian terlambat dikenakan biaya tambahan</li>
                      <li>• Pembatalan booking maksimal H-1 untuk pengembalian dana</li>
                    </ul>
                  </div>
                </div>
              </div>

              {/* Booking Box */}
              <div>
                <div className="bg-[#003f84] p-6 lg:p-8">
                  <h3 className="text-white text-xl lg:text-2xl font-bold mb-6">
                    Tertarik dengan peralatan ini?
                  </h3>

                  <button
                    onClick={handleWhatsAppClick}
                    className="w-full bg-[#ffd22f] text-[#013064] py-3 lg:py-4 font-bold hover:bg-[#ffe066] transition flex items-center justify-center gap-2"
                  >
                    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Hubungi Via WhatsApp
                  </button>

                  <p className="text-gray-300 text-xs mt-4 text-center">
                    Klik tombol di atas untuk konsultasi dan booking via WhatsApp
                  </p>
                </div>
              </div>
            </div>

            {/* Other Equipments Section */}
            {otherEquipments.length > 0 && (
              <div className="mt-16">
                <h2 className="text-white text-2xl md:text-3xl font-bold mb-8">
                  Peralatan Lainnya
                </h2>
                <div className="space-y-8">
                  {otherEquipments.map((item) => (
                    <div key={item.id} className="flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                      <div className="flex-shrink-0 w-full sm:w-auto">
                        <img
                          src={item.image || "https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800"}
                          alt={item.name}
                          className="w-full h-64 sm:w-64 sm:h-64 md:w-80 md:h-80 lg:w-96 lg:h-72 object-cover"
                        />
                      </div>
                      
                      <div className="flex-1 w-full">
                        <h3 className="text-white text-xl sm:text-2xl font-bold mb-3">{item.name}</h3>
                        {/* Mobile: Truncated description */}
                        <p className="text-white text-sm mb-4 leading-relaxed break-words whitespace-normal sm:hidden">
                          {truncateDescription(item.description, 100)}
                        </p>
                        {/* Desktop: Full description */}
                        <p className="hidden sm:block text-white text-sm mb-4 leading-relaxed break-words whitespace-normal" style={{ maxWidth: '60ch' }}>
                          {item.description}
                        </p>
                        <Link
                          href={`/booking-peralatan/${item.id}`}
                          className="inline-flex items-center gap-2 bg-[#ffd22f] text-[#013064] px-6 py-2.5 text-sm font-semibold hover:bg-[#ffe066] transition w-full sm:w-auto justify-center sm:justify-start"
                        >
                          Lihat Lebih Lengkap
                          <ChevronRight className="w-4 h-4" />
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
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