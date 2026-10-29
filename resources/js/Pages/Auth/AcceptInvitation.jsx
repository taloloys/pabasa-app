import GuestLayout from '@/Layouts/GuestLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import StatusBadge from '@/Components/StatusBadge';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function AcceptInvitation({ token, email, church, role }) {
    const { auth } = usePage().props;
    const currentUser = auth.user;

    const { post, processing } = useForm({
        token: token,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('invitations.accept'));
    };

    return (
        <GuestLayout
            title="Parish Ministry Invitation"
            subtitle="You have been invited to serve in pastoral ministry or liturgical leadership"
            latinMotto="Vocatus ad Servitium"
        >
            <Head title="Accept Parish Staff Invitation" />

            <div className="rounded-2xl border border-gold-300/80 bg-ivory-50/70 p-5 space-y-3.5 mb-6 text-xs text-stone-700">
                <div className="flex justify-between items-center pb-2.5 border-b border-gold-200/60">
                    <span className="font-serif uppercase tracking-wider text-[10px] text-stone-500 font-bold">Parish Sanctuary</span>
                    <span
                        className="font-bold text-liturgical-950 font-serif text-sm"
                        style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                    >
                        {church.name}
                    </span>
                </div>
                <div className="flex justify-between items-center pb-2.5 border-b border-gold-200/60">
                    <span className="font-serif uppercase tracking-wider text-[10px] text-stone-500 font-bold">Assigned Ministry</span>
                    <StatusBadge role={role} />
                </div>
                <div className="flex justify-between items-center">
                    <span className="font-serif uppercase tracking-wider text-[10px] text-stone-500 font-bold">Designated Recipient</span>
                    <span className="font-mono text-xs text-stone-800 font-bold bg-white px-2 py-0.5 rounded border border-gold-200">{email}</span>
                </div>
            </div>

            {currentUser ? (
                <form onSubmit={submit} className="space-y-4">
                    <div className="rounded-xl bg-liturgical-50 p-4 text-xs text-liturgical-900 border border-liturgical-200 space-y-1">
                        <div className="font-bold">Signed in as {currentUser.email}</div>
                        <p className="text-[11px] text-liturgical-800/90 leading-relaxed">
                            Accepting this invitation will immediately link your account as an authorized {role === 'church_admin' ? 'Church Administrator' : 'Liturgical Lector'} for {church.name}.
                        </p>
                    </div>

                    <PrimaryButton className="w-full justify-center py-3 text-xs tracking-wider" disabled={processing}>
                        {processing ? 'Confirming Ministry...' : 'Accept Invitation & Activate Ministry'}
                    </PrimaryButton>
                </form>
            ) : (
                <div className="space-y-4 text-center">
                    <div className="rounded-xl bg-amber-50 p-4 text-xs text-amber-900 border border-amber-200 space-y-1">
                        <div className="font-bold">Authentication Required</div>
                        <p className="text-[11px] text-amber-800 leading-relaxed">
                            Please sign in or register with your account to accept this ministerial appointment.
                        </p>
                    </div>

                    <div className="flex flex-col space-y-2.5 pt-2">
                        <Link
                            href={route('login')}
                            className="inline-flex justify-center items-center rounded-xl bg-gradient-to-r from-liturgical-800 to-liturgical-950 border border-gold-400/40 px-5 py-3 text-xs font-bold uppercase tracking-wider text-gold-100 shadow-sm hover:from-liturgical-700 hover:to-liturgical-900 transition"
                        >
                            Sign In to Accept &rarr;
                        </Link>
                        <Link
                            href={route('register')}
                            className="text-xs text-liturgical-800 hover:text-liturgical-950 font-bold hover:underline"
                        >
                            Don't have an account? Register here &rarr;
                        </Link>
                    </div>
                </div>
            )}
        </GuestLayout>
    );
}
