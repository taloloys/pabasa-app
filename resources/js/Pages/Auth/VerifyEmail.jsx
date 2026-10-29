import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout
            title="Verify Your Email"
            subtitle="Secure intention tracking and staff operations require a verified email address"
        >
            <Head title="Email Verification" />

            <div className="mb-4 text-xs text-slate-600 leading-relaxed">
                Thank you for registering with Pabasa. Before getting started, please verify your email address by clicking the link we just sent to your inbox.
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                    A new verification link has been sent to the email address you provided during registration.
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="flex items-center justify-between pt-2">
                    <PrimaryButton className="text-xs" disabled={processing}>
                        {processing ? 'Sending...' : 'Resend Verification Link'}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-xs font-medium text-slate-500 hover:text-slate-800 underline"
                    >
                        Sign Out
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
