import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import FlashMessage from '@/Components/FlashMessage';
import LocaleSwitcher from '@/Components/LocaleSwitcher';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import StatusBadge from '@/Components/StatusBadge';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isCentralAdmin = auth.is_central_admin;
    const memberships = auth.memberships || [];

    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

    // Primary role determination
    let primaryRole = 'Parishioner';
    let roleBadge = <StatusBadge role="parishioner" />;
    if (isCentralAdmin) {
        primaryRole = 'Central Admin';
        roleBadge = <StatusBadge role="central_admin" />;
    } else if (memberships.some((m) => m.role === 'church_admin')) {
        primaryRole = 'Church Admin';
        roleBadge = <StatusBadge role="church_admin" />;
    } else if (memberships.some((m) => m.role === 'lector')) {
        primaryRole = 'Lector';
        roleBadge = <StatusBadge role="lector" />;
    }

    return (
        <div className="min-h-screen bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F5ECE0] text-stone-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
            {/* Cathedral Sanctuary Navbar */}
            <nav className="border-b border-gold-300/60 bg-white/95 backdrop-blur-md sticky top-0 z-40 shadow-2xs">
                {/* Thin Top Liturgical Ribbon */}
                <div className="h-1 bg-gradient-to-r from-liturgical-900 via-gold-500 to-liturgical-900" />

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/" className="flex items-center space-x-2 group">
                                    <ApplicationLogo className="h-10 w-10 shadow-xs" size="sm" />
                                </Link>
                            </div>

                            {/* Desktop Nav Links */}
                            <div className="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex items-center">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Sanctuary
                                </NavLink>

                                <NavLink
                                    href={route('orders.tracking')}
                                    active={route().current('orders.tracking')}
                                >
                                    Track Intentions
                                </NavLink>

                                {isCentralAdmin && (
                                    <NavLink
                                        href={route('admin.dashboard')}
                                        active={route().current('admin.*')}
                                    >
                                        Diocese Console
                                    </NavLink>
                                )}

                                {/* Church Selector / Links */}
                                {memberships.map((membership) => (
                                    <NavLink
                                        key={membership.id}
                                        href={
                                            membership.role === 'church_admin'
                                                ? route('church.dashboard', { church: membership.church_id })
                                                : route('lector.dashboard', { church: membership.church_id })
                                        }
                                        active={
                                            route().current('church.*') || route().current('lector.*')
                                        }
                                    >
                                        {membership.church?.name || 'Parish Staff'}
                                    </NavLink>
                                ))}
                            </div>
                        </div>

                        {/* Right Header items */}
                        <div className="hidden sm:ms-6 sm:flex sm:items-center space-x-4">
                            <LocaleSwitcher />

                            {roleBadge}

                            <div className="relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-xl">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-xl border border-gold-300/80 bg-white px-3.5 py-1.5 text-xs font-semibold text-stone-800 shadow-2xs transition hover:bg-ivory-100 hover:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-400/40 cursor-pointer"
                                            >
                                                <span>{user.name}</span>
                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4 text-stone-400"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <div className="px-4 py-2.5 border-b border-stone-100 bg-ivory-50/50 text-xs text-stone-500">
                                            <div className="text-[10px] font-serif uppercase tracking-wider text-gold-700">Signed In As</div>
                                            <strong className="text-liturgical-950 font-bold">{user.email}</strong>
                                        </div>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            Account Settings
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Sign Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        {/* Mobile menu trigger */}
                        <div className="-me-2 flex items-center sm:hidden space-x-2">
                            <LocaleSwitcher />
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-xl p-2 text-stone-600 hover:bg-ivory-100 hover:text-stone-900 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile navigation menu */}
                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden border-t border-gold-200 bg-white/95'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            Sanctuary
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            href={route('orders.tracking')}
                            active={route().current('orders.tracking')}
                        >
                            Track Intentions
                        </ResponsiveNavLink>

                        {isCentralAdmin && (
                            <ResponsiveNavLink
                                href={route('admin.dashboard')}
                                active={route().current('admin.*')}
                            >
                                Diocese Console
                            </ResponsiveNavLink>
                        )}

                        {memberships.map((membership) => (
                            <ResponsiveNavLink
                                key={membership.id}
                                href={
                                    membership.role === 'church_admin'
                                        ? route('church.dashboard', { church: membership.church_id })
                                        : route('lector.dashboard', { church: membership.church_id })
                                }
                                active={route().current('church.*') || route().current('lector.*')}
                            >
                                {membership.church?.name || 'Parish Staff'}
                            </ResponsiveNavLink>
                        ))}
                    </div>

                    <div className="border-t border-stone-200 pb-3 pt-4 bg-ivory-50/50">
                        <div className="px-4">
                            <div className="text-sm font-bold text-liturgical-950">
                                {user.name}
                            </div>
                            <div className="text-xs text-stone-500 font-mono">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile Settings
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Sign Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Flash Messages */}
            <FlashMessage />

            {/* Header if supplied */}
            {header && (
                <header className="border-b border-gold-300/50 bg-white/70 backdrop-blur-xs shadow-2xs">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            {/* Main Content */}
            <main className="py-8 sm:py-10">{children}</main>
        </div>
    );
}
