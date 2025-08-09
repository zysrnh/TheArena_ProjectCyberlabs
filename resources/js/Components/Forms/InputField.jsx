import clsx from "clsx";

export default function InputField({
  label,
  name,
  id,
  type = "text",
  value,
  onChange,
  placeholder,
  required = false,
  error,
  disabled = false,
  className,
  labelClassName,
  inputClassName,
  containerClassName,
  ...inputProps
}) {
  return (
    <div className={clsx("flex flex-col gap-2 lg:gap-4", containerClassName)}>
      <label
        htmlFor={id || name}
        className={clsx(
          "text-base font-semibold md:text-lg lg:text-xl",
          {
            "text-gray-400": disabled,
            "text-red-600": error,
          },
          labelClassName
        )}
      >
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </label>

      <input
        type={type}
        name={name}
        id={id || name}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        required={required}
        disabled={disabled}
        className={clsx(
          "py-2 px-4 text-base bg-gray-100 md:text-lg lg:text-xl lg:py-3 lg:px-5 rounded",
          "border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent",
          "transition-colors duration-200",
          {
            "bg-gray-200 text-gray-500 cursor-not-allowed": disabled,
            "border-red-300 bg-red-50 focus:ring-red-500": error,
            "hover:bg-gray-200": !disabled && !error,
          },
          inputClassName,
          className
        )}
        {...inputProps}
      />

      {error && (
        <span className="text-red-600 text-sm font-medium">{error}</span>
      )}
    </div>
  );
}
