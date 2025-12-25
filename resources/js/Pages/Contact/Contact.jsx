import { Head, Link, useForm, usePage, router } from "@inertiajs/react";
import { Phone, Mail, MapPin, Clock, Instagram, Youtube, CheckCircle, AlertCircle } from "lucide-react";
import Navigation from "../../Components/Navigation";
import Footer from "../../Components/Footer";
import { useEffect, useState } from "react";

export default function Contact() {
  const { auth, flash } = usePage().props;
  const [showSuccess, setShowSuccess] = useState(false);
  const [showAuthWarning, setShowAuthWarning] = useState(false);
  
  const { data, setData, post, processing, errors, reset } = useForm({
    nama: '',
    email: '',
    subject: '',
    pesan: '',
  });

  useEffect(() => {
    if (flash?.success) {
      setShowSuccess(true);
      const timer = setTimeout(() => {
        setShowSuccess(false);
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [flash]);

  const handleSubmit = (e) => {
    e.preventDefault();
    
    // Check if user is logged in
    if (!auth?.client) {
      setShowAuthWarning(true);
      setTimeout(() => {
        setShowAuthWarning(false);
      }, 5000);
      return;
    }

    post(route('contact.submit'), {
      onSuccess: () => {
        reset();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      },
    });
  };

  const handleLoginRedirect = () => {
    router.visit('/login');
  };

  return (
    <>
      <Head title="THE ARENA - Contact" />
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
      `}</style>
      <div className="min-h-screen flex flex-col bg-[#013064]">
        {/* Navigation Component */}
        <Navigation activePage="kontak" />

        {/* Hero Section */}
        <div className="bg-[#013064] py-12 md:py-16 px-4">
          <div className="max-w-7xl mx-auto">
            <p className="text-[#ffd22f] text-base md:text-lg font-medium mb-2">
              Kontak
            </p>
            <h1 className="text-white text-3xl md:text-4xl lg:text-5xl font-bold">
              Hubungi Kami
            </h1>
          </div>
        </div>

        {/* Contact Info & Map Section */}
        <div className="bg-[#013064] py-8 md:py-12 px-4">
          <div className="max-w-7xl mx-auto">
            <div className="grid md:grid-cols-2 gap-8 md:gap-12 items-start">
              {/* Left: Contact Information */}
              <div className="text-white space-y-6">
                <div className="flex items-start gap-4">
                  <div className="bg-[#ffd22f] rounded-full p-3 flex-shrink-0">
                    <Phone className="w-5 h-5 text-[#013064]" />
                  </div>
                  <div>
                    <p className="text-white text-base">+62 812-2297-7985</p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="bg-[#ffd22f] rounded-full p-3 flex-shrink-0">
                    <Mail className="w-5 h-5 text-[#013064]" />
                  </div>
                  <div>
                    <p className="text-white text-base">thearena@gmail.com</p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="bg-[#ffd22f] rounded-full p-3 flex-shrink-0">
                    <MapPin className="w-5 h-5 text-[#013064]" />
                  </div>
                  <div>
                    <p className="text-white text-base">
                      The Arena Urban – Jl. Kelenteng No. 41, Ciroyom, Andir, Kota Bandung
                    </p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="bg-[#ffd22f] rounded-full p-3 flex-shrink-0">
                    <Clock className="w-5 h-5 text-[#013064]" />
                  </div>
                  <div>
                    <p className="text-white text-base">
                      Setiap hari, 06.00 – 22.00 WIB
                    </p>
                  </div>
                </div>
                
              </div>
{/* Right: Google Map - The Arena Urban */}
<div className="w-full h-[300px] md:h-[350px] rounded-lg overflow-hidden shadow-lg">
  <iframe
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.7769479528442!2d107.59060777499649!3d-6.917249193082343!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e745d6ede55f%3A0xc71097dde9e01f90!2sThe%20Arena%20Urban!5e0!3m2!1sid!2sid!4v1766674344955!5m2!1sid!2sid"
    width="100%"
    height="100%"
    style={{ border: 0 }}
    allowFullScreen=""
    loading="lazy"
    referrerPolicy="no-referrer-when-downgrade"
    title="The Arena Urban"
  ></iframe>
</div>
            </div>
          </div>
        </div>

        {/* Contact Form Section */}
        <div className="bg-[#013064] py-8 md:py-12 px-4">
          <div className="max-w-7xl mx-auto">
            {/* Success Message */}
            {showSuccess && flash?.success && (
              <div className="mb-6 bg-green-500 text-white px-6 py-4 rounded-lg flex items-center gap-3">
                <CheckCircle className="w-6 h-6 flex-shrink-0" />
                <span>{flash.success}</span>
              </div>
            )}

            {/* Auth Warning Message */}
            {showAuthWarning && (
              <div className="mb-6 bg-red-500 text-white px-6 py-4 rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                  <AlertCircle className="w-6 h-6 flex-shrink-0" />
                  <span>Anda harus login terlebih dahulu untuk mengirim pesan!</span>
                </div>
                <button
                  onClick={handleLoginRedirect}
                  className="bg-white text-red-500 px-4 py-2 rounded font-semibold hover:bg-gray-100 transition text-sm whitespace-nowrap"
                >
                  Login Sekarang
                </button>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-5">
              {/* Name and Email Row */}
              <div className="grid md:grid-cols-2 gap-5">
                <div>
                  <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                    Nama
                  </label>
                  <input
                    type="text"
                    placeholder="Nama"
                    value={data.nama}
                    onChange={(e) => setData('nama', e.target.value)}
                    className="w-full px-5 py-3 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] text-sm rounded"
                  />
                  {errors.nama && <p className="text-red-400 text-sm mt-1">{errors.nama}</p>}
                </div>

                <div>
                  <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                    Email
                  </label>
                  <input
                    type="email"
                    placeholder="Email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="w-full px-5 py-3 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] text-sm rounded"
                  />
                  {errors.email && <p className="text-red-400 text-sm mt-1">{errors.email}</p>}
                </div>
              </div>

              {/* Subject */}
              <div>
                <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                  Subject
                </label>
                <input
                  type="text"
                  placeholder="Subject"
                  value={data.subject}
                  onChange={(e) => setData('subject', e.target.value)}
                  className="w-full px-5 py-3 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] text-sm rounded"
                />
                {errors.subject && <p className="text-red-400 text-sm mt-1">{errors.subject}</p>}
              </div>

              {/* Message */}
              <div>
                <label className="block text-[#ffd22f] text-sm font-medium mb-2">
                  Pesan
                </label>
                <textarea
                  placeholder="Pesan"
                  rows="5"
                  value={data.pesan}
                  onChange={(e) => setData('pesan', e.target.value)}
                  className="w-full px-5 py-3 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] resize-none text-sm rounded"
                ></textarea>
                {errors.pesan && <p className="text-red-400 text-sm mt-1">{errors.pesan}</p>}
              </div>

              {/* Submit Button */}
              <div>
                <button
                  type="submit"
                  disabled={processing}
                  className="w-full bg-[#ffd22f] text-[#013064] px-8 py-3 text-base font-bold hover:bg-[#ffe066] transition disabled:opacity-50 disabled:cursor-not-allowed rounded"
                >
                  {processing ? 'Mengirim...' : 'Kirim'}
                </button>
              </div>
            </form>
          </div>
        </div>

        {/* Footer */}
        <Footer />
      </div>
    </>
  );
}