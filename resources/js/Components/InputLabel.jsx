export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={
                `block text-xs font-semibold uppercase tracking-wider text-stone-700 ` +
                className
            }
        >
            {value ? value : children}
        </label>
    );
}
