import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import Card from '@/Components/Card';
import { Head, Link, useForm } from '@inertiajs/react';

export default function AdminDashboard({ metrics, recent_churches = [], recent_audit_logs = [] }) {
    const [isRegisterModalOpen, setIsRegisterModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        slug: '',
        city: '',
        province: '',
        address: '',
        postal_code: '',
        contact_email: '',
        contact_phone: '',
        timezone: 'Asia/Manila',
        auto_verify: false,
        initial_admin_email: '',
    });

    const handleRegisterSubmit = (e) => {
        e.preventDefault();
        post(route('admin.churches.store'), {
            onSuccess: () => {
                reset();
                setIsRegisterModalOpen(false);
            },
        });
    };

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
                        <div className="mt-2 flex items-center justify-between">
                            <span className="text-xs text-stone-500">Parishioners, lectors, administrators</span>
                            <Link
                                href={route('admin.users.index')}
                                className="text-xs font-bold text-amber-800 hover:text-amber-950 underline"
                            >
                                Manage &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    {/* Recent Parishes */}
                    <div className="rounded-2xl border border-gold-300/70 bg-white shadow-xs overflow-hidden ring-1 ring-gold-400/20">
                        <div className="border-b border-gold-200/80 bg-gradient-to-r from-ivory-50 via-white to-ivory-50 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
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
                            <div className="flex items-center gap-2">
                                <span className="text-xs font-semibold text-gold-800 bg-gold-50 border border-gold-200 px-3 py-1 rounded-full">
                                    {recent_churches.length} Registered
                                </span>
                                <button
                                    type="button"
                                    onClick={() => setIsRegisterModalOpen(true)}
                                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-amber-800 hover:bg-amber-900 shadow-xs transition"
                                >
                                    <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Register Parish
                                </button>
                            </div>
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

            {/* Modal: Register New Parish */}
            {isRegisterModalOpen && (
                <div className="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-stone-200 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95">
                        <div className="flex items-center justify-between pb-4 border-b border-stone-100">
                            <div>
                                <span className="text-xs uppercase font-semibold tracking-wider text-amber-800">
                                    Archdiocese Canonical Onboarding
                                </span>
                                <h3 className="text-xl font-serif font-bold text-stone-900 mt-0.5">
                                    Register New Parish Sanctuary
                                </h3>
                            </div>
                            <button
                                type="button"
                                onClick={() => setIsRegisterModalOpen(false)}
                                className="p-1 rounded-lg text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition"
                            >
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form onSubmit={handleRegisterSubmit} className="mt-6 space-y-5">
                            {/* Parish Name */}
                            <div>
                                <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                    Parish Church Name <span className="text-rose-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="e.g. Santo Niño Parish Church"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.name}</p>}
                            </div>

                            {/* City & Province Grid */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        City / Municipality <span className="text-rose-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Cebu City"
                                        value={data.city}
                                        onChange={(e) => setData('city', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                        required
                                    />
                                    {errors.city && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.city}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        Province <span className="text-rose-600">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Cebu"
                                        value={data.province}
                                        onChange={(e) => setData('province', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                        required
                                    />
                                    {errors.province && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.province}</p>}
                                </div>
                            </div>

                            {/* Address & Postal Code */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        Street Address / Landmark
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="e.g. Mabini St., Poblacion"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    />
                                    {errors.address && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.address}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        Postal Code
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="e.g. 6000"
                                        value={data.postal_code}
                                        onChange={(e) => setData('postal_code', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    />
                                    {errors.postal_code && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.postal_code}</p>}
                                </div>
                            </div>

                            {/* Contact Details */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        Parish Office Email
                                    </label>
                                    <input
                                        type="email"
                                        placeholder="office@parish.ph"
                                        value={data.contact_email}
                                        onChange={(e) => setData('contact_email', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    />
                                    {errors.contact_email && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.contact_email}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        Parish Office Phone
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="e.g. +63 32 123 4567"
                                        value={data.contact_phone}
                                        onChange={(e) => setData('contact_phone', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    />
                                    {errors.contact_phone && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.contact_phone}</p>}
                                </div>
                            </div>

                            {/* Custom URL Slug & Timezone */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        Custom URL Slug (Optional)
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Leave blank to auto-generate"
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm font-mono border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    />
                                    <p className="mt-1 text-[11px] text-stone-400">Public directory URL: /churches/{"<slug>"}</p>
                                    {errors.slug && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.slug}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-stone-700 uppercase tracking-wide">
                                        Parish Timezone <span className="text-rose-600">*</span>
                                    </label>
                                    <select
                                        value={data.timezone}
                                        onChange={(e) => setData('timezone', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                        required
                                    >
                                        <option value="Asia/Manila">Asia/Manila (PST, UTC+8)</option>
                                    </select>
                                    {errors.timezone && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.timezone}</p>}
                                </div>
                            </div>

                            {/* Initial Church Admin Invitation */}
                            <div className="p-4 rounded-xl bg-amber-50/70 border border-amber-200/80 space-y-3">
                                <div>
                                    <label className="block text-xs font-bold text-amber-950 uppercase tracking-wide">
                                        Initial Parish Administrator Invitation (Optional)
                                    </label>
                                    <input
                                        type="email"
                                        placeholder="priest@parish.ph or admin@parish.ph"
                                        value={data.initial_admin_email}
                                        onChange={(e) => setData('initial_admin_email', e.target.value)}
                                        className="mt-1 w-full px-3.5 py-2 text-sm bg-white border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-600 focus:outline-none"
                                    />
                                    <p className="mt-1 text-xs text-amber-800">
                                        If provided, a secure 7-day staff onboarding invitation token will be dispatched to this email immediately upon creation.
                                    </p>
                                    {errors.initial_admin_email && <p className="mt-1 text-xs text-rose-600 font-medium">{errors.initial_admin_email}</p>}
                                </div>

                                <div className="pt-1">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.auto_verify}
                                            onChange={(e) => setData('auto_verify', e.target.checked)}
                                            className="rounded border-amber-300 text-amber-800 focus:ring-amber-600"
                                        />
                                        <span className="text-xs font-semibold text-amber-950">
                                            Verify Canonical Standing Immediately (Status: Verified)
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {/* Buttons */}
                            <div className="flex items-center justify-end gap-3 pt-3 border-t border-stone-100">
                                <button
                                    type="button"
                                    onClick={() => setIsRegisterModalOpen(false)}
                                    className="px-4 py-2 text-xs font-semibold text-stone-600 hover:text-stone-800 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-amber-800 hover:bg-amber-900 rounded-lg shadow-sm transition disabled:opacity-50"
                                >
                                    {processing ? 'Registering...' : 'Register Parish Sanctuary'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
