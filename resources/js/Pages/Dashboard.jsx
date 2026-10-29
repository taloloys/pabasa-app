import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import {
    ChurchIcon,
    ClipboardListIcon,
    ShieldIcon,
    CalendarIcon,
    BookOpenIcon,
    TagIcon,
} from '@/Components/Icons';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { auth } = usePage().props;
    const user = auth.user;
    const isCentralAdmin = auth.is_central_admin;
    const memberships = auth.memberships || [];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-[10px] font-serif uppercase tracking-widest text-gold-700">
                            <span>Sanctuarium Domini</span>
                            <span>&bull;</span>
                            <span>Pax Christi</span>
                        </div>
                        <h2
                            className="text-2xl font-bold tracking-tight text-liturgical-950 font-serif mt-0.5"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            Welcome in Christ, {user.name}
                        </h2>
                        <p className="mt-0.5 text-xs text-stone-600 font-sans">
                            Pabasa Sacred Intentions & Liturgical Ministry Sanctuary
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Sanctuary Dashboard - Pabasa" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Quick Access Grid */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {/* Browse Parishes & Book Intentions Tile */}
                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-7 shadow-xs hover:border-gold-400 hover:shadow-md transition flex flex-col justify-between ring-1 ring-gold-400/20 group">
                        <div className="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-liturgical-800 via-gold-500 to-liturgical-800" />
                        <div>
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-ivory-100 text-gold-700 border border-gold-200 mb-4 shadow-2xs group-hover:scale-105 transition-transform duration-200">
                                <ChurchIcon className="w-6 h-6 text-gold-700" />
                            </div>
                            <h3
                                className="text-base font-bold text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Find Parishes & Mass Schedules
                            </h3>
                            <p className="mt-1 text-xs text-stone-600 leading-relaxed font-sans">
                                Explore published Catholic parishes, view upcoming Mass times, and submit prayer intentions for Holy Mass.
                            </p>
                        </div>
                        <div className="mt-6 pt-4 border-t border-gold-100">
                            <Link
                                href={route('churches.index')}
                                className="inline-flex items-center text-xs font-bold text-liturgical-800 hover:text-liturgical-950"
                            >
                                Browse Parishes & Intentions &rarr;
                            </Link>
                        </div>
                    </div>

                    {/* Order Tracking Tile */}
                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-7 shadow-xs hover:border-gold-400 hover:shadow-md transition flex flex-col justify-between ring-1 ring-gold-400/20 group">
                        <div className="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-gold-400 via-gold-500 to-gold-400" />
                        <div>
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-ivory-100 text-liturgical-800 border border-gold-200 mb-4 shadow-2xs group-hover:scale-105 transition-transform duration-200">
                                <ClipboardListIcon className="w-6 h-6 text-liturgical-800" />
                            </div>
                            <h3
                                className="text-base font-bold text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Track Mass Intentions
                            </h3>
                            <p className="mt-1 text-xs text-stone-600 leading-relaxed font-sans">
                                Inspect the ecclesiastical verification, GCash payment confirmation, and liturgical scheduling of your prayer intentions.
                            </p>
                        </div>
                        <div className="mt-6 pt-4 border-t border-gold-100">
                            <Link
                                href={route('orders.tracking')}
                                className="inline-flex items-center text-xs font-bold text-liturgical-800 hover:text-liturgical-950"
                            >
                                View My Submitted Intentions &rarr;
                            </Link>
                        </div>
                    </div>

                    {/* Central Admin Portal Tile */}
                    {isCentralAdmin && (
                        <div className="relative overflow-hidden rounded-2xl border border-liturgical-300 bg-gradient-to-br from-liturgical-50 via-white to-ivory-50 p-7 shadow-xs flex flex-col justify-between ring-1 ring-liturgical-400/30 group">
                            <div className="absolute top-0 inset-x-0 h-1 bg-liturgical-800" />
                            <div>
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-liturgical-100 text-liturgical-900 border border-liturgical-200 mb-4 shadow-2xs group-hover:scale-105 transition-transform duration-200">
                                    <ShieldIcon className="w-6 h-6 text-liturgical-900" />
                                </div>
                                <div className="flex items-center space-x-2">
                                    <h3
                                        className="text-base font-bold text-liturgical-950 font-serif"
                                        style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                    >
                                        Diocese Authority Console
                                    </h3>
                                    <StatusBadge role="central_admin" />
                                </div>
                                <p className="mt-1 text-xs text-stone-600 leading-relaxed font-sans">
                                    Diocese-wide parish approvals, canonical verification rules, and system-wide append-only audit trail.
                                </p>

                                <div className="mt-3 flex flex-wrap gap-1.5 text-xs">
                                    <Link
                                        href={route('admin.payments.index')}
                                        className="px-2.5 py-1 rounded bg-liturgical-100 text-liturgical-900 font-semibold hover:bg-liturgical-200 transition"
                                    >
                                        Review Payments
                                    </Link>
                                    <Link
                                        href={route('admin.users.index')}
                                        className="px-2.5 py-1 rounded bg-liturgical-100 text-liturgical-900 font-semibold hover:bg-liturgical-200 transition"
                                    >
                                        Staff & Users
                                    </Link>
                                </div>
                            </div>
                            <div className="mt-6 pt-4 border-t border-liturgical-200/50">
                                <Link
                                    href={route('admin.dashboard')}
                                    className="inline-flex items-center text-xs font-bold text-liturgical-900 hover:text-liturgical-950"
                                >
                                    Open Diocese Console &rarr;
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Parish Memberships Tile */}
                    {memberships.map((membership) => (
                        <div
                            key={membership.id}
                            className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-7 shadow-xs flex flex-col justify-between ring-1 ring-gold-400/20 group"
                        >
                            <div className="absolute top-0 inset-x-0 h-1 bg-amber-600" />
                            <div>
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-800 border border-gold-200 mb-4 shadow-2xs group-hover:scale-105 transition-transform duration-200">
                                    <ChurchIcon className="w-6 h-6 text-amber-800" />
                                </div>
                                <div className="flex items-center space-x-2">
                                    <h3
                                        className="text-base font-bold text-liturgical-950 font-serif"
                                        style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                    >
                                        {membership.church?.name || 'Parish'}
                                    </h3>
                                    <StatusBadge role={membership.role} />
                                </div>
                                <p className="mt-1 text-xs text-stone-600 leading-relaxed font-sans">
                                    {membership.role === 'church_admin'
                                        ? 'Parish staff administration, Mass schedules, intention category management, and liturgical lectors.'
                                        : 'Digital ambo lectionary access and assigned Mass intention proclamation rosters.'}
                                </p>

                                {membership.role === 'church_admin' && (
                                    <div className="mt-3 flex flex-wrap gap-1.5 text-xs">
                                        <Link
                                            href={route('church.masses.index', { church: membership.church_id })}
                                            className="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-gold-100 text-gold-950 font-semibold hover:bg-gold-200 transition"
                                        >
                                            <CalendarIcon className="w-3.5 h-3.5" />
                                            <span>Mass Schedules</span>
                                        </Link>
                                        <Link
                                            href={route('church.lectors.matrix', { church: membership.church_id })}
                                            className="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-sky-100 text-sky-950 font-semibold hover:bg-sky-200 transition"
                                        >
                                            <BookOpenIcon className="w-3.5 h-3.5" />
                                            <span>Lectors Matrix</span>
                                        </Link>
                                        <Link
                                            href={route('church.categories.index', { church: membership.church_id })}
                                            className="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-amber-100 text-amber-950 font-semibold hover:bg-amber-200 transition"
                                        >
                                            <TagIcon className="w-3.5 h-3.5" />
                                            <span>Categories</span>
                                        </Link>
                                    </div>
                                )}

                                {membership.role === 'lector' && (
                                    <div className="mt-3 flex flex-wrap gap-1.5 text-xs">
                                        <Link
                                            href={route('lector.masses.index', { church: membership.church_id })}
                                            className="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-sky-100 text-sky-950 font-semibold hover:bg-sky-200 transition"
                                        >
                                            <CalendarIcon className="w-3.5 h-3.5" />
                                            <span>My Assigned Masses</span>
                                        </Link>
                                    </div>
                                )}
                            </div>
                            <div className="mt-6 pt-4 border-t border-gold-100">
                                <Link
                                    href={
                                        membership.role === 'church_admin'
                                            ? route('church.dashboard', { church: membership.church_id })
                                            : route('lector.dashboard', { church: membership.church_id })
                                    }
                                    className="inline-flex items-center text-xs font-bold text-liturgical-800 hover:text-liturgical-950"
                                >
                                    Enter Parish Ministry &rarr;
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Liturgical Platform Mission Card */}
                <div className="rounded-3xl border border-gold-300/70 bg-gradient-to-r from-liturgical-950 via-liturgical-900 to-stone-950 p-8 sm:p-10 text-gold-100 shadow-lg ring-1 ring-gold-400/30">
                    <div className="max-w-3xl space-y-3">
                        <div className="text-[10px] font-serif uppercase tracking-widest text-gold-400">
                            Sancta Ecclesia &bull; Liturgical Integrity
                        </div>
                        <h3
                            className="text-xl sm:text-2xl font-bold text-white font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            The Pabasa Sacred Mission
                        </h3>
                        <p className="text-xs sm:text-sm text-stone-300 leading-relaxed font-sans">
                            Pabasa unites Catholic parishes across the Philippine archipelago with liturgical reverence and fiduciary transparency. Intentions submitted by parishioners are proclaimed verbatim at the ambo during Holy Mass, supported by zero-trust multi-tenant isolation, verified GCash reconciliation, and immutable audit trails.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
