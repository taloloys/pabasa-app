import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { DoveIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@/Components/Icons';

export default function OrderTracking({ orders = [] }) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const filteredOrders = useMemo(() => {
        return orders.filter((order) => {
            const matchesStatus = statusFilter === 'all' || order.status === statusFilter;
            const q = search.toLowerCase().trim();
            if (!q) return matchesStatus;

            const matchesOrderNumber = order.order_number?.toLowerCase().includes(q);
            const matchesChurch = order.church?.name?.toLowerCase().includes(q);
            const matchesMass = order.mass_schedule?.title?.toLowerCase().includes(q);
            const matchesIntentions = order.intentions?.some(
                (i) =>
                    i.person_or_family_name?.toLowerCase().includes(q) ||
                    i.wording?.toLowerCase().includes(q)
            );
            const matchesReference = order.latest_payment?.submitted_reference?.toLowerCase().includes(q);

            return matchesStatus && (matchesOrderNumber || matchesChurch || matchesMass || matchesIntentions || matchesReference);
        });
    }, [orders, search, statusFilter]);

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
                                My Submitted Mass Intentions
                            </h2>
                            <span className="inline-flex items-center space-x-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200 ring-1 ring-emerald-500/10">
                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                <span>Faithful Intention Portal</span>
                            </span>
                        </div>
                        <p className="mt-1 text-xs text-stone-600 font-sans">
                            Track the diocesan review, GCash payment verification, and ambo proclamation status of your prayer offerings.
                        </p>
                    </div>

                    <div className="flex items-center space-x-3">
                        <Link
                            href={route('churches.index')}
                            className="inline-flex items-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 border border-gold-400/50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gold-100 shadow-md hover:from-liturgical-700 hover:to-liturgical-900 transition"
                        >
                            + Submit Prayer Intention &rarr;
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Track Intentions - Pabasa" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Search & Filter Bar */}
                <div className="rounded-2xl border border-gold-300/70 bg-white p-5 sm:p-6 shadow-xs ring-1 ring-gold-400/20">
                    <div className="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                        <div className="flex-1 relative">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <TextInput
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by Order #, Parish, Intention Name, or GCash Reference..."
                                className="pl-9 block w-full text-xs"
                            />
                        </div>

                        <div className="flex items-center space-x-2">
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="text-xs border-stone-200 rounded-lg focus:border-gold-500 focus:ring-gold-500 py-2 px-3 bg-white"
                            >
                                <option value="all">All Statuses ({orders.length})</option>
                                <option value="awaiting_payment">Awaiting Payment</option>
                                <option value="payment_under_review">Payment Under Review</option>
                                <option value="confirmed">Confirmed / Enrolled</option>
                                <option value="action_required">Action Required</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>

                            {(search || statusFilter !== 'all') && (
                                <SecondaryButton
                                    onClick={() => {
                                        setSearch('');
                                        setStatusFilter('all');
                                    }}
                                    className="text-xs shrink-0"
                                >
                                    Reset
                                </SecondaryButton>
                            )}
                        </div>
                    </div>
                </div>

                {/* Orders List */}
                {filteredOrders.length === 0 ? (
                    <EmptyState
                        icon={<DoveIcon className="w-8 h-8 text-gold-700" />}
                        title={orders.length === 0 ? "No Mass Intentions Submitted Yet" : "No Orders Match Your Search"}
                        description={
                            orders.length === 0
                                ? "You haven't submitted any prayer intentions yet. Choose a Catholic parish, select an upcoming Mass, and submit your Thanksgiving, Memorial, or Healing intentions."
                                : "No submitted orders matched your active filter. Try resetting your search terms."
                        }
                        action={
                            orders.length === 0 ? (
                                <Link
                                    href={route('churches.index')}
                                    className="inline-flex items-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 px-6 py-3 text-xs font-bold uppercase tracking-wider text-gold-100 border border-gold-400/50 shadow-md hover:from-liturgical-700 hover:to-liturgical-900 transition"
                                >
                                    Browse Parishes & Submit First Intention &rarr;
                                </Link>
                            ) : null
                        }
                        className="border-gold-300/60 bg-white/90 ring-1 ring-gold-400/20 py-16"
                    />
                ) : (
                    <div className="space-y-6">
                        {filteredOrders.map((order) => {
                            const payment = order.latest_payment;
                            const isAwaitingPayment = order.status === 'awaiting_payment';
                            const isUnderReview = order.status === 'payment_under_review';
                            const isConfirmed = order.status === 'confirmed' || order.status === 'completed';
                            const isActionRequired = order.status === 'action_required';

                            return (
                                <div
                                    key={order.id}
                                    className="rounded-2xl border border-gold-300/80 bg-white shadow-xs overflow-hidden ring-1 ring-gold-400/20 transition hover:border-gold-400"
                                >
                                    {/* Card Header */}
                                    <div className="bg-gradient-to-r from-ivory-50 via-white to-ivory-50 border-b border-gold-200/80 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div className="flex items-center space-x-3">
                                            <span className="font-mono font-bold text-sm text-liturgical-950 bg-gold-100/70 border border-gold-300/70 px-2.5 py-1 rounded-md">
                                                {order.order_number}
                                            </span>
                                            <StatusBadge status={order.status} />
                                        </div>

                                        <div className="text-xs text-stone-500 font-sans">
                                            Submitted: <span className="font-medium text-stone-700">{new Date(order.submitted_at || order.created_at).toLocaleString()}</span>
                                        </div>
                                    </div>

                                    {/* Card Body */}
                                    <div className="p-6 space-y-6">
                                        {/* Mass & Church Info */}
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pb-4 border-b border-stone-100 text-xs">
                                            <div>
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400 block mb-0.5">
                                                    Parish Sanctuary
                                                </span>
                                                <span className="font-serif font-bold text-sm text-liturgical-950 block">
                                                    {order.church?.name || 'Parish'}
                                                </span>
                                            </div>

                                            <div>
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400 block mb-0.5">
                                                    Liturgical Mass
                                                </span>
                                                <span className="font-semibold text-stone-900 block">
                                                    {order.mass_schedule?.title || 'Scheduled Mass'}
                                                </span>
                                                <span className="text-stone-500 block text-[11px] mt-0.5">
                                                    {order.mass_schedule?.starts_at ? new Date(order.mass_schedule.starts_at).toLocaleString() : ''}
                                                    {order.mass_schedule?.language ? ` • ${order.mass_schedule.language}` : ''}
                                                </span>
                                            </div>

                                            <div>
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400 block mb-0.5">
                                                    Offering Total
                                                </span>
                                                <span className="font-bold text-base text-liturgical-950 font-serif block">
                                                    PHP {Number(order.expected_amount).toFixed(2)}
                                                </span>
                                                <span className="text-stone-500 text-[11px] block">
                                                    {order.intentions?.length || 0} intention(s)
                                                </span>
                                            </div>
                                        </div>

                                        {/* Intentions List */}
                                        <div>
                                            <h4 className="text-xs font-bold uppercase tracking-wider text-gold-900 font-serif mb-3">
                                                Prayer Intentions Proclaimed at the Ambo
                                            </h4>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                {order.intentions?.map((intention, idx) => (
                                                    <div
                                                        key={intention.id || idx}
                                                        className="rounded-xl border border-gold-200/80 bg-ivory-50/50 p-4 space-y-1.5 text-xs"
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-bold text-liturgical-950 font-serif text-sm">
                                                                {intention.person_or_family_name}
                                                            </span>
                                                            <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gold-100 text-gold-900 border border-gold-300/80">
                                                                {intention.category_label_snapshot || intention.category?.label || 'Prayer'}
                                                            </span>
                                                        </div>
                                                        {intention.wording && (
                                                            <p className="text-stone-600 italic font-serif leading-relaxed">
                                                                &ldquo;{intention.wording}&rdquo;
                                                            </p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Status Message / Action Row */}
                                        <div className="rounded-xl p-4 bg-stone-50 border border-stone-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
                                            <div>
                                                {isAwaitingPayment && (
                                                    <div className="text-amber-800 font-medium flex items-center space-x-2">
                                                        <ExclamationTriangleIcon className="w-4 h-4 text-amber-600 shrink-0" />
                                                        <span>Order is awaiting GCash offering submission. Please complete payment to reserve these intentions.</span>
                                                    </div>
                                                )}
                                                {isUnderReview && (
                                                    <div className="text-liturgical-900">
                                                        <span className="font-bold">Payment Under Verification:</span> GCash Reference: <code className="font-mono bg-white px-2 py-0.5 rounded border border-gold-200">{payment?.submitted_reference || 'Submitted'}</code>. Diocesan staff will verify your receipt shortly.
                                                    </div>
                                                )}
                                                {isConfirmed && (
                                                    <div className="text-emerald-900 font-medium flex items-center space-x-2">
                                                        <CheckCircleIcon className="w-4 h-4 text-emerald-700 shrink-0" />
                                                        <span>
                                                            <strong>Verified & Confirmed:</strong> Your intentions have been enrolled into the official Mass Reading List for proclamation.
                                                        </span>
                                                    </div>
                                                )}
                                                {isActionRequired && (
                                                    <div className="text-rose-800 font-medium flex items-center space-x-2">
                                                        <ExclamationTriangleIcon className="w-4 h-4 text-rose-600 shrink-0" />
                                                        <span>An exception was noted on your payment (e.g. short payment or unclear reference). Please contact parish administration.</span>
                                                    </div>
                                                )}
                                                {order.status === 'cancelled' && (
                                                    <div className="text-stone-500 font-medium flex items-center space-x-2">
                                                        <svg className="w-4 h-4 text-stone-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        <span>This order was cancelled. Intentions are discarded and will not be proclaimed at Mass.</span>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="shrink-0 flex items-center space-x-2">
                                                {isAwaitingPayment && (
                                                    <Link
                                                        href={route('orders.payment', { orderNumber: order.order_number })}
                                                        className="inline-flex items-center px-4 py-2 rounded-lg bg-liturgical-900 text-gold-200 hover:bg-liturgical-950 font-bold text-xs uppercase tracking-wider shadow-xs transition"
                                                    >
                                                        Complete Payment &rarr;
                                                    </Link>
                                                )}

                                                <Link
                                                    href={route('churches.show', { slug: order.church?.slug || 'cebu-cathedral' })}
                                                    className="inline-flex items-center px-3 py-1.5 rounded-lg border border-stone-300 text-stone-700 hover:bg-white text-xs font-semibold transition"
                                                >
                                                    View Parish
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
