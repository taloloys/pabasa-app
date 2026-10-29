import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Matrix({ church, upcomingMasses, lectors }) {
    const [selectedMass, setSelectedMass] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        user_id: '',
        notes: '',
    });

    const handleOpenAssignModal = (mass) => {
        setSelectedMass(mass);
        reset();
    };

    const handleAssign = (e) => {
        e.preventDefault();
        if (!selectedMass) return;

        post(route('church.lectors.assign', { church: church.id, mass: selectedMass.id }), {
            onSuccess: () => {
                setSelectedMass(null);
                reset();
            },
        });
    };

    const handleRemove = (assignmentId) => {
        if (!confirm('Are you sure you want to remove this lector from the Mass?')) return;
        router.delete(route('church.lectors.remove', { church: church.id, assignment: assignmentId }));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                            Lector Assignment Matrix
                        </h1>
                        <p className="text-xs text-stone-500 mt-1">
                            Assign active church lectors to upcoming Masses. Conflict detection alerts prevent double-booking.
                        </p>
                    </div>

                    <Link
                        href={route('church.masses.index', { church: church.id })}
                        className="text-xs font-semibold uppercase tracking-wider text-liturgical-900 hover:text-gold-700"
                    >
                        &larr; Back to Schedule Builder
                    </Link>
                </div>
            }
        >
            <Head title={`Lector Matrix — ${church.name}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Matrix Schedule Board */}
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs overflow-hidden">
                        <div className="p-4 border-b border-stone-100 flex items-center justify-between">
                            <h2 className="font-cinzel text-base font-bold text-liturgical-950">
                                Upcoming Masses & Roster ({upcomingMasses.length})
                            </h2>
                            <span className="text-xs text-stone-500">
                                Active verified lectors available: {lectors.length}
                            </span>
                        </div>

                        {upcomingMasses.length === 0 ? (
                            <EmptyState
                                title="No Masses to Assign"
                                description="There are no upcoming scheduled Masses requiring lector assignments."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs text-stone-600">
                                    <thead className="bg-stone-50 text-[11px] uppercase tracking-wider text-stone-500 border-b border-stone-200">
                                        <tr>
                                            <th className="py-3 px-4">Mass</th>
                                            <th className="py-3 px-4">Date & Time</th>
                                            <th className="py-3 px-4">Location</th>
                                            <th className="py-3 px-4">Assigned Lectors</th>
                                            <th className="py-3 px-4 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-stone-100">
                                        {upcomingMasses.map((mass) => (
                                            <tr key={mass.id} className="hover:bg-gold-50/30 transition">
                                                <td className="py-3 px-4">
                                                    <span className="font-bold text-stone-900 block">{mass.title}</span>
                                                    <span className="text-[10px] text-stone-500 font-mono">ID: #{mass.id}</span>
                                                </td>

                                                <td className="py-3 px-4 whitespace-nowrap">
                                                    <span className="font-semibold text-stone-800">
                                                        {new Date(mass.starts_at).toLocaleString([], {
                                                            month: 'short',
                                                            day: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        })}
                                                    </span>
                                                </td>

                                                <td className="py-3 px-4">
                                                    <span>{mass.location_label || 'Sanctuary'}</span>
                                                </td>

                                                <td className="py-3 px-4">
                                                    {mass.active_assignments?.length > 0 ? (
                                                        <div className="flex flex-wrap gap-2">
                                                            {mass.active_assignments.map((assignment) => (
                                                                <span
                                                                    key={assignment.id}
                                                                    className="inline-flex items-center space-x-1.5 pl-2.5 pr-1.5 py-0.5 rounded-full text-xs font-medium bg-gold-50 text-gold-900 border border-gold-200"
                                                                >
                                                                    <span>{assignment.user?.name || `User #${assignment.user_id}`}</span>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleRemove(assignment.id)}
                                                                        className="h-4 w-4 rounded-full text-gold-600 hover:bg-gold-200 hover:text-gold-900 flex items-center justify-center cursor-pointer text-xs leading-none"
                                                                        title="Remove lector assignment"
                                                                    >
                                                                        &times;
                                                                    </button>
                                                                </span>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                            Needs Lector
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="py-3 px-4 text-right">
                                                    <button
                                                        onClick={() => handleOpenAssignModal(mass)}
                                                        className="px-3 py-1 rounded bg-liturgical-900 text-gold-200 hover:bg-liturgical-800 text-xs font-semibold uppercase tracking-wider transition cursor-pointer"
                                                    >
                                                        + Assign Lector
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal: Assign Lector */}
            <Modal show={!!selectedMass} onClose={() => setSelectedMass(null)}>
                <form onSubmit={handleAssign} className="p-6 space-y-4">
                    <h2 className="font-cinzel text-lg font-bold text-liturgical-950">
                        Assign Lector to {selectedMass?.title}
                    </h2>
                    <p className="text-xs text-stone-500">
                        Scheduled for {selectedMass && new Date(selectedMass.starts_at).toLocaleString()}
                    </p>

                    <div>
                        <InputLabel htmlFor="user_id" value="Select Verified Lector *" />
                        <select
                            id="user_id"
                            value={data.user_id}
                            onChange={(e) => setData('user_id', e.target.value)}
                            className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                            required
                        >
                            <option value="">-- Choose a lector --</option>
                            {lectors.map((lec) => (
                                <option key={lec.id} value={lec.id}>
                                    {lec.name} ({lec.email})
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.user_id} />
                    </div>

                    <div>
                        <InputLabel htmlFor="notes" value="Assignment Notes (Optional)" />
                        <TextInput
                            id="notes"
                            type="text"
                            className="mt-1 block w-full text-xs"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="e.g. Lead lector / First reading"
                        />
                    </div>

                    <div className="pt-4 border-t border-stone-100 flex justify-end space-x-2">
                        <SecondaryButton onClick={() => setSelectedMass(null)}>Cancel</SecondaryButton>
                        <PrimaryButton disabled={processing || !data.user_id}>
                            {processing ? 'Assigning...' : 'Confirm Assignment'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

