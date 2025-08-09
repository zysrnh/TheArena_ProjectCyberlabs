import clsx from "clsx";

export default function FileInputField({
  label,
  name,
  id,
  value,
  onChange,
  accept,
  required = false,
  error,
  className,
}) {
  const handleFileChange = (e) => {
    const file = e.target.files[0];

    // Create event object similar to regular inputs
    const event = {
      target: {
        name,
        value: file || null,
        type: "file",
      },
    };

    onChange(event);
  };

  return (
    <div className={clsx("mb-4", className)}>
      {label && (
        <label
          htmlFor={id}
          className="block text-sm font-medium text-gray-700 mb-2"
        >
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </label>
      )}

      <input
        type="file"
        id={id}
        name={name}
        onChange={handleFileChange}
        accept={accept}
        required={required}
        className={clsx(
          "block w-full text-sm text-gray-500",
          "file:mr-4 file:py-2 file:px-4",
          "file:rounded-md file:border-0",
          "file:text-sm file:font-medium",
          "file:bg-blue-50 file:text-blue-700",
          "hover:file:bg-blue-100",
          "border border-gray-300 rounded-md",
          "focus:ring-2 focus:ring-blue-500 focus:border-blue-500",
          {
            "border-red-300": error,
          }
        )}
      />

      {value && (
        <p className="mt-1 text-sm text-green-600">
          ✓ File selected: {value.name}
        </p>
      )}

      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
  );
}
