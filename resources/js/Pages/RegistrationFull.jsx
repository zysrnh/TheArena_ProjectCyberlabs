import { useState } from "react";

export default function RegistrationWelcome({ images }) {
  return (
    <>
      <div className="welcome-bg-main min-h-screen flex flex-col justify-between text-white">
        <header className="flex flex-col justify-center items-center p-5 gap-8">
          <img
            src={images["sby_art_white"]}
            alt="SBY Logo"
            className="h-25 md:h-25 object-contain"
          />
          <div className="flex">
            <div className="flex space-x-4">
              <img
                src={images["ekraf_white"]}
                alt="EKRAF Logo"
                className="h-20 md:h-25 object-contain"
              />
              <img
                src={images["kkri_white"]}
                alt="KEMENBUD Logo"
                className="h-20 md:h-25 object-contain"
              />
            </div>
          </div>
        </header>

        <div className="w-full flex justify-center">
          <main className="flex flex-col items-center px-4 text-center gap-4 max-w-3xl">
            <h1 className="cinzel text-4xl md:text-5xl font-bold">
              Mohon Maaf Pendaftaran Telah Ditutup Karena Melebihi Kapasitas
            </h1>
            <p className="cinzel text-3xl md:text-4xl mb-8">
              Terima Kasih Atas Partisipasi Anda
            </p>
          </main>
        </div>

        <footer className="text-center text-xs text-gray-300 p-4">
          Copyright © 2025 CyberLabs | Powered By Alco Media Indonesia
        </footer>
      </div>
    </>
  );
}
