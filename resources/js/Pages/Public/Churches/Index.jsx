import ApplicationLogo from '@/Components/ApplicationLogo';
import EmptyState from '@/Components/EmptyState';
import StatusBadge from '@/Components/StatusBadge';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ churches, filters, availableCities, availableProvinces, auth }) {
    const [search, setSearch] = useState(filters.search || '');
    const [city, setCity] = useState(filters.city || '');
    const [province, setProvince] = useState(filters.province || '');

    const handleFilterSubmit = (e) => {
        e?.preventDefault();
        router.get(
            route('churches.index'),
            {
                search: search || undefined,
                city: city || undefined,
                province: province || undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        setCity('');
        setProvince('');
        router.get(route('churches.index'));
    };

    return (
        <div className="min-h-screen bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F5ECE0] text-stone-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
            <Head title="Parish Directory — Holy Mass & Prayer Intentions" />

            {/* Public Top Navbar */}
            <header className="border-b border-gold-300/60 bg-white/95 backdrop-blur-md sticky top-0 z-40 shadow-2xs">
                <div className="h-1 bg-gradient-to-r from-liturgical-900 via-gold-500 to-liturgical-900" />
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <Link href="/" className="flex items-center space-x-3 group">
                        <ApplicationLogo className="h-10 w-10 shadow-xs" size="sm" />
                        <div>
                            <span className="font-cinzel text-lg font-bold tracking-wider text-liturgical-950 block">PABASA</span>
                            <span className="text-[10px] tracking-widest uppercase font-serif text-gold-700 block -mt-1">Holy Mass Intentions</span>
                        </div>
                    </Link>

                    <div className="flex items-center space-x-4">
                        {auth?.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-wider text-liturgical-900 hover:text-gold-700 transition"
                            >
                                Sanctuary Dashboard &rarr;
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="text-xs font-semibold uppercase tracking-wider text-stone-700 hover:text-liturgical-900 transition"
                                >
                                    Sign In
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="inline-flex items-center px-3.5 py-1.5 rounded-sm border border-gold-400 bg-gold-50 text-xs font-semibold uppercase tracking-wider text-gold-900 hover:bg-gold-100 transition shadow-2xs"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            {/* Hero Section */}
            <div className="relative py-12 px-4 sm:px-6 lg:px-8 text-center bg-white/50 border-b border-gold-200/60">
                <div className="max-w-3xl mx-auto">
                    <h1 className="font-cinzel text-3xl sm:text-4xl font-bold tracking-tight text-liturgical-950 mb-3">
                        Parish Directory
                    </h1>
                    <p className="font-serif text-base sm:text-lg text-stone-600 max-w-2xl mx-auto leading-relaxed">
                        Find Catholic parishes, view scheduled Holy Masses, and submit your prayer intentions with reverent care.
                    </p>

                    {/* Filter Bar */}
                    <form onSubmit={handleFilterSubmit} className="mt-8 bg-white p-4 rounded-xl border border-gold-300 shadow-sm flex flex-col sm:flex-row gap-3">
                        <div className="relative flex-1">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search parish name, city, or province..."
                                className="block w-full pl-10 pr-3 py-2 text-sm border-stone-200 rounded-md focus:border-gold-500 focus:ring-gold-500"
                            />
                        </div>

                        {availableCities?.length > 0 && (
                            <select
                                value={city}
                                onChange={(e) => setCity(e.target.value)}
                                className="block text-sm border-stone-200 rounded-md focus:border-gold-500 focus:ring-gold-500 min-w-[140px]"
                            >
                                <option value="">All Cities</option>
                                {availableCities.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </select>
                        )}

                        {availableProvinces?.length > 0 && (
                            <select
                                value={province}
                                onChange={(e) => setProvince(e.target.value)}
                                className="block text-sm border-stone-200 rounded-md focus:border-gold-500 focus:ring-gold-500 min-w-[140px]"
                            >
                                <option value="">All Provinces</option>
                                {availableProvinces.map((p) => (
                                    <option key={p} value={p}>{p}</option>
                                ))}
                            </select>
                        )}

                        <div className="flex gap-2">
                            <button
                                type="submit"
                                className="px-5 py-2 rounded-md bg-liturgical-900 text-gold-200 hover:bg-liturgical-800 text-xs font-semibold tracking-wider uppercase transition shadow-2xs cursor-pointer"
                            >
                                Search
                            </button>
                            {(search || city || province) && (
                                <button
                                    type="button"
                                    onClick={handleClearFilters}
                                    className="px-3 py-2 rounded-md border border-stone-300 text-stone-600 hover:bg-stone-50 text-xs font-semibold tracking-wider uppercase transition cursor-pointer"
                                >
                                    Clear
                                </button>
                            )}
                        </div>
                    </form>
                </div>
            </div>

            {/* Church Cards Grid */}
            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                {churches.data.length === 0 ? (
                    <EmptyState
                        title="No Parishes Found"
                        description="No registered published parishes match your search criteria. Please try clearing your filters or searching for another location."
                        action={
                            (search || city || province) && (
                                <button
                                    onClick={handleClearFilters}
                                    className="inline-flex items-center px-4 py-2 rounded-sm border border-gold-400 bg-gold-50 text-xs font-semibold uppercase tracking-wider text-gold-900 hover:bg-gold-100 transition shadow-2xs cursor-pointer"
                                >
                                    Reset Filters
                                </button>
                            )
                        }
                    />
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {churches.data.map((church) => (
                            <div
                                key={church.id}
                                className="bg-white rounded-xl border border-gold-200/80 shadow-xs hover:shadow-md transition duration-200 flex flex-col overflow-hidden group"
                            >
                                {/* Header / Cover image preview */}
                                <div className="h-32 bg-gradient-to-r from-liturgical-900 via-liturgical-800 to-liturgical-950 relative flex items-center justify-center p-4">
                                    <div className="absolute inset-0 opacity-10 bg-[radial-gradient(#d4a03c_1px,transparent_1px)] [background-size:16px_16px]" />
                                    <div className="text-center relative z-10">
                                        <div className="inline-flex p-2 rounded-full bg-gold-500/20 text-gold-300 border border-gold-400/30 mb-1">
                                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div className="absolute top-3 right-3">
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Published
                                        </span>
                                    </div>
                                </div>

                                {/* Body */}
                                <div className="p-6 flex-1 flex flex-col justify-between">
                                    <div>
                                        <h2 className="font-cinzel text-lg font-bold text-liturgical-950 group-hover:text-gold-700 transition">
                                            {church.name}
                                        </h2>

                                        <p className="mt-2 text-xs text-stone-500 flex items-start space-x-1.5">
                                            <svg className="w-4 h-4 text-gold-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>
                                                {church.address ? `${church.address}, ` : ''}
                                                {church.city}, {church.province}
                                            </span>
                                        </p>

                                        {church.branding?.public_description && (
                                            <p className="mt-3 text-xs text-stone-600 line-clamp-2">
                                                {church.branding.public_description}
                                            </p>
                                        )}
                                    </div>

                                    <div className="mt-6 pt-4 border-t border-stone-100 flex items-center justify-between">
                                        <div className="flex items-center space-x-1.5 text-xs text-stone-600">
                                            <svg className="w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span className="font-medium text-stone-900">{church.upcoming_masses_count ?? 0}</span>
                                            <span>Upcoming Masses</span>
                                        </div>

                                        <Link
                                            href={route('churches.show', { slug: church.slug })}
                                            className="inline-flex items-center px-4 py-2 rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 text-gold-100 hover:from-liturgical-700 hover:to-liturgical-900 text-xs font-bold tracking-wider uppercase transition shadow-2xs group-hover:from-liturgical-700 group-hover:to-liturgical-900"
                                        >
                                            View Masses & Book &rarr;
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {churches.links?.length > 3 && (
                    <div className="mt-10 flex justify-center space-x-1">
                        {churches.links.map((link, idx) => (
                            <Link
                                key={idx}
                                href={link.url || '#'}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1.5 text-xs rounded border ${
                                    link.active
                                        ? 'bg-liturgical-900 text-gold-200 border-liturgical-900 font-bold'
                                        : link.url
                                          ? 'bg-white text-stone-700 border-stone-300 hover:bg-stone-50'
                                          : 'bg-stone-100 text-stone-400 border-stone-200 cursor-not-allowed'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}

