import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import LocaleSwitcher from '@/Components/LocaleSwitcher';
import {
    DoveIcon,
    ClipboardListIcon,
    ClockIcon,
    MapPinIcon,
    ShieldIcon,
    ScrollIcon,
} from '@/Components/Icons';

export default function Welcome({ auth, publishedChurches = [] }) {
    const demoAccounts = [
        {
            role: 'Central Administrator',
            title: 'Archdiocese Oversight',
            email: 'admin@pabasa.ph',
            badge: 'Global Authority',
            badgeStyle: 'bg-liturgical-100 text-liturgical-950 border-liturgical-300 ring-1 ring-gold-400/30',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-6 h-6 text-liturgical-800">
                    <path d="M12 2L4 6v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V6l-8-4z" />
                    <path d="M12 8v8M8 12h8" strokeLinecap="round" />
                </svg>
            ),
            description: 'Diocese-level oversight, church directory verification, payment review queue, and immutable system-wide audit logging.',
            latin: 'Auctoritas Centralis',
        },
        {
            role: 'Church Administrator',
            title: 'Cebu Metropolitan Cathedral',
            email: 'churchadmin@pabasa.ph',
            badge: 'Parish Administration',
            badgeStyle: 'bg-amber-50 text-amber-950 border-gold-300 ring-1 ring-gold-400/20',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-6 h-6 text-gold-700">
                    <path d="M3 21h18M5 21V9l7-6 7 6v12M9 21v-6a3 3 0 0 1 6 0v6" />
                    <path d="M12 7v3M10.5 8.5h3" strokeLinecap="round" />
                </svg>
            ),
            description: 'Parish operations, Mass schedule builder, intention categories, staff invitations, and lector rosters.',
            latin: 'Cura Pastoralis',
        },
        {
            role: 'Liturgical Lector',
            title: 'Ministry of the Word',
            email: 'lector@pabasa.ph',
            badge: 'Lector Ministry',
            badgeStyle: 'bg-sky-50 text-sky-950 border-sky-300 ring-1 ring-sky-400/20',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-6 h-6 text-sky-800">
                    <path d="M2 4.5A2.5 2.5 0 0 1 4.5 2H11v18H4.5A2.5 2.5 0 0 0 2 22.5V4.5zM22 4.5A2.5 2.5 0 0 0 19.5 2H13v18h6.5a2.5 2.5 0 0 1 2.5 2.5V4.5z" />
                    <path d="M12 2v20" strokeLinecap="round" />
                </svg>
            ),
            description: 'Digital ambo lectionary, intention proclamation guidelines, and assigned Mass checklists.',
            latin: 'Ministerium Verbi',
        },
        {
            role: 'Faithful Parishioner',
            title: 'Intention & Prayer Portal',
            email: 'user@pabasa.ph',
            badge: 'Verified Parishioner',
            badgeStyle: 'bg-emerald-50 text-emerald-950 border-emerald-300 ring-1 ring-emerald-400/20',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="w-6 h-6 text-emerald-800">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                </svg>
            ),
            description: 'Submit prayer intentions for Thanksgiving, Memorial, and Healing with secure GCash offering verification.',
            latin: 'Populus Dei',
        },
    ];

    // Collect all open upcoming masses across published churches
    const allOpenMasses = [];
    publishedChurches.forEach((church) => {
        (church.mass_schedules || []).forEach((mass) => {
            allOpenMasses.push({
                church,
                mass,
            });
        });
    });

    return (
        <>
            <Head title="Pabasa — Sacred Reading & Mass Intention Management" />

            <div className="relative min-h-screen bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F3ECE0] text-stone-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
                {/* Atmospheric Cathedral Light Halos */}
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div className="absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[600px] bg-gradient-to-b from-gold-300/25 via-liturgical-300/10 to-transparent blur-3xl opacity-80" />
                    <div className="absolute top-96 -left-40 w-96 h-96 rounded-full bg-liturgical-600/10 blur-3xl" />
                    <div className="absolute top-[48rem] -right-40 w-96 h-96 rounded-full bg-gold-500/15 blur-3xl" />
                </div>

                {/* Cathedral Navbar */}
                <header className="relative z-30 border-b border-gold-300/50 bg-white/85 backdrop-blur-md sticky top-0 shadow-xs">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex h-20 justify-between items-center">
                            <div className="flex items-center space-x-6">
                                <Link href="/" className="hover:opacity-95 transition">
                                    <ApplicationLogo className="h-12 w-12" size="lg" />
                                </Link>

                                <nav className="hidden md:flex items-center space-x-5 text-xs font-semibold text-stone-700">
                                    <Link
                                        href={route('churches.index')}
                                        className="hover:text-liturgical-950 transition py-1"
                                    >
                                        Parishes & Masses
                                    </Link>
                                    <Link
                                        href={route('orders.tracking')}
                                        className="hover:text-liturgical-950 transition py-1"
                                    >
                                        Track Intentions
                                    </Link>
                                </nav>
                            </div>

                            <div className="flex items-center space-x-3 sm:space-x-5">
                                <LocaleSwitcher />

                                {auth.user ? (
                                    <div className="flex items-center space-x-2">
                                        <Link
                                            href={route('churches.index')}
                                            className="hidden sm:inline-flex items-center rounded-xl bg-gold-50 border border-gold-300 px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-gold-950 hover:bg-gold-100 transition shadow-2xs"
                                        >
                                            Submit Intention &rarr;
                                        </Link>
                                        <Link
                                            href={route('dashboard')}
                                            className="inline-flex items-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gold-100 border border-gold-400/40 shadow-sm hover:from-liturgical-700 hover:to-liturgical-900 transition"
                                        >
                                            Sanctuary &rarr;
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="flex items-center space-x-2">
                                        <Link
                                            href={route('churches.index')}
                                            className="hidden sm:inline-flex items-center rounded-xl bg-gradient-to-r from-gold-500 to-amber-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-liturgical-950 border border-gold-400 shadow-xs hover:from-gold-400 hover:to-amber-500 transition"
                                        >
                                            Submit Intention &rarr;
                                        </Link>
                                        <Link
                                            href={route('login')}
                                            className="rounded-xl px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-stone-700 hover:bg-gold-50 hover:text-liturgical-950 transition"
                                        >
                                            Sign In
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="inline-flex items-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gold-100 border border-gold-400/40 shadow-sm hover:from-liturgical-700 hover:to-liturgical-900 transition"
                                        >
                                            Register
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Hero Section */}
                <main className="relative z-10 py-12 sm:py-20">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-16">
                        {/* Liturgical Sanctus Title */}
                        <div className="text-center max-w-3xl mx-auto space-y-5">
                            <div className="inline-flex items-center space-x-2 rounded-full border border-gold-300/80 bg-white/80 px-4 py-1.5 shadow-2xs backdrop-blur-xs">
                                <span className="h-2 w-2 rounded-full bg-gold-500 animate-pulse" />
                                <span className="text-xs font-serif font-bold tracking-wide text-liturgical-950">
                                    Philippine Catholic Reading-Intention Platform &bull; GCash Verified
                                </span>
                            </div>

                            <h1
                                className="text-3xl sm:text-6xl font-bold tracking-tight text-liturgical-950 leading-[1.15]"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Reverent Mass Intentions & Liturgical Ministry
                            </h1>

                            <p className="text-base sm:text-lg text-stone-700 leading-relaxed font-sans max-w-2xl mx-auto">
                                A sacred multi-tenant platform for Catholic parishes in the Philippines. Submit Thanksgiving, Memorial, and Healing intentions with GCash verification and live ambo lectionary rosters.
                            </p>

                            <div className="pt-2 text-xs font-serif italic text-gold-800/90 flex items-center justify-center space-x-2">
                                <span className="h-px w-8 bg-gold-400/40" />
                                <span>&ldquo;Dirigatur, Domine, oratio mea sicut incensum in conspectu tuo&rdquo; &mdash; Ps 141:2</span>
                                <span className="h-px w-8 bg-gold-400/40" />
                            </div>

                            {/* Primary Call to Actions */}
                            <div className="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3.5">
                                <Link
                                    href={route('churches.index')}
                                    className="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-liturgical-800 via-liturgical-900 to-liturgical-950 px-8 py-4 text-xs font-bold uppercase tracking-wider text-gold-100 border border-gold-400/50 shadow-md shadow-liturgical-950/20 hover:from-liturgical-700 hover:to-liturgical-900 transition cursor-pointer"
                                >
                                    <DoveIcon className="w-4 h-4 mr-2 text-gold-300" />
                                    <span>Submit Prayer Intentions &rarr;</span>
                                </Link>

                                <Link
                                    href={route('orders.tracking')}
                                    className="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-gold-300/80 bg-white/90 px-6 py-4 text-xs font-bold uppercase tracking-wider text-stone-800 hover:bg-ivory-100 hover:text-liturgical-950 transition shadow-2xs"
                                >
                                    <ClipboardListIcon className="w-4 h-4 mr-2 text-liturgical-900" />
                                    <span>Track My Submitted Orders</span>
                                </Link>

                                <Link
                                    href={route('login')}
                                    className="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-stone-200 bg-stone-50/80 px-6 py-4 text-xs font-semibold uppercase tracking-wider text-stone-600 hover:bg-stone-100 hover:text-stone-900 transition"
                                >
                                    Pastoral Staff Portal
                                </Link>
                            </div>
                        </div>

                        {/* Open Masses Direct Booking Section */}
                        {allOpenMasses.length > 0 && (
                            <div className="rounded-3xl border border-gold-300/80 bg-white/95 p-6 sm:p-8 shadow-xl shadow-stone-900/5 ring-1 ring-gold-400/20">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 pb-4 border-b border-gold-200">
                                    <div>
                                        <div className="flex items-center space-x-2 text-[10px] font-serif uppercase tracking-widest text-gold-700">
                                            <span>Liturgia Praesens</span>
                                            <span>&bull;</span>
                                            <span>Holy Masses Open For Offerings</span>
                                        </div>
                                        <h2
                                            className="text-xl sm:text-2xl font-bold text-liturgical-950 font-serif mt-0.5"
                                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                        >
                                            Upcoming Holy Masses Open for Prayer Intentions
                                        </h2>
                                    </div>

                                    <Link
                                        href={route('churches.index')}
                                        className="text-xs font-bold text-liturgical-900 hover:text-liturgical-950 hover:underline flex items-center space-x-1"
                                    >
                                        <span>View All Parishes</span>
                                        <span>&rarr;</span>
                                    </Link>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                    {allOpenMasses.map(({ church, mass }) => (
                                        <div
                                            key={mass.id}
                                            className="rounded-2xl border border-gold-200/90 bg-gradient-to-b from-white via-ivory-50/40 to-white p-5 flex flex-col justify-between hover:border-gold-400 hover:shadow-md transition duration-200"
                                        >
                                            <div className="space-y-3">
                                                <div className="flex items-center justify-between">
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        Open for Intentions
                                                    </span>
                                                    <span className="text-[11px] font-medium text-gold-800 bg-gold-50 border border-gold-200 px-2 py-0.5 rounded">
                                                        {mass.language || 'English'}
                                                    </span>
                                                </div>

                                                <div>
                                                    <h3 className="font-cinzel text-base font-bold text-liturgical-950">
                                                        {mass.title}
                                                    </h3>
                                                    <div className="text-xs font-serif text-stone-600 mt-1">
                                                        {church.name}
                                                    </div>
                                                </div>

                                                <div className="space-y-1.5 text-xs text-stone-600">
                                                    <div className="flex items-center space-x-2 font-medium text-stone-900">
                                                        <ClockIcon className="w-3.5 h-3.5 text-gold-700 shrink-0" />
                                                        <span>{new Date(mass.starts_at).toLocaleString()}</span>
                                                    </div>
                                                    {mass.location_label && (
                                                        <div className="flex items-center space-x-2 text-stone-500 text-[11px]">
                                                            <MapPinIcon className="w-3.5 h-3.5 text-stone-500 shrink-0" />
                                                            <span>{mass.location_label}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mt-5 pt-4 border-t border-gold-100">
                                                <Link
                                                    href={route('orders.create', { church: church.id, mass: mass.id })}
                                                    className="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gold-100 border border-gold-400/40 shadow-xs hover:from-liturgical-700 hover:to-liturgical-900 transition"
                                                >
                                                    Submit Intentions &rarr;
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Interactive Demo Portals Matrix */}
                        <div id="demo-matrix" className="rounded-3xl border border-gold-300/80 bg-white/95 p-6 sm:p-10 shadow-xl shadow-stone-900/5 ring-1 ring-gold-400/20 backdrop-blur-sm">
                            <div className="text-center max-w-xl mx-auto mb-10 space-y-1.5">
                                <div className="text-[11px] font-serif font-bold uppercase tracking-widest text-gold-700">
                                    Sanctuarium &bull; Phase 1 to 3 Ministries & Roles
                                </div>
                                <h2
                                    className="text-2xl font-bold text-liturgical-950"
                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                >
                                    Explore Pre-Configured Ministries
                                </h2>
                                <p className="text-xs text-stone-600">
                                    Sign in as any role with password: <code className="font-mono bg-ivory-100 border border-gold-200 px-2 py-0.5 rounded font-bold text-liturgical-900">password</code>
                                </p>
                            </div>

                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                {demoAccounts.map((account) => (
                                    <div
                                        key={account.role}
                                        className="relative rounded-2xl border border-gold-200/90 bg-gradient-to-b from-white via-ivory-50/50 to-ivory-100/70 p-6 flex flex-col justify-between hover:border-gold-400 hover:shadow-lg hover:shadow-gold-900/5 transition duration-200 group"
                                    >
                                        <div className="space-y-4">
                                            {/* Top Icon & Badge */}
                                            <div className="flex items-center justify-between">
                                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white border border-gold-200 shadow-2xs group-hover:scale-105 transition-transform duration-200">
                                                    {account.icon}
                                                </div>
                                                <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ${account.badgeStyle}`}>
                                                    {account.badge}
                                                </span>
                                            </div>

                                            <div>
                                                <div className="text-[10px] font-serif uppercase tracking-widest text-gold-800">
                                                    {account.latin}
                                                </div>
                                                <h3
                                                    className="text-base font-bold text-liturgical-950 mt-0.5"
                                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                                >
                                                    {account.role}
                                                </h3>
                                                <div className="text-xs font-semibold text-stone-600">
                                                    {account.title}
                                                </div>
                                            </div>

                                            <p className="text-xs text-stone-600 leading-relaxed">
                                                {account.description}
                                            </p>

                                            <div className="rounded-xl border border-gold-200/60 bg-white/80 p-2.5 font-mono text-[11px] text-stone-700">
                                                <div className="text-[9px] uppercase tracking-wider text-stone-400 font-sans font-bold">
                                                    Demo Credential
                                                </div>
                                                <div className="truncate font-semibold text-liturgical-900 mt-0.5">
                                                    {account.email}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mt-6 pt-4 border-t border-gold-200/70 space-y-2">
                                            <Link
                                                href={route('login')}
                                                className="w-full inline-flex justify-center items-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 border border-gold-400/30 px-3.5 py-2.5 text-xs font-bold uppercase tracking-wider text-gold-100 hover:from-liturgical-700 hover:to-liturgical-900 transition shadow-2xs cursor-pointer"
                                            >
                                                Sign In as {account.role.split(' ')[0]} &rarr;
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Sacred Architecture Pillars */}
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div className="rounded-2xl border border-gold-300/60 bg-white/90 p-7 shadow-xs space-y-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-liturgical-100 border border-liturgical-200 text-liturgical-800">
                                    <ShieldIcon className="w-5 h-5 text-liturgical-900" />
                                </div>
                                <h3
                                    className="text-base font-bold text-liturgical-950"
                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                >
                                    Zero-Trust Tenant Isolation
                                </h3>
                                <p className="text-xs text-stone-600 leading-relaxed">
                                    Cross-tenant access attempts return 404 to avoid confirming resource existence. Deactivating parish staff immediately invalidates sessions across all devices.
                                </p>
                            </div>

                            <div className="rounded-2xl border border-gold-300/60 bg-white/90 p-7 shadow-xs space-y-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 border border-gold-200 text-amber-800">
                                    <ScrollIcon className="w-5 h-5 text-amber-800" />
                                </div>
                                <h3
                                    className="text-base font-bold text-liturgical-950"
                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                >
                                    Append-Only Altar Audit Trail
                                </h3>
                                <p className="text-xs text-stone-600 leading-relaxed">
                                    Every membership role assignment, revocation, schedule change, and payment verification is permanently recorded with actor identity and audit metadata.
                                </p>
                            </div>

                            <div className="rounded-2xl border border-gold-300/60 bg-white/90 p-7 shadow-xs space-y-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                                    <DoveIcon className="w-5 h-5 text-emerald-800" />
                                </div>
                                <h3
                                    className="text-base font-bold text-liturgical-950"
                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                >
                                    Verified Ambo Reading Lists
                                </h3>
                                <p className="text-xs text-stone-600 leading-relaxed">
                                    Only human-verified GCash intention offerings are enrolled onto the lector's reading roster, preserving sacred intention wording verbatim.
                                </p>
                            </div>
                        </div>
                    </div>
                </main>

                {/* Cathedral Footer */}
                <footer className="relative z-10 border-t border-gold-300/60 bg-liturgical-950 text-gold-200 py-12 mt-12">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center space-y-4">
                        <div className="flex justify-center">
                            <ApplicationLogo className="h-10 w-10" showWordmark={true} inverted={true} />
                        </div>
                        <p className="text-xs text-gold-300/80 max-w-md mx-auto">
                            Dedicated to orderly, transparent, and reverent liturgical operations for Philippine Catholic parishes.
                        </p>
                        <div className="text-[11px] text-stone-400 font-serif italic">
                            Ad Majorem Dei Gloriam &bull; In Veritate et Caritate &bull; &copy; {new Date().getFullYear()} Pabasa
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
