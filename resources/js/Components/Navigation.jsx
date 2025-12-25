import { Link, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { Menu, X } from "lucide-react";

export default function Navigation({ activePage = "" }) {
  const { auth } = usePage().props;
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

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

  const navItems = [
    { name: "Beranda", href: "/", key: "home" },
    { name: "Tentang", href: "/tentang", key: "tentang" },
    { name: "Jadwal & Hasil", href: "/jadwal-hasil", key: "jadwal-hasil" },
    { name: "Siaran Langsung", href: "/siaran-langsung", key: "siaran-langsung" },
    { name: "Kontak", href: "/kontak", key: "kontak" },
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
            <span className="text-[#ffd22f] font-bold text-lg">Menu</span>
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
          </div>
        </div>
      </div>
    </>
  );
}