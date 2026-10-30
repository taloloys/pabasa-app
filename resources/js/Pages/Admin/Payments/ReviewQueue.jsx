import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function ReviewQueue({ payments, currentFilter }) {
    const handleFilterChange = (status) => {
        router.get('/admin/payments', { status }, { preserveState: true });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'pending_verification':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Pending Verification</span>;
            case 'needs_review':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-900 border border-amber-300">Needs Review</span>;
            case 'resolution_required':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Resolution Required</span>;
            case 'verified':
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Verified</span>;
            default:
                return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-700 capitalize">{status?.replace(/_/g, ' ')}</span>;
        }
    };

    return (
        <AdminLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <span className="text-xs font-semibold uppercase tracking-wider text-amber-800">Central Administration</span>
                        <h2 className="text-xl font-serif font-bold text-stone-900">Payment Verification Queue</h2>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs text-stone-500">
                            Total in queue: <strong className="text-stone-800">{payments.total || 0}</strong>
                        </span>
                    </div>
                </div>
            }
        >
            <Head title="Payment Review Queue - Central Admin" />

            <div className="py-8 bg-[#FDFBF7] min-h-[calc(100vh-140px)]">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Status Filter Tabs */}
                    <div className="flex border-b border-stone-200 gap-6">
                        <button
                            type="button"
                            onClick={() => handleFilterChange('all')}
                            className={`pb-3 text-sm font-medium border-b-2 transition ${currentFilter === 'all'
                                ? 'border-amber-800 text-amber-950 font-semibold'
                                : 'border-transparent text-stone-500 hover:text-stone-700'
                                }`}
                        >
                            All Active Queue
                        </button>
                        <button
                            type="button"
                            onClick={() => handleFilterChange('pending_verification')}
                            className={`pb-3 text-sm font-medium border-b-2 transition ${currentFilter === 'pending_verification'
                                ? 'border-amber-800 text-amber-950 font-semibold'
                                : 'border-transparent text-stone-500 hover:text-stone-700'
                                }`}
                        >
                            Pending Verification
                        </button>
                        <button
                            type="button"
                            onClick={() => handleFilterChange('needs_review')}
                            className={`pb-3 text-sm font-medium border-b-2 transition ${currentFilter === 'needs_review'
                                ? 'border-amber-800 text-amber-950 font-semibold'
                                : 'border-transparent text-stone-500 hover:text-stone-700'
                                }`}
                        >
                            Flagged Exceptions / Under Review
                        </button>
                    </div>

                    {/* Table Card */}
                    <div className="bg-white rounded-2xl shadow-sm border border-stone-200/70 overflow-hidden">
                        {payments.data.length === 0 ? (
                            <div className="p-12 text-center">
                                <div className="w-12 h-12 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 className="text-base font-serif font-bold text-stone-900">Queue is Clear</h3>
                                <p className="text-sm text-stone-500 mt-1">There are no pending GCash payments waiting for verification in this view.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-stone-200">
                                    <thead className="bg-stone-50 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">
                                        <tr>
                                            <th className="px-6 py-3.5">Order & Parish</th>
                                            <th className="px-6 py-3.5">Mass Schedule</th>
                                            <th className="px-6 py-3.5">Amounts</th>
                                            <th className="px-6 py-3.5">Reference</th>
                                            <th className="px-6 py-3.5">Status & Exceptions</th>
                                            <th className="px-6 py-3.5 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-stone-100 text-sm">
                                        {payments.data.map((payment) => (
                                            <tr key={payment.id} className="hover:bg-amber-50/30 transition">
                                                <td className="px-6 py-4">
                                                    <span className="font-mono font-bold text-stone-900 block">
                                                        {payment.order?.order_number}
                                                    </span>
                                                    <span className="text-xs text-stone-500">
                                                        {payment.order?.church?.name}
                                                    </span>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <span className="font-medium text-stone-900 block">
                                                        {payment.order?.mass_schedule?.title}
                                                    </span>
                                                    <span className="text-xs text-stone-500">
                                                        {payment.order?.mass_schedule?.starts_at}
                                                    </span>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <div className="font-mono text-xs">
                                                        <span className="text-stone-500">Exp: </span>
                                                        <strong className="text-stone-900">₱{payment.expected_amount}</strong>
                                                    </div>
                                                    <div className="font-mono text-xs">
                                                        <span className="text-stone-500">Sub: </span>
                                                        <strong className="text-amber-800">
                                                            {payment.submitted_amount ? `₱${payment.submitted_amount}` : '—'}
                                                        </strong>
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <span className="font-mono text-xs bg-stone-100 text-stone-800 px-2 py-1 rounded">
                                                        {payment.submitted_reference || 'Pending'}
                                                    </span>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col gap-1 items-start">
                                                        {getStatusBadge(payment.status)}
                                                        {payment.exceptions?.map((exc) => {
                                                            const excType = exc.type || exc.exception_type || 'exception';
                                                            return (
                                                                <span
                                                                    key={exc.id}
                                                                    className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 capitalize"
                                                                >
                                                                    {typeof excType === 'string' ? excType.replace(/_/g, ' ') : 'exception'}
                                                                </span>
                                                            );
                                                        })}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4 text-right">
                                                    <Link
                                                        href={`/admin/payments/${payment.id}`}
                                                        className="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded-lg transition"
                                                    >
                                                        Review Workbench →
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Pagination */}
                        {payments.links && payments.links.length > 3 && (
                            <div className="px-6 py-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
                                <div>
                                    Showing {payments.from} to {payments.to} of {payments.total} payments
                                </div>
                                <div className="flex items-center gap-1">
                                    {payments.links.map((link, idx) => (
                                        <Link
                                            key={idx}
                                            href={link.url || '#'}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1.5 rounded-md border text-xs ${link.active
                                                ? 'bg-amber-800 text-white border-amber-800'
                                                : link.url
                                                    ? 'border-stone-200 hover:bg-stone-50 text-stone-700'
                                                    : 'border-stone-100 text-stone-300 pointer-events-none'
                                                }`}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

