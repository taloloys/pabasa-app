import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const autofillRole = (email) => {
        setData((prev) => ({
            ...prev,
            email: email,
            password: 'password',
        }));
    };

    return (
        <GuestLayout
            title="Sanctuary Portal Sign In"
            subtitle="Access your parish ministry assignments, Mass intention tracking, or diocese administration"
        >
            <Head title="Sign In" />

            {/* Quick Demo Sign In Roles Bar */}
            <div className="mb-6 rounded-2xl border border-gold-300/60 bg-gradient-to-br from-ivory-50 via-gold-50/40 to-ivory-100 p-3.5 text-center shadow-2xs">
                <div className="text-[10px] font-bold tracking-wider uppercase text-gold-800">
                    Quick Role Sign In (Demo Mode)
                </div>
                <div className="mt-2 grid grid-cols-2 gap-2 text-xs">
                    <button
                        type="button"
                        onClick={() => autofillRole('admin@pabasa.ph')}
                        className="rounded-lg border border-liturgical-200 bg-white px-2.5 py-1.5 text-left text-[11px] font-medium text-liturgical-900 hover:border-liturgical-400 hover:bg-liturgical-50 transition cursor-pointer shadow-2xs"
                    >
                        <span className="block font-bold">Central Admin</span>
                        <span className="text-[9px] text-stone-500">admin@pabasa.ph</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => autofillRole('churchadmin@pabasa.ph')}
                        className="rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-left text-[11px] font-medium text-amber-950 hover:border-amber-400 hover:bg-amber-50 transition cursor-pointer shadow-2xs"
                    >
                        <span className="block font-bold">Church Admin</span>
                        <span className="text-[9px] text-stone-500">churchadmin@pabasa.ph</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => autofillRole('lector@pabasa.ph')}
                        className="rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-left text-[11px] font-medium text-sky-950 hover:border-sky-400 hover:bg-sky-50 transition cursor-pointer shadow-2xs"
                    >
                        <span className="block font-bold">Liturgical Lector</span>
                        <span className="text-[9px] text-stone-500">lector@pabasa.ph</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => autofillRole('user@pabasa.ph')}
                        className="rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-left text-[11px] font-medium text-emerald-950 hover:border-emerald-400 hover:bg-emerald-50 transition cursor-pointer shadow-2xs"
                    >
                        <span className="block font-bold">Parishioner</span>
                        <span className="text-[9px] text-stone-500">user@pabasa.ph</span>
                    </button>
                </div>
            </div>

            {status && (
                <div className="mb-4 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/80 rounded-xl p-3">
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
                        className="mt-1.5 block w-full text-sm"
                        autoComplete="username"
                        placeholder="parishioner@example.com"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-1.5" />
                </div>

                <div>
                    <div className="flex items-center justify-between">
                        <InputLabel htmlFor="password" value="Password" />
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="text-xs text-liturgical-700 hover:text-liturgical-950 font-medium hover:underline"
                            >
                                Forgot password?
                            </Link>
                        )}
                    </div>

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1.5 block w-full text-sm"
                        autoComplete="current-password"
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <div className="block">
                    <label className="flex items-center cursor-pointer">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-xs text-stone-600">
                            Remember this sanctum device
                        </span>
                    </label>
                </div>

                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-3 text-xs tracking-wider"
                        disabled={processing}
                    >
                        {processing ? 'Entering Sanctuary...' : 'Sign In to Portal'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-stone-100 text-center text-xs text-stone-500">
                    New to Pabasa?{' '}
                    <Link
                        href={route('register')}
                        className="font-bold text-liturgical-800 hover:text-liturgical-950 hover:underline"
                    >
                        Create a Parishioner Account
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
