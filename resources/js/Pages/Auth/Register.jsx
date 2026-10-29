import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout
            title="Register with Pabasa"
            subtitle="Create an account to book Mass intentions, offer prayers, and participate in parish life"
            latinMotto="Ecclesia Sancta"
        >
            <Head title="Register Account" />

            {/* Liturgical Note Banner */}
            <div className="mb-6 rounded-2xl border border-gold-300/60 bg-gradient-to-br from-ivory-50 via-gold-50/30 to-ivory-100 p-3.5 text-xs text-stone-700 space-y-1">
                <div className="font-bold text-liturgical-950 flex items-center space-x-1.5">
                    <span>🕊️</span>
                    <span>Parishioner Registration</span>
                </div>
                <p className="text-[11px] text-stone-600 leading-relaxed">
                    Registered parishioners can submit Thanksgiving, Memorial, and Healing prayer requests with GCash screenshot verification.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="name" value="Full Christian Name" />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1.5 block w-full text-sm"
                        autoComplete="name"
                        placeholder="e.g. Maria Clara Santos"
                        isFocused={true}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />

                    <InputError message={errors.name} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email Address" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1.5 block w-full text-sm"
                        autoComplete="username"
                        placeholder="maria.santos@example.com"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1.5 block w-full text-sm"
                        autoComplete="new-password"
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-1.5" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Confirm Password"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1.5 block w-full text-sm"
                        autoComplete="new-password"
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-1.5"
                    />
                </div>

                <div className="pt-2">
                    <PrimaryButton
                        className="w-full justify-center py-3 text-xs tracking-wider"
                        disabled={processing}
                    >
                        {processing ? 'Registering...' : 'Complete Registration'}
                    </PrimaryButton>
                </div>

                <div className="pt-4 border-t border-stone-100 text-center text-xs text-stone-500">
                    Already registered?{' '}
                    <Link
                        href={route('login')}
                        className="font-bold text-liturgical-800 hover:text-liturgical-950 hover:underline"
                    >
                        Sign in to your account
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
