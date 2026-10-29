import ApplicationLogo from '@/Components/ApplicationLogo';
import LocaleSwitcher from '@/Components/LocaleSwitcher';
import FlashMessage from '@/Components/FlashMessage';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children, title, subtitle, latinMotto = 'Ad Majorem Dei Gloriam' }) {
    return (
        <div className="relative min-h-screen flex flex-col items-center justify-between bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F4ECE0] text-slate-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
            {/* Atmospheric Cathedral Glow & Sacred Light Beams */}
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                {/* Central Altar Light Beam */}
                <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-gradient-to-b from-gold-300/20 via-gold-400/5 to-transparent blur-3xl opacity-70" />
                {/* Subtle Amethyst Rosette Halos */}
                <div className="absolute -top-24 left-1/4 w-96 h-96 rounded-full bg-liturgical-500/10 blur-3xl" />
                <div className="absolute top-12 right-1/4 w-80 h-80 rounded-full bg-gold-500/10 blur-3xl" />
            </div>

            {/* Top Sanctuary Navigation */}
            <header className="relative z-20 w-full max-w-6xl px-6 py-6 flex items-center justify-between">
                <Link href="/" className="hover:opacity-90 transition group">
                    <ApplicationLogo className="h-11 w-11 shadow-md shadow-liturgical-950/10" />
                </Link>

                <div className="flex items-center space-x-3">
                    <span className="hidden sm:inline-block text-[11px] font-serif italic text-gold-700/80 tracking-wide">
                        {latinMotto}
                    </span>
                    <LocaleSwitcher />
                </div>
            </header>

            <FlashMessage />

            {/* Main Sacred Portal Card */}
            <main className="relative z-10 w-full px-4 sm:max-w-lg my-auto py-6">
                {/* Arched Architectural Header */}
                <div className="text-center mb-6 space-y-1">
                    {/* Altar Cross Icon Accent */}
                    <div className="mx-auto mb-2 flex h-8 w-8 items-center justify-center text-gold-600 opacity-80">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-5 h-5">
                            <path d="M12 2v20M7 7h10" strokeLinecap="round" />
                        </svg>
                    </div>

                    {title && (
                        <h1
                            className="text-2xl sm:text-3xl font-bold tracking-tight text-liturgical-950 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {title}
                        </h1>
                    )}

                    {subtitle && (
                        <p className="mx-auto max-w-sm text-xs text-stone-600 leading-relaxed font-sans">
                            {subtitle}
                        </p>
                    )}
                </div>

                {/* Sanctuary Card with Altar Gold Border */}
                <div className="relative overflow-hidden rounded-3xl border border-gold-300/70 bg-white/95 p-7 sm:p-9 shadow-xl shadow-stone-900/5 ring-1 ring-gold-500/20 backdrop-blur-sm">
                    {/* Top Decorative Gold Foil Hairline */}
                    <div className="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-gold-300 via-gold-500 to-gold-300" />

                    {children}
                </div>

                {/* Reverent Bottom Note */}
                <div className="mt-8 text-center space-y-1">
                    <p className="text-[11px] font-medium text-stone-500">
                        Pabasa Philippine Catholic Reading-Intention Platform
                    </p>
                    <p className="text-[10px] text-stone-400 font-serif italic">
                        Sanctificetur Nomen Tuum &bull; {new Date().getFullYear()}
                    </p>
                </div>
            </main>

            {/* Bottom Spacer */}
            <div className="h-6" />
        </div>
    );
}
