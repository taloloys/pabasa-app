import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import CreateUserModal from './Components/CreateUserModal';
import ManageRolesModal from './Components/ManageRolesModal';
import { Head, Link, router } from '@inertiajs/react';

export default function UsersIndex({ users, churches = [], filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [role, setRole] = useState(filters.role || '');
    const [churchId, setChurchId] = useState(filters.church_id || '');
    const [status, setStatus] = useState(filters.status || '');

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [selectedUserForRoles, setSelectedUserForRoles] = useState(null);

    const handleFilterChange = (newFilters) => {
        router.get(
            route('admin.users.index'),
            {
                search,
                role,
                church_id: churchId,
                status,
                ...newFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        handleFilterChange({ search });
    };

    const handleToggleUserStatus = (user) => {
        const newStatus = user.status === 'active' ? 'disabled' : 'active';
        const actionLabel = newStatus === 'disabled' ? 'disable' : 'activate';

        if (confirm(`Are you sure you want to ${actionLabel} account for ${user.name}?`)) {
            router.post(
                route('admin.users.toggle-status', { user: user.id }),
                {
                    status: newStatus,
                    reason: `Status toggled to ${newStatus} by administrator`,
                },
                {
                    preserveScroll: true,
                }
            );
        }
    };

    // Derived statistics
    const totalCount = users.total || 0;

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
                                Staff & Faithful Oversight
                            </h2>
                            <StatusBadge role="central_admin" />
                        </div>
                        <p className="mt-1 text-xs text-stone-600 font-sans">
                            Manage diocesan staff, parish administrators, liturgical lectors, and registered faithful accounts.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={route('admin.dashboard')}
                            className="text-xs font-semibold text-liturgical-800 hover:text-liturgical-950 underline"
                        >
                            &larr; Diocese Console
                        </Link>
                        <PrimaryButton
                            type="button"
                            onClick={() => setIsCreateModalOpen(true)}
                            className="inline-flex items-center gap-1.5 text-xs"
                        >
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Provision User
                        </PrimaryButton>
                    </div>
                </div>
            }
        >
            <Head title="Staff & Faithful Oversight - Diocese Console" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                {/* Search & Filter Bar */}
                <div className="rounded-2xl border border-gold-300/70 bg-white p-4 shadow-xs ring-1 ring-gold-400/20">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
                        <div className="lg:col-span-2">
                            <label className="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">
                                Search Accounts
                            </label>
                            <div className="relative">
                                <TextInput
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Search by name or email address..."
                                    className="w-full text-xs pr-8"
                                />
                                {search && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSearch('');
                                            handleFilterChange({ search: '' });
                                        }}
                                        className="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 text-xs"
                                    >
                                        &times;
                                    </button>
                                )}
                            </div>
                        </div>

                        <div>
                            <label className="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">
                                System Role
                            </label>
                            <select
                                value={role}
                                onChange={(e) => {
                                    setRole(e.target.value);
                                    handleFilterChange({ role: e.target.value });
                                }}
                                className="w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 text-xs py-1.5"
                            >
                                <option value="">All Roles</option>
                                <option value="central_admin">Central Authority</option>
                                <option value="church_admin">Parish Administrator</option>
                                <option value="lector">Liturgical Lector</option>
                                <option value="parishioner">Parishioner</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">
                                Parish Affiliation
                            </label>
                            <select
                                value={churchId}
                                onChange={(e) => {
                                    setChurchId(e.target.value);
                                    handleFilterChange({ church_id: e.target.value });
                                }}
                                className="w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 text-xs py-1.5"
                            >
                                <option value="">All Parishes</option>
                                {churches.map((church) => (
                                    <option key={church.id} value={church.id}>
                                        {church.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-[11px] font-bold text-stone-600 uppercase tracking-wider mb-1">
                                Account Status
                            </label>
                            <select
                                value={status}
                                onChange={(e) => {
                                    setStatus(e.target.value);
                                    handleFilterChange({ status: e.target.value });
                                }}
                                className="w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 text-xs py-1.5"
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </form>
                </div>

                {/* User Table Card */}
                <div className="rounded-2xl border border-gold-300/70 bg-white shadow-xs overflow-hidden ring-1 ring-gold-400/20">
                    <div className="border-b border-gold-200/80 bg-gradient-to-r from-ivory-50 via-white to-ivory-50 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3
                                className="text-base font-bold text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Registered Accounts ({totalCount})
                            </h3>
                            <p className="text-xs text-stone-500">
                                Total members registered in diocese network
                            </p>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-stone-700">
                            <thead className="bg-stone-50 text-[11px] font-bold uppercase tracking-wider text-stone-500 border-b border-stone-200">
                                <tr>
                                    <th className="px-6 py-3.5">User Identity</th>
                                    <th className="px-6 py-3.5">Roles & Affiliations</th>
                                    <th className="px-6 py-3.5">Account Status</th>
                                    <th className="px-6 py-3.5">Registered</th>
                                    <th className="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100">
                                {users.data.length === 0 ? (
                                    <tr>
                                        <td colSpan="5" className="px-6 py-10 text-center text-stone-500">
                                            No user accounts match the current filter criteria.
                                        </td>
                                    </tr>
                                ) : (
                                    users.data.map((user) => {
                                        const activeMemberships = (user.church_memberships || []).filter(
                                            (m) => m.status === 'active'
                                        );

                                        return (
                                            <tr key={user.id} className="hover:bg-ivory-50/60 transition">
                                                <td className="px-6 py-4">
                                                    <div className="font-bold text-stone-900">
                                                        {user.name}
                                                    </div>
                                                    <div className="text-stone-500 font-mono text-[11px]">
                                                        {user.email}
                                                    </div>
                                                    {user.email_verified_at ? (
                                                        <span className="inline-flex items-center gap-1 text-[10px] text-emerald-700 font-medium mt-0.5">
                                                            <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            Email Verified
                                                        </span>
                                                    ) : (
                                                        <span className="text-[10px] text-amber-600 font-medium mt-0.5">
                                                            Pending Verification
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="px-6 py-4">
                                                    <div className="flex flex-wrap gap-1.5 items-center max-w-sm">
                                                        {user.is_central_admin && (
                                                            <StatusBadge role="central_admin" />
                                                        )}

                                                        {activeMemberships.map((membership) => (
                                                            <span
                                                                key={membership.id}
                                                                className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border border-gold-300 bg-gold-50 text-gold-950"
                                                            >
                                                                <span>{membership.church?.name || `Parish #${membership.church_id}`}</span>
                                                                <span className="text-gold-600">&bull;</span>
                                                                <span className="capitalize">{membership.role === 'church_admin' ? 'Admin' : 'Lector'}</span>
                                                            </span>
                                                        ))}

                                                        {!user.is_central_admin && activeMemberships.length === 0 && (
                                                            <StatusBadge role="parishioner" />
                                                        )}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <StatusBadge status={user.status} />
                                                </td>

                                                <td className="px-6 py-4 text-stone-500 text-[11px]">
                                                    {new Date(user.created_at).toLocaleDateString()}
                                                </td>

                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => setSelectedUserForRoles(user)}
                                                            className="text-xs font-bold text-amber-800 hover:text-amber-950 hover:underline px-2 py-1 rounded"
                                                        >
                                                            Manage Roles
                                                        </button>

                                                        <button
                                                            type="button"
                                                            onClick={() => handleToggleUserStatus(user)}
                                                            className={`text-xs font-semibold px-2 py-1 rounded transition ${
                                                                user.status === 'active'
                                                                    ? 'text-rose-700 hover:text-rose-900 hover:bg-rose-50'
                                                                    : 'text-emerald-700 hover:text-emerald-900 hover:bg-emerald-50'
                                                            }`}
                                                        >
                                                            {user.status === 'active' ? 'Disable' : 'Activate'}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {users.links && users.links.length > 3 && (
                        <div className="border-t border-stone-200 bg-stone-50/50 px-6 py-3 flex items-center justify-between">
                            <div className="text-xs text-stone-500">
                                Showing {users.from || 0} to {users.to || 0} of {users.total} accounts
                            </div>
                            <div className="flex items-center gap-1">
                                {users.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        preserveScroll
                                        preserveState
                                        className={`px-2.5 py-1 text-xs rounded-md font-medium transition ${
                                            link.active
                                                ? 'bg-amber-800 text-white font-bold'
                                                : link.url
                                                ? 'text-stone-700 hover:bg-stone-200'
                                                : 'text-stone-400 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modals */}
            <CreateUserModal
                show={isCreateModalOpen}
                onClose={() => setIsCreateModalOpen(false)}
                churches={churches}
            />

            {selectedUserForRoles && (
                <ManageRolesModal
                    show={!!selectedUserForRoles}
                    onClose={() => setSelectedUserForRoles(null)}
                    user={selectedUserForRoles}
                    churches={churches}
                />
            )}
        </AuthenticatedLayout>
    );
}

