import { useState } from "react";
import { useWizard } from "react-use-wizard";
import { z } from "zod";
import InputField from "../../Components/Forms/InputField";
import WizardStepButton from "../../Components/UI/WizardStepButton";

const schema = z.object({
  organization: z.string().min(1, { message: "Institusi wajib diisi." }),
});

export default function VolunteerStep4({ data, setData, handleChange }) {
  const { isFirstStep, isLastStep, previousStep, nextStep } = useWizard();
  const [errors, setErrors] = useState({ organization: "" });

  const handleNext = () => {
    const result = schema.safeParse(data);

    if (!result.success) {
      const newErrors = { organization: "" };

      result.error.issues.forEach((issue) => {
        const field = issue.path[0];
        if (field in newErrors) {
          newErrors[field] = issue.message;
        }
      });

      setErrors(newErrors);
      return;
    }

    setErrors({ organization: "" });
    nextStep();
  };
  return (
    <>
      <div className="min-w-full">
        <InputField
          label="Institusi"
          name="organization"
          id="organization"
          value={data.organization}
          onChange={handleChange}
          required
          error={errors?.organization}
        />

        <WizardStepButton
          isFirstStep={isFirstStep}
          isLastStep={isLastStep}
          previousStep={previousStep}
          nextStep={handleNext}
          handleSubmit={handleNext}
        />
      </div>
    </>
  );
}
