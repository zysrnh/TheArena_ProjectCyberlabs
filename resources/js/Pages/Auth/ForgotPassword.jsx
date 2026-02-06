import { Head, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { Mail, Phone, ArrowLeft, AlertCircle, CheckCircle, X } from "lucide-react";
import Navigation from "../../Components/Navigation";

export default function ForgotPassword() {
  const [notification, setNotification] = useState(null);

  const { data, setData, post, processing, errors } = useForm({
    email: "",
    phone: "",
  });

  const handleSubmit = (e) => {
    e.preventDefault();

    post('/forgot-password', {
      onSuccess: () => {
        setNotification({
          type: 'success',
          message: 'Verifikasi berhasil! Silakan reset password Anda.'
        });
      },
      onError: (errors) => {
        const errorMessage = errors.email || errors.phone || errors.verification || 'Data tidak cocok dengan yang terdaftar';
        setNotification({
          type: 'error',
          message: errorMessage
        });
        setTimeout(() => setNotification(null), 5000);
      }
    });
  };

  return (
    <>
      <Head title="Lupa Password" />
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
        
        .animate-slide-down {
          animation: slideDown 0.3s ease-out;
        }
        
        .animate-progress {
          animation: progress 5s linear;
        }
        
        .animate-fade-in-up {
          animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-float {
          animation: float 3s ease-in-out infinite;
        }
        
        .animate-pulse-ring {
          animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .stagger-1 {
          animation-delay: 0.1s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
        
        .stagger-2 {
          animation-delay: 0.2s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
        
        .stagger-3 {
          animation-delay: 0.3s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
        
        .stagger-4 {
          animation-delay: 0.4s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
      `}</style>

      <div className="min-h-screen flex flex-col bg-[#013064] relative overflow-hidden">
        {/* Animated Background Elements */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute top-20 left-10 w-32 h-32 bg-[#ffd22f]/10 rounded-full blur-3xl animate-float"></div>
          <div className="absolute top-40 right-20 w-40 h-40 bg-[#ffd22f]/5 rounded-full blur-3xl animate-float" style={{animationDelay: '1s'}}></div>
          <div className="absolute bottom-20 left-1/4 w-36 h-36 bg-[#ffd22f]/10 rounded-full blur-3xl animate-float" style={{animationDelay: '2s'}}></div>
        </div>

        {/* Navigation */}
        <Navigation activePage="" />

        {/* Notification Popup */}
        {notification && (
          <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4">
            <div
              className="absolute inset-0 bg-[#013064]/80 backdrop-blur-sm"
              onClick={() => setNotification(null)}
            />

            <div className="relative bg-white max-w-md w-full animate-slide-down shadow-2xl">
              <div className={`border-t-4 ${notification.type === 'success' ? 'border-green-500' : 'border-red-500'}`}>
                <div className="bg-[#013064] px-6 py-4 flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    {notification.type === 'success' ? (
                      <CheckCircle className="w-6 h-6 text-green-400" />
                    ) : (
                      <AlertCircle className="w-6 h-6 text-red-400" />
                    )}
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
                  <div className={`h-full ${notification.type === 'success' ? 'bg-green-500' : 'bg-red-500'} animate-progress`} />
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Forgot Password Form */}
        <main className="flex-1 flex items-center justify-center py-12 px-4 relative z-10">
          <div className="w-full max-w-md">
            {/* Back to Login Link */}
            <Link
              href="/login"
              className="inline-flex items-center gap-2 text-[#ffd22f] hover:text-[#ffe066] transition mb-6 animate-fade-in-up"
            >
              <ArrowLeft className="w-4 h-4" />
              <span className="text-sm font-medium">Kembali ke Login</span>
            </Link>

            <h1 className="text-[#ffd22f] text-4xl font-bold text-center mb-3 animate-fade-in-up stagger-1">
              Lupa Password?
            </h1>
            
            <p className="text-white text-center mb-8 text-sm animate-fade-in-up stagger-1">
              Masukkan email dan nomor telepon yang terdaftar untuk memverifikasi akun Anda
            </p>

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Email Field */}
              <div className="animate-fade-in-up stagger-2">
                <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                  Email <span className="text-red-400">*</span>
                </label>
                <div className="relative">
                  <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    type="email"
                    placeholder="Email yang terdaftar"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                    className="w-full pl-12 pr-4 py-3 bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] transition"
                  />
                </div>
                {errors.email && (
                  <p className="text-red-400 text-xs mt-1 flex items-center gap-1">
                    <AlertCircle className="w-3 h-3" />
                    {errors.email}
                  </p>
                )}
              </div>

              {/* Phone Field */}
              <div className="animate-fade-in-up stagger-3">
                <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                  Nomor Telepon <span className="text-red-400">*</span>
                </label>
                <div className="relative">
                  <Phone className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    type="tel"
                    placeholder="08123456789"
                    value={data.phone}
                    onChange={(e) => setData('phone', e.target.value)}
                    required
                    className="w-full pl-12 pr-4 py-3 bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] transition"
                  />
                </div>
                {errors.phone && (
                  <p className="text-red-400 text-xs mt-1 flex items-center gap-1">
                    <AlertCircle className="w-3 h-3" />
                    {errors.phone}
                  </p>
                )}
              </div>

              {/* General Error */}
              {errors.verification && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-4 animate-fade-in-up">
                  <p className="text-red-600 text-sm flex items-center gap-2">
                    <AlertCircle className="w-4 h-4" />
                    {errors.verification}
                  </p>
                </div>
              )}

              {/* Submit Button */}
              <button
                type="submit"
                disabled={processing}
                className="w-full bg-[#ffd22f] text-[#013064] py-3 font-bold text-lg hover:bg-[#ffe066] transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 animate-fade-in-up stagger-4 shadow-lg hover:shadow-xl"
              >
                {processing ? (
                  <>
                    <div className="w-5 h-5 border-2 border-[#013064] border-t-transparent rounded-full animate-spin"></div>
                    Memverifikasi...
                  </>
                ) : (
                  <>
                    <CheckCircle className="w-5 h-5" />
                    Verifikasi Akun
                  </>
                )}
              </button>

              {/* Info Box */}
              <div className="bg-white/10 border border-white/20 rounded-lg p-4 animate-fade-in-up stagger-4">
                <p className="text-white text-xs leading-relaxed">
                  <strong className="text-[#ffd22f]">Catatan:</strong> Email dan nomor telepon harus sesuai dengan data yang Anda gunakan saat registrasi.
                </p>
              </div>
            </form>
          </div>
        </main>

        {/* WhatsApp Button */}
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