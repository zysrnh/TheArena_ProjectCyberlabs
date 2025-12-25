import { useState } from "react";
import { Link } from "@inertiajs/react";
import { Instagram, Music, Youtube, MessageCircle, ChevronDown, X } from "lucide-react";

export default function Footer() {
  const [showFaqModal, setShowFaqModal] = useState(false);
  const [openIndex, setOpenIndex] = useState(null);

  const faqs = [
    {
      question: "Bagaimana cara mendaftar untuk bermain di The Arena?",
      answer: "Anda dapat mendaftar melalui website kami atau datang langsung ke lokasi The Arena. Tim kami akan membantu proses pendaftaran Anda."
    },
    {
      question: "Apakah ada persyaratan khusus untuk bergabung?",
      answer: "Tidak ada persyaratan khusus. Semua level pemain dari pemula hingga profesional dapat bergabung di The Arena."
    },
    {
      question: "Berapa biaya sewa lapangan per jam?",
      answer: "Biaya sewa lapangan bervariasi tergantung waktu dan hari. Silakan hubungi kami untuk informasi harga terkini."
    },
    {
      question: "Apakah tersedia pelatih basket?",
      answer: "Ya, kami memiliki pelatih berpengalaman yang siap membantu meningkatkan skill basket Anda."
    },
    {
      question: "Apakah The Arena buka setiap hari?",
      answer: "Ya, The Arena buka setiap hari dari jam 06.00 hingga 22.00 WIB."
    }
  ];

  const toggleFaq = (index) => {
    setOpenIndex(openIndex === index ? null : index);
  };

  return (
    <>
      {/* Footer */}
      <footer className="bg-[#ffd22f] py-12 md:py-16 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12 mb-8 md:mb-12">
            {/* Logo & Description */}
            <div className="md:col-span-2 lg:col-span-1 text-center md:text-left">
              <img 
                src="/images/LogoHitam.png" 
                alt="The Arena Basketball" 
                className="h-20 md:h-24 w-auto mb-4 md:mb-6 mx-auto md:mx-0" 
              />
              <p className="text-[#013064] text-sm leading-relaxed px-4 md:px-0">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim
              </p>
            </div>

            {/* Menu */}
            <div className="text-center md:text-left">
              <h3 className="text-[#013064] text-lg md:text-xl font-bold mb-4 md:mb-6">Menu</h3>
              <ul className="space-y-2 md:space-y-3 text-[#013064] text-sm md:text-base">
                <li><Link href="/berita" className="hover:underline">Berita</Link></li>
                <li>
                  <button 
                    onClick={() => setShowFaqModal(true)}
                    className="hover:underline"
                  >
                    FAQ
                  </button>
                </li>
                <li><Link href="/jadwal-hasil" className="hover:underline">Jadwal</Link></li>
                <li><Link href="/siaran-langsung" className="hover:underline">Siaran Langsung</Link></li>
                <li><Link href="/" className="hover:underline">Partner dan Sponsor</Link></li>
              </ul>
            </div>

            {/* Legal */}
            <div className="text-center md:text-left">
              <h3 className="text-[#013064] text-lg md:text-xl font-bold mb-4 md:mb-6">Legal</h3>
              <ul className="space-y-2 md:space-y-3 text-[#013064] text-sm md:text-base">
                <li><Link href="/kebijakan-privasi" className="hover:underline">Kebijakan Privasi</Link></li>
                <li><Link href="/syarat-layanan" className="hover:underline">Syarat Layanan</Link></li>
                <li><Link href="/license" className="hover:underline">License Agreement</Link></li>
                <li><Link href="/ketentuan" className="hover:underline">Ketentuan Penggunaan</Link></li>
                <li><Link href="/komunitas" className="hover:underline">Komunitas</Link></li>
              </ul>
            </div>

            {/* Contact */}
            <div className="text-center md:text-left">
              <h3 className="text-[#013064] text-lg md:text-xl font-bold mb-4 md:mb-6">Kontak</h3>
              <div className="space-y-3 md:space-y-4">
                <div className="flex items-center gap-3 justify-center md:justify-start">
                  <img src="/images/Phone_fill-1.svg" alt="Phone" className="w-5 h-5 flex-shrink-0" />
                  <span className="text-[#013064] text-sm md:text-base">+62 812-2297-7985</span>
                </div>
                <div className="flex items-center gap-3 justify-center md:justify-start">
                  <img src="/images/Message_alt_fill-2.svg" alt="Email" className="w-5 h-5 flex-shrink-0" />
                  <span className="text-[#013064] text-sm md:text-base break-all">thearena@gmail.com</span>
                </div>
                <div className="flex items-start gap-3 justify-center md:justify-start px-4 md:px-0">
                  <img src="/images/Pin_fill.svg" alt="Location" className="w-5 h-5 mt-1 flex-shrink-0" />
                  <span className="text-[#013064] text-sm text-left">The Arena Urban – Jl. Kelenteng No. 41, Ciroyom, Andir, Kota Bandung</span>
                </div>
                <div className="flex items-start gap-3 justify-center md:justify-start px-4 md:px-0">
                  <img src="/images/Clock.svg" alt="Hours" className="w-5 h-5 mt-1 flex-shrink-0" />
                  <span className="text-[#013064] text-sm text-left">Jam Operasional: Setiap hari, 06.00 – 22.00 WIB</span>
                </div>
                
                {/* Social Media Icons */}
                <div className="flex gap-3 pt-2 md:pt-4 justify-center md:justify-start">
                  <a 
                    href="https://www.instagram.com/the.arena.basketball/" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    className="w-9 h-9 md:w-10 md:h-10 bg-[#013064] rounded-full flex items-center justify-center hover:opacity-80 transition"
                    aria-label="Instagram"
                  >
                    <Instagram className="w-4 h-4 md:w-5 md:h-5 text-white" />
                  </a>
                  <a 
                    href="https://tiktok.com" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    className="w-9 h-9 md:w-10 md:h-10 bg-[#013064] rounded-full flex items-center justify-center hover:opacity-80 transition"
                    aria-label="TikTok"
                  >
                    <Music className="w-4 h-4 md:w-5 md:h-5 text-white" />
                  </a>
                  <a 
                    href="https://www.youtube.com/@thearenapvj" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    className="w-9 h-9 md:w-10 md:h-10 bg-[#013064] rounded-full flex items-center justify-center hover:opacity-80 transition"
                    aria-label="YouTube"
                  >
                    <Youtube className="w-4 h-4 md:w-5 md:h-5 text-white" />
                  </a>
                  <a 
                    href="https://wa.me/6281222977985" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    className="w-9 h-9 md:w-10 md:h-10 bg-[#013064] rounded-full flex items-center justify-center hover:opacity-80 transition"
                    aria-label="WhatsApp"
                  >
                    <MessageCircle className="w-4 h-4 md:w-5 md:h-5 text-white" />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </footer>

      {/* Copyright Bar */}
      <div className="bg-[#013064] py-4 px-4">
        <div className="max-w-7xl mx-auto text-center">
          <p className="text-white text-xs md:text-sm leading-relaxed">
            © Copyright The Arena All Rights Reserved. Design & Development By CyberLabs
          </p>
        </div>
      </div>

      {/* FAQ Modal */}
      {showFaqModal && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
          onClick={() => {
            setShowFaqModal(false);
            setOpenIndex(null);
          }}
        >
          <div 
            className="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="bg-[#013064] px-6 py-4 flex items-center justify-between">
              <div>
                <p className="text-[#ffd22f] text-sm font-semibold">FAQ</p>
                <h2 className="text-xl md:text-2xl font-bold text-white">
                  Pertanyaan yang Sering Diajukan
                </h2>
              </div>
              <button
                onClick={() => {
                  setShowFaqModal(false);
                  setOpenIndex(null);
                }}
                className="text-white hover:text-[#ffd22f] transition"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* Modal Content */}
            <div className="overflow-y-auto p-6">
              <div className="space-y-3">
                {faqs.map((faq, index) => (
                  <div 
                    key={index} 
                    className="border border-gray-200 rounded-lg overflow-hidden"
                  >
                    <button
                      onClick={() => toggleFaq(index)}
                      className="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition"
                    >
                      <span className="text-sm md:text-base font-semibold text-[#013064] pr-4">
                        {faq.question}
                      </span>
                      <ChevronDown 
                        className={`w-5 h-5 text-[#013064] flex-shrink-0 transition-transform ${
                          openIndex === index ? 'rotate-180' : ''
                        }`}
                      />
                    </button>
                    
                    {openIndex === index && (
                      <div className="px-4 pb-4 text-gray-700 text-sm">
                        {faq.answer}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}