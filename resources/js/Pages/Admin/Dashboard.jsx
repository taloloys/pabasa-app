import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import Card from '@/Components/Card';
import { Head, Link } from '@inertiajs/react';

export default function AdminDashboard({ metrics, recent_churches = [], recent_audit_logs = [] }) {
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
                                Central Diocese Console
                            </h2>
                            <StatusBadge role="central_admin" />
                        </div>
                        <p className="mt-1 text-xs text-stone-600 font-sans">
                            Archdiocese oversight, parish directory verification, and system-wide append-only audit trail.
                        </p>
                    </div>

                    <div className="flex items-center space-x-2 text-xs font-serif italic text-gold-800">
                        <span>Auctoritas Diocesana &bull; In Veritate</span>
                    </div>
                </div>
            }
        >
            <Head title="Diocese Authority Console - Pabasa" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Metrics Grid */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs ring-1 ring-gold-400/20">
                        <div className="absolute top-0 inset-x-0 h-1 bg-liturgical-800" />
                        <dt className="text-[11px] font-bold tracking-wider text-stone-500 uppercase">
                            Total Registered Parishes
                        </dt>
                        <dd
                            className="mt-2 text-3xl font-extrabold text-liturgical-950 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {metrics?.churches_count ?? 0}
                        </dd>
                        <div className="mt-2 text-xs text-stone-500">
                            Diocesan parish profiles
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs ring-1 ring-gold-400/20">
                        <div className="absolute top-0 inset-x-0 h-1 bg-emerald-600" />
                        <dt className="text-[11px] font-bold tracking-wider text-emerald-700 uppercase">
                            Published Sanctuaries
                        </dt>
                        <dd
                            className="mt-2 text-3xl font-extrabold text-emerald-800 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {metrics?.published_churches ?? 0}
                        </dd>
                        <div className="mt-2 text-xs text-stone-500">
                            Visible for public intentions
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs ring-1 ring-gold-400/20">
                        <div className="absolute top-0 inset-x-0 h-1 bg-gold-500" />
                        <dt className="text-[11px] font-bold tracking-wider text-gold-700 uppercase">
                            Pending Canonical Review
                        </dt>
                        <dd
                            className="mt-2 text-3xl font-extrabold text-gold-800 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {metrics?.pending_churches ?? 0}
                        </dd>
                        <div className="mt-2 text-xs text-stone-500">
                            Awaiting verification approval
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-2xl border border-gold-300/70 bg-white p-6 shadow-xs ring-1 ring-gold-400/20">
                        <div className="absolute top-0 inset-x-0 h-1 bg-liturgical-600" />
                        <dt className="text-[11px] font-bold tracking-wider text-liturgical-700 uppercase">
                            Faithful & Staff Accounts
                        </dt>
                        <dd
                            className="mt-2 text-3xl font-extrabold text-liturgical-900 font-serif"
                            style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                        >
                            {metrics?.users_count ?? 0}
                        </dd>
                        <div className="mt-2 text-xs text-stone-500">
                            Parishioners, lectors, administrators
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    {/* Recent Parishes */}
                    <div className="rounded-2xl border border-gold-300/70 bg-white shadow-xs overflow-hidden ring-1 ring-gold-400/20">
                        <div className="border-b border-gold-200/80 bg-gradient-to-r from-ivory-50 via-white to-ivory-50 px-6 py-4 flex items-center justify-between">
                            <div>
                                <h3
                                    className="text-base font-bold text-liturgical-950 font-serif"
                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                >
                                    Parish Directory
                                </h3>
                                <p className="text-xs text-stone-500">
                                    Affiliated Catholic Parishes
                                </p>
                            </div>
                            <span className="text-xs font-semibold text-gold-800 bg-gold-50 border border-gold-200 px-3 py-1 rounded-full">
                                {recent_churches.length} Registered
                            </span>
                        </div>
                        <div className="divide-y divide-stone-100">
                            {recent_churches.length === 0 ? (
                                <div className="p-8 text-center text-sm text-stone-500">
                                    No parishes registered yet.
                                </div>
                            ) : (
                                recent_churches.map((church) => (
                                    <div key={church.id} className="p-4 sm:px-6 flex items-center justify-between hover:bg-ivory-50 transition">
                                        <div>
                                            <div className="font-bold text-liturgical-950 text-sm">
                                                {church.name}
                                            </div>
                                            <div className="text-xs text-stone-500 mt-0.5">
                                                {church.city ? `${church.city}, ${church.province}` : 'Location unconfigured'} &bull; {church.timezone}
                                            </div>
                                        </div>
                                        <div className="flex items-center space-x-3">
                                            <StatusBadge status={church.status} />
                                            <Link
                                                href={route('church.dashboard', { church: church.id })}
                                                className="text-xs font-bold text-liturgical-800 hover:text-liturgical-950 underline"
                                            >
                                                Manage &rarr;
                                            </Link>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    {/* Recent Audit Logs */}
                    <div className="rounded-2xl border border-gold-300/70 bg-white shadow-xs overflow-hidden ring-1 ring-gold-400/20">
                        <div className="border-b border-gold-200/80 bg-gradient-to-r from-ivory-50 via-white to-ivory-50 px-6 py-4 flex items-center justify-between">
                            <div>
                                <h3
                                    className="text-base font-bold text-liturgical-950 font-serif"
                                    style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                                >
                                    Canonical Audit Log
                                </h3>
                                <p className="text-xs text-stone-500">
                                    Append-Only Immutability Trail
                                </p>
                            </div>
                            <span className="text-xs font-semibold text-liturgical-800 bg-liturgical-50 border border-liturgical-200 px-3 py-1 rounded-full">
                                Sealed Trail
                            </span>
                        </div>
                        <div className="divide-y divide-stone-100">
                            {recent_audit_logs.length === 0 ? (
                                <div className="p-8 text-center text-sm text-stone-500">
                                    No audit entries recorded yet.
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
                                        <div className="mt-0.5 text-stone-400 text-[10px]">
                                            Target: {log.target_type} #{log.target_id} {log.ip_hash ? `&bull; Hash: ${log.ip_hash.substring(0, 10)}...` : ''}
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
