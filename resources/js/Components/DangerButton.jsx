export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center rounded-xl border border-rose-300/60 bg-gradient-to-r from-rose-700 via-rose-800 to-rose-900 px-5 py-2.5 text-xs font-semibold tracking-wider uppercase text-white shadow-xs transition-all duration-150 ease-in-out hover:from-rose-600 hover:to-rose-800 hover:shadow-md hover:shadow-rose-900/20 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 active:scale-[0.99] cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed ${
                    disabled && 'opacity-40'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
