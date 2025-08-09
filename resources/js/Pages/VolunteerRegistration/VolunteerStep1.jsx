import { useWizard } from "react-use-wizard";
import InputField from "../../Components/Forms/InputField";
import WizardStepButton from "../../Components/UI/WizardStepButton";
import { useState } from "react";
import { z } from "zod";

const schema = z.object({
  name: z.string().min(1, { message: "Nama wajib diisi." }),
  phone: z.string().regex(/^(\+?62|0)\d{8,}$/, {
    message: "Nomor telepon tidak valid (min. 10 digit).",
  }),
});

export default function VolunteerStep1({ data, setData, handleChange }) {
  const { isFirstStep, isLastStep, previousStep, nextStep } = useWizard();
  const [errors, setErrors] = useState({ name: "", phone: "" });

  const handleNext = () => {
    const result = schema.safeParse(data);

    if (!result.success) {
      const newErrors = { name: "", phone: "" };

      result.error.issues.forEach((issue) => {
        const field = issue.path[0];
        if (field in newErrors) {
          newErrors[field] = issue.message;
        }
      });

      setErrors(newErrors);
      return;
    }

    setErrors({ name: "", phone: "" });
    nextStep();
  };
  return (
    <>
      <div className="min-w-full">
        <InputField
          label="Nama Lengkap"
          name="name"
          id="name"
          value={data.name}
          onChange={handleChange}
          required
          error={errors?.name}
        />
        <InputField
          label="Nomor Telepon"
          name="phone"
          id="phone"
          type="tel"
          value={data.phone}
          onChange={handleChange}
          required
          error={errors?.phone}
          containerClassName="mt-4"
        />

        <WizardStepButton
          isFirstStep={isFirstStep}
          isLastStep={isLastStep}
          previousStep={previousStep}
          nextStep={handleNext}
        />
      </div>
    </>
  );
}
