import React, { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

const translations = {
    en: {
        title: 'Intention Correction Review',
        subtitle: 'Only Central Admin may approve or reject correction requests.',
        count: 'Open requests',
        emptyTitle: 'No correction requests are waiting for review.',
        emptyText: 'Submitted corrections will appear here for review.',
        church: 'Church',
        mass: 'Mass',
        order: 'Order',
        status: 'Status',
        previous: 'Previous',
        next: 'Next',
        current: 'Current approved wording',
        requested: 'Requested correction',
        name: 'Name',
        wording: 'Intention',
        reason: 'Decision reason (required)',
        approve: 'Approve and apply',
        reject: 'Reject request',
        finalized: 'The assigned lector must reopen this finalized list with a reason before approval.',
        completed: 'This reading list is completed. This workflow does not allow corrections after completion.',
        noList: 'No reading list entry exists; the correction cannot be applied.',
        preparing: 'The correction can be applied while this list is preparing.',
        reopened: 'The list has been reopened; the correction can be applied.',
        processing: 'Saving decision…',
        noListLabel: 'No list',
    },
    fil: {
        title: 'Pagsusuri ng Pagwawasto ng Intensyon',
        subtitle: 'Central Admin lamang ang maaaring mag-apruba o tumanggi sa mga kahilingan.',
        count: 'Bukas na kahilingan',
        emptyTitle: 'Walang kahilingan sa pagwawasto na naghihintay.',
        emptyText: 'Dito lalabas ang mga isinumiteng pagwawasto para sa pagsusuri.',
        church: 'Simbahan',
        mass: 'Misa',
        order: 'Order',
        status: 'Katayuan',
        previous: 'Nakaraan',
        next: 'Susunod',
        current: 'Kasalukuyang aprubadong teksto',
        requested: 'Iminungkahing pagwawasto',
        name: 'Pangalan',
        wording: 'Intensyon',
        reason: 'Dahilan ng pasya (kinakailangan)',
        approve: 'Aprubahan at ilapat',
        reject: 'Tanggihan ang kahilingan',
        finalized: 'Dapat muling buksan ng nakatalagang lector ang pinal na listahan at magbigay ng dahilan bago aprubahan.',
        completed: 'Tapos na ang listahang ito. Hindi pinapayagan ng prosesong ito ang pagwawasto matapos makumpleto.',
        noList: 'Walang entry sa listahan ng pagbasa; hindi mailalapat ang pagwawasto.',
        preparing: 'Maaaring ilapat ang pagwawasto habang inihahanda ang listahan.',
        reopened: 'Muling binuksan ang listahan; maaaring ilapat ang pagwawasto.',
        processing: 'Sine-save ang pasya…',
        noListLabel: 'Walang listahan',
    },
    ceb: {
        title: 'Pagrepaso sa Pagkorihir sa Intensyon',
        subtitle: 'Central Admin lamang ang mahimong moaprubar o mosalikway sa mga hangyo.',
        count: 'Bukas nga mga hangyo',
        emptyTitle: 'Walay pagkorihir nga naghulat sa pagrepaso.',
        emptyText: 'Dinhi makita ang gisumite nga mga pagkorihir.',
        church: 'Simbahan',
        mass: 'Misa',
        order: 'Order',
        status: 'Kahimtang',
        previous: 'Miagi',
        next: 'Sunod',
        current: 'Kasamtangang giaprobahang teksto',
        requested: 'Gihangyo nga pagkorihir',
        name: 'Ngalan',
        wording: 'Intensyon',
        reason: 'Rason sa desisyon (kinahanglan)',
        approve: 'Aprubahan ug ipatuman',
        reject: 'Isalikway ang hangyo',
        finalized: 'Kinahanglan ablihan pag-usab sa gi-assign nga lector ang pinal nga listahan uban sa rason sa dili pa aprobahan.',
        completed: 'Nahuman na ang listahan. Dili tugotan niining proseso ang pagkorihir human mahuman.',
        noList: 'Walay entry sa listahan sa pagbasa; dili maipatuman ang pagkorihir.',
        preparing: 'Mahimong ipatuman ang pagkorihir samtang giandam ang listahan.',
        reopened: 'Giablihan pag-usab ang listahan; mahimong ipatuman ang pagkorihir.',
        processing: 'Gitipigan ang desisyon…',
        noListLabel: 'Walay listahan',
    },
};

function CorrectionCard({ correction, labels, locale }) {
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');
    const canApprove = ['preparing', 'reopened'].includes(correction.list_status);
    const listMessage = correction.list_status === 'finalized'
        ? labels.finalized
        : correction.list_status === 'completed'
            ? labels.completed
            : correction.list_status === 'preparing'
                ? labels.preparing
                : correction.list_status === 'reopened'
                    ? labels.reopened
                    : labels.noList;

    const decide = (decision) => {
        setError('');
        router.post(
            route(`admin.corrections.${decision}`, { correction: correction.id }),
            { reason },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(Object.values(errors).join(' ')),
            },
        );
    };

    const localizedDate = correction.mass_starts_at
        ? new Date(correction.mass_starts_at).toLocaleString(locale === 'fil' ? 'fil-PH' : locale === 'ceb' ? 'ceb-PH' : 'en')
        : '';

    return (
        <article className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap justify-between gap-3 border-b border-stone-100 pb-4">
                <div>
                    <h2 className="font-semibold text-stone-900">{correction.church_name}</h2>
                    <p className="mt-1 text-sm text-stone-600">
                        {labels.mass}: {correction.mass_title} {localizedDate && `· ${localizedDate}`}
                    </p>
                    <p className="text-xs text-stone-500">{labels.order}: {correction.order_number}</p>
                    <p className="text-xs text-stone-500">
                        {labels.status}: {correction.list_status ?? labels.noListLabel}
                        {correction.list_version ? ` · v${correction.list_version}` : ''}
                    </p>
                </div>
                <span className="h-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">
                    {correction.status.replaceAll('_', ' ')}
                </span>
            </div>

            <p className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950" role="status">
                {listMessage}
            </p>

            <div className="mt-4 grid gap-4 md:grid-cols-2">
                {[['current', correction.old_values], ['requested', correction.requested_values]].map(([heading, values]) => (
                    <section key={heading} className="rounded-lg border border-stone-200 p-4">
                        <h3 className="text-sm font-bold text-stone-800">{labels[heading]}</h3>
                        <dl className="mt-3 space-y-3 text-sm">
                            <div>
                                <dt className="font-semibold text-stone-600">{labels.name}</dt>
                                <dd className="mt-1 whitespace-pre-wrap text-stone-900">{values?.person_or_family_name}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-stone-600">{labels.wording}</dt>
                                <dd className="mt-1 whitespace-pre-wrap text-stone-900">{values?.wording}</dd>
                            </div>
                        </dl>
                    </section>
                ))}
            </div>

            <label className="mt-4 block text-sm font-semibold text-stone-700">
                {labels.reason}
                <textarea
                    value={reason}
                    onChange={(event) => setReason(event.target.value)}
                    maxLength={1000}
                    rows={3}
                    required
                    className="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-amber-700 focus:ring-amber-700"
                />
            </label>

            {error && <p className="mt-3 text-sm text-red-700" role="alert">{error}</p>}

            <div className="mt-4 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    disabled={processing || !reason.trim()}
                    onClick={() => decide('reject')}
                    className="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-800 disabled:opacity-50"
                >
                    {processing ? labels.processing : labels.reject}
                </button>
                <button
                    type="button"
                    disabled={processing || !reason.trim() || !canApprove}
                    onClick={() => decide('approve')}
                    className="rounded-lg bg-liturgical-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                >
                    {processing ? labels.processing : labels.approve}
                </button>
            </div>
        </article>
    );
}

