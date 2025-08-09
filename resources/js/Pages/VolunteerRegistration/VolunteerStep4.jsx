import { useWizard } from "react-use-wizard";
import InputField from "../../Components/Forms/InputField";
import WizardStepButton from "../../Components/UI/WizardStepButton";
import { z } from "zod";
import { useState } from "react";

const schema = z.object({
  job_title: z.string().min(1, { message: "Pekerjaan wajib diisi." }),
});

export default function VolunteerStep4({ data, setData, handleChange }) {
  const { isFirstStep, isLastStep, previousStep, nextStep } = useWizard();
  const [errors, setErrors] = useState({ job_title: "" });

  const handleNext = () => {
    const result = schema.safeParse(data);

    if (!result.success) {
      const newErrors = { job_title: "" };

      result.error.issues.forEach((issue) => {
        const field = issue.path[0];
        if (field in newErrors) {
          newErrors[field] = issue.message;
        }
      });

      setErrors(newErrors);
      return;
    }

    setErrors({ job_title: "" });
    nextStep();
  };
  return (
    <>
      <div className="min-w-full">
        <InputField
          label="Pekerjaan"
          name="job_title"
          id="job_title"
          value={data.job_title}
          onChange={handleChange}
          required
          error={errors?.job_title}
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
