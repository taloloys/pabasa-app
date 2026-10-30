import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const FEE_TYPE_LABELS = {
    fixed_per_order: 'Fixed per Order',
    fixed_per_slip: 'Fixed per Slip',
    percentage: 'Percentage of Subtotal',
    none: 'No Fee',
};

const STATUS_STYLES = {
    approved: 'bg-emerald-100 text-emerald-800 border-emerald-300',
    draft: 'bg-amber-100 text-amber-800 border-amber-300',
    retired: 'bg-stone-100 text-stone-500 border-stone-200',
};

function SectionTitle({ children }) {
    return (
        <h2 className="font-cinzel text-base font-bold text-liturgical-950 uppercase tracking-wider border-b border-gold-200 pb-2 mb-4">
            {children}
        </h2>
    );
}

function Badge({ status }) {
    return (
        <span className={`text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border ${STATUS_STYLES[status] ?? STATUS_STYLES.draft}`}>
            {status}
        </span>
    );
}

export default function PaymentSettingsIndex({ configs = [], churches = [], activeConfig = null }) {
    const [tab, setTab] = useState('fees');
    const [createOpen, setCreateOpen] = useState(false);
    const [approveTarget, setApproveTarget] = useState(null);
    const [gcashTarget, setGcashTarget] = useState(null);

    // ── Create config form ──────────────────────────────────────────
    const createForm = useForm({
        name: '',
        currency: 'PHP',
        effective_from: '',
        effective_until: '',
        base_price_per_intention: '',
        service_fee_type: 'none',
        service_fee_amount: '',
        service_fee_label: 'Platform Convenience Fee',
        service_fee_active: false,
        change_reason: '',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        createForm.post(route('admin.payment-settings.configs.store'), {
            onSuccess: () => { createForm.reset(); setCreateOpen(false); },
        });
    };

    // ── Approve form ─────────────────────────────────────────────────
    const approveForm = useForm({ reason: '' });
    const handleApprove = (e) => {
        e.preventDefault();
        approveForm.post(route('admin.payment-settings.configs.approve', { config: approveTarget.id }), {
            onSuccess: () => { approveForm.reset(); setApproveTarget(null); },
        });
    };

    // ── GCash edit form ──────────────────────────────────────────────
    const gcashForm = useForm({ gcash_name: '', gcash_number: '', gcash_qr_image: null });
    const openGcash = (church) => {
        gcashForm.setData({ gcash_name: church.gcash_name || '', gcash_number: church.gcash_number || '', gcash_qr_image: null });
        setGcashTarget(church);
    };
    const handleGcash = (e) => {
        e.preventDefault();
        gcashForm.post(route('admin.payment-settings.churches.gcash', { church: gcashTarget.id }), {
            forceFormData: true,
            onSuccess: () => { setGcashTarget(null); },
        });
    };

    return (
        <AdminLayout
            header={
                <div>
                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                        Platform Payment Settings
                    </h1>
                    <p className="text-xs text-stone-500 mt-1">
                        Manage diocese fee schedules, platform convenience fees, and parish GCash payment credentials.
                    </p>
                </div>
            }
        >
            <Head title="Payment Settings — Central Admin" />

            <div className="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                {/* Tabs */}
                <div className="flex border-b border-stone-200 gap-4">
                    {[['fees', 'Platform Fee Configuration'], ['parishes', 'Parish GCash Profiles']].map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setTab(key)}
                            className={`pb-2 text-xs font-bold uppercase tracking-wider transition border-b-2 ${tab === key
                                    ? 'border-liturgical-900 text-liturgical-950'
                                    : 'border-transparent text-stone-500 hover:text-stone-800'
                                }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                {/* ── Tab 1: Fee Configuration ──────────────────────────────── */}
                {tab === 'fees' && (
                    <div className="space-y-6">
                        {/* Active config banner */}
                        {activeConfig && (
                            <div className="p-4 rounded-xl border border-emerald-300 bg-emerald-50 space-y-1 text-xs">
                                <span className="font-bold text-emerald-900 uppercase tracking-wider">
                                    ✓ Active Pricing Schedule — v{activeConfig.version}
                                </span>
                                <div className="text-emerald-800">{activeConfig.name} &bull; {activeConfig.currency}</div>
                                {activeConfig.pricing_rules?.service_fee?.is_active && (
                                    <div className="text-emerald-700">
                                        Fee: {FEE_TYPE_LABELS[activeConfig.pricing_rules.service_fee.type] || 'N/A'}
                                        {' — '}{activeConfig.pricing_rules.service_fee.label}
                                        {' '}({activeConfig.pricing_rules.service_fee.type === 'percentage'
                                            ? `${activeConfig.pricing_rules.service_fee.amount}%`
                                            : `${activeConfig.currency} ${activeConfig.pricing_rules.service_fee.amount}`})
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex justify-between items-center">
                            <SectionTitle>Fee Schedule History</SectionTitle>
                            <button
                                type="button"
                                onClick={() => setCreateOpen(true)}
                                className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white bg-liturgical-900 hover:bg-liturgical-950 shadow-xs transition"
                            >
                                <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                New Draft
                            </button>
                        </div>

                        <div className="bg-white rounded-xl border border-stone-200 shadow-xs overflow-hidden">
                            <table className="w-full text-xs">
                                <thead className="bg-stone-50 border-b border-stone-200">
                                    <tr>
                                        {['Version', 'Scope / Parish', 'Name', 'Fee Type', 'Fee Amount', 'Status', 'Approved By', ''].map((h) => (
                                            <th key={h} className="text-left px-4 py-3 font-bold text-stone-600 uppercase tracking-wider text-[11px]">{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-100">
                                    {configs.length === 0 ? (
                                        <tr><td colSpan={8} className="text-center py-10 text-stone-400">No configurations created yet.</td></tr>
                                    ) : configs.map((c) => {
                                        const fee = c.pricing_rules?.service_fee ?? {};
                                        return (
                                            <tr key={c.id} className="hover:bg-stone-50 transition">
                                                <td className="px-4 py-3 font-mono font-bold text-stone-700">v{c.version}</td>
                                                <td className="px-4 py-3">
                                                    {c.scope === 'global' ? (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-liturgical-100 text-liturgical-900 border border-liturgical-200">
                                                            Diocese Global
                                                        </span>
                                                    ) : (
                                                        <div className="flex flex-col">
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-900 border border-amber-200 w-fit">
                                                                Parish Override
                                                            </span>
                                                            <span className="text-[11px] text-stone-600 mt-0.5 truncate max-w-[150px]" title={c.church_name}>
                                                                {c.church_name || `Parish #${c.church_id}`}
                                                            </span>
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 font-semibold text-stone-900">{c.name}</td>
                                                <td className="px-4 py-3 text-stone-600">{FEE_TYPE_LABELS[fee.type] ?? '—'}</td>
                                                <td className="px-4 py-3 font-mono text-stone-800">
                                                    {fee.is_active
                                                        ? (fee.type === 'percentage' ? `${fee.amount}%` : `${c.currency} ${fee.amount}`)
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3"><Badge status={c.status} /></td>
                                                <td className="px-4 py-3 text-stone-500">{c.approved_by || '—'}</td>
                                                <td className="px-4 py-3">
                                                    {c.status === 'draft' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setApproveTarget(c)}
                                                            className="px-3 py-1 rounded text-[11px] font-bold text-emerald-800 border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 transition"
                                                        >
                                                            Approve
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* ── Tab 2: Parish GCash Profiles ─────────────────────────── */}
                {tab === 'parishes' && (
                    <div className="space-y-4">
                        <SectionTitle>Parish GCash Payment Profiles</SectionTitle>
                        <div className="bg-white rounded-xl border border-stone-200 shadow-xs overflow-hidden">
                            <table className="w-full text-xs">
                                <thead className="bg-stone-50 border-b border-stone-200">
                                    <tr>
                                        {['Parish', 'Location', 'GCash Account', 'GCash Number', 'QR Code', ''].map((h) => (
                                            <th key={h} className="text-left px-4 py-3 font-bold text-stone-600 uppercase tracking-wider text-[11px]">{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-100">
                                    {churches.length === 0 ? (
                                        <tr><td colSpan={6} className="text-center py-10 text-stone-400">No published parishes.</td></tr>
                                    ) : churches.map((ch) => (
                                        <tr key={ch.id} className="hover:bg-stone-50 transition">
                                            <td className="px-4 py-3 font-semibold text-stone-900">{ch.name}</td>
                                            <td className="px-4 py-3 text-stone-500">{ch.city}, {ch.province}</td>
                                            <td className="px-4 py-3 text-stone-700">{ch.gcash_name || <span className="text-stone-300 italic">Not set</span>}</td>
                                            <td className="px-4 py-3 font-mono text-stone-700">{ch.gcash_number || <span className="text-stone-300 italic">Not set</span>}</td>
                                            <td className="px-4 py-3">
                                                {ch.gcash_qr_url
                                                    ? <img src={ch.gcash_qr_url} alt="QR" className="h-8 w-8 rounded object-cover border border-stone-200" />
                                                    : <span className="text-stone-300 italic">No QR</span>}
                                            </td>
                                            <td className="px-4 py-3">
                                                <button
                                                    type="button"
                                                    onClick={() => openGcash(ch)}
                                                    className="px-3 py-1 rounded text-[11px] font-bold text-liturgical-800 border border-liturgical-300 bg-liturgical-50 hover:bg-liturgical-100 transition"
                                                >
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>

            {/* ── Modal: Create Draft Config ────────────────────────────── */}
            {createOpen && (
                <div className="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-stone-200 max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between pb-4 border-b border-stone-100">
                            <div>
                                <span className="text-xs uppercase font-semibold tracking-wider text-liturgical-700">New Draft</span>
                                <h3 className="text-lg font-cinzel font-bold text-stone-900">Platform Fee Configuration</h3>
                            </div>
                            <button type="button" onClick={() => setCreateOpen(false)} className="p-1 rounded text-stone-400 hover:text-stone-600">
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form onSubmit={handleCreate} className="mt-5 space-y-4 text-xs">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="col-span-2">
                                    <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Configuration Name *</label>
                                    <input type="text" value={createForm.data.name} onChange={(e) => createForm.setData('name', e.target.value)}
                                        className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-liturgical-500 focus:outline-none" required />
                                    {createForm.errors.name && <p className="text-rose-600 mt-1">{createForm.errors.name}</p>}
                                </div>

                                <div>
                                    <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Currency</label>
                                    <input type="text" value={createForm.data.currency} onChange={(e) => createForm.setData('currency', e.target.value)}
                                        className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none" maxLength={3} />
                                </div>

                                <div>
                                    <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Base Price/Intention (PHP)</label>
                                    <input type="number" step="0.01" min="0" value={createForm.data.base_price_per_intention}
                                        onChange={(e) => createForm.setData('base_price_per_intention', e.target.value)}
                                        className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none" placeholder="e.g. 150.00" />
                                </div>

                                <div>
                                    <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Effective From</label>
                                    <input type="datetime-local" value={createForm.data.effective_from} onChange={(e) => createForm.setData('effective_from', e.target.value)}
                                        className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none" />
                                </div>

                                <div>
                                    <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Effective Until</label>
                                    <input type="datetime-local" value={createForm.data.effective_until} onChange={(e) => createForm.setData('effective_until', e.target.value)}
                                        className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none" />
                                </div>
                            </div>

                            {/* Service Fee Section */}
                            <div className="p-4 rounded-lg border border-gold-200 bg-gold-50/50 space-y-3">
                                <p className="font-bold text-stone-700 uppercase tracking-wide">Platform Convenience Fee</p>

                                <div className="flex items-center gap-2">
                                    <input type="checkbox" id="fee_active" checked={createForm.data.service_fee_active}
                                        onChange={(e) => createForm.setData('service_fee_active', e.target.checked)}
                                        className="rounded border-stone-300 text-liturgical-900" />
                                    <label htmlFor="fee_active" className="font-semibold text-stone-700 cursor-pointer">Enable service fee</label>
                                </div>

                                {createForm.data.service_fee_active && (
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="col-span-2">
                                            <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Fee Model *</label>
                                            <select value={createForm.data.service_fee_type}
                                                onChange={(e) => createForm.setData('service_fee_type', e.target.value)}
                                                className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none">
                                                <option value="none">No Fee</option>
                                                <option value="fixed_per_order">Fixed per Order (flat fee per checkout)</option>
                                                <option value="fixed_per_slip">Fixed per Slip (fee × number of slips)</option>
                                                <option value="percentage">Percentage of Slips Subtotal</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">
                                                {createForm.data.service_fee_type === 'percentage' ? 'Rate (%)' : 'Amount (PHP)'}
                                            </label>
                                            <input type="number" step="0.01" min="0" value={createForm.data.service_fee_amount}
                                                onChange={(e) => createForm.setData('service_fee_amount', e.target.value)}
                                                className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none"
                                                placeholder={createForm.data.service_fee_type === 'percentage' ? 'e.g. 2.5' : 'e.g. 15.00'} />
                                        </div>

                                        <div>
                                            <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Fee Label</label>
                                            <input type="text" value={createForm.data.service_fee_label}
                                                onChange={(e) => createForm.setData('service_fee_label', e.target.value)}
                                                className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none"
                                                placeholder="Platform Convenience Fee" />
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Change Reason (Required for Audit) *</label>
                                <textarea rows={2} value={createForm.data.change_reason} onChange={(e) => createForm.setData('change_reason', e.target.value)}
                                    className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-liturgical-500 focus:outline-none" required />
                                {createForm.errors.change_reason && <p className="text-rose-600 mt-1">{createForm.errors.change_reason}</p>}
                            </div>

                            <div className="flex justify-end gap-3 pt-2 border-t border-stone-100">
                                <button type="button" onClick={() => setCreateOpen(false)} className="px-4 py-2 text-xs font-semibold text-stone-600">Cancel</button>
                                <button type="submit" disabled={createForm.processing}
                                    className="px-5 py-2 text-xs font-bold text-white bg-liturgical-900 hover:bg-liturgical-950 rounded-lg shadow-xs transition disabled:opacity-50">
                                    {createForm.processing ? 'Saving...' : 'Save as Draft'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* ── Modal: Approve Config ──────────────────────────────────── */}
            {approveTarget && (
                <div className="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-stone-200">
                        <h3 className="font-cinzel text-base font-bold text-stone-900 mb-1">Approve Configuration</h3>
                        <p className="text-xs text-stone-500 mb-4">
                            Approving <span className="font-bold text-stone-800">{approveTarget.name} v{approveTarget.version}</span> will
                            activate it immediately and retire any currently active schedule.
                        </p>
                        <form onSubmit={handleApprove} className="space-y-3 text-xs">
                            <div>
                                <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">Approval Reason *</label>
                                <textarea rows={2} value={approveForm.data.reason} onChange={(e) => approveForm.setData('reason', e.target.value)}
                                    className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none" required />
                                {approveForm.errors.reason && <p className="text-rose-600 mt-1">{approveForm.errors.reason}</p>}
                            </div>
                            <div className="flex justify-end gap-3 pt-2">
                                <button type="button" onClick={() => setApproveTarget(null)} className="px-4 py-2 font-semibold text-stone-600">Cancel</button>
                                <button type="submit" disabled={approveForm.processing}
                                    className="px-5 py-2 font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-lg shadow-xs transition disabled:opacity-50">
                                    {approveForm.processing ? 'Approving...' : 'Confirm Approval'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* ── Modal: Edit Parish GCash ───────────────────────────────── */}
            {gcashTarget && (
                <div className="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-stone-200">
                        <h3 className="font-cinzel text-base font-bold text-stone-900 mb-0.5">GCash Credentials</h3>
                        <p className="text-xs text-stone-500 mb-4">{gcashTarget.name}</p>
                        <form onSubmit={handleGcash} className="space-y-4 text-xs" encType="multipart/form-data">
                            <div>
                                <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">GCash Account Name</label>
                                <input type="text" value={gcashForm.data.gcash_name} onChange={(e) => gcashForm.setData('gcash_name', e.target.value)}
                                    className="w-full px-3 py-2 border border-stone-300 rounded-lg focus:outline-none" placeholder="Registered account name" />
                                {gcashForm.errors.gcash_name && <p className="text-rose-600 mt-1">{gcashForm.errors.gcash_name}</p>}
                            </div>
                            <div>
                                <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">GCash Mobile Number</label>
                                <input type="text" value={gcashForm.data.gcash_number} onChange={(e) => gcashForm.setData('gcash_number', e.target.value)}
                                    className="w-full px-3 py-2 border border-stone-300 font-mono rounded-lg focus:outline-none" placeholder="09xxxxxxxxxx or +639xxxxxxxxx" />
                                {gcashForm.errors.gcash_number && <p className="text-rose-600 mt-1">{gcashForm.errors.gcash_number}</p>}
                            </div>
                            <div>
                                <label className="block font-bold text-stone-700 uppercase tracking-wide mb-1">GCash QR Code Image</label>
                                {gcashTarget.gcash_qr_url && (
                                    <img src={gcashTarget.gcash_qr_url} alt="Current QR" className="h-20 w-20 mb-2 rounded border border-stone-200 object-contain" />
                                )}
                                <input type="file" accept="image/jpeg,image/png,image/webp"
                                    onChange={(e) => gcashForm.setData('gcash_qr_image', e.target.files[0])}
                                    className="w-full text-stone-600" />
                                <p className="text-stone-400 mt-1">PNG, JPG, WebP &le; 2MB</p>
                                {gcashForm.errors.gcash_qr_image && <p className="text-rose-600 mt-1">{gcashForm.errors.gcash_qr_image}</p>}
                            </div>
                            <div className="flex justify-end gap-3 pt-2 border-t border-stone-100">
                                <button type="button" onClick={() => setGcashTarget(null)} className="px-4 py-2 font-semibold text-stone-600">Cancel</button>
                                <button type="submit" disabled={gcashForm.processing}
                                    className="px-5 py-2 font-bold text-white bg-liturgical-900 hover:bg-liturgical-950 rounded-lg shadow-xs transition disabled:opacity-50">
                                    {gcashForm.processing ? 'Saving...' : 'Save GCash Credentials'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

