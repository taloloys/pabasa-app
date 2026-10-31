import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

const translations = {
    en: {
        title: 'Reading-list review',
        back: 'Back to Mass schedules',
        listStatus: 'List status',
        empty: 'No paid and verified intentions are available for this Mass.',
        pronunciation: 'Pronunciation hint',
        pronunciationHelp: 'Separate staff-authored guidance. The submitted name and wording are not changed.',
        save: 'Save hint',
        clear: 'Clear hint',
        issue: 'Reported issue',
        underReview: 'Under review',
        returnPending: 'Return to reading list',
        exclude: 'Exclude from this list',
        reopenRequired: 'An assigned lector must reopen this finalized list with a reason before a staff member can resolve the issue or edit the hint.',
        locked: 'Hints can only be edited while the list is preparing or reopened.',
        noList: 'A reading list will appear after a payment is verified by Central Admin.',
        errors: 'Please correct the highlighted fields.',
    },
    fil: {
        title: 'Pagsusuri ng listahan ng pagbasa',
        back: 'Bumalik sa mga iskedyul ng Misa',
        listStatus: 'Kalagayan ng listahan',
        empty: 'Walang bayad at beripikadong intensyon para sa Misa na ito.',
        pronunciation: 'Gabay sa pagbigkas',
        pronunciationHelp: 'Hiwalay na gabay ng kawani. Hindi binabago ang isinumiteng pangalan at pananalita.',
        save: 'I-save ang gabay',
        clear: 'Burahin ang gabay',
        issue: 'Iniulat na isyu',
        underReview: 'Sinusuri',
        returnPending: 'Ibalik sa listahan ng babasahin',
        exclude: 'Alisin sa listahang ito',
        reopenRequired: 'Kailangang muling buksan ng nakatalagang lector ang pinal na listahan at magbigay ng dahilan bago maresolba ang isyu o mabago ang gabay.',
        locked: 'Mababago lamang ang gabay habang inihahanda o muling binuksan ang listahan.',
        noList: 'Lilitaw ang listahan matapos beripikahin ng Central Admin ang bayad.',
        errors: 'Itama ang mga naka-highlight na field.',
    },
    ceb: {
        title: 'Pagrepaso sa listahan sa pagbasa',
        back: 'Balik sa mga iskedyul sa Misa',
        listStatus: 'Kahimtang sa listahan',
        empty: 'Walay nabayran ug napamatud-ang intensyon alang niining Misa.',
        pronunciation: 'Giya sa paglitok',
        pronunciationHelp: 'Lahi nga giya sa kawani. Dili mausab ang gisumiter nga ngalan ug pulong.',
        save: 'I-save ang giya',
        clear: 'Tangtanga ang giya',
        issue: 'Gi-report nga isyu',
        underReview: 'Girepaso',
        returnPending: 'Ibalik sa listahan sa basahon',
        exclude: 'Kuhaa gikan niining listahana',
        reopenRequired: 'Kinahanglan ablihan pag-usab sa gi-assign nga lector kining pinal nga listahan uban ang rason sa dili pa maresolba ang isyu o mausab ang giya.',
        locked: 'Mausab lamang ang giya samtang giandam o giablihan pag-usab ang listahan.',
        noList: 'Mugawas ang listahan human mapamatud-an sa Central Admin ang bayad.',
        errors: 'Palihog tul-ira ang gipasiugda nga mga field.',
    },
};

