export default function WizardStepButton({
  isFirstStep,
  isLastStep,
  previousStep,
  nextStep,
  handleSubmit,
  processing = false,
  submitText = "Submit",
  nextText = "Selanjutnya",
  prevText = "Kembali"
}) {
  return (
    <div className="flex justify-between mt-6">
      {!isFirstStep && (
        <button
          type="button"
          onClick={previousStep}
          disabled={processing}
          className="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {prevText}
        </button>
      )}
      
      {isLastStep ? (
        <button
          type="button"
          onClick={handleSubmit}
          disabled={processing}
          className="cursor-pointer px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 disabled:opacity-50 disabled:cursor-not-allowed ml-auto flex items-center"
        >
          {processing && (
            <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          )}
          {processing ? 'Mengupload...' : submitText}
        </button>
      ) : (
        <button
          type="button"
          onClick={nextStep}
          disabled={processing}
          className="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed ml-auto"
        >
          {nextText}
        </button>
      )}
    </div>
  );
}