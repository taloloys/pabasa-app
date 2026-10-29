export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center justify-center rounded-xl border border-gold-300/60 bg-white px-5 py-2.5 text-xs font-semibold tracking-wider uppercase text-stone-700 shadow-2xs transition-all duration-150 ease-in-out hover:bg-ivory-100 hover:text-stone-900 hover:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-400/40 focus:ring-offset-2 active:scale-[0.99] cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed ${
                    disabled && 'opacity-40'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
