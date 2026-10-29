import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import { CalendarIcon, BookOpenIcon, TagIcon, ChurchIcon } from '@/Components/Icons';
import { Head, Link } from '@inertiajs/react';

export default function ChurchDashboard({ church, stats, recent_audit_logs = [] }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-3">
                            <h2
                                className="text-2xl font-bold tracking-tight text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                {church.name}
                            </h2>
                            <StatusBadge status={church.status} />
                        </div>
                        <p className="mt-1 text-xs text-stone-600 font-sans">
                            Parish Pastoral Administration &bull; Local Canonical Timezone: {church.timezone}
                        </p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <Link
                            href={route('church.memberships.index', { church: church.id })}
                            className="inline-flex items-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 border border-gold-400/40 px-4 py-2 text-xs font-bold uppercase tracking-wider text-gold-100 shadow-xs hover:from-liturgical-700 hover:to-liturgical-900 transition"
                        >
                            Manage Parish Staff &rarr;
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`${church.name} - Pastoral Administration`} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Stats Row */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs ring-1 ring-gold-400/20">
                        <div className="absolute top-0 inset-x-0 h-1 bg-liturgical-800" />
                        <dt className="text-[11px] font-bold tracking-wider text-stone-500 uppercase">
                            Parish Administrators
                        </dt>
                        <dd
                            className="mt-2 text-3xl font-extrabold text-liturgical-950 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {stats?.admins_count ?? 0}
                        </dd>
                        <div className="mt-1 text-xs text-stone-500">
                            Authorized pastoral staff with administrative rights
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs ring-1 ring-gold-400/20">
                        <div className="absolute top-0 inset-x-0 h-1 bg-sky-600" />
                        <dt className="text-[11px] font-bold tracking-wider text-sky-800 uppercase">
                            Liturgical Lectors
                        </dt>
                        <dd
                            className="mt-2 text-3xl font-extrabold text-sky-900 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {stats?.lectors_count ?? 0}
                        </dd>
                        <div className="mt-1 text-xs text-stone-500">
                            Commissioned ambo readers for Mass intentions
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs ring-1 ring-gold-400/20">
                        <div className="absolute top-0 inset-x-0 h-1 bg-gold-500" />
                        <dt className="text-[11px] font-bold tracking-wider text-gold-700 uppercase">
                            Pending Invitations
                        </dt>
                        <dd
                            className="mt-2 text-3xl font-extrabold text-gold-800 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {stats?.pending_invitations_count ?? 0}
                        </dd>
                        <div className="mt-1 text-xs text-stone-500">
                            Awaiting recipient acceptance & verification
                        </div>
                    </div>
                </div>

                {/* Pastoral Ministry Quick Actions */}
                <div className="space-y-4">
                    <div className="border-b border-gold-200/80 pb-3">
                        <h3
                            className="text-lg font-bold text-liturgical-950 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            Pastoral Ministry Functions
                        </h3>
                        <p className="text-xs text-stone-600">
                            Core administrative tools to schedule Masses, assign ambo lectors, manage prayer categories, and configure parish details.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        {/* 1. Mass Schedules */}
                        <Link
                            href={route('church.masses.index', { church: church.id })}
                            className="group relative overflow-hidden rounded-2xl border border-gold-300/80 bg-white p-6 shadow-xs hover:border-gold-500 hover:shadow-md transition flex flex-col justify-between"
                        >
                            <div className="absolute top-0 inset-x-0 h-1 bg-liturgical-800" />
                            <div>
                                <div className="w-10 h-10 rounded-xl bg-ivory-100 border border-gold-200 flex items-center justify-center text-liturgical-900 mb-3 group-hover:scale-110 transition-transform">
                                    <CalendarIcon className="w-5 h-5" />
                                </div>
                                <h4 className="font-cinzel text-sm font-bold text-liturgical-950 group-hover:text-gold-700 transition">
                                    Mass Schedule Builder
                                </h4>
                                <p className="mt-1 text-xs text-stone-600 leading-relaxed">
                                    Create single Masses or generate recurring weekly series. Configure booking cutoffs and altar spaces.
                                </p>
                            </div>
                            <div className="mt-4 pt-3 border-t border-gold-100 text-xs font-bold text-liturgical-900 flex items-center justify-between">
                                <span>Open Schedule Builder</span>
                                <span className="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>

                        {/* 2. Lector Assignment Matrix */}
                        <Link
                            href={route('church.lectors.matrix', { church: church.id })}
                            className="group relative overflow-hidden rounded-2xl border border-gold-300/80 bg-white p-6 shadow-xs hover:border-sky-500 hover:shadow-md transition flex flex-col justify-between"
                        >
                            <div className="absolute top-0 inset-x-0 h-1 bg-sky-600" />
                            <div>
                                <div className="w-10 h-10 rounded-xl bg-sky-50 border border-sky-200 flex items-center justify-center text-sky-800 mb-3 group-hover:scale-110 transition-transform">
                                    <BookOpenIcon className="w-5 h-5" />
                                </div>
                                <h4 className="font-cinzel text-sm font-bold text-liturgical-950 group-hover:text-sky-700 transition">
                                    Lector Matrix Board
                                </h4>
                                <p className="mt-1 text-xs text-stone-600 leading-relaxed">
                                    Assign commissioned readers to upcoming Masses, prevent double-booking, and track ambo assignments.
                                </p>
                            </div>
                            <div className="mt-4 pt-3 border-t border-sky-100 text-xs font-bold text-sky-900 flex items-center justify-between">
                                <span>Assign Lectors</span>
                                <span className="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>

                        {/* 3. Intention Categories */}
                        <Link
                            href={route('church.categories.index', { church: church.id })}
                            className="group relative overflow-hidden rounded-2xl border border-gold-300/80 bg-white p-6 shadow-xs hover:border-amber-500 hover:shadow-md transition flex flex-col justify-between"
                        >
                            <div className="absolute top-0 inset-x-0 h-1 bg-amber-600" />
                            <div>
                                <div className="w-10 h-10 rounded-xl bg-amber-50 border border-gold-200 flex items-center justify-center text-amber-800 mb-3 group-hover:scale-110 transition-transform">
                                    <TagIcon className="w-5 h-5" />
                                </div>
                                <h4 className="font-cinzel text-sm font-bold text-liturgical-950 group-hover:text-amber-800 transition">
                                    Intention Categories
                                </h4>
                                <p className="mt-1 text-xs text-stone-600 leading-relaxed">
                                    Manage Thanksgiving, Souls, Petitions, and Healing categories. Toggle active status for parish offerings.
                                </p>
                            </div>
                            <div className="mt-4 pt-3 border-t border-gold-100 text-xs font-bold text-amber-900 flex items-center justify-between">
                                <span>Configure Categories</span>
                                <span className="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>

                        {/* 4. Parish Profile & Details */}
                        <Link
                            href={route('church.branding.edit', { church: church.id })}
                            className="group relative overflow-hidden rounded-2xl border border-gold-300/80 bg-white p-6 shadow-xs hover:border-gold-500 hover:shadow-md transition flex flex-col justify-between"
                        >
                            <div className="absolute top-0 inset-x-0 h-1 bg-gold-600" />
                            <div>
                                <div className="w-10 h-10 rounded-xl bg-ivory-100 border border-gold-200 flex items-center justify-center text-gold-700 mb-3 group-hover:scale-110 transition-transform">
                                    <ChurchIcon className="w-5 h-5" />
                                </div>
                                <h4 className="font-cinzel text-sm font-bold text-liturgical-950 group-hover:text-gold-700 transition">
                                    Parish Profile & Details
                                </h4>
                                <p className="mt-1 text-xs text-stone-600 leading-relaxed">
                                    Configure public parish motto, address, contact telephone, email, and theme styling for faithful visitors.
                                </p>
                            </div>
                            <div className="mt-4 pt-3 border-t border-gold-100 text-xs font-bold text-liturgical-900 flex items-center justify-between">
                                <span>Edit Parish Profile</span>
                                <span className="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    {/* Church Profile Card */}
                    <div className="rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs space-y-4 ring-1 ring-gold-400/20">
                        <div className="border-b border-gold-200/80 pb-3 flex items-center justify-between">
                            <h3
                                className="text-base font-bold text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Parish Sanctuary
                            </h3>
                            <span className="text-[10px] font-serif uppercase tracking-widest text-gold-700">Paroecia</span>
                        </div>
                        <dl className="space-y-3.5 text-xs text-stone-700">
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wider text-stone-500">Physical Address</dt>
                                <dd className="font-semibold text-stone-900 mt-0.5">
                                    {church.address || 'Address unconfigured'}, {church.city}, {church.province}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wider text-stone-500">Pastoral Email</dt>
                                <dd className="font-semibold text-liturgical-900 mt-0.5 font-mono">
                                    {church.contact_email || 'None registered'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wider text-stone-500">Pastoral Telephone</dt>
                                <dd className="font-semibold text-stone-900 mt-0.5">
                                    {church.contact_phone || 'None registered'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wider text-stone-500">Canonical Slug</dt>
                                <dd className="font-mono text-xs text-stone-600 mt-0.5 bg-ivory-100 px-2 py-1 rounded border border-gold-200">
                                    {church.slug}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {/* Parish Audit Activity */}
                    <div className="rounded-2xl border border-gold-300/70 bg-white shadow-xs overflow-hidden lg:col-span-2 ring-1 ring-gold-400/20">
                        <div className="border-b border-gold-200/80 bg-gradient-to-r from-ivory-50 via-white to-ivory-50 px-6 py-4 flex items-center justify-between">
                            <div>
                                <h3
                                    className="text-base font-bold text-liturgical-950 font-serif"
                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                >
                                    Recent Parish Audit Entries
                                </h3>
                                <p className="text-xs text-stone-500">
                                    Parish staff administrative trail
                                </p>
                            </div>
                            <span className="text-xs font-semibold text-gold-800 bg-gold-50 border border-gold-200 px-3 py-1 rounded-full">
                                Staff Actions
                            </span>
                        </div>
                        <div className="divide-y divide-stone-100">
                            {recent_audit_logs.length === 0 ? (
                                <div className="p-8 text-center text-xs text-stone-500">
                                    No administrative staff actions logged for this parish yet.
                                </div>
                            ) : (
                                recent_audit_logs.map((log) => (
                                    <div key={log.id} className="p-4 sm:px-6 text-xs hover:bg-ivory-50 transition">
                                        <div className="flex items-center justify-between">
                                            <span className="font-mono font-bold text-liturgical-900 bg-liturgical-50 border border-liturgical-200 px-2 py-0.5 rounded text-[11px]">
                                                {log.action}
                                            </span>
                                            <span className="text-stone-400 font-mono text-[11px]">
                                                {new Date(log.created_at).toLocaleString()}
                                            </span>
                                        </div>
                                        <div className="mt-1 text-stone-700">
                                            Actor: <span className="font-bold text-stone-900">{log.actor?.name || log.actor_type}</span>
                                            {log.reason && <span className="italic text-stone-500"> &mdash; &ldquo;{log.reason}&rdquo;</span>}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
