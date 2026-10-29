import React, { useState } from 'react';
import Modal from '@/Components/Modal';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import { useForm, router } from '@inertiajs/react';

export default function ManageRolesModal({ show, onClose, user, churches = [] }) {
    if (!user) return null;

    const [revokingMembershipId, setRevokingMembershipId] = useState(null);
    const [revokeReason, setRevokeReason] = useState('');

    // Central Admin form
    const adminForm = useForm({
        role_type: 'central_admin',
        is_central_admin: !user.is_central_admin,
        reason: '',
    });

    // Parish role assignment form
    const parishRoleForm = useForm({
        role_type: 'church_membership',
        church_id: '',
        role: 'church_admin',
        reason: '',
    });

    const handleToggleCentralAdmin = (e) => {
        e.preventDefault();
        adminForm.post(route('admin.users.roles.assign', { user: user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                adminForm.reset();
            },
        });
    };

    const handleAssignParishRole = (e) => {
        e.preventDefault();
        parishRoleForm.post(route('admin.users.roles.assign', { user: user.id }), {
            preserveScroll: true,
            onSuccess: () => {
                parishRoleForm.reset('church_id', 'reason');
            },
        });
    };

    const handleRevokeRole = (membershipId) => {
        router.delete(route('admin.users.roles.revoke', { user: user.id, membership: membershipId }), {
            data: { reason: revokeReason },
            preserveScroll: true,
            onSuccess: () => {
                setRevokingMembershipId(null);
                setRevokeReason('');
            },
        });
    };

    const activeMemberships = (user.church_memberships || []).filter((m) => m.status === 'active');
    const pastMemberships = (user.church_memberships || []).filter((m) => m.status !== 'active');

    return (
        <Modal show={show} onClose={onClose} maxWidth="xl">
            <div className="p-6">
                <div className="flex items-center justify-between border-b border-gold-200/80 pb-3">
                    <div>
                        <div className="flex items-center gap-2">
                            <h2 className="text-lg font-bold font-serif text-liturgical-950">
                                Roles & Memberships: {user.name}
                            </h2>
                            <StatusBadge status={user.status} />
                        </div>
                        <p className="text-xs text-stone-500 mt-0.5">
                            {user.email} &bull; Manage system authority and parish staff assignments.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-stone-400 hover:text-stone-600 rounded-lg p-1"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="mt-5 space-y-6">
                    {/* Section 1: Diocese Global Role */}
                    <div className="rounded-xl border border-liturgical-200 bg-liturgical-50/50 p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="flex items-center gap-2">
                                    <h3 className="text-sm font-bold text-liturgical-950 font-serif">
                                        Diocesan Authority (Central Admin)
                                    </h3>
                                    {user.is_central_admin ? (
                                        <StatusBadge role="central_admin" />
                                    ) : (
                                        <span className="text-xs text-stone-500">Not assigned</span>
                                    )}
                                </div>
                                <p className="text-xs text-stone-600 mt-1 max-w-md">
                                    Grants system-wide access to verify payments, approve settlements, publish parishes, and oversee diocesan staff.
                                </p>
                            </div>

                            <form onSubmit={handleToggleCentralAdmin} className="shrink-0">
                                {user.is_central_admin ? (
                                    <DangerButton
                                        type="submit"
                                        disabled={adminForm.processing}
                                        className="text-xs"
                                    >
                                        {adminForm.processing ? 'Revoking...' : 'Revoke Diocese Admin'}
                                    </DangerButton>
                                ) : (
                                    <PrimaryButton
                                        type="submit"
                                        disabled={adminForm.processing}
                                        className="text-xs"
                                    >
                                        {adminForm.processing ? 'Granting...' : 'Grant Diocese Admin'}
                                    </PrimaryButton>
                                )}
                            </form>
                        </div>
                        <InputError message={adminForm.errors.is_central_admin} className="mt-2" />
                    </div>

                    {/* Section 2: Active Parish Memberships */}
                    <div>
                        <h3 className="text-sm font-bold text-stone-800 uppercase tracking-wider mb-2">
                            Active Parish Staff Roles ({activeMemberships.length})
                        </h3>

                        {activeMemberships.length === 0 ? (
                            <div className="p-4 rounded-lg border border-dashed border-stone-200 text-center text-xs text-stone-500">
                                No active parish assignments. This user has standard parishioner access.
                            </div>
                        ) : (
                            <div className="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white overflow-hidden shadow-2xs">
                                {activeMemberships.map((membership) => (
                                    <div key={membership.id} className="p-3.5 flex items-center justify-between">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-bold text-stone-900">
                                                    {membership.church?.name || `Church #${membership.church_id}`}
                                                </span>
                                                <StatusBadge role={membership.role} />
                                            </div>
                                            <div className="text-[11px] text-stone-500 mt-0.5">
                                                Assigned {new Date(membership.activated_at || membership.created_at).toLocaleDateString()}
                                                {membership.reason ? ` &bull; ${membership.reason}` : ''}
                                            </div>
                                        </div>

                                        <div>
                                            {revokingMembershipId === membership.id ? (
                                                <div className="flex items-center gap-2">
                                                    <TextInput
                                                        type="text"
                                                        value={revokeReason}
                                                        onChange={(e) => setRevokeReason(e.target.value)}
                                                        placeholder="Reason for revoking"
                                                        className="text-xs py-1"
                                                    />
                                                    <DangerButton
                                                        type="button"
                                                        onClick={() => handleRevokeRole(membership.id)}
                                                        className="text-xs py-1"
                                                    >
                                                        Confirm
                                                    </DangerButton>
                                                    <SecondaryButton
                                                        type="button"
                                                        onClick={() => setRevokingMembershipId(null)}
                                                        className="text-xs py-1"
                                                    >
                                                        Cancel
                                                    </SecondaryButton>
                                                </div>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => setRevokingMembershipId(membership.id)}
                                                    className="text-xs font-semibold text-rose-700 hover:text-rose-900 hover:underline"
                                                >
                                                    Revoke Role
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Section 3: Assign New Parish Role */}
                    <div className="rounded-xl border border-gold-300/80 bg-ivory-50/70 p-4">
                        <h3 className="text-xs font-bold text-gold-950 uppercase tracking-wider mb-2">
                            Assign New Parish Role
                        </h3>

                        <form onSubmit={handleAssignParishRole} className="space-y-3">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <InputLabel htmlFor="assign_church_id" value="Parish *" />
                                    <select
                                        id="assign_church_id"
                                        value={parishRoleForm.data.church_id}
                                        onChange={(e) => parishRoleForm.setData('church_id', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 text-xs"
                                        required
                                    >
                                        <option value="">-- Choose Parish --</option>
                                        {churches.map((church) => (
                                            <option key={church.id} value={church.id}>
                                                {church.name} {church.city ? `(${church.city})` : ''}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={parishRoleForm.errors.church_id} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="assign_role" value="Role *" />
                                    <select
                                        id="assign_role"
                                        value={parishRoleForm.data.role}
                                        onChange={(e) => parishRoleForm.setData('role', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 text-xs"
                                        required
                                    >
                                        <option value="church_admin">Parish Administrator</option>
                                        <option value="lector">Liturgical Lector</option>
                                    </select>
                                    <InputError message={parishRoleForm.errors.role} className="mt-1" />
                                </div>
                            </div>

                            <div>
                                <InputLabel htmlFor="assign_reason" value="Assignment Justification" />
                                <TextInput
                                    id="assign_reason"
                                    type="text"
                                    value={parishRoleForm.data.reason}
                                    onChange={(e) => parishRoleForm.setData('reason', e.target.value)}
                                    placeholder="e.g. Appointed as Parish Secretary"
                                    className="mt-1 block w-full text-xs"
                                />
                                <InputError message={parishRoleForm.errors.reason} className="mt-1" />
                            </div>

                            <div className="flex justify-end pt-2">
                                <PrimaryButton
                                    type="submit"
                                    disabled={parishRoleForm.processing || !parishRoleForm.data.church_id}
                                    className="text-xs"
                                >
                                    {parishRoleForm.processing ? 'Assigning...' : 'Assign Parish Role'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div className="mt-6 flex justify-end pt-3 border-t border-stone-200">
                    <SecondaryButton type="button" onClick={onClose}>
                        Close
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    );
}

