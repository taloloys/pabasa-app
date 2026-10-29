import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import StatusBadge from '@/Components/StatusBadge';
import Card from '@/Components/Card';
import Table from '@/Components/Table';
import EmptyState from '@/Components/EmptyState';
import { UsersIcon } from '@/Components/Icons';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function MembershipIndex({ church, memberships = [], invitations = [] }) {
    const { auth } = usePage().props;
    const currentUserId = auth.user.id;

    // Invite Modal State
    const [isInviteOpen, setIsInviteOpen] = useState(false);
    const inviteForm = useForm({
        email: '',
        role: 'lector',
        reason: '',
    });

    // Revoke Membership Modal State
    const [memberToRevoke, setMemberToRevoke] = useState(null);
    const revokeForm = useForm({
        reason: '',
    });

    // Revoke Invitation Modal State
    const [inviteToRevoke, setInviteToRevoke] = useState(null);
    const revokeInviteForm = useForm({
        reason: '',
    });

    const handleInviteSubmit = (e) => {
        e.preventDefault();
        inviteForm.post(route('church.invitations.store', { church: church.id }), {
            onSuccess: () => {
                inviteForm.reset();
                setIsInviteOpen(false);
            },
        });
    };

    const handleRevokeMemberSubmit = (e) => {
        e.preventDefault();
        if (!memberToRevoke) return;

        revokeForm.delete(
            route('church.memberships.destroy', {
                church: church.id,
                membership: memberToRevoke.id,
            }),
            {
                onSuccess: () => {
                    revokeForm.reset();
                    setMemberToRevoke(null);
                },
            }
        );
    };

    const handleRevokeInviteSubmit = (e) => {
        e.preventDefault();
        if (!inviteToRevoke) return;

        revokeInviteForm.delete(
            route('church.invitations.revoke', {
                church: church.id,
                invitation: inviteToRevoke.id,
            }),
            {
                onSuccess: () => {
                    revokeInviteForm.reset();
                    setInviteToRevoke(null);
                },
            }
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                            <Link href={route('church.dashboard', { church: church.id })} className="hover:underline">
                                {church.name}
                            </Link>
                            <span>/</span>
                            <span>Staff Management</span>
                        </div>
                        <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                            Parish Staff & Memberships
                        </h2>
                    </div>

                    <div>
                        <button
                            type="button"
                            onClick={() => setIsInviteOpen(true)}
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            + Invite New Staff
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Staff Memberships - ${church.name}`} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10">
                {/* Active & Historical Memberships Table */}
                <Card>
                    <Card.Header>
                        <div>
                            <Card.Title>Parish Staff Directory</Card.Title>
                            <Card.Description>
                                Active Church Administrators and Liturgical Lectors
                            </Card.Description>
                        </div>
                        <span className="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                            {memberships.length} Total Records
                        </span>
                    </Card.Header>

                    <Table>
                        <Table.Header>
                            <tr>
                                <Table.Head>Staff Member</Table.Head>
                                <Table.Head>Role</Table.Head>
                                <Table.Head>Status</Table.Head>
                                <Table.Head>Activated On</Table.Head>
                                <Table.Head className="text-right">Actions</Table.Head>
                            </tr>
                        </Table.Header>
                        <Table.Body>
                            {memberships.length === 0 ? (
                                <Table.Empty colSpan={5}>
                                    <EmptyState
                                        icon={<UsersIcon className="w-7 h-7 text-gold-700" />}
                                        title="No Staff Members Found"
                                        description="No active or historical staff assignments exist yet for this parish."
                                        action={
                                            <button
                                                type="button"
                                                onClick={() => setIsInviteOpen(true)}
                                                className="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700"
                                            >
                                                Invite First Staff Member
                                            </button>
                                        }
                                    />
                                </Table.Empty>
                            ) : (
                                memberships.map((membership) => (
                                    <Table.Row key={membership.id}>
                                        <Table.Cell>
                                            <div className="font-semibold text-slate-900">
                                                {membership.user?.name || 'Unnamed User'}
                                                {membership.user_id === currentUserId && (
                                                    <span className="ms-2 text-[10px] font-normal text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                                                        You
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-xs text-slate-500">
                                                {membership.user?.email}
                                            </div>
                                        </Table.Cell>
                                        <Table.Cell>
                                            <StatusBadge role={membership.role} />
                                        </Table.Cell>
                                        <Table.Cell>
                                            <StatusBadge status={membership.status} />
                                            {membership.reason && (
                                                <div className="text-[11px] text-slate-400 mt-0.5 italic">
                                                    "{membership.reason}"
                                                </div>
                                            )}
                                        </Table.Cell>
                                        <Table.Cell className="text-xs text-slate-500">
                                            {membership.activated_at
                                                ? new Date(membership.activated_at).toLocaleDateString()
                                                : '—'}
                                        </Table.Cell>
                                        <Table.Cell className="text-right">
                                            {membership.status === 'active' && membership.user_id !== currentUserId && (
                                                <button
                                                    type="button"
                                                    onClick={() => setMemberToRevoke(membership)}
                                                    className="text-xs font-semibold text-rose-600 hover:text-rose-800 transition"
                                                >
                                                    Revoke Access
                                                </button>
                                            )}
                                        </Table.Cell>
                                    </Table.Row>
                                ))
                            )}
                        </Table.Body>
                    </Table>
                </Card>

                {/* Pending Invitations Table */}
                <Card>
                    <Card.Header>
                        <div>
                            <Card.Title>Pending Invitations</Card.Title>
                            <Card.Description>
                                Invitations awaiting acceptance by recipient
                            </Card.Description>
                        </div>
                        <span className="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
                            {invitations.length} Pending
                        </span>
                    </Card.Header>

                    <Table>
                        <Table.Header>
                            <tr>
                                <Table.Head>Invited Email</Table.Head>
                                <Table.Head>Assigned Role</Table.Head>
                                <Table.Head>Invited By</Table.Head>
                                <Table.Head>Expires At</Table.Head>
                                <Table.Head className="text-right">Actions</Table.Head>
                            </tr>
                        </Table.Header>
                        <Table.Body>
                            {invitations.length === 0 ? (
                                <Table.Empty colSpan={5}>
                                    <div className="py-4 text-center text-slate-500 text-xs">
                                        No active pending invitations.
                                    </div>
                                </Table.Empty>
                            ) : (
                                invitations.map((invitation) => (
                                    <Table.Row key={invitation.id}>
                                        <Table.Cell className="font-medium text-slate-900 font-mono text-xs">
                                            {invitation.email}
                                        </Table.Cell>
                                        <Table.Cell>
                                            <StatusBadge role={invitation.role} />
                                        </Table.Cell>
                                        <Table.Cell className="text-xs text-slate-500">
                                            {invitation.inviter?.name || 'Staff Member'}
                                        </Table.Cell>
                                        <Table.Cell className="text-xs text-slate-500">
                                            {new Date(invitation.expires_at).toLocaleString()}
                                        </Table.Cell>
                                        <Table.Cell className="text-right">
                                            <button
                                                type="button"
                                                onClick={() => setInviteToRevoke(invitation)}
                                                className="text-xs font-semibold text-rose-600 hover:text-rose-800 transition"
                                            >
                                                Revoke Invite
                                            </button>
                                        </Table.Cell>
                                    </Table.Row>
                                ))
                            )}
                        </Table.Body>
                    </Table>
                </Card>
            </div>

            {/* Invite Staff Modal */}
            <Modal show={isInviteOpen} onClose={() => setIsInviteOpen(false)} maxWidth="md">
                <form onSubmit={handleInviteSubmit} className="p-6">
                    <h3 className="text-lg font-bold text-slate-900">
                        Invite Staff Member
                    </h3>
                    <p className="text-xs text-slate-500 mt-1">
                        Send a secure, one-time invitation link to add an administrator or lector to {church.name}.
                    </p>

                    <div className="mt-6 space-y-4">
                        <div>
                            <InputLabel htmlFor="email" value="Email Address" />
                            <TextInput
                                id="email"
                                type="email"
                                value={inviteForm.data.email}
                                onChange={(e) => inviteForm.setData('email', e.target.value)}
                                className="mt-1 block w-full"
                                placeholder="name@example.com"
                                required
                            />
                            <InputError message={inviteForm.errors.email} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="role" value="Staff Role" />
                            <select
                                id="role"
                                value={inviteForm.data.role}
                                onChange={(e) => inviteForm.setData('role', e.target.value)}
                                className="mt-1 block w-full rounded-md border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="lector">Lector (Read intentions during Mass)</option>
                                <option value="church_admin">Church Administrator (Full parish management)</option>
                            </select>
                            <InputError message={inviteForm.errors.role} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="reason" value="Reason / Note (Audited)" />
                            <TextInput
                                id="reason"
                                type="text"
                                value={inviteForm.data.reason}
                                onChange={(e) => inviteForm.setData('reason', e.target.value)}
                                className="mt-1 block w-full"
                                placeholder="e.g. Appointed for Sunday 8AM service"
                            />
                            <InputError message={inviteForm.errors.reason} className="mt-1" />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end space-x-3">
                        <SecondaryButton onClick={() => setIsInviteOpen(false)}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton disabled={inviteForm.processing}>
                            {inviteForm.processing ? 'Creating...' : 'Create Invitation'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Revoke Membership Modal */}
            <Modal show={memberToRevoke !== null} onClose={() => setMemberToRevoke(null)} maxWidth="md">
                <form onSubmit={handleRevokeMemberSubmit} className="p-6">
                    <h3 className="text-lg font-bold text-rose-900">
                        Revoke Staff Access
                    </h3>
                    <p className="text-xs text-slate-600 mt-1">
                        Are you sure you want to revoke staff membership for <strong>{memberToRevoke?.user?.name}</strong>? Their active sessions will be terminated immediately.
                    </p>

                    <div className="mt-4">
                        <InputLabel htmlFor="revoke_reason" value="Reason for Revocation (Required for Audit)" />
                        <TextInput
                            id="revoke_reason"
                            type="text"
                            value={revokeForm.data.reason}
                            onChange={(e) => revokeForm.setData('reason', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="e.g. End of term / Role reassignment"
                            required
                        />
                        <InputError message={revokeForm.errors.reason} className="mt-1" />
                    </div>

                    <div className="mt-6 flex justify-end space-x-3">
                        <SecondaryButton onClick={() => setMemberToRevoke(null)}>
                            Cancel
                        </SecondaryButton>
                        <DangerButton disabled={revokeForm.processing}>
                            {revokeForm.processing ? 'Revoking...' : 'Confirm Revocation'}
                        </DangerButton>
                    </div>
                </form>
            </Modal>

            {/* Revoke Invitation Modal */}
            <Modal show={inviteToRevoke !== null} onClose={() => setInviteToRevoke(null)} maxWidth="md">
                <form onSubmit={handleRevokeInviteSubmit} className="p-6">
                    <h3 className="text-lg font-bold text-rose-900">
                        Revoke Pending Invitation
                    </h3>
                    <p className="text-xs text-slate-600 mt-1">
                        Cancel the invitation sent to <strong>{inviteToRevoke?.email}</strong>? The token will become unusable immediately.
                    </p>

                    <div className="mt-4">
                        <InputLabel htmlFor="invite_revoke_reason" value="Reason (Optional)" />
                        <TextInput
                            id="invite_revoke_reason"
                            type="text"
                            value={revokeInviteForm.data.reason}
                            onChange={(e) => revokeInviteForm.setData('reason', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="e.g. Issued in error"
                        />
                    </div>

                    <div className="mt-6 flex justify-end space-x-3">
                        <SecondaryButton onClick={() => setInviteToRevoke(null)}>
                            Cancel
                        </SecondaryButton>
                        <DangerButton disabled={revokeInviteForm.processing}>
                            {revokeInviteForm.processing ? 'Revoking...' : 'Revoke Invitation'}
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