export default function ReviewQueue({ corrections }) {
    const { locale = 'en' } = usePage().props;
    const labels = translations[locale] ?? translations.en;

    return (
        <AdminLayout
            header={
                <div>
                    <span className="text-xs font-semibold uppercase tracking-wider text-amber-800">Central Administration</span>
                    <h1 className="text-xl font-serif font-bold text-stone-900">{labels.title}</h1>
                    <p className="mt-1 text-sm text-stone-600">{labels.subtitle}</p>
                </div>
            }
        >
            <Head title={labels.title} />
            <div className="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
                <p className="text-sm text-stone-600">
                    {labels.count}: <strong className="text-stone-900">{corrections.total}</strong>
                </p>
                {corrections.data.length === 0 ? (
                    <div className="rounded-2xl border border-stone-200 bg-white p-10 text-center">
                        <h2 className="font-semibold text-stone-900">{labels.emptyTitle}</h2>
                        <p className="mt-1 text-sm text-stone-600">{labels.emptyText}</p>
                    </div>
                ) : (
                    <>
                        <div className="space-y-4">
                            {corrections.data.map((correction) => (
                                <CorrectionCard key={correction.id} correction={correction} labels={labels} locale={locale} />
                            ))}
                        </div>
                        <div className="flex justify-between">
                            {corrections.prev_page_url
                                ? <Link href={corrections.prev_page_url} className="text-sm font-semibold text-amber-900 underline">{labels.previous}</Link>
                                : <span />}
                            {corrections.next_page_url
                                ? <Link href={corrections.next_page_url} className="text-sm font-semibold text-amber-900 underline">{labels.next}</Link>
                                : <span />}
                        </div>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
