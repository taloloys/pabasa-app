import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DangerButton from '@/Components/DangerButton';
import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ church, masses, categories }) {
    const [showingSingleModal, setShowingSingleModal] = useState(false);
    const [showingSeriesModal, setShowingSeriesModal] = useState(false);
    const [cancellingMass, setCancellingMass] = useState(null);
    const [cancellationReason, setCancellationReason] = useState('');
    const [extendingCutoffMass, setExtendingCutoffMass] = useState(null);
    const [newCutoffTime, setNewCutoffTime] = useState('');

    // Single Mass form
    const singleForm = useForm({
        title: 'Holy Mass',
        starts_at: '',
        ends_at: '',
        category_id: '',
        language: 'English',
        location_label: 'Main Sanctuary',
        resource_key: 'sanctuary-main',
        booking_opens_at: '',
        booking_cutoff_at: '',
    });

    // Recurring Series form
    const seriesForm = useForm({
        title: 'Sunday Morning Mass',
        description: 'Weekly Sunday Holy Mass',
        recurrence_rule: {
            days_of_week: [0], // Sunday
            time: '08:00',
            duration_minutes: 60,
        },
        starts_on: '',
        ends_on: '',
        category_id: '',
        language: 'English',
        location_label: 'Main Sanctuary',
        resource_key: 'sanctuary-main',
        booking_opens_days_before: 7,
        booking_cutoff_hours_before: 2,
    });

    const handleCreateSingle = (e) => {
        e.preventDefault();
        singleForm.post(route('church.masses.store', { church: church.id }), {
            onSuccess: () => {
                setShowingSingleModal(false);
                singleForm.reset();
            },
        });
    };

    const handleCreateSeries = (e) => {
        e.preventDefault();
        seriesForm.post(route('church.masses.series', { church: church.id }), {
            onSuccess: () => {
                setShowingSeriesModal(false);
                seriesForm.reset();
            },
        });
    };

    const handleExecuteCancel = () => {
        if (!cancellingMass) return;
        router.post(
            route('church.masses.cancel', { church: church.id, mass: cancellingMass.id }),
            { reason: cancellationReason },
            {
                onSuccess: () => {
                    setCancellingMass(null);
                    setCancellationReason('');
                },
            }
        );
    };

    const handleCloseEarly = (massId) => {
        if (!confirm('Are you sure you want to close submissions early for this Mass?')) return;
        router.post(route('church.masses.close-early', { church: church.id, mass: massId }));
    };

    const handleExecuteExtendCutoff = () => {
        if (!extendingCutoffMass) return;
        router.post(
            route('church.masses.extend-cutoff', { church: church.id, mass: extendingCutoffMass.id }),
            { booking_cutoff_at: newCutoffTime },
            {
                onSuccess: () => {
                    setExtendingCutoffMass(null);
                    setNewCutoffTime('');
                },
            }
        );
    };

    const toggleDayOfWeek = (day) => {
        const current = [...seriesForm.data.recurrence_rule.days_of_week];
        const idx = current.indexOf(day);
        if (idx > -1) {
            current.splice(idx, 1);
        } else {
            current.push(day);
        }
        seriesForm.setData('recurrence_rule', {
            ...seriesForm.data.recurrence_rule,
            days_of_week: current,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                            Mass Schedule Builder
                        </h1>
                        <p className="text-xs text-stone-500 mt-1">
                            Schedule single occurrences or recurring Mass series. Times are rendered in {church.timezone}.
                        </p>
                    </div>

                    <div className="flex items-center space-x-2">
                        <SecondaryButton onClick={() => setShowingSingleModal(true)} className="text-xs cursor-pointer">
                            + Single Mass
                        </SecondaryButton>
                        <PrimaryButton onClick={() => setShowingSeriesModal(true)} className="text-xs cursor-pointer">
                            + Recurring Series
                        </PrimaryButton>
                    </div>
                </div>
            }
        >
            <Head title={`Mass Schedules — ${church.name}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Navigation Pills */}
                    <div className="flex items-center space-x-3 text-xs">
                        <span className="font-bold text-liturgical-900 border-b-2 border-gold-500 pb-1">
                            Schedule Builder
                        </span>
                        <Link
                            href={route('church.lectors.matrix', { church: church.id })}
                            className="text-stone-500 hover:text-liturgical-900 pb-1"
                        >
                            Lector Matrix Board &rarr;
                        </Link>
                    </div>

                    {/* Masses Listing */}
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs overflow-hidden">
                        <div className="p-4 border-b border-stone-100 flex items-center justify-between">
                            <h2 className="font-cinzel text-base font-bold text-liturgical-950">
                                Scheduled Masses ({masses.total ?? masses.data?.length ?? 0})
                            </h2>
                        </div>

                        {masses.data.length === 0 ? (
                            <EmptyState
                                title="No Scheduled Masses"
                                description="No Holy Masses have been created yet. Generate a recurring weekly schedule or add a single Mass."
                                action={
                                    <PrimaryButton onClick={() => setShowingSeriesModal(true)}>
                                        Create First Schedule
                                    </PrimaryButton>
                                }
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs text-stone-600">
                                    <thead className="bg-stone-50 text-[11px] uppercase tracking-wider text-stone-500 border-b border-stone-200">
                                        <tr>
                                            <th className="py-3 px-4">Mass Title</th>
                                            <th className="py-3 px-4">Date & Time</th>
                                            <th className="py-3 px-4">Venue / Resource</th>
                                            <th className="py-3 px-4">Cutoff Time</th>
                                            <th className="py-3 px-4">Assigned Lectors</th>
                                            <th className="py-3 px-4">Status</th>
                                            <th className="py-3 px-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-stone-100">
                                        {masses.data.map((m) => {
                                            const isCancelled = m.status === 'cancelled';
                                            const isClosed = m.booking_status === 'closed';
                                            const isOpen = m.booking_status === 'open' && !isCancelled;

                                            return (
                                                <tr key={m.id} className={isCancelled ? 'bg-stone-50/80 opacity-60' : 'hover:bg-gold-50/30'}>
                                                    <td className="py-3 px-4">
                                                        <span className="font-bold text-stone-900 block">{m.title}</span>
                                                        <div className="flex items-center space-x-1 mt-0.5">
                                                            <span className="text-[10px] uppercase font-semibold text-gold-700 bg-gold-50 px-1.5 py-0.2 rounded border border-gold-200">
                                                                {m.language}
                                                            </span>
                                                            {m.category && (
                                                                <span className="text-[10px] text-liturgical-700 bg-liturgical-50 px-1.5 py-0.2 rounded">
                                                                    {m.category.label}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>

                                                    <td className="py-3 px-4 whitespace-nowrap">
                                                        <span className="font-semibold text-stone-800">
                                                            {new Date(m.starts_at).toLocaleString([], {
                                                                month: 'short',
                                                                day: 'numeric',
                                                                year: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            })}
                                                        </span>
                                                    </td>

                                                    <td className="py-3 px-4">
                                                        <span>{m.location_label || 'Sanctuary'}</span>
                                                        {m.resource_key && (
                                                            <span className="text-[10px] font-mono text-stone-400 block">
                                                                key: {m.resource_key}
                                                            </span>
                                                        )}
                                                    </td>

                                                    <td className="py-3 px-4 whitespace-nowrap">
                                                        <span>
                                                            {new Date(m.booking_cutoff_at).toLocaleString([], {
                                                                month: 'short',
                                                                day: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            })}
                                                        </span>
                                                    </td>

                                                    <td className="py-3 px-4">
                                                        {m.lectors?.length > 0 ? (
                                                            <div className="flex flex-wrap gap-1">
                                                                {m.lectors.map((lec) => (
                                                                    <span
                                                                        key={lec.id}
                                                                        className="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-stone-100 text-stone-700"
                                                                    >
                                                                        {lec.name}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        ) : (
                                                            <span className="text-amber-700 font-medium text-[11px]">Unassigned</span>
                                                        )}
                                                    </td>

                                                    <td className="py-3 px-4">
                                                        {isCancelled ? (
                                                            <span className="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                                                Cancelled
                                                            </span>
                                                        ) : isClosed ? (
                                                            <span className="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-stone-200 text-stone-700">
                                                                Closed
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                                Open
                                                            </span>
                                                        )}
                                                    </td>

                                                    <td className="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                                        <Link
                                                            href={route('church.masses.reading-list', { church: church.id, mass: m.id })}
                                                            className="inline-flex min-h-11 items-center rounded border border-liturgical-700 px-3 py-1 text-[10px] font-semibold uppercase text-liturgical-900 hover:bg-stone-50"
                                                        >
                                                            Reading List
                                                        </Link>
                                                        {!isCancelled && (
                                                            <>
                                                                {isOpen && (
                                                                    <button
                                                                        onClick={() => handleCloseEarly(m.id)}
                                                                        className="px-2 py-1 rounded border border-amber-300 bg-amber-50 text-amber-800 text-[10px] font-semibold uppercase hover:bg-amber-100 cursor-pointer"
                                                                        title="Close intention bookings now"
                                                                    >
                                                                        Close Early
                                                                    </button>
                                                                )}

                                                                <button
                                                                    onClick={() => {
                                                                        setExtendingCutoffMass(m);
                                                                        setNewCutoffTime('');
                                                                    }}
                                                                    className="px-2 py-1 rounded border border-stone-300 text-stone-700 text-[10px] font-semibold uppercase hover:bg-stone-50 cursor-pointer"
                                                                >
                                                                    Extend Cutoff
                                                                </button>

                                                                <button
                                                                    onClick={() => {
                                                                        setCancellingMass(m);
                                                                        setCancellationReason('');
                                                                    }}
                                                                    className="px-2 py-1 rounded border border-rose-300 bg-rose-50 text-rose-800 text-[10px] font-semibold uppercase hover:bg-rose-100 cursor-pointer"
                                                                >
                                                                    Cancel Mass
                                                                </button>
                                                            </>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal: Single Mass */}
            <Modal show={showingSingleModal} onClose={() => setShowingSingleModal(false)}>
                <form onSubmit={handleCreateSingle} className="p-6 space-y-4">
                    <h2 className="font-cinzel text-lg font-bold text-liturgical-950">
                        Create Single Mass Occurrence
                    </h2>

                    <div>
                        <InputLabel htmlFor="title" value="Mass Title *" />
                        <TextInput
                            id="title"
                            type="text"
                            className="mt-1 block w-full"
                            value={singleForm.data.title}
                            onChange={(e) => singleForm.setData('title', e.target.value)}
                            required
                        />
                        <InputError message={singleForm.errors.title} />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="starts_at" value="Starts At *" />
                            <TextInput
                                id="starts_at"
                                type="datetime-local"
                                className="mt-1 block w-full text-xs"
                                value={singleForm.data.starts_at}
                                onChange={(e) => singleForm.setData('starts_at', e.target.value)}
                                required
                            />
                            <InputError message={singleForm.errors.starts_at} />
                        </div>

                        <div>
                            <InputLabel htmlFor="ends_at" value="Ends At (Optional)" />
                            <TextInput
                                id="ends_at"
                                type="datetime-local"
                                className="mt-1 block w-full text-xs"
                                value={singleForm.data.ends_at}
                                onChange={(e) => singleForm.setData('ends_at', e.target.value)}
                            />
                            <InputError message={singleForm.errors.ends_at} />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="location_label" value="Venue / Chapel" />
                            <TextInput
                                id="location_label"
                                type="text"
                                className="mt-1 block w-full text-xs"
                                value={singleForm.data.location_label}
                                onChange={(e) => singleForm.setData('location_label', e.target.value)}
                                placeholder="Main Sanctuary, Side Chapel"
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="resource_key" value="Resource Key (for conflict check)" />
                            <TextInput
                                id="resource_key"
                                type="text"
                                className="mt-1 block w-full text-xs"
                                value={singleForm.data.resource_key}
                                onChange={(e) => singleForm.setData('resource_key', e.target.value)}
                                placeholder="e.g. sanctuary-main"
                            />
                            <InputError message={singleForm.errors.resource_key} />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="booking_opens_at" value="Booking Opens (Optional)" />
                            <TextInput
                                id="booking_opens_at"
                                type="datetime-local"
                                className="mt-1 block w-full text-xs"
                                value={singleForm.data.booking_opens_at}
                                onChange={(e) => singleForm.setData('booking_opens_at', e.target.value)}
                            />
                            <InputError message={singleForm.errors.booking_opens_at} />
                        </div>

                        <div>
                            <InputLabel htmlFor="booking_cutoff_at" value="Booking Cutoff (Optional)" />
                            <TextInput
                                id="booking_cutoff_at"
                                type="datetime-local"
                                className="mt-1 block w-full text-xs"
                                value={singleForm.data.booking_cutoff_at}
                                onChange={(e) => singleForm.setData('booking_cutoff_at', e.target.value)}
                            />
                            <InputError message={singleForm.errors.booking_cutoff_at} />
                        </div>
                    </div>

                    <div className="pt-4 border-t border-stone-100 flex justify-end space-x-2">
                        <SecondaryButton onClick={() => setShowingSingleModal(false)}>Cancel</SecondaryButton>
                        <PrimaryButton disabled={singleForm.processing}>Save Mass</PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal: Recurring Series */}
            <Modal show={showingSeriesModal} onClose={() => setShowingSeriesModal(false)}>
                <form onSubmit={handleCreateSeries} className="p-6 space-y-4">
                    <h2 className="font-cinzel text-lg font-bold text-liturgical-950">
                        Recurring Mass Series Generator
                    </h2>
                    <p className="text-xs text-stone-500">
                        Generates independent Mass occurrences for the selected weekly schedule.
                    </p>

                    <div>
                        <InputLabel htmlFor="series_title" value="Series Title *" />
                        <TextInput
                            id="series_title"
                            type="text"
                            className="mt-1 block w-full text-xs"
                            value={seriesForm.data.title}
                            onChange={(e) => seriesForm.setData('title', e.target.value)}
                            required
                        />
                        <InputError message={seriesForm.errors.title} />
                    </div>

                    {/* Day selector */}
                    <div>
                        <InputLabel value="Days of the Week *" />
                        <div className="mt-1 flex flex-wrap gap-2">
                            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((name, idx) => {
                                const isSelected = seriesForm.data.recurrence_rule.days_of_week.includes(idx);
                                return (
                                    <button
                                        type="button"
                                        key={idx}
                                        onClick={() => toggleDayOfWeek(idx)}
                                        className={`px-3 py-1.5 rounded text-xs font-semibold uppercase tracking-wider transition cursor-pointer ${
                                            isSelected
                                                ? 'bg-liturgical-900 text-gold-200'
                                                : 'bg-stone-100 text-stone-600 hover:bg-stone-200'
                                        }`}
                                    >
                                        {name}
                                    </button>
                                );
                            })}
                        </div>
                        <InputError message={seriesForm.errors['recurrence_rule.days_of_week']} />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="time" value="Time of Day *" />
                            <TextInput
                                id="time"
                                type="time"
                                className="mt-1 block w-full text-xs"
                                value={seriesForm.data.recurrence_rule.time}
                                onChange={(e) =>
                                    seriesForm.setData('recurrence_rule', {
                                        ...seriesForm.data.recurrence_rule,
                                        time: e.target.value,
                                    })
                                }
                                required
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="duration_minutes" value="Duration (Minutes)" />
                            <TextInput
                                id="duration_minutes"
                                type="number"
                                className="mt-1 block w-full text-xs"
                                value={seriesForm.data.recurrence_rule.duration_minutes}
                                onChange={(e) =>
                                    seriesForm.setData('recurrence_rule', {
                                        ...seriesForm.data.recurrence_rule,
                                        duration_minutes: parseInt(e.target.value),
                                    })
                                }
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="starts_on" value="Start Date *" />
                            <TextInput
                                id="starts_on"
                                type="date"
                                className="mt-1 block w-full text-xs"
                                value={seriesForm.data.starts_on}
                                onChange={(e) => seriesForm.setData('starts_on', e.target.value)}
                                required
                            />
                            <InputError message={seriesForm.errors.starts_on} />
                        </div>

                        <div>
                            <InputLabel htmlFor="ends_on" value="End Date (Generates occurrences)" />
                            <TextInput
                                id="ends_on"
                                type="date"
                                className="mt-1 block w-full text-xs"
                                value={seriesForm.data.ends_on}
                                onChange={(e) => seriesForm.setData('ends_on', e.target.value)}
                            />
                            <InputError message={seriesForm.errors.ends_on} />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="location_label" value="Chapel / Venue" />
                            <TextInput
                                id="location_label"
                                type="text"
                                className="mt-1 block w-full text-xs"
                                value={seriesForm.data.location_label}
                                onChange={(e) => seriesForm.setData('location_label', e.target.value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="resource_key" value="Resource Key" />
                            <TextInput
                                id="resource_key"
                                type="text"
                                className="mt-1 block w-full text-xs"
                                value={seriesForm.data.resource_key}
                                onChange={(e) => seriesForm.setData('resource_key', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="pt-4 border-t border-stone-100 flex justify-end space-x-2">
                        <SecondaryButton onClick={() => setShowingSeriesModal(false)}>Cancel</SecondaryButton>
                        <PrimaryButton disabled={seriesForm.processing}>Generate Occurrences</PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal: Cancel Mass */}
            <Modal show={!!cancellingMass} onClose={() => setCancellingMass(null)}>
                <div className="p-6">
                    <h2 className="font-cinzel text-lg font-bold text-rose-900">
                        Cancel Mass: {cancellingMass?.title}
                    </h2>
                    <p className="mt-2 text-xs text-stone-600">
                        Cancelling will immediately stop bookings and preserve original schedule data for parish audit and records.
                    </p>

                    <div className="mt-4">
                        <InputLabel htmlFor="cancellation_reason" value="Reason for Cancellation (Required) *" />
                        <textarea
                            id="cancellation_reason"
                            rows={3}
                            value={cancellationReason}
                            onChange={(e) => setCancellationReason(e.target.value)}
                            className="mt-1 block w-full rounded-md border-stone-300 text-xs shadow-2xs"
                            placeholder="e.g. Typhoon warning / Priest unavailable"
                            required
                        />
                    </div>

                    <div className="mt-6 flex justify-end space-x-2">
                        <SecondaryButton onClick={() => setCancellingMass(null)}>Go Back</SecondaryButton>
                        <DangerButton onClick={handleExecuteCancel} disabled={!cancellationReason.trim()}>
                            Confirm Cancellation
                        </DangerButton>
                    </div>
                </div>
            </Modal>

            {/* Modal: Extend Cutoff */}
            <Modal show={!!extendingCutoffMass} onClose={() => setExtendingCutoffMass(null)}>
                <div className="p-6">
                    <h2 className="font-cinzel text-lg font-bold text-liturgical-950">
                        Extend Booking Cutoff
                    </h2>
                    <p className="mt-2 text-xs text-stone-600">
                        Cutoff cannot be extended past the Mass start time. This action is recorded in the audit trail.
                    </p>

                    <div className="mt-4">
                        <InputLabel htmlFor="newCutoffTime" value="New Cutoff Deadline *" />
                        <TextInput
                            id="newCutoffTime"
                            type="datetime-local"
                            className="mt-1 block w-full text-xs"
                            value={newCutoffTime}
                            onChange={(e) => setNewCutoffTime(e.target.value)}
                            required
                        />
                    </div>

                    <div className="mt-6 flex justify-end space-x-2">
                        <SecondaryButton onClick={() => setExtendingCutoffMass(null)}>Cancel</SecondaryButton>
                        <PrimaryButton onClick={handleExecuteExtendCutoff} disabled={!newCutoffTime}>
                            Update Cutoff
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
