import { Head, router, useForm, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import toast, { Toaster } from "react-hot-toast";
import LoadingSpinner from "../Components/UI/LoadingSpinner";

export default function ChooseSeat({
  images,
  seatingType,
  seats,
  formData,
  maxColumnCount,
}) {
  const { flash } = usePage().props;
  const [isGoingBack, setIsGoingBack] = useState(false);
  const { data, setData, post, processing, errors } = useForm({
    seat: null,
    seat_id: null,
  });

  const chooseSeat = (seat) => {
    if (!seat.is_available) {
      toast.error("Kursi telah terisi");
      return;
    }

    setData({
      seat: seat,
      seat_id: seat.id,
    });
  };

  const handleSubmit = () => {
    post(route("sac_vip.choose_seat"), {
      seat_id: data.seat.id,
    });
  };

  const goBack = () => {
    if (isGoingBack) return;
    setIsGoingBack(true);
    router.get(route("sac_vip.registration"));
  };

  useEffect(() => {
    if (!flash?.info) return;

    const info = flash.info;
    if (typeof info === "string") {
      toast(info);
    } else if (typeof info === "object") {
      if (info.error) {
        toast.error(info.error);
      } else if (info.success) {
        toast.success(info.success);
      } else if (info.info) {
        toast.info(info.info);
      } else if (info.warning) {
        toast.warning(info.warning);
      }
    }
  }, [flash?.info]);

  return (
    <>
      <Head title="Pilih Kursi" />
      <Toaster />
      <div className="bg-[#0a0a0a] text-white min-h-screen flex flex-col justify-between">
        {/* Top Section */}
        <header className="flex justify-between items-center p-6">
          <img
            src={images["sby_art_white"]}
            alt="SBY Logo"
            className="h-20 md:h-30 object-contain"
          />
          <div className="flex space-x-4">
            <img
              src={images["ekraf_white"]}
              alt="EKRAF Logo"
              className="h-18 md:h-30 object-contain"
            />
            <img
              src={images["kkri_white"]}
              alt="KEMENBUD Logo"
              className="h-18 md:h-30 object-contain"
            />
          </div>
        </header>

        {/* Main Content */}
        <main className="flex flex-col items-center px-4">
          <h1 className="cinzel text-2xl md:text-3xl font-bold text-center">
            PILIH KURSI
          </h1>
          <p className="cinzel text-lg md:text-xl mb-6 text-center">
            {seatingType}
          </p>

          {/* Participant Info */}
          <div className="w-full max-w-md bg-[#1a1a1a] rounded-lg p-4 mb-6">
            <table className="w-full poppins text-sm md:text-base">
              <tbody>
                <tr>
                  <td className="py-1">Nama</td>
                  <td className="px-1">:</td>
                  <td>{formData.name}</td>
                </tr>
                <tr>
                  <td className="py-1">Email</td>
                  <td className="px-1">:</td>
                  <td>{formData.email}</td>
                </tr>
                <tr>
                  <td className="py-1">Intitusi</td>
                  <td className="px-1">:</td>
                  <td>{formData.organization}</td>
                </tr>
              </tbody>
            </table>
            <button
              onClick={goBack}
              disabled={isGoingBack}
              className={`cursor-pointer mt-4 w-full py-2 rounded-md font-medium flex items-center justify-center ${
                isGoingBack ? "bg-blue-400" : "bg-gray-600 hover:bg-gray-700"
              }`}
            >
              {isGoingBack ? (
                <LoadingSpinner />
              ) : (
                <>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth={1.5}
                    stroke="currentColor"
                    className="w-5 h-5 mr-2"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                    />
                  </svg>
                  Edit Informasi Saya
                </>
              )}
            </button>
          </div>

          {/* Seating Grid */}
          <div className="overflow-x-auto w-full max-w-3xl mb-6">
            <div
              className="grid gap-2"
              style={{
                gridTemplateColumns: `repeat(${maxColumnCount}, minmax(50px, 1fr))`,
              }}
            >
              {seats.map((seat) => (
                <button
                  key={seat.id}
                  onClick={() => chooseSeat(seat)}
                  style={{
                    gridColumnStart: seat.column,
                    gridRowStart: seat.row,
                  }}
                  className={`p-3 cursor-pointer rounded-md text-center flex justify-center items-center poppins text-sm font-medium
                    ${
                      seat.is_available
                        ? data.seat?.id === seat.id
                          ? "bg-blue-700 text-white"
                          : "bg-gray-700 hover:bg-blue-600"
                        : "bg-gray-500 text-gray-300 cursor-not-allowed"
                    }`}
                >
                  {seat.label}
                </button>
              ))}
            </div>
          </div>

          {/* Submit */}
          {data.seat?.id && (
            <button
              type="button"
              onClick={handleSubmit}
              disabled={processing}
              className={`cursor-pointer w-full max-w-md text-white font-medium py-3 rounded-md flex items-center justify-center ${
                processing ? "bg-blue-400" : "bg-blue-600 hover:bg-blue-700"
              }`}
            >
              {processing ? (
                <LoadingSpinner />
              ) : (
                <>Pilih Kursi <b className="ml-2">{data.seat.label}</b></>
              )}
            </button>
          )}
        </main>

        {/* Footer */}
        <footer className="text-center text-xs text-gray-400 p-4">
          Copyright © 2025 CyberLabs | Powered By Alco Media Indonesia
        </footer>
      </div>
    </>
  );
}
