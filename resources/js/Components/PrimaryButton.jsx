export default function PrimaryButton({
    className = '',
    disabled,
    variant = 'liturgical', // 'liturgical' or 'gold'
    children,
    ...props
}) {
    const variantStyles =
        variant === 'gold'
            ? 'bg-gradient-to-r from-gold-500 via-gold-600 to-gold-700 text-white border-gold-400/60 shadow-gold-900/20 hover:from-gold-400 hover:to-gold-600 focus:ring-gold-400'
            : 'bg-gradient-to-r from-liturgical-800 via-liturgical-900 to-liturgical-950 text-gold-100 border-gold-400/30 shadow-liturgical-950/25 hover:from-liturgical-700 hover:to-liturgical-900 hover:border-gold-300 focus:ring-gold-400';

    return (
        <button
            {...props}
            className={`inline-flex items-center justify-center rounded-xl border px-5 py-2.5 text-xs font-semibold tracking-wider uppercase transition-all duration-200 ease-in-out shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 active:scale-[0.99] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 ${variantStyles} ${className}`}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
