import { useEffect, useState } from "react";
import { useWizard } from "react-use-wizard";
import { z } from "zod";
import WizardStepButton from "../../Components/UI/WizardStepButton";
import FileInputField from "../../Components/Forms/FileInputField";
import { router } from "@inertiajs/react";

const schema = z.object({
  cv: z
    .instanceof(File, { message: "Dokumen CV wajib diunggah." })
    .refine((file) => file.size <= 2 * 1024 * 1024, {
      message: "Ukuran file maksimal 2MB.",
    }),
});

export default function VolunteerStep6({
  data,
  setData,
  handleChange,
  onSubmit,
  processing,
  goBackToFirstStep = false,
  setGoBackToFirstStep,
}) {
  const { isFirstStep, isLastStep, previousStep, nextStep, goToStep } =
    useWizard();
  const [errors, setErrors] = useState({ cv: "" });

  const handleNext = () => {
    const result = schema.safeParse(data);
    // console.log(result);

    if (!result.success) {
      // console.log(result.error);
      const newErrors = { cv: "" };

      result.error.issues.forEach((issue) => {
        const field = issue.path[0];
        if (field in newErrors) {
          newErrors[field] = issue.message;
        }
      });

      console.log(newErrors);
      setErrors(newErrors);
      return;
    }

    setErrors({ cv: "" });
    onSubmit();
  };

  useEffect(() => {
    console.log('goBackToFirstStep', goBackToFirstStep);
    if (goBackToFirstStep) {
      setGoBackToFirstStep(false);
      goToStep(0); // Go to first step (0-indexed)
      console.log('goBackToFirstStep', goBackToFirstStep);
    }
  }, [goBackToFirstStep, goToStep]);

  // useEffect(() => {
  //   // Check if there's a success message and reset wizard to first step
  //   if (flash?.info?.success) {
  //     goToStep(0); // Go to first step (0-indexed)

  //     setTimeout(() => {
  //       router.reload({
  //         only: ["events"],
  //         preserveState: true,
  //         preserveScroll: true,
  //       });
  //     }, 100);
  //   }
  // }, [flash?.info, goToStep]);
  return (
    <>
      <div className="min-w-full">
        <FileInputField
          label="Upload CV Anda (Maksimal 2MB) (PDF, JPG, JPEG, PNG)"
          name="cv"
          id="cv"
          value={data.cv}
          onChange={handleChange}
          accept=".pdf,.jpg,.jpeg,.png"
          required
          error={errors?.cv}
        />

        <WizardStepButton
          isFirstStep={isFirstStep}
          isLastStep={isLastStep}
          previousStep={previousStep}
          nextStep={handleNext}
          handleSubmit={handleNext}
          processing={processing}
        />
      </div>
    </>
  );
}
