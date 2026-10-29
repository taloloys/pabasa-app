import React from 'react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Checkbox from '@/Components/Checkbox';
import { useForm } from '@inertiajs/react';

export default function CreateUserModal({ show, onClose, churches = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        is_central_admin: false,
        church_id: '',
        church_role: '',
        email_verified: true,
        reason: '',
    });

    const handleClose = () => {
        reset();
        onClose();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.users.store'), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="lg">
            <form onSubmit={handleSubmit} className="p-6">
                <div className="flex items-center justify-between border-b border-gold-200/80 pb-3">
                    <div>
                        <h2 className="text-lg font-bold font-serif text-liturgical-950">
                            Provision New User Account
                        </h2>
                        <p className="text-xs text-stone-500">
                            Create a diocesan staff, parish administrator, or faithful member account.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={handleClose}
                        className="text-stone-400 hover:text-stone-600 rounded-lg p-1"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="mt-4 space-y-4">
                    <div>
                        <InputLabel htmlFor="name" value="Full Name *" />
                        <TextInput
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full text-sm"
                            placeholder="e.g. Maria Clara Santos"
                            required
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="email" value="Email Address *" />
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1 block w-full text-sm"
                            placeholder="e.g. maria@parish.ph"
                            required
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <InputLabel htmlFor="password" value="Initial Password" />
                            <TextInput
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1 block w-full text-sm"
                                placeholder="Leave blank to auto-generate"
                            />
                            <InputError message={errors.password} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="password_confirmation" value="Confirm Password" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="mt-1 block w-full text-sm"
                                placeholder="Repeat password"
                            />
                            <InputError message={errors.password_confirmation} className="mt-1" />
                        </div>
                    </div>

                    <div className="pt-2 border-t border-stone-200 space-y-3">
                        <label className="flex items-center space-x-2 cursor-pointer">
                            <Checkbox
                                checked={data.email_verified}
                                onChange={(e) => setData('email_verified', e.target.checked)}
                            />
                            <span className="text-xs text-stone-700 font-medium">
                                Mark email address as verified immediately
                            </span>
                        </label>

                        <label className="flex items-center space-x-2 cursor-pointer">
                            <Checkbox
                                checked={data.is_central_admin}
                                onChange={(e) => setData('is_central_admin', e.target.checked)}
                            />
                            <span className="text-xs text-liturgical-900 font-bold">
                                Grant Central Diocese Administrator Authority (System-wide)
                            </span>
                        </label>
                    </div>

                    <div className="pt-2 border-t border-stone-200">
                        <h4 className="text-xs font-bold text-stone-700 uppercase tracking-wider mb-2">
                            Optional Parish Role Assignment
                        </h4>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <InputLabel htmlFor="church_id" value="Parish" />
                                <select
                                    id="church_id"
                                    value={data.church_id}
                                    onChange={(e) => setData('church_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 text-xs"
                                >
                                    <option value="">-- No Parish Assignment --</option>
                                    {churches.map((church) => (
                                        <option key={church.id} value={church.id}>
                                            {church.name} {church.city ? `(${church.city})` : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.church_id} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="church_role" value="Parish Role" />
                                <select
                                    id="church_role"
                                    value={data.church_role}
                                    onChange={(e) => setData('church_role', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 text-xs"
                                    disabled={!data.church_id}
                                >
                                    <option value="">-- Select Role --</option>
                                    <option value="church_admin">Parish Administrator</option>
                                    <option value="lector">Liturgical Lector</option>
                                </select>
                                <InputError message={errors.church_role} className="mt-1" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="reason" value="Administrative Justification" />
                        <TextInput
                            id="reason"
                            type="text"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            className="mt-1 block w-full text-xs"
                            placeholder="Optional reason recorded in append-only audit trail"
                        />
                        <InputError message={errors.reason} className="mt-1" />
                    </div>
                </div>

                <div className="mt-6 flex justify-end gap-3 pt-3 border-t border-stone-200">
                    <SecondaryButton type="button" onClick={handleClose} disabled={processing}>
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" disabled={processing}>
                        {processing ? 'Provisioning...' : 'Provision User'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}

