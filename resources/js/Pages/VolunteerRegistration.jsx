import { Head, router, useForm, usePage } from "@inertiajs/react";
import toast, { Toaster } from "react-hot-toast";
import { Wizard } from "react-use-wizard";
import VolunteerStep1 from "./VolunteerRegistration/VolunteerStep1";
import VolunteerStep2 from "./VolunteerRegistration/VolunteerStep2";
import VolunteerStep3 from "./VolunteerRegistration/VolunteerStep3";
import VolunteerStep4 from "./VolunteerRegistration/VolunteerStep4";
import VolunteerStep5 from "./VolunteerRegistration/VolunteerStep5";
import VolunteerStep6 from "./VolunteerRegistration/VolunteerStep6";
import { useEffect, useState } from "react";

export default function VolunteerRegistration({ events }) {
  const { flash } = usePage().props;
  const { data, setData, post, processing, errors, reset } = useForm({
    name: "",
    phone: "",
    email: "",
    event: "",
    job_title: "",
    organization: "",
    cv: "",
  });
  const [goBackToFirstStep, setGoBackToFirstStep] = useState(false);

  const handleChange = ({ target: { name, value } }) => {
    setData(name, value);
  };

  const handleSubmit = () => {
    console.log(data);
    post(route("volunteer.submit_registration"), {
      data: data,
      onSuccess: (page) => {
        console.log("onSuccess triggered!", page);
        reset();

        setGoBackToFirstStep(true);
      },

      onError: (errors) => {
        console.log(errors);
      },
      onFinish: () => {
        console.log("onFinish triggered - request completed");
      },
    });
  };

  useEffect(() => {
    if (!flash?.info) return;

    const info = flash.info;

    // Handle different info types
    if (typeof info === "string") {
      toast(info);
    } else if (typeof info === "object") {
      // Handle your backend format: ['error' => 'info']
      if (info.error) {
        toast.error(info.error);
      } else if (info.success) {
        toast.success(info.success, {
          duration: 12 * 1000,
        });
      } else if (info.info) {
        toast.info(info.info);
      } else if (info.warning) {
        toast.warning(info.warning);
      }
    }
  }, [flash?.info]);

  return (
    <>
      <Head title="Register Volunteer" />
      <Toaster />
      <div className="min-h-screen bg-gray-100 dark:bg-gray-900 py-8">
        <div className="max-w-3xl mx-auto px-4">
          <div className="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md border dark:border-gray-700">
            {/* Header */}
            <div className="flex justify-between items-start mb-6">
              <h1 className="text-2xl font-bold text-gray-800 dark:text-white">
                Registrasi Volunteer
              </h1>
            </div>

            <Wizard>
              <VolunteerStep1
                data={data}
                setData={setData}
                handleChange={handleChange}
              />
              <VolunteerStep2
                data={data}
                setData={setData}
                handleChange={handleChange}
              />
              <VolunteerStep3
                data={data}
                setData={setData}
                handleChange={handleChange}
                events={events}
              />
              <VolunteerStep4
                data={data}
                setData={setData}
                handleChange={handleChange}
              />
              <VolunteerStep5
                data={data}
                setData={setData}
                handleChange={handleChange}
              />
              <VolunteerStep6
                data={data}
                setData={setData}
                handleChange={handleChange}
                onSubmit={handleSubmit}
                processing={processing}
                goBackToFirstStep={goBackToFirstStep}
                setGoBackToFirstStep={setGoBackToFirstStep}
              />
            </Wizard>
          </div>
        </div>
      </div>
    </>
  );
}
