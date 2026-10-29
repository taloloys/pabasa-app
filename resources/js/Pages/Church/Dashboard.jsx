import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
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
                                <dt className="text-[10px] font-bold uppercase tracking-wider text-stone-400">Physical Address</dt>
                                <dd className="font-semibold text-stone-900 mt-0.5">
                                    {church.address || 'Address unconfigured'}, {church.city}, {church.province}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-[10px] font-bold uppercase tracking-wider text-stone-400">Pastoral Email</dt>
                                <dd className="font-semibold text-liturgical-900 mt-0.5 font-mono">
                                    {church.contact_email || 'None registered'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-[10px] font-bold uppercase tracking-wider text-stone-400">Pastoral Telephone</dt>
                                <dd className="font-semibold text-stone-900 mt-0.5">
                                    {church.contact_phone || 'None registered'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-[10px] font-bold uppercase tracking-wider text-stone-400">Canonical Slug</dt>
                                <dd className="font-mono text-[11px] text-stone-600 mt-0.5 bg-ivory-100 px-2 py-1 rounded border border-gold-200">
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
