import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Tracking({
    token,
    isEmailVerified,
    maskedEmail,
    order,
    church,
    mass,
    intentions = [],
    payment = null,
}) {
    const [activeModal, setActiveModal] = useState(null); // 'correction' | 'cancellation' | 'reschedule' | null
    const [selectedIntentionId, setSelectedIntentionId] = useState(intentions[0]?.id || null);
    const [copied, setCopied] = useState(false);

    // Email challenge form
    const challengeForm = useForm({
        verification_code: '',
    });

    // Correction request form
    const correctionForm = useForm({
        intention_id: selectedIntentionId || '',
        person_or_family_name: '',
        wording: '',
        reason: '',
    });

    // Cancellation request form
    const cancellationForm = useForm({
        reason: '',
    });

    // Rescheduling request form
    const reschedulingForm = useForm({
        requested_mass_schedule_id: '',
        reason: '',
    });

    const handleCopyOrderNumber = () => {
        navigator.clipboard.writeText(order.order_number);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleVerifyEmail = (e) => {
        e.preventDefault();
        challengeForm.post(`/guest/track/${token}/verify`, {
            preserveScroll: true,
        });
    };

    const handleOpenCorrection = (intention) => {
        setSelectedIntentionId(intention.id);
        correctionForm.setData({
            intention_id: intention.id,
            person_or_family_name: intention.name,
            wording: intention.wording,
            reason: '',
        });
        setActiveModal('correction');
    };

    const handleSubmitCorrection = (e) => {
        e.preventDefault();
        correctionForm.post(`/user/orders/${order.id}/requests/correction`, {
            onSuccess: () => setActiveModal(null),
        });
    };

    const handleSubmitCancellation = (e) => {
        e.preventDefault();
        cancellationForm.post(`/user/orders/${order.id}/requests/cancellation`, {
            onSuccess: () => setActiveModal(null),
        });
    };

    const handleSubmitRescheduling = (e) => {
        e.preventDefault();
        reschedulingForm.post(`/user/orders/${order.id}/requests/rescheduling`, {
            onSuccess: () => setActiveModal(null),
        });
    };

    // Timeline progress definition
    const steps = [
        { key: 'awaiting_payment', label: '1. Order Submitted', desc: 'Prayer intentions recorded' },
        { key: 'payment_under_review', label: '2. Payment Under Review', desc: 'GCash verification pending' },
        { key: 'confirmed', label: '3. Intention Confirmed', desc: 'Added to sacred reading queue' },
        { key: 'completed', label: '4. Mass Proclaimed', desc: 'Offered during the celebration' },
    ];

    const getStepStatus = (stepKey) => {
        if (order.status === 'cancelled') return 'cancelled';
        if (order.status === 'action_required') return 'action_required';

        const orderHierarchy = ['awaiting_payment', 'payment_under_review', 'confirmed', 'completed'];
        const currentIndex = orderHierarchy.indexOf(order.status);
        const stepIndex = orderHierarchy.indexOf(stepKey);

        if (currentIndex > stepIndex) return 'completed';
        if (currentIndex === stepIndex) return 'current';
        return 'upcoming';
    };

    return (
        <div className="min-h-screen bg-[#FDFBF7] text-stone-800 font-sans antialiased selection:bg-amber-100 selection:text-amber-900">
            <Head title={`Track Intention - ${order.order_number}`} />

            {/* Liturgical Top Navigation Header */}
            <header className="border-b border-amber-900/10 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-2 group">
                        <div className="w-8 h-8 rounded-full bg-amber-900/10 flex items-center justify-center text-amber-900 group-hover:bg-amber-900/20 transition-colors">
                            <svg className="w-4 h-4 text-amber-800" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11 2v9H4v2h7v9h2v-9h7v-2h-7V2h-2z" />
                            </svg>
                        </div>
                        <span className="font-serif font-bold text-lg tracking-wide text-amber-950">PABASA</span>
                    </Link>

                    <div className="flex items-center gap-3">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-900 border border-amber-200">
                            <span className="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse" />
                            Guest Order Access
                        </span>
                    </div>
                </div>
            </header>

            <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
                {/* Email Verification Alert Banner */}
                {!isEmailVerified && (
                    <div className="mb-8 bg-amber-50 border-l-4 border-amber-600 p-5 rounded-r-xl shadow-sm">
                        <div className="flex items-start">
                            <div className="shrink-0 text-amber-600 mt-0.5">
                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div className="ml-4 flex-1">
                                <h3 className="text-sm font-semibold text-amber-900">Email Verification Pending</h3>
                                <p className="mt-1 text-sm text-amber-800">
                                    To protect sensitive prayer requests and prevent unauthorized alterations, we sent a 6-digit confirmation code to <strong className="font-semibold">{maskedEmail}</strong>.
                                </p>
                                <form onSubmit={handleVerifyEmail} className="mt-4 flex flex-wrap items-center gap-3">
                                    <input
                                        type="text"
                                        maxLength={6}
                                        placeholder="6-digit code"
                                        value={challengeForm.data.verification_code}
                                        onChange={(e) => challengeForm.setData('verification_code', e.target.value)}
                                        className="w-36 px-3 py-2 text-center tracking-widest font-mono text-base bg-white border border-amber-300 rounded-lg shadow-inner focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        required
                                    />
                                    <button
                                        type="submit"
                                        disabled={challengeForm.processing}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-amber-800 hover:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-600 shadow-sm transition disabled:opacity-50"
                                    >
                                        {challengeForm.processing ? 'Verifying...' : 'Unlock Full Access'}
                                    </button>
                                </form>
                                {challengeForm.errors.verification_code && (
                                    <p className="mt-2 text-xs text-red-600 font-medium">{challengeForm.errors.verification_code}</p>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Hero / Header Card */}
                <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6 sm:p-8 mb-8">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-stone-100">
                        <div>
                            <span className="text-xs uppercase tracking-wider font-semibold text-amber-800">Parish Intention Order</span>
                            <div className="flex items-center gap-3 mt-1">
                                <h1 className="text-2xl sm:text-3xl font-serif font-bold text-stone-900 tracking-tight">
                                    {order.order_number}
                                </h1>
                                <button
                                    onClick={handleCopyOrderNumber}
                                    type="button"
                                    className="p-1.5 text-stone-400 hover:text-stone-600 rounded-lg hover:bg-stone-50 border border-transparent hover:border-stone-200 transition"
                                    title="Copy Order Number"
                                >
                                    {copied ? (
                                        <svg className="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    ) : (
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    )}
                                </button>
                            </div>
                            <p className="text-sm text-stone-500 mt-1">
                                Submitted by <span className="font-medium text-stone-700">{order.submitter_name}</span>
                            </p>
                        </div>

                        <div className="flex flex-col items-start md:items-end">
                            <span className="text-xs uppercase tracking-wider text-stone-400 font-medium">Order Status</span>
                            <div className="mt-1">
                                {order.status === 'awaiting_payment' && (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                        Awaiting Payment Upload
                                    </span>
                                )}
                                {order.status === 'payment_under_review' && (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                        Payment Under Verification
                                    </span>
                                )}
                                {order.status === 'confirmed' && (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        Confirmed & In Reading List
                                    </span>
                                )}
                                {order.status === 'completed' && (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                        Proclaimed at Mass
                                    </span>
                                )}
                                {order.status === 'action_required' && (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                        Action Required
                                    </span>
                                )}
                                {order.status === 'cancelled' && (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-stone-200 text-stone-700">
                                        Cancelled
                                    </span>
                                )}
                            </div>
                            <span className="text-xs text-stone-400 mt-1">Total: ₱{Number(order.expected_amount).toFixed(2)}</span>
                        </div>
                    </div>

                    {/* Church & Mass Info Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div className="flex items-start gap-4">
                            <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center shrink-0 border border-amber-100">
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-xs font-semibold text-stone-400 uppercase tracking-wider">Parish Church</h3>
                                <p className="text-base font-semibold text-stone-900 mt-0.5">{church.name}</p>
                                {church.contact_phone && (
                                    <p className="text-xs text-stone-500 mt-0.5">Parish Office: {church.contact_phone}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-start gap-4">
                            <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-800 flex items-center justify-center shrink-0 border border-purple-100">
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-xs font-semibold text-stone-400 uppercase tracking-wider">Scheduled Mass</h3>
                                <p className="text-base font-semibold text-stone-900 mt-0.5">{mass.title}</p>
                                <p className="text-xs text-purple-900 font-medium mt-0.5">Time: {mass.starts_at_formatted}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Status Timeline */}
                <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6 sm:p-8 mb-8">
                    <h2 className="text-base font-serif font-bold text-stone-900 mb-6">Fulfillment Progress</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {steps.map((step, idx) => {
                            const status = getStepStatus(step.key);
                            return (
                                <div
                                    key={step.key}
                                    className={`p-4 rounded-xl border relative transition ${
                                        status === 'completed'
                                            ? 'bg-emerald-50/60 border-emerald-200 text-emerald-950'
                                            : status === 'current'
                                            ? 'bg-amber-50/80 border-amber-300 ring-2 ring-amber-400/30 text-amber-950'
                                            : 'bg-stone-50 border-stone-200 text-stone-500 opacity-60'
                                    }`}
                                >
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-xs font-semibold uppercase tracking-wider">Step {idx + 1}</span>
                                        {status === 'completed' && (
                                            <svg className="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        )}
                                        {status === 'current' && (
                                            <span className="w-2.5 h-2.5 rounded-full bg-amber-600 animate-pulse" />
                                        )}
                                    </div>
                                    <h4 className="text-sm font-bold">{step.label}</h4>
                                    <p className="text-xs mt-1 text-stone-600">{step.desc}</p>
                                </div>
                            );
                        })}
                    </div>

                    {order.status === 'awaiting_payment' && (
                        <div className="mt-6 p-4 rounded-xl bg-amber-50/80 border border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div>
                                <h4 className="text-sm font-semibold text-amber-950">Payment screenshot not yet uploaded</h4>
                                <p className="text-xs text-amber-800 mt-0.5">Please send your GCash offering and upload your receipt screenshot before cutoff.</p>
                            </div>
                            <Link
                                href={`/orders/${order.order_number}/payment`}
                                className="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-amber-800 hover:bg-amber-900 shadow-sm transition shrink-0"
                            >
                                Upload GCash Payment
                            </Link>
                        </div>
                    )}
                </div>

                {/* Payment Summary */}
                <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6 sm:p-8 mb-8">
                    <h2 className="text-base font-serif font-bold text-stone-900 mb-4">Payment Summary</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                        <div className="p-4 rounded-xl bg-stone-50 border border-stone-200">
                            <span className="text-xs text-stone-500 uppercase tracking-wider font-semibold">Total Offering</span>
                            <p className="text-xl font-bold font-mono text-stone-900 mt-1">₱{Number(order.expected_amount).toFixed(2)}</p>
                            <span className="text-xs text-stone-400">Prescribed by parish schedule</span>
                        </div>

                        <div className="p-4 rounded-xl bg-stone-50 border border-stone-200">
                            <span className="text-xs text-stone-500 uppercase tracking-wider font-semibold">Submitted Reference</span>
                            <p className="text-base font-semibold font-mono text-stone-800 mt-1">
                                {payment?.submitted_reference || 'Pending submission'}
                            </p>
                            <span className="text-xs text-stone-400">GCash reference number</span>
                        </div>

                        <div className="p-4 rounded-xl bg-stone-50 border border-stone-200">
                            <span className="text-xs text-stone-500 uppercase tracking-wider font-semibold">Payment Status</span>
                            <p className="text-base font-semibold text-stone-800 mt-1 capitalize">
                                {payment?.status ? payment.status.replace(/_/g, ' ') : 'Not paid'}
                            </p>
                            <span className="text-xs text-stone-400">Central Admin verification</span>
                        </div>
                    </div>
                </div>

                {/* Intentions List */}
                <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 p-6 sm:p-8 mb-8">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 className="text-lg font-serif font-bold text-stone-900">Prayer Intentions</h2>
                            <p className="text-xs text-stone-500 mt-0.5">Preserved verbatim as submitted for sacred proclamation.</p>
                        </div>

                        {/* Change Action Buttons (if email verified and order allows) */}
                        {isEmailVerified && order.status !== 'cancelled' && order.status !== 'completed' && (
                            <div className="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => setActiveModal('cancellation')}
                                    className="px-3 py-1.5 text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200 transition"
                                >
                                    Request Cancellation
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveModal('reschedule')}
                                    className="px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg border border-purple-200 transition"
                                >
                                    Request Reschedule
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="divide-y divide-stone-100">
                        {intentions.map((item, idx) => (
                            <div key={item.id} className="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-900">
                                            {item.category}
                                        </span>
                                        <span className="text-xs text-stone-400">Intention #{idx + 1}</span>
                                    </div>
                                    <h3 className="text-base font-serif font-bold text-stone-900 mt-1.5">
                                        {item.name}
                                    </h3>
                                    {item.wording && (
                                        <p className="text-sm text-stone-600 mt-1 italic whitespace-pre-wrap">
                                            "{item.wording}"
                                        </p>
                                    )}
                                </div>

                                {isEmailVerified && (
                                    <div className="shrink-0 flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => handleOpenCorrection(item)}
                                            className="px-3 py-1.5 text-xs font-medium text-stone-700 bg-stone-100 hover:bg-stone-200 rounded-lg transition"
                                        >
                                            Correct Typo / Name
                                        </button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </main>

            {/* Modal: Request Typo / Wording Correction */}
            {activeModal === 'correction' && (
                <div className="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-stone-200 animate-in fade-in zoom-in-95">
                        <h3 className="text-lg font-serif font-bold text-stone-900">Request Correction</h3>
                        <p className="text-xs text-stone-500 mt-1">
                            Request changes to submitted intention wording or spelling. Corrections are reviewed by parish staff.
                        </p>
                        <form onSubmit={handleSubmitCorrection} className="mt-4 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-stone-700 uppercase">Intention Name</label>
                                <input
                                    type="text"
                                    value={correctionForm.data.person_or_family_name}
                                    onChange={(e) => correctionForm.setData('person_or_family_name', e.target.value)}
                                    className="mt-1 w-full px-3 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-stone-700 uppercase">Prayer Wording</label>
                                <textarea
                                    rows={3}
                                    value={correctionForm.data.wording}
                                    onChange={(e) => correctionForm.setData('wording', e.target.value)}
                                    className="mt-1 w-full px-3 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-stone-700 uppercase">Reason for Correction</label>
                                <input
                                    type="text"
                                    placeholder="e.g. Typo in deceased grandparent's name"
                                    value={correctionForm.data.reason}
                                    onChange={(e) => correctionForm.setData('reason', e.target.value)}
                                    className="mt-1 w-full px-3 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                    required
                                />
                            </div>
                            <div className="flex items-center justify-end gap-3 pt-3">
                                <button
                                    type="button"
                                    onClick={() => setActiveModal(null)}
                                    className="px-4 py-2 text-xs font-medium text-stone-600 hover:text-stone-900"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={correctionForm.processing}
                                    className="px-4 py-2 text-xs font-semibold text-white bg-amber-800 hover:bg-amber-900 rounded-lg shadow-sm disabled:opacity-50"
                                >
                                    {correctionForm.processing ? 'Submitting...' : 'Submit Correction'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Request Cancellation */}
            {activeModal === 'cancellation' && (
                <div className="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 animate-in fade-in zoom-in-95">
                        <h3 className="text-lg font-serif font-bold text-stone-900">Request Cancellation</h3>
                        <p className="text-xs text-stone-500 mt-1">
                            Per parish policy, cancellations are reviewed by parish staff and do not automatically issue a refund.
                        </p>
                        <form onSubmit={handleSubmitCancellation} className="mt-4 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-stone-700 uppercase">Reason for Cancellation</label>
                                <textarea
                                    rows={3}
                                    placeholder="Please state why you wish to cancel this intention"
                                    value={cancellationForm.data.reason}
                                    onChange={(e) => cancellationForm.setData('reason', e.target.value)}
                                    className="mt-1 w-full px-3 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:outline-none"
                                    required
                                />
                            </div>
                            <div className="flex items-center justify-end gap-3 pt-3">
                                <button
                                    type="button"
                                    onClick={() => setActiveModal(null)}
                                    className="px-4 py-2 text-xs font-medium text-stone-600 hover:text-stone-900"
                                >
                                    Back
                                </button>
                                <button
                                    type="submit"
                                    disabled={cancellationForm.processing}
                                    className="px-4 py-2 text-xs font-semibold text-white bg-rose-700 hover:bg-rose-800 rounded-lg shadow-sm disabled:opacity-50"
                                >
                                    {cancellationForm.processing ? 'Submitting...' : 'Confirm Cancellation Request'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Request Reschedule */}
            {activeModal === 'reschedule' && (
                <div className="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 animate-in fade-in zoom-in-95">
                        <h3 className="text-lg font-serif font-bold text-stone-900">Request Reschedule</h3>
                        <p className="text-xs text-stone-500 mt-1">
                            Submit your request to move these intentions to another Mass date. Requires staff confirmation.
                        </p>
                        <form onSubmit={handleSubmitRescheduling} className="mt-4 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-stone-700 uppercase">Reason for Rescheduling</label>
                                <textarea
                                    rows={3}
                                    placeholder="State the reason and preferred new schedule date"
                                    value={reschedulingForm.data.reason}
                                    onChange={(e) => reschedulingForm.setData('reason', e.target.value)}
                                    className="mt-1 w-full px-3 py-2 text-sm border border-stone-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                    required
                                />
                            </div>
                            <div className="flex items-center justify-end gap-3 pt-3">
                                <button
                                    type="button"
                                    onClick={() => setActiveModal(null)}
                                    className="px-4 py-2 text-xs font-medium text-stone-600 hover:text-stone-900"
                                >
                                    Back
                                </button>
                                <button
                                    type="submit"
                                    disabled={reschedulingForm.processing}
                                    className="px-4 py-2 text-xs font-semibold text-white bg-purple-700 hover:bg-purple-800 rounded-lg shadow-sm disabled:opacity-50"
                                >
                                    {reschedulingForm.processing ? 'Submitting...' : 'Confirm Reschedule Request'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

