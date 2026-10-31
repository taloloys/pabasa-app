export default function ApplicationLogo({
    className = 'h-11 w-11',
    size = 'md',
    showWordmark = true,
    wordmarkClassName = '',
    inverted = false,
    ...props
}) {
    return (
        <div className={`flex items-center space-x-3.5 select-none ${props.containerClassName || ''}`}>
            {/* Sacred Emblem: Lectionary & Cross */}
            <div
                className={`relative flex items-center justify-center rounded-2xl bg-gradient-to-b from-liturgical-900 via-liturgical-950 to-slate-950 text-gold-400 shadow-md ring-1 ring-gold-400/40 p-2 overflow-hidden transition-all duration-200 group-hover:ring-gold-400/70 ${className}`}
            >
                {/* Subtle Amber Candlelight Glow in Center */}
                <div className="absolute inset-0 bg-radial from-gold-500/20 via-transparent to-transparent opacity-80" />

                <svg
                    viewBox="0 0 32 32"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    className="relative z-10 w-full h-full text-gold-400"
                >
                    {/* Altar Light Rays */}
                    <path
                        d="M16 2L16 6M16 26L16 30M2 16L6 16M26 16L30 16"
                        stroke="currentColor"
                        strokeWidth="1.2"
                        strokeLinecap="round"
                        className="opacity-40"
                    />

                    {/* Open Lectionary / Sacred Scriptures */}
                    <path
                        d="M6 21.5C8.5 20.2 12 20.2 16 22.5C20 20.2 23.5 20.2 26 21.5V9.5C23.5 8.2 20 8.2 16 10.5C12 8.2 8.5 8.2 6 9.5V21.5Z"
                        fill="currentColor"
                        fillOpacity="0.15"
                        stroke="currentColor"
                        strokeWidth="1.6"
                        strokeLinejoin="round"
                    />
                    <path
                        d="M16 10.5V22.5"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                    />

                    {/* Liturgical Reading Ribbon Marker */}
                    <path
                        d="M16 22.5C16 25 14.5 26.5 13.5 27.5L16 26L18.5 27.5C17.5 26.5 16 25 16 22.5Z"
                        fill="#F59E0B"
                        stroke="#B45309"
                        strokeWidth="0.8"
                    />

                    {/* Sanctus Cross protruding from Lectionary */}
                    <path
                        d="M16 4V13M13 7H19"
                        stroke="#FDE68A"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </div>

            {showWordmark && (
                <div className={`flex flex-col text-left ${wordmarkClassName}`}>
                    <div className="flex items-center space-x-1.5">
                        <span
                            className={`font-serif tracking-widest font-black leading-none ${
                                size === 'lg' ? 'text-2xl' : 'text-xl'
                            } ${inverted ? 'text-ivory-100' : 'text-liturgical-950'}`}
                            style={{ fontFamily: 'var(--font-cinzel, serif)' }}
                        >
                            PABASA
                        </span>
                        <span className="h-1.5 w-1.5 rounded-full bg-gold-500 ring-2 ring-gold-300/40" />
                    </div>
                    <span
                        className={`text-[9px] font-semibold tracking-widest uppercase mt-0.5 ${
                            inverted ? 'text-gold-300' : 'text-gold-700'
                        }`}
                        style={{ letterSpacing: '0.14em' }}
                    >
                        Liturgical Intentions & Lectionary
                    </span>
                </div>
            )}
        </div>
    );
}
