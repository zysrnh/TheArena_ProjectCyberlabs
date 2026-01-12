import React from 'react';
import { Phone } from 'lucide-react';

const Contact = () => {
  return (
    <div className="relative h-[450px] md:h-[550px] lg:h-[600px] overflow-hidden">
      {/* Blue Background - Fixed to specific height instead of percentage */}
      <div className="absolute top-0 left-0 right-0 h-[120px] md:h-[140px] bg-[#013064]" />

      {/* Court Background Image - starts after blue section */}
      <div
        className="absolute left-0 right-0 bottom-0 top-[120px] md:top-[140px] bg-cover bg-center"
        style={{ backgroundImage: "url('/images/lapang.jpg')" }}
      />

      {/* Dark Overlay */}
      <div className="absolute left-0 right-0 bottom-0 top-[120px] md:top-[140px] bg-black/70" />

      {/* Gradient Overlay */}
      <div className="absolute left-0 right-0 bottom-0 top-[120px] md:top-[140px] bg-gradient-to-r from-[#013064]/80 via-[#013064]/40 to-transparent" />

      {/* Content Container */}
      <div className="relative z-10 h-full max-w-7xl mx-auto px-4 md:px-8">
        <div className="grid md:grid-cols-2 gap-8 md:gap-12 h-full items-center">
          {/* Text Content */}
          <div className="text-white">
            <p className="text-[#ffd22f] text-base md:text-xl lg:text-2xl font-semibold mb-3 md:mb-4">
              Kontak
            </p>
            <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold mb-6 md:mb-8 leading-tight">
              Hubungi kami untuk informasi lebih lanjut!
            </h2>
            <a
              href="http://127.0.0.1:8000/kontak"
              className="bg-[#ffd22f] text-[#013064] px-6 md:px-8 py-2 md:py-3 text-sm md:text-base font-semibold hover:bg-[#ffe066] transition inline-flex items-center gap-2 w-fit"
            >
              Kontak Kami
              <Phone className="w-4 h-4 md:w-5 md:h-5" />
            </a>
          </div>

          {/* Player Image */}
          <div className="hidden md:flex justify-end items-end h-full">
            <img
              src="/images/jelema.png"
              alt="Basketball Player"
              className="h-[450px] lg:h-[580px] w-auto object-contain"
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default Contact;