function PronunciationField({ church, mass, entry, labels, canEdit }) {
    const form = useForm({ pronunciation_hint: entry.pronunciationHint ?? '' });

    const submit = (event) => {
        event.preventDefault();
        form.patch(route('church.masses.reading-list.entries.pronunciation', {
            church: church.id,
            mass: mass.id,
            entry: entry.id,
        }), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="mt-4 space-y-2 border-t border-stone-200 pt-3">
            <label htmlFor={`pronunciation-${entry.id}`} className="block text-xs font-semibold text-stone-800">
                {labels.pronunciation}
            </label>
            <p className="text-xs text-stone-600">{labels.pronunciationHelp}</p>
            <div className="flex flex-col gap-2 sm:flex-row">
                <input
                    id={`pronunciation-${entry.id}`}
                    value={form.data.pronunciation_hint}
                    onChange={(event) => form.setData('pronunciation_hint', event.target.value)}
                    disabled={!canEdit || form.processing}
                    maxLength={255}
                    className="min-h-11 flex-1 rounded-md border-stone-300 text-sm focus:border-amber-700 focus:ring-amber-700 disabled:bg-stone-100"
                />
                <button
                    type="submit"
                    disabled={!canEdit || form.processing}
                    className="min-h-11 rounded-md bg-liturgical-900 px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50"
                >
                    {form.data.pronunciation_hint.trim() ? labels.save : labels.clear}
                </button>
            </div>
            {form.errors.pronunciation_hint && <p className="text-sm text-red-700">{form.errors.pronunciation_hint}</p>}
        </form>
    );
}

function IssueReview({ church, mass, entry, labels, canResolve }) {
    const form = useForm({ resolution: '' });

    const resolve = (resolution) => {
        form.transform(() => ({ resolution }));
        form.post(route('church.masses.reading-list.entries.issues.resolve', {
            church: church.id,
            mass: mass.id,
            entry: entry.id,
            issue: entry.issue.id,
        }), { preserveScroll: true });
    };

    return (
        <div className="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p className="text-xs font-bold uppercase tracking-wide text-amber-950">{labels.underReview} · {labels.issue}</p>
            <p className="mt-2 whitespace-pre-wrap text-sm text-stone-800">{entry.issue.privateNote}</p>
            {canResolve ? (
                <div className="mt-3 flex flex-wrap gap-2">
                    <button
                        type="button"
                        disabled={form.processing}
                        onClick={() => resolve('pending')}
                        className="min-h-11 rounded-md border border-amber-800 px-3 py-2 text-sm font-semibold text-amber-950 disabled:opacity-50"
                    >
                        {labels.returnPending}
                    </button>
                    <button
                        type="button"
                        disabled={form.processing}
                        onClick={() => resolve('excluded')}
                        className="min-h-11 rounded-md border border-red-700 px-3 py-2 text-sm font-semibold text-red-800 disabled:opacity-50"
                    >
                        {labels.exclude}
                    </button>
                </div>
            ) : (
                <p className="mt-3 text-xs text-amber-950">{labels.reopenRequired}</p>
            )}
            {form.errors.resolution && <p className="mt-2 text-sm text-red-700">{form.errors.resolution}</p>}
        </div>
    );
}

export default function ReadingList({ church, mass, readingList, entries = [], canEditHints, canResolveIssues }) {
    const { locale = 'en' } = usePage().props;
    const labels = translations[locale] ?? translations.en;

    return (
        <AuthenticatedLayout>
            <Head title={`${labels.title} — ${mass.title}`} />
            <main className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <header className="space-y-2">
                    <Link
                        href={route('church.masses.index', { church: church.id })}
                        className="text-sm font-semibold text-liturgical-900 underline"
                    >
                        {labels.back}
                    </Link>
                    <h1 className="font-cinzel text-2xl font-bold text-liturgical-950">{labels.title}</h1>
                    <p className="text-sm text-stone-700">{mass.title} · {church.name}</p>
                    {readingList && <p className="text-xs text-stone-600">{labels.listStatus}: {readingList.status}</p>}
                </header>

                {!readingList ? (
                    <p className="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-700">{labels.noList}</p>
                ) : entries.length === 0 ? (
                    <p className="rounded-lg border border-stone-200 bg-white p-5 text-sm text-stone-700">{labels.empty}</p>
                ) : (
                    <ol className="space-y-4">
                        {entries.map((entry) => (
                            <li key={entry.id} className="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
                                <h2 className="font-semibold text-stone-950">{entry.name}</h2>
                                {entry.issue && (
                                    <IssueReview
                                        church={church}
                                        mass={mass}
                                        entry={entry}
                                        labels={labels}
                                        canResolve={canResolveIssues}
                                    />
                                )}
                                <PronunciationField
                                    church={church}
                                    mass={mass}
                                    entry={entry}
                                    labels={labels}
                                    canEdit={canEditHints}
                                />
                                {!canEditHints && !entry.issue && (
                                    <p className="mt-2 text-xs text-stone-600">{labels.locked}</p>
                                )}
                            </li>
                        ))}
                    </ol>
                )}
            </main>
        </AuthenticatedLayout>
    );
}
