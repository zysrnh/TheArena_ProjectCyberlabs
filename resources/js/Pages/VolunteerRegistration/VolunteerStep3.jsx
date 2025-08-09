import { useWizard } from "react-use-wizard";
import { z } from "zod";
import { useState } from "react";
import SelectField from "../../Components/Forms/SelectField";
import WizardStepButton from "../../Components/UI/WizardStepButton";

const schema = z.object({
  event: z.string().min(1, { message: "Event Wajib Diisi." }),
});

export default function VolunteerStep3({ data, setData, handleChange, events }) {
  const { isFirstStep, isLastStep, previousStep, nextStep } = useWizard();
  const [errors, setErrors] = useState({ event: "" });

  const eventOptions = events.map((event) => {
    return {
      value: event.id,
      label: event.name,
    };
  });

  const handleNext = () => {
    const result = schema.safeParse(data);

    if (!result.success) {
      const newErrors = { event: "" };

      result.error.issues.forEach((issue) => {
        const field = issue.path[0];
        if (field in newErrors) {
          newErrors[field] = issue.message;
        }
      });

      setErrors(newErrors);
      return;
    }

    setErrors({ event: "" });
    nextStep();
  };
  return (
    <>
      <div className="min-w-full">
        <SelectField
          label="Event"
          name="event"
          id="event"
          value={data.event}
          onChange={handleChange}
          options={eventOptions}
          required
          error={errors?.event}
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
