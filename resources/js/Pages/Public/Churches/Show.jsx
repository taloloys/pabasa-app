import ApplicationLogo from '@/Components/ApplicationLogo';
import EmptyState from '@/Components/EmptyState';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function Show({ church, upcomingMasses, auth }) {
    const [selectedTab, setSelectedTab] = useState('all'); // 'all', 'today', 'upcoming'
    const [selectedLanguage, setSelectedLanguage] = useState('all');

    const availableLanguages = useMemo(() => {
        const langs = new Set();
        upcomingMasses.forEach((m) => m.language && langs.add(m.language));
        return Array.from(langs);
    }, [upcomingMasses]);

    const filteredMasses = useMemo(() => {
        return upcomingMasses.filter((mass) => {
            if (selectedLanguage !== 'all' && mass.language !== selectedLanguage) {
                return false;
            }

            if (selectedTab === 'today') {
                const today = new Date().toISOString().slice(0, 10);
                return mass.starts_at.slice(0, 10) === today;
            }

            return true;
        });
    }, [upcomingMasses, selectedTab, selectedLanguage]);

    return (
        <div className="min-h-screen bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F5ECE0] text-stone-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
            <Head title={`${church.name} — Holy Mass Schedule & Intentions`} />

            {/* Public Top Navbar */}
            <header className="border-b border-gold-300/60 bg-white/95 backdrop-blur-md sticky top-0 z-40 shadow-2xs">
                <div className="h-1 bg-gradient-to-r from-liturgical-900 via-gold-500 to-liturgical-900" />
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <Link href="/" className="flex items-center space-x-2 group">
                            <ApplicationLogo className="h-10 w-10 shadow-xs" size="sm" />
                        </Link>
                        <span className="text-stone-300">/</span>
                        <Link href={route('churches.index')} className="text-xs font-semibold uppercase tracking-wider text-stone-600 hover:text-liturgical-900">
                            Parish Directory
                        </Link>
                        <span className="text-stone-300">/</span>
                        <span className="text-xs font-semibold text-liturgical-950 truncate max-w-[200px] sm:max-w-xs">{church.name}</span>
                    </div>

                    <div className="flex items-center space-x-4">
                        {auth?.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-wider text-liturgical-900 hover:text-gold-700 transition"
                            >
                                Sanctuary Dashboard &rarr;
                            </Link>
                        ) : (
                            <Link
                                href={route('login')}
                                className="text-xs font-semibold uppercase tracking-wider text-stone-700 hover:text-liturgical-900 transition"
                            >
                                Sign In
                            </Link>
                        )}
                    </div>
                </div>
            </header>

            {/* Parish Banner & Header */}
            <div className="bg-white border-b border-gold-200/80 shadow-2xs">
                <div className="h-44 sm:h-56 bg-gradient-to-r from-liturgical-950 via-liturgical-900 to-stone-900 relative flex items-center justify-center overflow-hidden">
                    <div className="absolute inset-0 opacity-15 bg-[radial-gradient(#d4a03c_1px,transparent_1px)] [background-size:20px_20px]" />
                    <div className="text-center px-4 relative z-10">
                        <h1 className="font-cinzel text-2xl sm:text-4xl font-bold tracking-tight text-white mb-2">
                            {church.name}
                        </h1>
                        <p className="font-serif text-xs sm:text-sm text-gold-200 flex items-center justify-center space-x-1.5">
                            <svg className="w-4 h-4 text-gold-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>
                                {church.address ? `${church.address}, ` : ''}
                                {church.city}, {church.province} &bull; Timezone: {church.timezone}
                            </span>
                        </p>
                    </div>
                </div>

                {church.branding?.public_description && (
                    <div className="max-w-4xl mx-auto px-4 py-4 text-center text-xs sm:text-sm text-stone-600 italic">
                        &ldquo;{church.branding.public_description}&rdquo;
                    </div>
                )}
            </div>

            {/* Content & Schedule */}
            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                {/* Control & Filter Bar */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                    <div>
                        <h2 className="font-cinzel text-xl font-bold text-liturgical-950">
                            Holy Mass Schedule
                        </h2>
                        <p className="text-xs text-stone-500 mt-0.5">
                            Select an open Mass to offer your intentions. Times are displayed in {church.timezone}.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* Tab Switcher */}
                        <div className="inline-flex rounded-md p-1 bg-stone-100 border border-stone-200 text-xs">
                            <button
                                onClick={() => setSelectedTab('all')}
                                className={`px-3 py-1 rounded font-medium transition cursor-pointer ${
                                    selectedTab === 'all'
                                        ? 'bg-white text-liturgical-900 shadow-2xs font-semibold'
                                        : 'text-stone-600 hover:text-stone-900'
                                }`}
                            >
                                All Upcoming
                            </button>
                            <button
                                onClick={() => setSelectedTab('today')}
                                className={`px-3 py-1 rounded font-medium transition cursor-pointer ${
                                    selectedTab === 'today'
                                        ? 'bg-white text-liturgical-900 shadow-2xs font-semibold'
                                        : 'text-stone-600 hover:text-stone-900'
                                }`}
                            >
                                Today
                            </button>
                        </div>

                        {/* Language Filter */}
                        {availableLanguages.length > 1 && (
                            <select
                                value={selectedLanguage}
                                onChange={(e) => setSelectedLanguage(e.target.value)}
                                className="text-xs py-1.5 border-stone-200 rounded-md focus:border-gold-500 focus:ring-gold-500"
                            >
                                <option value="all">All Languages</option>
                                {availableLanguages.map((l) => (
                                    <option key={l} value={l}>{l}</option>
                                ))}
                            </select>
                        )}
                    </div>
                </div>

                {/* Mass List */}
                {filteredMasses.length === 0 ? (
                    <EmptyState
                        title="No Scheduled Masses"
                        description="There are currently no upcoming Holy Masses matching the selected filter for this parish. Please check back later."
                    />
                ) : (
                    <div className="space-y-4">
                        {filteredMasses.map((mass) => {
                            const isOpen = mass.is_booking_open;
                            const isPastCutoff = mass.is_cutoff_passed;
                            const isClosed = mass.booking_status === 'closed' || isPastCutoff;

                            return (
                                <div
                                    key={mass.id}
                                    className={`bg-white rounded-xl border p-6 transition duration-200 flex flex-col md:flex-row md:items-center justify-between gap-6 ${
                                        isOpen
                                            ? 'border-gold-300 shadow-xs hover:border-gold-400'
                                            : 'border-stone-200 bg-stone-50/70 opacity-90'
                                    }`}
                                >
                                    <div className="flex-1">
                                        <div className="flex flex-wrap items-center gap-2 mb-2">
                                            {/* Status Badge */}
                                            {isOpen ? (
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    Open for Intentions
                                                </span>
                                            ) : isPastCutoff ? (
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                    Cutoff Passed
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-stone-200 text-stone-700">
                                                    Submissions Closed
                                                </span>
                                            )}

                                            {/* Language Tag */}
                                            {mass.language && (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gold-50 text-gold-800 border border-gold-200">
                                                    {mass.language}
                                                </span>
                                            )}

                                            {/* Category Tag */}
                                            {mass.category && (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-liturgical-50 text-liturgical-800 border border-liturgical-200">
                                                    {mass.category.label}
                                                </span>
                                            )}
                                        </div>

                                        <h3 className="font-cinzel text-lg font-bold text-liturgical-950">
                                            {mass.title}
                                        </h3>

                                        <div className="mt-2 flex flex-wrap items-center gap-4 text-xs text-stone-600">
                                            <span className="flex items-center space-x-1 font-medium text-stone-900">
                                                <svg className="w-4 h-4 text-gold-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{mass.starts_at_formatted}</span>
                                                {mass.ends_at_formatted && <span> &ndash; {mass.ends_at_formatted}</span>}
                                            </span>

                                            {mass.location_label && (
                                                <span className="flex items-center space-x-1">
                                                    <svg className="w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                    <span>{mass.location_label}</span>
                                                </span>
                                            )}

                                            <span className="flex items-center space-x-1 text-stone-500">
                                                <span>Cutoff:</span>
                                                <span className="font-medium">{mass.booking_cutoff_formatted}</span>
                                            </span>
                                        </div>
                                    </div>

                                    {/* Action button */}
                                    <div className="shrink-0 flex items-center">
                                        {isOpen ? (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    alert(`Order submission flow opens in Phase 3 for Mass: ${mass.title}`);
                                                }}
                                                className="inline-flex items-center px-4 py-2 rounded-sm bg-liturgical-900 text-gold-200 hover:bg-liturgical-800 text-xs font-semibold tracking-wider uppercase transition shadow-2xs hover:shadow-xs cursor-pointer"
                                            >
                                                Book Prayer Intention &rarr;
                                            </button>
                                        ) : (
                                            <button
                                                disabled
                                                type="button"
                                                className="inline-flex items-center px-4 py-2 rounded-sm bg-stone-200 text-stone-400 text-xs font-semibold tracking-wider uppercase cursor-not-allowed"
                                                title="Submissions are closed for this Mass."
                                            >
                                                Submissions Closed
                                            </button>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </main>
        </div>
    );
}

