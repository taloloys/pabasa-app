import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Workbench({ payment, duplicateReferenceCount = 0 }) {
    const [zoomLevel, setZoomLevel] = useState(1);
    const [activeTab, setActiveTab] = useState('verify'); // 'verify' | 'reject'

    const latestEvidence = payment.evidence?.[payment.evidence.length - 1];

    // Verification Form
    const verifyForm = useForm({
        verified_amount: payment.expected_amount || '',
        notes: '',
        force_exception_resolution: false,
    });

    // Rejection Form
    const rejectForm = useForm({
        reason: '',
    });

    const handleVerify = (e) => {
        e.preventDefault();
        verifyForm.post(`/admin/payments/${payment.id}/verify`);
    };

    const handleReject = (e) => {
        e.preventDefault();
        rejectForm.post(`/admin/payments/${payment.id}/reject`);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/admin/payments"
                            className="p-1.5 rounded-lg border border-stone-200 text-stone-500 hover:text-stone-800 hover:bg-stone-50 transition"
                            title="Back to Review Queue"
                        >
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-wider text-amber-800">Review Workbench</span>
                            <h2 className="text-xl font-serif font-bold text-stone-900">
                                Payment #{payment.id} — Order {payment.order?.order_number}
                            </h2>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <span className="text-xs font-medium text-stone-500">Parish:</span>
                        <span className="text-xs font-bold text-stone-800">{payment.order?.church?.name}</span>
                    </div>
                </div>
            }
        >
            <Head title={`Workbench: Payment #${payment.id}`} />

            <div className="py-6 bg-[#FDFBF7] min-h-[calc(100vh-140px)]">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Duplicate Reference Critical Banner */}
                    {duplicateReferenceCount > 0 && (
                        <div className="mb-6 p-4 rounded-xl bg-rose-50 border-2 border-rose-500 text-rose-950 flex items-start gap-3 shadow-sm">
                            <div className="p-1 rounded-full bg-rose-200 text-rose-700 shrink-0 mt-0.5">
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div className="flex-1">
                                <h4 className="font-bold text-sm">Potential Duplicate GCash Reference Detected!</h4>
                                <p className="text-xs mt-0.5">
                                    Reference <strong>{payment.submitted_reference}</strong> has appeared in <strong>{duplicateReferenceCount}</strong> other payment record(s). Verify the transaction timestamp and GCash screenshot to ensure this transaction is not being counted twice.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Split Screen Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        {/* Left Column: Details & Decision Panel (5 cols) */}
                        <div className="lg:col-span-5 space-y-6">
                            {/* Order & Mass Card */}
                            <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6 space-y-4">
                                <div className="flex items-center justify-between pb-3 border-b border-stone-100">
                                    <div>
                                        <span className="text-xs text-stone-400 uppercase font-semibold">Parish & Mass</span>
                                        <h3 className="font-serif font-bold text-base text-stone-900 mt-0.5">{payment.order?.church?.name}</h3>
                                        <p className="text-xs text-stone-600">{payment.order?.mass_schedule?.title}</p>
                                    </div>
                                    <div className="text-right">
                                        <span className="text-xs text-stone-400 uppercase font-semibold">Mass Date</span>
                                        <p className="text-xs font-semibold text-stone-800 mt-0.5">{payment.order?.mass_schedule?.starts_at}</p>
                                    </div>
                                </div>

                                {/* Financial Compare */}
                                <div className="grid grid-cols-2 gap-4 pt-1">
                                    <div className="p-3 bg-stone-50 rounded-xl border border-stone-200">
                                        <span className="text-[11px] font-semibold text-stone-500 uppercase">Expected Amount</span>
                                        <p className="text-lg font-mono font-bold text-stone-900 mt-1">₱{payment.expected_amount}</p>
                                    </div>
                                    <div className={`p-3 rounded-xl border ${
                                        payment.submitted_amount && Number(payment.submitted_amount) < Number(payment.expected_amount)
                                            ? 'bg-rose-50 border-rose-200 text-rose-900'
                                            : 'bg-amber-50/60 border-amber-200 text-amber-950'
                                    }`}>
                                        <span className="text-[11px] font-semibold uppercase">Submitted Amount</span>
                                        <p className="text-lg font-mono font-bold mt-1">₱{payment.submitted_amount || '0.00'}</p>
                                    </div>
                                </div>

                                <div className="pt-2">
                                    <span className="text-xs text-stone-500 uppercase font-semibold">Submitted Reference:</span>
                                    <div className="mt-1 font-mono text-sm font-bold bg-stone-100 px-3 py-1.5 rounded-lg text-stone-800 flex items-center justify-between">
                                        <span>{payment.submitted_reference || 'None provided'}</span>
                                        <span className="text-xs text-stone-400 font-sans font-normal">GCash ID</span>
                                    </div>
                                </div>

                                {/* Exceptions List */}
                                {payment.exceptions && payment.exceptions.length > 0 && (
                                    <div className="pt-2">
                                        <span className="text-xs text-stone-500 uppercase font-semibold">Active Exceptions:</span>
                                        <div className="mt-2 space-y-1.5">
                                            {payment.exceptions.map((exc) => (
                                                <div key={exc.id} className="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-xs">
                                                    <div className="flex items-center justify-between">
                                                        <span className="font-bold text-rose-800 capitalize">{exc.exception_type.replace(/_/g, ' ')}</span>
                                                        <span className="text-[10px] uppercase font-semibold text-rose-600">{exc.status}</span>
                                                    </div>
                                                    {exc.details && (
                                                        <p className="text-rose-700 mt-1">{JSON.stringify(exc.details)}</p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Intentions Included */}
                            <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6">
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-stone-500 mb-3">
                                    Intentions in this Order ({payment.order?.intentions?.length || 0})
                                </h4>
                                <div className="divide-y divide-stone-100 max-h-56 overflow-y-auto pr-1">
                                    {payment.order?.intentions?.map((item, idx) => (
                                        <div key={item.id} className="py-2.5 first:pt-0 last:pb-0 text-xs">
                                            <div className="flex items-center justify-between">
                                                <span className="font-serif font-bold text-stone-900">{item.person_or_family_name}</span>
                                                <span className="px-2 py-0.5 rounded bg-amber-50 text-amber-800 text-[10px] font-semibold border border-amber-200">
                                                    {item.category_label_snapshot || item.category?.name}
                                                </span>
                                            </div>
                                            {item.wording && (
                                                <p className="text-stone-500 mt-0.5 italic">{item.wording}</p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Decision Action Tabs & Form */}
                            <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6">
                                <div className="flex border-b border-stone-200 mb-4">
                                    <button
                                        type="button"
                                        onClick={() => setActiveTab('verify')}
                                        className={`flex-1 pb-2.5 text-xs font-semibold uppercase tracking-wider transition border-b-2 ${
                                            activeTab === 'verify'
                                                ? 'border-emerald-600 text-emerald-800'
                                                : 'border-transparent text-stone-400 hover:text-stone-600'
                                        }`}
                                    >
                                        Verify & Confirm
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setActiveTab('reject')}
                                        className={`flex-1 pb-2.5 text-xs font-semibold uppercase tracking-wider transition border-b-2 ${
                                            activeTab === 'reject'
                                                ? 'border-rose-600 text-rose-800'
                                                : 'border-transparent text-stone-400 hover:text-stone-600'
                                        }`}
                                    >
                                        Reject Payment
                                    </button>
                                </div>

                                {activeTab === 'verify' ? (
                                    <form onSubmit={handleVerify} className="space-y-4">
                                        <div>
                                            <label className="block text-xs font-semibold text-stone-700 uppercase">
                                                Verified Amount (PHP ₱)
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={verifyForm.data.verified_amount}
                                                onChange={(e) => verifyForm.setData('verified_amount', e.target.value)}
                                                className="mt-1 w-full px-3 py-2 text-sm font-mono border border-stone-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                                required
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-xs font-semibold text-stone-700 uppercase">
                                                Audit Notes (Optional)
                                            </label>
                                            <input
                                                type="text"
                                                placeholder="e.g. Reference verified on GCash merchant dashboard"
                                                value={verifyForm.data.notes}
                                                onChange={(e) => verifyForm.setData('notes', e.target.value)}
                                                className="mt-1 w-full px-3 py-2 text-xs border border-stone-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                            />
                                        </div>

                                        {payment.exceptions && payment.exceptions.length > 0 && (
                                            <div className="pt-1">
                                                <label className="flex items-start gap-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={verifyForm.data.force_exception_resolution}
                                                        onChange={(e) => verifyForm.setData('force_exception_resolution', e.target.checked)}
                                                        className="mt-0.5 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500"
                                                    />
                                                    <span className="text-xs text-stone-700">
                                                        <strong>Resolve all open exceptions</strong> (e.g. override underpayment/duplicate warning after staff investigation)
                                                    </span>
                                                </label>
                                            </div>
                                        )}

                                        <button
                                            type="submit"
                                            disabled={verifyForm.processing}
                                            className="w-full py-2.5 px-4 text-xs font-bold uppercase tracking-wider text-white bg-emerald-700 hover:bg-emerald-800 rounded-lg shadow transition disabled:opacity-50"
                                        >
                                            {verifyForm.processing ? 'Verifying...' : 'Confirm & Verify Payment'}
                                        </button>
                                    </form>
                                ) : (
                                    <form onSubmit={handleReject} className="space-y-4">
                                        <div>
                                            <label className="block text-xs font-semibold text-stone-700 uppercase">
                                                Rejection Reason (Required)
                                            </label>
                                            <textarea
                                                rows={3}
                                                placeholder="Explain why this payment is rejected (e.g., Fake screenshot, unmatched reference)"
                                                value={rejectForm.data.reason}
                                                onChange={(e) => rejectForm.setData('reason', e.target.value)}
                                                className="mt-1 w-full px-3 py-2 text-xs border border-stone-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none"
                                                required
                                            />
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={rejectForm.processing}
                                            className="w-full py-2.5 px-4 text-xs font-bold uppercase tracking-wider text-white bg-rose-700 hover:bg-rose-800 rounded-lg shadow transition disabled:opacity-50"
                                        >
                                            {rejectForm.processing ? 'Rejecting...' : 'Reject Payment & Notify Submitter'}
                                        </button>
                                    </form>
                                )}
                            </div>
                        </div>

                        {/* Right Column: Private Screenshot Viewer (7 cols) */}
                        <div className="lg:col-span-7">
                            <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6 flex flex-col h-full min-h-[560px]">
                                <div className="flex items-center justify-between pb-4 border-b border-stone-100">
                                    <div>
                                        <h3 className="font-serif font-bold text-stone-900">Payment Evidence Screenshot</h3>
                                        <p className="text-xs text-stone-500">Stored on private encrypted disk. Streamed securely via Central Admin gate.</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setZoomLevel((z) => Math.max(0.5, z - 0.25))}
                                            className="p-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                                            title="Zoom Out"
                                        >
                                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <span className="text-xs font-mono font-medium text-stone-600 px-1">{Math.round(zoomLevel * 100)}%</span>
                                        <button
                                            type="button"
                                            onClick={() => setZoomLevel((z) => Math.min(2.5, z + 0.25))}
                                            className="p-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50"
                                            title="Zoom In"
                                        >
                                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setZoomLevel(1)}
                                            className="px-2 py-1 text-xs font-medium text-stone-500 hover:text-stone-800"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                </div>

                                <div className="flex-1 mt-4 bg-stone-900 rounded-xl overflow-auto flex items-center justify-center p-4 relative min-h-[440px]">
                                    {latestEvidence ? (
                                        <img
                                            src={`/admin/payments/${payment.id}/evidence/${latestEvidence.id}`}
                                            alt="Payment Evidence"
                                            style={{ transform: `scale(${zoomLevel})`, transformOrigin: 'center center' }}
                                            className="max-h-[500px] object-contain transition-transform duration-150 shadow-2xl rounded"
                                        />
                                    ) : (
                                        <div className="text-center text-stone-400">
                                            <svg className="w-12 h-12 mx-auto mb-2 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p className="text-sm font-medium">No Payment Screenshot Available</p>
                                            <p className="text-xs text-stone-500 mt-1">Submitter has not uploaded a receipt image yet.</p>
                                        </div>
                                    )}
                                </div>

                                {latestEvidence && (
                                    <div className="mt-3 flex items-center justify-between text-[11px] text-stone-400">
                                        <span>File: {latestEvidence.original_filename} ({latestEvidence.mime_type})</span>
                                        <span>Uploaded: {latestEvidence.created_at}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

