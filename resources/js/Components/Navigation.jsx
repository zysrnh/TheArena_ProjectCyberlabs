import { Link, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { Menu, X } from "lucide-react";

export default function Navigation({ activePage = "" }) {
  const { auth } = usePage().props;
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [currentLanguage, setCurrentLanguage] = useState("id"); // 'id' for Indonesian, 'en' for English

  useEffect(() => {
    const handleScroll = () => {
      const currentScrollY = window.scrollY;
      setIsScrolled(currentScrollY > 50);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  // Close mobile menu when window is resized to desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 1024) {
        setIsMobileMenuOpen(false);
      }
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Prevent body scroll when mobile menu is open
  useEffect(() => {
    if (isMobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'unset';
    }
  }, [isMobileMenuOpen]);

  // Load language from localStorage
  useEffect(() => {
    const savedLanguage = localStorage.getItem('language') || 'id';
    setCurrentLanguage(savedLanguage);
  }, []);

  const toggleLanguage = () => {
    const newLanguage = currentLanguage === "id" ? "en" : "id";
    setCurrentLanguage(newLanguage);
    localStorage.setItem('language', newLanguage);
    // Tambahkan logika untuk mengganti bahasa di seluruh aplikasi
    // Misalnya: window.location.reload() atau dispatch action ke state management
  };

  const navItems = [
    { 
      name: currentLanguage === "id" ? "Beranda" : "Home", 
      href: "/", 
      key: "home" 
    },
    { 
      name: currentLanguage === "id" ? "Booking Lapangan" : "About", 
      href: "/booking", 
      key: "booking" 
    },
    { 
      name: currentLanguage === "id" ? "Tentang" : "About", 
      href: "/tentang", 
      key: "tentang" 
    },
    { 
      name: currentLanguage === "id" ? "Pertandingan" : "Schedule & Results", 
      href: "/jadwal-hasil", 
      key: "jadwal-hasil" 
    },
    { 
      name: currentLanguage === "id" ? "Siaran Langsung" : "Live Stream", 
      href: "/siaran-langsung", 
      key: "siaran-langsung" 
    },
    { 
      name: currentLanguage === "id" ? "Kontak" : "Contact", 
      href: "/kontak", 
      key: "kontak" 
    },
  ];

  return (
    <>
      <nav className="bg-[#013064] text-white py-3 px-4 border-b border-[#024b8a] sticky top-0 z-50 transition-all duration-300">
        <div className="max-w-7xl mx-auto">
          {/* Main Navigation */}
          <div className="flex justify-between items-center">
            {/* Logo */}
            <div className="flex items-center gap-2">
              <Link href="/">
                <img
                  src="/images/LogoR.png"
                  alt="The Arena Basketball"
                  className="h-10 md:h-14 w-auto object-contain cursor-pointer"
                />
              </Link>
            </div>

            {/* Navigation Menu - Desktop */}
            <div className="hidden lg:flex items-center gap-8 text-sm">
              {navItems.map((item) => (
                <Link
                  key={item.key}
                  href={item.href}
                  className={`transition ${
                    activePage === item.key
                      ? "text-[#ffd22f] font-semibold"
                      : "hover:text-[#ffd22f]"
                  }`}
                >
                  {item.name}
                </Link>
              ))}
            </div>

            {/* Right Side: Profile/Login + Language + Hamburger */}
            <div className="flex items-center gap-2 md:gap-4">
              {/* Profile or Login Button */}
              {auth.client ? (
                <div className="flex items-center gap-2">
                  <span className="hidden sm:block text-white font-semibold italic text-sm">
                    {auth.client.name}
                  </span>
                  <Link href="/profile">
                    <img
                      src={
                        auth.client.profile_image
                          ? `/storage/${auth.client.profile_image}`
                          : "/images/default-avatar.jpg"
                      }
                      alt="Profile"
                      className="w-8 h-8 md:w-10 md:h-10 rounded-full object-cover border-2 border-white cursor-pointer hover:border-[#ffd22f] transition"
                    />
                  </Link>
                </div>
              ) : (
                <Link
                  href="/login"
                  className="bg-[#ffd22f] text-[#013064] px-4 md:px-6 py-1.5 md:py-2 text-sm font-semibold hover:bg-[#ffe066] transition"
                >
                  Login
                </Link>
              )}

              {/* Language Switcher */}
              <button
                onClick={toggleLanguage}
                className="flex items-center gap-1 p-1.5 md:p-2 hover:bg-[#024b8a] rounded transition group"
                title={currentLanguage === "id" ? "Switch to English" : "Ganti ke Bahasa Indonesia"}
              >
                {currentLanguage === "id" ? (
                  // Indonesia Flag
                  <div className="w-6 h-6 md:w-7 md:h-7 rounded overflow-hidden shadow-md group-hover:scale-110 transition-transform">
                    <div className="w-full h-1/2 bg-red-600"></div>
                    <div className="w-full h-1/2 bg-white"></div>
                  </div>
                ) : (
                  // USA Flag
                  <div className="w-6 h-6 md:w-7 md:h-7 rounded overflow-hidden shadow-md group-hover:scale-110 transition-transform">
                    <svg viewBox="0 0 60 30" className="w-full h-full">
                      {/* Red stripes */}
                      <rect width="60" height="30" fill="#B22234"/>
                      <rect y="3.85" width="60" height="2.3" fill="white"/>
                      <rect y="7.7" width="60" height="2.3" fill="white"/>
                      <rect y="11.55" width="60" height="2.3" fill="white"/>
                      <rect y="15.4" width="60" height="2.3" fill="white"/>
                      <rect y="19.25" width="60" height="2.3" fill="white"/>
                      <rect y="23.1" width="60" height="2.3" fill="white"/>
                      {/* Blue canton */}
                      <rect width="24" height="15.4" fill="#3C3B6E"/>
                      {/* Stars (simplified) */}
                      <g fill="white">
                        <circle cx="3" cy="2" r="0.8"/>
                        <circle cx="7" cy="2" r="0.8"/>
                        <circle cx="11" cy="2" r="0.8"/>
                        <circle cx="15" cy="2" r="0.8"/>
                        <circle cx="19" cy="2" r="0.8"/>
                        <circle cx="5" cy="4.5" r="0.8"/>
                        <circle cx="9" cy="4.5" r="0.8"/>
                        <circle cx="13" cy="4.5" r="0.8"/>
                        <circle cx="17" cy="4.5" r="0.8"/>
                        <circle cx="21" cy="4.5" r="0.8"/>
                        <circle cx="3" cy="7" r="0.8"/>
                        <circle cx="7" cy="7" r="0.8"/>
                        <circle cx="11" cy="7" r="0.8"/>
                        <circle cx="15" cy="7" r="0.8"/>
                        <circle cx="19" cy="7" r="0.8"/>
                        <circle cx="5" cy="9.5" r="0.8"/>
                        <circle cx="9" cy="9.5" r="0.8"/>
                        <circle cx="13" cy="9.5" r="0.8"/>
                        <circle cx="17" cy="9.5" r="0.8"/>
                        <circle cx="21" cy="9.5" r="0.8"/>
                        <circle cx="3" cy="12" r="0.8"/>
                        <circle cx="7" cy="12" r="0.8"/>
                        <circle cx="11" cy="12" r="0.8"/>
                        <circle cx="15" cy="12" r="0.8"/>
                        <circle cx="19" cy="12" r="0.8"/>
                      </g>
                    </svg>
                  </div>
                )}
              </button>

              {/* Hamburger Menu Button - Mobile */}
              <button
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                className="lg:hidden text-white p-2 hover:text-[#ffd22f] transition"
                aria-label="Toggle menu"
              >
                {isMobileMenuOpen ? (
                  <X className="w-6 h-6" />
                ) : (
                  <Menu className="w-6 h-6" />
                )}
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Mobile Menu Overlay */}
      {isMobileMenuOpen && (
        <div 
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}

      {/* Mobile Menu Drawer */}
      <div className={`fixed top-0 right-0 h-full w-64 bg-[#013064] z-50 transform transition-transform duration-300 lg:hidden ${
        isMobileMenuOpen ? 'translate-x-0' : 'translate-x-full'
      }`}>
        <div className="flex flex-col h-full">
          {/* Mobile Menu Header */}
          <div className="flex justify-between items-center p-4 border-b border-[#024b8a]">
            <span className="text-[#ffd22f] font-bold text-lg">
              {currentLanguage === "id" ? "Menu" : "Menu"}
            </span>
            <button
              onClick={() => setIsMobileMenuOpen(false)}
              className="text-white hover:text-[#ffd22f] transition"
            >
              <X className="w-6 h-6" />
            </button>
          </div>

          {/* Mobile Menu Items */}
          <div className="flex flex-col p-4 space-y-4">
            {navItems.map((item) => (
              <Link
                key={item.key}
                href={item.href}
                onClick={() => setIsMobileMenuOpen(false)}
                className={`text-base py-2 transition ${
                  activePage === item.key
                    ? "text-[#ffd22f] font-semibold"
                    : "text-white hover:text-[#ffd22f]"
                }`}
              >
                {item.name}
              </Link>
            ))}

            {/* Language Switcher in Mobile Menu */}
            <div className="pt-4 border-t border-[#024b8a]">
              <button
                onClick={toggleLanguage}
                className="flex items-center gap-3 text-white hover:text-[#ffd22f] transition w-full py-2"
              >
                {currentLanguage === "id" ? (
                  <>
                    <div className="w-8 h-8 rounded overflow-hidden shadow-md flex-shrink-0">
                      <div className="w-full h-1/2 bg-red-600"></div>
                      <div className="w-full h-1/2 bg-white"></div>
                    </div>
                    <span className="text-base">Bahasa Indonesia</span>
                  </>
                ) : (
                  <>
                    <div className="w-8 h-8 rounded overflow-hidden shadow-md flex-shrink-0">
                      <svg viewBox="0 0 60 30" className="w-full h-full">
                        <rect width="60" height="30" fill="#B22234"/>
                        <rect y="3.85" width="60" height="2.3" fill="white"/>
                        <rect y="7.7" width="60" height="2.3" fill="white"/>
                        <rect y="11.55" width="60" height="2.3" fill="white"/>
                        <rect y="15.4" width="60" height="2.3" fill="white"/>
                        <rect y="19.25" width="60" height="2.3" fill="white"/>
                        <rect y="23.1" width="60" height="2.3" fill="white"/>
                        <rect width="24" height="15.4" fill="#3C3B6E"/>
                        <g fill="white">
                          <circle cx="3" cy="2" r="0.8"/>
                          <circle cx="7" cy="2" r="0.8"/>
                          <circle cx="11" cy="2" r="0.8"/>
                          <circle cx="15" cy="2" r="0.8"/>
                          <circle cx="19" cy="2" r="0.8"/>
                          <circle cx="5" cy="4.5" r="0.8"/>
                          <circle cx="9" cy="4.5" r="0.8"/>
                          <circle cx="13" cy="4.5" r="0.8"/>
                          <circle cx="17" cy="4.5" r="0.8"/>
                          <circle cx="21" cy="4.5" r="0.8"/>
                          <circle cx="3" cy="7" r="0.8"/>
                          <circle cx="7" cy="7" r="0.8"/>
                          <circle cx="11" cy="7" r="0.8"/>
                          <circle cx="15" cy="7" r="0.8"/>
                          <circle cx="19" cy="7" r="0.8"/>
                          <circle cx="5" cy="9.5" r="0.8"/>
                          <circle cx="9" cy="9.5" r="0.8"/>
                          <circle cx="13" cy="9.5" r="0.8"/>
                          <circle cx="17" cy="9.5" r="0.8"/>
                          <circle cx="21" cy="9.5" r="0.8"/>
                          <circle cx="3" cy="12" r="0.8"/>
                          <circle cx="7" cy="12" r="0.8"/>
                          <circle cx="11" cy="12" r="0.8"/>
                          <circle cx="15" cy="12" r="0.8"/>
                          <circle cx="19" cy="12" r="0.8"/>
                        </g>
                      </svg>
                    </div>
                    <span className="text-base">English</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}