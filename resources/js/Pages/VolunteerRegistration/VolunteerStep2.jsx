import { useWizard } from "react-use-wizard";
import InputField from "../../Components/Forms/InputField";
import WizardStepButton from "../../Components/UI/WizardStepButton";
import { useState } from "react";
import { z } from "zod";

const schema = z.object({
  email: z
    .string()
    .min(1, { message: "Email wajib diisi." })
    .email({ message: "Format email tidak valid." }),
});

export default function VolunteerStep2({ data, setData, handleChange }) {
  const { isFirstStep, isLastStep, previousStep, nextStep } = useWizard();
  const [errors, setErrors] = useState({ email: "" });

  const handleNext = () => {
    const result = schema.safeParse(data);

    if (!result.success) {
      const newErrors = { email: "" };

      result.error.issues.forEach((issue) => {
        const field = issue.path[0];
        if (field in newErrors) {
          newErrors[field] = issue.message;
        }
      });

      setErrors(newErrors);
      return;
    }

    setErrors({ email: "" });
    nextStep();
  };
  return (
    <>
      <div className="min-w-full">
        <InputField
          label="Email"
          name="email"
          id="email"
          value={data.email}
          onChange={handleChange}
          required
          error={errors?.email}
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
