import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const resources = {
  id: {
    translation: {
      // Navigation
      nav: {
        home: "Beranda",
        about: "Tentang",
        schedule: "Jadwal & Hasil",
        live: "Siaran Langsung",
        contact: "Kontak",
        login: "Login",
        menu: "Menu"
      },
      // Home Page
      home: {
        title: "Selamat Datang di The Arena",
        subtitle: "Kompetisi Basketball Terbaik",
        watchLive: "Tonton Siaran Langsung",
        upcomingMatches: "Pertandingan Mendatang"
      },
      // About Page
      about: {
        title: "Tentang Kami",
        description: "The Arena adalah platform..."
      },
      // Common words
      common: {
        loading: "Memuat...",
        submit: "Kirim",
        cancel: "Batal",
        save: "Simpan",
        edit: "Edit",
        delete: "Hapus"
      }
    }
  },
  en: {
    translation: {
      // Navigation
      nav: {
        home: "Home",
        about: "About",
        schedule: "Schedule & Results",
        live: "Live Streaming",
        contact: "Contact",
        login: "Login",
        menu: "Menu"
      },
      // Home Page
      home: {
        title: "Welcome to The Arena",
        subtitle: "Premier Basketball Competition",
        watchLive: "Watch Live Stream",
        upcomingMatches: "Upcoming Matches"
      },
      // About Page
      about: {
        title: "About Us",
        description: "The Arena is a platform..."
      },
      // Common words
      common: {
        loading: "Loading...",
        submit: "Submit",
        cancel: "Cancel",
        save: "Save",
        edit: "Edit",
        delete: "Delete"
      }
    }
  }
};

i18n
  .use(initReactI18next)
  .init({
    resources,
    lng: localStorage.getItem('language') || 'id',
    fallbackLng: 'id',
    interpolation: {
      escapeValue: false
    }
  });

export default i18n;