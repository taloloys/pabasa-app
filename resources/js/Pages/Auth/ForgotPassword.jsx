import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout
            title="Reset Password"
            subtitle="Enter your verified account email to receive a secure password recovery link"
        >
            <Head title="Forgot Password" />

            {status && (
                <div className="mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="email" value="Email Address" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full text-sm"
                        placeholder="parishioner@example.com"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-1.5" />
                </div>

                <div className="pt-2">
                    <PrimaryButton className="w-full justify-center py-2.5 text-sm" disabled={processing}>
                        {processing ? 'Sending Link...' : 'Email Password Reset Link'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
                    Remembered your password?{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-indigo-600 hover:text-indigo-800"
                    >
                        Return to sign in
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
