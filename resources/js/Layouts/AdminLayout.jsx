import React, { useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import FlashMessage from '@/Components/FlashMessage';
import LocaleSwitcher from '@/Components/LocaleSwitcher';
import StatusBadge from '@/Components/StatusBadge';
import {
    ShieldIcon,
    ClipboardListIcon,
    ChurchIcon,
} from '@/Components/Icons';
import { Head, Link, router, usePage } from '@inertiajs/react';

function AdminNavLink({ href, active, children, icon: Icon }) {
    return (
        <Link
            href={href}
            className={`inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold tracking-wide transition-all duration-150 ${
                active
                    ? 'bg-liturgical-900 text-gold-200 shadow-xs border border-gold-400/30'
                    : 'text-stone-600 hover:text-liturgical-950 hover:bg-stone-100/80 border border-transparent'
            }`}
        >
            {Icon && (
                <Icon
                    className={`w-4 h-4 ${
                        active ? 'text-gold-300' : 'text-stone-400 group-hover:text-stone-600'
                    }`}
                />
            )}
            <span>{children}</span>
        </Link>
    );
}

export default function AdminLayout({ header, children }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const isAtHome = () => {
        if (route().current('admin.dashboard')) return true;
        if (typeof window !== 'undefined') {
            try {
                const targetPath = new URL(route('admin.dashboard'), window.location.origin).pathname;
                return window.location.pathname === targetPath;
            } catch {
                return false;
            }
        }
        return false;
    };

    const handleBrandClick = (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        if (isAtHome()) {
            e.preventDefault();
            router.reload();
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F5ECE0] text-stone-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
            {/* Topbar: Diocese Chancery Identity & Executive Controls */}
            <header className="border-b border-gold-300/60 bg-white/95 backdrop-blur-md sticky top-0 z-40 shadow-2xs">
                {/* Liturgical Golden Ribbon */}
                <div className="h-1 bg-gradient-to-r from-liturgical-950 via-gold-500 to-liturgical-950" />

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Upper Bar: Brand & Profile */}
                    <div className="flex h-16 items-center justify-between border-b border-stone-100/80">
                        {/* Left: Chancery Logo & Subtitle */}
                        <div className="flex items-center space-x-3">
                            <Link
                                href={route('admin.dashboard')}
                                onClick={handleBrandClick}
                                className="flex items-center space-x-3 group"
                                title={isAtHome() ? 'Reload console' : 'Return to Chancery Dashboard'}
                            >
                                <ApplicationLogo className="h-10 w-10 shadow-xs" size="sm" showWordmark={false} />
                                <div className="flex flex-col">
                                    <div className="flex items-center space-x-2">
                                        <span
                                            className="font-serif tracking-wider font-bold text-sm text-liturgical-950"
                                            style={{ fontFamily: 'var(--font-cinzel, serif)' }}
                                        >
                                            PABASA DIOCESE CHANCERY
                                        </span>
                                        <StatusBadge role="central_admin" />
                                    </div>
                                    <span className="text-[10px] text-gold-800 font-serif italic tracking-wide">
                                        Curia Archidiocesana &bull; Central Authority Console
                                    </span>
                                </div>
                            </Link>
                        </div>

                        {/* Right: Switch to Sanctuary + Language + User Menu */}
                        <div className="hidden md:flex items-center space-x-3.5">
                            {/* Return to Public Sanctuary Portal */}
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-stone-600 bg-stone-50 border border-stone-200 hover:bg-stone-100 hover:text-stone-900 transition shadow-2xs"
                                title="Exit admin console to user sanctuary"
                            >
                                <svg className="w-3.5 h-3.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Public Sanctuary</span>
                            </Link>

                            <LocaleSwitcher />

                            {/* User Profile Dropdown */}
                            <div className="relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button
                                            type="button"
                                            className="inline-flex items-center gap-2 rounded-xl border border-gold-300/80 bg-white px-3 py-1.5 text-xs font-semibold text-stone-800 shadow-2xs transition hover:bg-stone-50 focus:outline-none cursor-pointer"
                                        >
                                            <div className="h-6 w-6 rounded-full bg-liturgical-900 text-gold-300 flex items-center justify-center font-bold text-[10px]">
                                                {user?.name?.charAt(0) || 'A'}
                                            </div>
                                            <span className="truncate max-w-[120px]">{user?.name}</span>
                                            <svg className="w-3.5 h-3.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <div className="px-4 py-2 border-b border-stone-100">
                                            <p className="text-xs font-bold text-stone-900">{user?.name}</p>
                                            <p className="text-[11px] text-stone-500 truncate">{user?.email}</p>
                                        </div>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            Administrator Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button">
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        {/* Mobile Menu Trigger */}
                        <div className="flex md:hidden items-center space-x-2">
                            <LocaleSwitcher />
                            <button
                                type="button"
                                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                className="p-2 rounded-lg text-stone-600 hover:bg-stone-100"
                            >
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={mobileMenuOpen ? "M6 18L18 6M6 6l12 12" : "M4 6h16M4 12h16M4 18h16"} />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Lower Bar: Administrative Module Navigation Tabs */}
                    <nav className="hidden md:flex items-center space-x-2 py-2 overflow-x-auto">
                        <AdminNavLink
                            href={route('admin.dashboard')}
                            active={route().current('admin.dashboard')}
                            icon={ChurchIcon}
                        >
                            Diocese Console
                        </AdminNavLink>

                        <AdminNavLink
                            href={route('admin.payments.index')}
                            active={route().current('admin.payments.*')}
                            icon={ClipboardListIcon}
                        >
                            Payment Verification
                        </AdminNavLink>

                        <AdminNavLink
                            href={route('admin.payment-settings.index')}
                            active={route().current('admin.payment-settings.*')}
                            icon={() => (
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            )}
                        >
                            Payment & Fee Settings
                        </AdminNavLink>

                        <AdminNavLink
                            href={route('admin.users.index')}
                            active={route().current('admin.users.*')}
                            icon={ShieldIcon}
                        >
                            Staff & Users
                        </AdminNavLink>
                    </nav>
                </div>

                {/* Mobile Dropdown Menu */}
                {mobileMenuOpen && (
                    <div className="md:hidden border-t border-stone-200 bg-white px-4 py-3 space-y-2">
                        <div className="text-[10px] font-bold text-stone-400 uppercase tracking-wider px-2">
                            Administration Modules
                        </div>
                        <Link
                            href={route('admin.dashboard')}
                            className="block px-3 py-2 rounded-lg text-xs font-semibold text-stone-700 hover:bg-stone-50"
                        >
                            Diocese Console
                        </Link>
                        <Link
                            href={route('admin.payments.index')}
                            className="block px-3 py-2 rounded-lg text-xs font-semibold text-stone-700 hover:bg-stone-50"
                        >
                            Payment Verification
                        </Link>
                        <Link
                            href={route('admin.payment-settings.index')}
                            className="block px-3 py-2 rounded-lg text-xs font-semibold text-stone-700 hover:bg-stone-50"
                        >
                            Payment & Fee Settings
                        </Link>
                        <Link
                            href={route('admin.users.index')}
                            className="block px-3 py-2 rounded-lg text-xs font-semibold text-stone-700 hover:bg-stone-50"
                        >
                            Staff & Users
                        </Link>
                        <div className="border-t border-stone-100 pt-2">
                            <Link
                                href={route('dashboard')}
                                className="block px-3 py-2 rounded-lg text-xs font-bold text-amber-800 hover:bg-amber-50"
                            >
                                &larr; Return to Public Sanctuary
                            </Link>
                        </div>
                    </div>
                )}
            </header>

            {/* Flash Alerts */}
            <FlashMessage />

            {/* Page Header Banner */}
            {header && (
                <div className="border-b border-gold-200/70 bg-gradient-to-r from-ivory-50 via-white to-ivory-50 py-6 shadow-2xs">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </div>
            )}

            {/* Page Body */}
            <main className="py-8">
                {children}
            </main>
        </div>
    );
}

