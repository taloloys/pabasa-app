import { router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

const translations = {
    en: {
        title: 'Reading list',
        noList: 'No verified intentions are available for this Mass yet.',
        version: 'Version',
        preparing: 'Preparing',
        reopened: 'Reopened',
        finalized: 'Finalized',
        completed: 'Completed',
        entries: 'Intentions',
        empty: 'No confirmed intentions are currently on this reading list.',
        reorder: 'Save order',
        moveUp: 'Move up',
        moveDown: 'Move down',
        finalize: 'Finalize list',
        reopen: 'Reopen list',
        reopenTitle: 'Reopen reading list',
        reopenReason: 'Reason for reopening this list',
        reopenWarning: 'Reopening allows preparation changes and records this reason in the audit history.',
        reopenConfirm: 'Reopen list',
        complete: 'Mark as read',
        ambo: 'Ambo reading controls',
        contrast: 'High contrast',
        textSize: 'Text size',
        lateTitle: 'Verified but not included',
        lateFinalized: 'These intentions were verified after this list was finalized. Reopen the list with a reason to include them in a new version.',
        lateCompleted: 'This list is completed. These intentions need a separately authorized review.',
        lateDraft: 'These verified intentions are waiting to be added to the reading list.',
        confirmFinalize: 'Finalize and freeze the current reading-list version?',
        reviewFinalize: 'Review before finalizing',
        finalizeSummary: (count, version) => `${count} intentions will be frozen into version ${version}.`,
        waitingSummary: (count) => `${count} verified intentions waiting to be added will not be included.`,
        freezeWarning: 'After finalizing, the list cannot be reordered or edited unless it is reopened with a reason.',
        cancel: 'Cancel',
        everyEntry: 'Every included intention must be marked as read before the reading list can be completed.',
        category: 'Category',
        downloadPdf: 'Download reading-list PDF',
        offlineError: 'The reading list is read-only while you are offline.',
        pronunciation: 'Pronunciation',
        reportIssue: 'Report an issue',
        issueNote: 'Private note for authorized church staff',
        submitIssue: 'Send report',
        cancelIssue: 'Cancel',
        underReview: 'Under review',
        returnPending: 'Return to reading list',
        excludeEntry: 'Exclude from this list',
        reopenToResolve: 'Reopen this list with a reason before resolving the issue.',
        excluded: 'Excluded from this reading list',
        correctCompletion: 'Correct completion',
        correctionTitle: 'Correct a completion record',
        correctionReason: 'Reason for correcting this completion',
        correctionWarning: 'This will return the intention to the unread list. The original completion remains in the audit history.',
        correctionConfirm: 'Correct completion',
    },
    fil: {
        title: 'Listahan ng mga intensyon',
        noList: 'Wala pang bayad at beripikadong intensyon para sa Misa na ito.',
        version: 'Bersyon',
        preparing: 'Inihahanda',
        reopened: 'Muling binuksan',
        finalized: 'Pinal',
        completed: 'Tapos na',
        entries: 'Mga intensyon',
        empty: 'Wala pang kumpirmadong intensyon sa listahang ito.',
        reorder: 'I-save ang ayos',
        moveUp: 'Itaas',
        moveDown: 'Ibaba',
        finalize: 'I-finalize ang listahan',
        reopen: 'Muling buksan ang listahan',
        reopenTitle: 'Muling buksan ang listahan',
        reopenReason: 'Dahilan sa muling pagbubukas ng listahan',
        reopenWarning: 'Pinapayagan ng muling pagbubukas ang mga pagbabago at itatala ang dahilan sa audit history.',
        reopenConfirm: 'Muling buksan',
        complete: 'Markahang nabasa',
        ambo: 'Mga kontrol sa pagbasa',
        contrast: 'Mataas na contrast',
        textSize: 'Laki ng teksto',
        lateTitle: 'Beripikado ngunit hindi naisama',
        lateFinalized: 'Na-verify ang mga intensyong ito matapos i-finalize ang listahan. Muling buksan ito at magbigay ng dahilan upang maisama sa bagong bersyon.',
        lateCompleted: 'Tapos na ang listahang ito. Kailangan ng hiwalay na awtorisadong pagsusuri sa mga intensyong ito.',
        lateDraft: 'Nakabinbing maidagdag sa listahan ang mga beripikadong intensyong ito.',
        confirmFinalize: 'I-finalize at i-freeze ang kasalukuyang bersyon ng listahan?',
        reviewFinalize: 'Suriin bago i-finalize',
        finalizeSummary: (count, version) => `${count} intensyon ang isasama sa hindi na mababagong bersyon ${version}.`,
        waitingSummary: (count) => `${count} beripikadong intensyon na naghihintay maidagdag ang hindi maisasama.`,
        freezeWarning: 'Pagkatapos i-finalize, hindi na maaayos o mababago ang listahan maliban kung muli itong bubuksan na may dahilan.',
        cancel: 'Kanselahin',
        everyEntry: 'Dapat markahang nabasa ang bawat kasamang intensyon bago makumpleto ang listahan.',
        category: 'Kategorya',
        downloadPdf: 'I-download ang PDF ng listahan',
        offlineError: 'Read-only ang listahan habang walang koneksyon.',
        pronunciation: 'Pagbigkas',
        reportIssue: 'Mag-ulat ng isyu',
        issueNote: 'Pribadong tala para sa awtorisadong kawani ng simbahan',
        submitIssue: 'Ipadala ang ulat',
        cancelIssue: 'Kanselahin',
        underReview: 'Sinusuri',
        returnPending: 'Ibalik sa listahan ng babasahin',
        excludeEntry: 'Alisin sa listahang ito',
        reopenToResolve: 'Muling buksan ang listahan at magbigay ng dahilan bago lutasin ang isyu.',
        excluded: 'Inalis sa listahan ng pagbasa',
        correctCompletion: 'Itama ang pagkakakumpleto',
        correctionTitle: 'Itama ang tala ng pagkakabasa',
        correctionReason: 'Dahilan ng pagwawasto sa pagkakabasa',
        correctionWarning: 'Ibabalik ang intensyon sa listahan ng hindi pa nababasa. Mananatili sa audit history ang orihinal na tala.',
        correctionConfirm: 'Itama ang tala',
    },
    ceb: {
        title: 'Listahan sa mga intensyon',
        noList: 'Wala pay nabayran ug napamatud-ang intensyon alang niining Misa.',
        version: 'Bersyon',
        preparing: 'Giandam',
        reopened: 'Giablihan pag-usab',
        finalized: 'Pinal',
        completed: 'Nahuman',
        entries: 'Mga intensyon',
        empty: 'Walay kumpirmadong intensyon niining listahana karon.',
        reorder: 'I-save ang han-ay',
        moveUp: 'Itaas',
        moveDown: 'Ipaubos',
        finalize: 'I-finalize ang listahan',
        reopen: 'Ablihi pag-usab ang listahan',
        reopenTitle: 'Ablihi pag-usab ang listahan',
        reopenReason: 'Rason sa pag-abli pag-usab sa listahan',
        reopenWarning: 'Ang pag-abli pag-usab motugot sa mga kausaban ug itala kini nga rason sa audit history.',
        reopenConfirm: 'Ablihi pag-usab',
        complete: 'Markahi nga nabasa',
        ambo: 'Mga kontrol sa pagbasa',
        contrast: 'Mas taas nga contrast',
        textSize: 'Gidak-on sa teksto',
        lateTitle: 'Napamatud-an apan wala maapil',
        lateFinalized: 'Napamatud-an kini nga mga intensyon human ma-finalize ang listahan. Ablihi kini pag-usab uban ang rason aron maapil sa bag-ong bersyon.',
        lateCompleted: 'Nahuman na kini nga listahan. Kinahanglan ug lahi nga awtorisadong pagrepaso niining mga intensyon.',
        lateDraft: 'Naghulat kining napamatud-ang mga intensyon nga idugang sa listahan.',
        confirmFinalize: 'I-finalize ug i-freeze ang kasamtangang bersyon sa listahan?',
        reviewFinalize: 'Susiha sa dili pa i-finalize',
        finalizeSummary: (count, version) => `${count} ka intensyon ang i-freeze sa bersyon ${version}.`,
        waitingSummary: (count) => `${count} ka napamatud-ang intensyon nga naghulat idugang dili maapil.`,
        freezeWarning: 'Human i-finalize, dili na mausab ang han-ay o sulod sa listahan gawas kon ablihan kini pag-usab uban ang rason.',
        cancel: 'Kanselahon',
        everyEntry: 'Kinahanglang markahan nga nabasa ang tanang naapil nga intensyon sa dili pa mahuman ang listahan.',
        category: 'Kategorya',
        downloadPdf: 'I-download ang PDF sa listahan',
        offlineError: 'Read-only ang listahan kon walay koneksyon.',
        pronunciation: 'Paglitok',
        reportIssue: 'I-report ang isyu',
        issueNote: 'Pribadong sulat alang sa awtorisadong kawani sa simbahan',
        submitIssue: 'Ipadala ang report',
        cancelIssue: 'Kanselahon',
        underReview: 'Girepaso',
        returnPending: 'Ibalik sa listahan sa basahon',
        excludeEntry: 'Kuhaa gikan niining listahana',
        reopenToResolve: 'Ablihi pag-usab ang listahan uban ang rason sa dili pa sulbaron ang isyu.',
        excluded: 'Wala naapil sa listahan sa pagbasa',
        correctCompletion: 'Itul-id ang pagkahuman',
        correctionTitle: 'Itul-id ang rekord sa pagbasa',
        correctionReason: 'Rason sa pagtul-id sa rekord sa pagbasa',
        correctionWarning: 'Ibalik ang intensyon sa wala pa mabasang listahan. Magpabilin sa audit history ang orihinal nga rekord.',
        correctionConfirm: 'Itul-id ang rekord',
    },
};

const reportIssue = (event) => {
    event.preventDefault();
    if (!reportingEntryId || !issueNote.trim()) {
        return;
    }

    post(
        'lector.masses.reading-list.entries.report-issue',
        { private_note: issueNote },
        { entry: reportingEntryId },
        () => {
            setReportingEntryId(null);
            setIssueNote('');
        }
    );
};

const resolveIssue = (entry, resolution) => {
    if (!entry.issueId) {
        return;
    }

    post(
        'lector.masses.reading-list.entries.issues.resolve',
        { resolution },
        { entry: entry.id, issue: entry.issueId }
    );
};

export default function ReadingListWorkbench({
    church,
    mass,
    readingList,
    pendingIntentions,
    canManageReadingList,
    isOnline,
    markSynced,
}) {
    const { locale = 'en' } = usePage().props;
    const labels = translations[locale] ?? translations.en;
    const [fontSize, setFontSize] = useState(24);
    const [highContrast, setHighContrast] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [actionError, setActionError] = useState('');
    const [showingFinalizationReview, setShowingFinalizationReview] = useState(false);
    const [showingReopenReview, setShowingReopenReview] = useState(false);
    const [reopenReason, setReopenReason] = useState('');
    const [correctingEntryId, setCorrectingEntryId] = useState(null);
    const [completionCorrectionReason, setCompletionCorrectionReason] = useState('');
    const finalizeButtonRef = useRef(null);
    const reopenButtonRef = useRef(null);
    const cancelFinalizationRef = useRef(null);
    const confirmFinalizationRef = useRef(null);
    const reopenReasonRef = useRef(null);
    const reopenConfirmRef = useRef(null);
    const completionCorrectionButtonRef = useRef(null);
    const completionCorrectionReasonRef = useRef(null);
    const completionCorrectionConfirmRef = useRef(null);
    const draggedEntryIndexRef = useRef(null);
    const [reportingEntryId, setReportingEntryId] = useState(null);
    const [issueNote, setIssueNote] = useState('');
    const eligibleEntries = (readingList?.entries ?? []).filter((entry) => entry.status !== 'excluded');
    const excludedEntries = (readingList?.entries ?? []).filter((entry) => entry.status === 'excluded');

    const post = (name, data = {}, routeParams = {}, onSuccess = null) => {
        setActionError('');
        if (!isOnline) {
            setActionError(labels.offlineError);
            return;
        }

        router.post(
            route(name, { church: church.id, mass: mass.id, ...routeParams }),
            data,
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => {
                    markSynced();
                    onSuccess?.();
                },
                onError: (errors) => setActionError(Object.values(errors).join(' ')),
            }
        );
    };

    const reorderTo = (entryIndex, targetIndex) => {
        if (
            !isOnline
            || !readingList
            || entryIndex < 0
            || targetIndex < 0
            || entryIndex >= eligibleEntries.length
            || targetIndex >= eligibleEntries.length
            || entryIndex === targetIndex
        ) {
            return;
        }

        const entryIds = eligibleEntries.map((entry) => entry.id);
        const [movedEntry] = entryIds.splice(entryIndex, 1);
        entryIds.splice(targetIndex, 0, movedEntry);
        post('lector.masses.reading-list.reorder', { entry_ids: entryIds });
    };

    const reorder = (entryIndex, direction) => reorderTo(entryIndex, entryIndex + direction);

    const handleEntryDrop = (event, targetIndex) => {
        event.preventDefault();
        if (draggedEntryIndexRef.current !== null) {
            reorderTo(draggedEntryIndexRef.current, targetIndex);
        }
        draggedEntryIndexRef.current = null;
    };

    const closeFinalizationReview = () => {
        setShowingFinalizationReview(false);
        finalizeButtonRef.current?.focus();
    };

    const finalize = () => {
        setShowingFinalizationReview(false);
        post('lector.masses.reading-list.finalize');
    };

    const handleFinalizationReviewKeyDown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeFinalizationReview();
        }

        if (event.key === 'Tab') {
            const first = cancelFinalizationRef.current;
            const last = confirmFinalizationRef.current;

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    };

    const closeReopenReview = (restoreFocus = true) => {
        setShowingReopenReview(false);
        setReopenReason('');
        if (restoreFocus) {
            reopenButtonRef.current?.focus();
        } else {
            document.getElementById('reading-list-title')?.focus();
        }
    };

    const handleReopenReviewKeyDown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeReopenReview();
        }

        if (event.key === 'Tab') {
            const first = reopenReasonRef.current;
            const last = reopenConfirmRef.current;

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    };

    const submitReopen = () => {
        if (!reopenReason.trim()) {
            return;
        }

        post(
            'lector.masses.reading-list.reopen',
            { reason: reopenReason },
            {},
            () => closeReopenReview(false)
        );
    };

    const closeCompletionCorrection = (restoreFocus = true) => {
        setCorrectingEntryId(null);
        setCompletionCorrectionReason('');
        if (restoreFocus) {
            completionCorrectionButtonRef.current?.focus();
        } else {
            document.getElementById('reading-list-title')?.focus();
        }
    };

    const handleCompletionCorrectionKeyDown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeCompletionCorrection();
        }

        if (event.key === 'Tab') {
            const first = completionCorrectionReasonRef.current;
            const last = completionCorrectionConfirmRef.current;

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    };

    const correctCompletion = () => {
        if (!correctingEntryId || !completionCorrectionReason.trim()) {
            return;
        }

        post(
            'lector.masses.reading-list.entries.correct-completion',
            { reason: completionCorrectionReason },
            { entry: correctingEntryId },
            () => closeCompletionCorrection(false)
        );
    };

    const statusLabels = {
        preparing: labels.preparing,
        reopened: labels.reopened,
        finalized: labels.finalized,
        completed: labels.completed,
    };
    const isEditable = ['preparing', 'reopened'].includes(readingList?.status);
    const panelClass = highContrast
        ? 'border-white bg-black text-white'
        : 'border-gold-300/80 bg-white text-stone-900';

    return (
        <section
            className={`rounded-xl border shadow-xs ${panelClass}`}
            aria-labelledby="reading-list-title"
            aria-busy={processing}
        >
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-current/10 p-5">
                <div>
                    <h2 id="reading-list-title" tabIndex="-1" className="font-cinzel text-lg font-bold">
                        {labels.title}
                    </h2>
                    {readingList && (
                        <p className="mt-1 text-xs opacity-75">
                            {statusLabels[readingList.status] ?? readingList.status}
                            {' · '}
                            {labels.version} {readingList.version}
                        </p>
                    )}
                </div>
                {readingList && canManageReadingList && isEditable && (
                    <button
                        type="button"
                        ref={finalizeButtonRef}
                        disabled={processing || !isOnline}
                        onClick={() => setShowingFinalizationReview(true)}
                        className="min-h-11 rounded-md bg-liturgical-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gold-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50"
                    >
                        {labels.finalize}
                    </button>
                )}
                {readingList?.status === 'finalized' && canManageReadingList && (
                    <button
                        type="button"
                        ref={reopenButtonRef}
                        disabled={processing || !isOnline}
                        onClick={() => setShowingReopenReview(true)}
                        className={`min-h-11 rounded-md border border-amber-500 px-4 py-2 text-xs font-semibold uppercase tracking-wide focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50 ${
                            highContrast ? 'text-amber-300' : 'text-amber-800'
                        }`}
                    >
                        {labels.reopen}
                    </button>
                )}
                {readingList && (
                    <a
                        href={route('lector.masses.reading-list.pdf', { church: church.id, mass: mass.id })}
                        target="_blank"
                        rel="noreferrer"
                        className={`inline-flex min-h-11 items-center rounded-md border px-4 py-2 text-xs font-semibold uppercase tracking-wide focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 ${
                            highContrast
                                ? 'border-stone-500 text-white hover:bg-stone-800'
                                : 'border-stone-300 text-stone-800 hover:bg-stone-50'
                        }`}
                    >
                        {labels.downloadPdf}
                    </a>
                )}
            </div>

            {actionError && (
                <p role="alert" className="m-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800">
                    {actionError}
                </p>
            )}

            {showingFinalizationReview && readingList && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/60 p-4">
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="finalization-review-title"
                        aria-describedby="finalization-review-description"
                        onKeyDown={handleFinalizationReviewKeyDown}
                        className="w-full max-w-lg rounded-xl border border-gold-300 bg-white p-6 text-stone-900 shadow-xl"
                    >
                        <h3 id="finalization-review-title" className="font-cinzel text-lg font-bold text-liturgical-950">
                            {labels.reviewFinalize}
                        </h3>
                        <p id="finalization-review-description" className="mt-3 text-sm">
                            {labels.finalizeSummary(
                                eligibleEntries.length,
                                readingList.version + (readingList.status === 'reopened' ? 1 : 0)
                            )}
                        </p>
                        {pendingIntentions.length > 0 && (
                            <p className="mt-2 text-sm text-amber-900">
                                {labels.waitingSummary(pendingIntentions.length)}
                            </p>
                        )}
                        <p className="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-950">
                            {labels.freezeWarning}
                        </p>
                        <div className="mt-6 flex flex-col-reverse justify-end gap-3 sm:flex-row">
                            <button
                                type="button"
                                ref={cancelFinalizationRef}
                                autoFocus
                                onClick={closeFinalizationReview}
                                className="min-h-11 rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                            >
                                {labels.cancel}
                            </button>
                            <button
                                type="button"
                                ref={confirmFinalizationRef}
                                disabled={processing || !isOnline}
                                onClick={finalize}
                                className="min-h-11 rounded-md bg-liturgical-900 px-4 py-2 text-sm font-semibold text-gold-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50"
                            >
                                {labels.confirmFinalize}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {showingReopenReview && readingList?.status === 'finalized' && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/60 p-4">
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="reopen-review-title"
                        aria-describedby="reopen-review-warning"
                        onKeyDown={handleReopenReviewKeyDown}
                        className="w-full max-w-lg rounded-xl border border-amber-300 bg-white p-6 text-stone-900 shadow-xl"
                    >
                        <h3 id="reopen-review-title" className="font-cinzel text-lg font-bold text-liturgical-950">
                            {labels.reopenTitle}
                        </h3>
                        <p id="reopen-review-warning" className="mt-3 text-sm text-amber-950">
                            {labels.reopenWarning}
                        </p>
                        <label htmlFor="reopen-reason" className="mt-4 block text-sm font-semibold text-stone-800">
                            {labels.reopenReason}
                        </label>
                        <textarea
                            id="reopen-reason"
                            ref={reopenReasonRef}
                            autoFocus
                            required
                            maxLength={2000}
                            value={reopenReason}
                            onChange={(event) => setReopenReason(event.target.value)}
                            className="mt-2 min-h-28 w-full rounded-md border border-stone-300 p-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                        />
                        <div className="mt-5 flex flex-col-reverse justify-end gap-3 sm:flex-row">
                            <button
                                type="button"
                                onClick={() => closeReopenReview()}
                                className="min-h-11 rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                            >
                                {labels.cancel}
                            </button>
                            <button
                                type="button"
                                ref={reopenConfirmRef}
                                disabled={processing || !isOnline || !reopenReason.trim()}
                                onClick={submitReopen}
                                className="min-h-11 rounded-md bg-amber-800 px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50"
                            >
                                {labels.reopenConfirm}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {!readingList ? (
                <p className="p-5 text-sm opacity-75">{labels.noList}</p>
            ) : (
                <>
                    <div className="flex flex-wrap items-center gap-5 border-b border-current/10 p-4 text-sm">
                        <strong>{labels.ambo}</strong>
                        <label className="flex min-h-11 cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                checked={highContrast}
                                onChange={(event) => setHighContrast(event.target.checked)}
                                className="h-5 w-5 accent-liturgical-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                            />
                            {labels.contrast}
                        </label>
                        <label className="flex min-h-11 cursor-pointer items-center gap-2">
                            {labels.textSize}
                            <input
                                type="range"
                                min="18"
                                max="40"
                                step="2"
                                value={fontSize}
                                onChange={(event) => setFontSize(Number(event.target.value))}
                                aria-label={labels.textSize}
                                className="min-h-11 w-36 accent-liturgical-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                            />
                        </label>
                    </div>

                    <ol className="divide-y divide-current/10">
                        {eligibleEntries.length === 0 ? (
                            <li className="p-5 text-sm opacity-75">{labels.empty}</li>
                        ) : eligibleEntries.map((entry, index) => (
                            <li
                                key={entry.id}
                                draggable={canManageReadingList && isEditable && isOnline && !processing}
                                onDragStart={() => {
                                    draggedEntryIndexRef.current = index;
                                }}
                                onDragOver={(event) => event.preventDefault()}
                                onDrop={(event) => handleEntryDrop(event, index)}
                                onDragEnd={() => {
                                    draggedEntryIndexRef.current = null;
                                }}
                                className={`flex flex-col gap-3 p-4 sm:flex-row sm:gap-4 sm:p-5 ${
                                    canManageReadingList && isEditable ? 'cursor-move' : 'cursor-default'
                                }`}
                            >
                                <span className="pt-1 text-sm font-bold opacity-60">{index + 1}.</span>
                                <div className="min-w-0 flex-1">
                                    <p className="text-xs font-semibold uppercase tracking-wide opacity-70">
                                        {entry.category || labels.category}
                                    </p>
                                    <p className="mt-1 font-bold" style={{ fontSize: `${fontSize}px` }}>
                                        {entry.name}
                                    </p>
                                    {entry.pronunciationHint && (
                                        <p className="mt-1 text-sm font-medium text-amber-800">
                                            {labels.pronunciation}: {entry.pronunciationHint}
                                        </p>
                                    )}
                                    <p className="mt-1 leading-relaxed" style={{ fontSize: `${fontSize}px` }}>
                                        {entry.wording}
                                    </p>
                                    {entry.status === 'issue_reported' && (
                                        <div className="mt-3 rounded-md border border-amber-400 bg-amber-50 p-3 text-sm text-amber-950">
                                            <p className="font-semibold">{labels.underReview}</p>
                                            {entry.issueNote && <p className="mt-1 whitespace-pre-wrap">{entry.issueNote}</p>}
                                            {isEditable ? (
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        disabled={processing || !isOnline}
                                                        onClick={() => resolveIssue(entry, 'pending')}
                                                        className="min-h-11 rounded border border-amber-800 px-3 py-2 text-xs font-semibold disabled:opacity-50"
                                                    >
                                                        {labels.returnPending}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        disabled={processing || !isOnline}
                                                        onClick={() => resolveIssue(entry, 'excluded')}
                                                        className="min-h-11 rounded border border-red-700 px-3 py-2 text-xs font-semibold text-red-800 disabled:opacity-50"
                                                    >
                                                        {labels.excludeEntry}
                                                    </button>
                                                </div>
                                            ) : (
                                                <p className="mt-2">{labels.reopenToResolve}</p>
                                            )}
                                        </div>
                                    )}
                                    {entry.status === 'pending' && readingList.status !== 'completed' && (
                                        <div className="mt-3">
                                            {reportingEntryId === entry.id ? (
                                                <form onSubmit={reportIssue} className="space-y-2 rounded-md border border-amber-300 bg-amber-50 p-3">
                                                    <label htmlFor={`issue-note-${entry.id}`} className="block text-xs font-semibold">
                                                        {labels.issueNote}
                                                    </label>
                                                    <textarea
                                                        id={`issue-note-${entry.id}`}
                                                        value={issueNote}
                                                        onChange={(event) => setIssueNote(event.target.value)}
                                                        maxLength={2000}
                                                        required
                                                        disabled={processing || !isOnline}
                                                        className="min-h-24 w-full rounded border-stone-300 text-sm focus:border-amber-700 focus:ring-amber-700 disabled:opacity-60"
                                                    />
                                                    <div className="flex flex-wrap gap-2">
                                                        <button
                                                            type="submit"
                                                            disabled={processing || !isOnline || !issueNote.trim()}
                                                            className="min-h-11 rounded bg-amber-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"
                                                        >
                                                            {labels.submitIssue}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                setReportingEntryId(null);
                                                                setIssueNote('');
                                                            }}
                                                            className="min-h-11 rounded border border-current/30 px-3 py-2 text-xs font-semibold"
                                                        >
                                                            {labels.cancelIssue}
                                                        </button>
                                                    </div>
                                                </form>
                                            ) : (
                                                <button
                                                    type="button"
                                                    disabled={processing || !isOnline}
                                                    onClick={() => {
                                                        setReportingEntryId(entry.id);
                                                        setIssueNote('');
                                                    }}
                                                    className="min-h-11 rounded border border-amber-700 px-3 py-2 text-xs font-semibold text-amber-900 disabled:opacity-50"
                                                >
                                                    {labels.reportIssue}
                                                </button>
                                            )}
                                        </div>
                                    )}
                                    {entry.status === 'completed' && (
                                        <p
                                            className={`mt-2 text-sm font-semibold ${
                                                highContrast ? 'text-emerald-300' : 'text-emerald-700'
                                            }`}
                                            role="status"
                                            aria-live="polite"
                                        >
                                            {labels.completed}
                                        </p>
                                    )}
                                </div>
                                {canManageReadingList && isEditable && (
                                    <div className="flex shrink-0 gap-2 sm:flex-col">
                                        <button
                                            type="button"
                                            aria-label={labels.moveUp}
                                            disabled={processing || !isOnline || index === 0}
                                            onClick={() => reorder(index, -1)}
                                            className="min-h-11 min-w-11 rounded border border-current/20 px-2 py-1 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-30"
                                        >
                                            ↑
                                        </button>
                                        <button
                                            type="button"
                                            aria-label={labels.moveDown}
                                            disabled={processing || !isOnline || index === eligibleEntries.length - 1}
                                            onClick={() => reorder(index, 1)}
                                            className="min-h-11 min-w-11 rounded border border-current/20 px-2 py-1 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-30"
                                        >
                                            ↓
                                        </button>
                                    </div>
                                )}
                                {canManageReadingList && readingList.status === 'finalized' && entry.status === 'pending' && (
                                    <button
                                        type="button"
                                        disabled={processing || !isOnline}
                                        onClick={() => post('lector.masses.reading-list.entries.complete', {}, { entry: entry.id })}
                                        className="min-h-11 w-full rounded-md bg-emerald-700 px-3 py-2 text-xs font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50 sm:w-auto"
                                    >
                                        {labels.complete}
                                    </button>
                                )}
                                {canManageReadingList && readingList.status === 'finalized' && entry.status === 'completed' && (
                                    <button
                                        type="button"
                                        disabled={processing || !isOnline}
                                        onClick={(event) => {
                                            completionCorrectionButtonRef.current = event.currentTarget;
                                            setCorrectingEntryId(entry.id);
                                        }}
                                        className="min-h-11 w-full rounded-md border border-amber-500 px-3 py-2 text-xs font-semibold text-amber-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50 sm:w-auto"
                                    >
                                        {labels.correctCompletion}
                                    </button>
                                )}
                            </li>
                        ))}
                    </ol>
                    {excludedEntries.length > 0 && (
                        <div className="border-t border-current/10 px-5 py-4">
                            <h3 className="text-sm font-semibold">{labels.excluded}</h3>
                            <ul className="mt-2 list-inside list-disc text-sm opacity-75">
                                {excludedEntries.map((entry) => <li key={entry.id}>{entry.name}</li>)}
                            </ul>
                        </div>
                    )}
                    {readingList.status === 'finalized' && (
                        <p className="border-t border-current/10 px-5 py-3 text-sm opacity-75">
                            {labels.everyEntry}
                        </p>
                    )}
                </>
            )}

            {pendingIntentions.length > 0 && (
                <div className="border-t border-amber-300 bg-amber-50 p-5 text-amber-950">
                    <h3 className="font-semibold">{labels.lateTitle}</h3>
                    <p className="mt-1 text-sm">
                        {readingList?.status === 'completed'
                            ? labels.lateCompleted
                            : readingList?.status === 'finalized'
                                ? labels.lateFinalized
                                : labels.lateDraft}
                    </p>
                    <ul className="mt-3 list-disc space-y-2 pl-5 text-sm">
                        {pendingIntentions.map((intention) => (
                            <li key={intention.id}>
                                <span className="font-semibold">{intention.name}</span>
                                <span>: {intention.wording}</span>
                            </li>
                        ))}
                    </ul>
                    {readingList?.status === 'finalized' && canManageReadingList && (
                        <p className="mt-3 text-sm font-semibold">
                            {labels.reopen} {labels.reopenWarning}
                        </p>
                    )}
                </div>
            )}

            {correctingEntryId && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/60 p-4">
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="completion-correction-title"
                        aria-describedby="completion-correction-warning"
                        onKeyDown={handleCompletionCorrectionKeyDown}
                        className="w-full max-w-lg rounded-xl border border-amber-300 bg-white p-6 text-stone-900 shadow-xl"
                    >
                        <h3 id="completion-correction-title" className="font-cinzel text-lg font-bold text-liturgical-950">
                            {labels.correctionTitle}
                        </h3>
                        <p id="completion-correction-warning" className="mt-3 text-sm text-amber-950">
                            {labels.correctionWarning}
                        </p>
                        <label htmlFor="completion-correction-reason" className="mt-4 block text-sm font-semibold text-stone-800">
                            {labels.correctionReason}
                        </label>
                        <textarea
                            id="completion-correction-reason"
                            ref={completionCorrectionReasonRef}
                            autoFocus
                            required
                            maxLength={2000}
                            value={completionCorrectionReason}
                            onChange={(event) => setCompletionCorrectionReason(event.target.value)}
                            className="mt-2 min-h-28 w-full rounded-md border border-stone-300 p-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                        />
                        <div className="mt-5 flex flex-col-reverse justify-end gap-3 sm:flex-row">
                            <button
                                type="button"
                                onClick={() => closeCompletionCorrection()}
                                className="min-h-11 rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                            >
                                {labels.cancel}
                            </button>
                            <button
                                type="button"
                                ref={completionCorrectionConfirmRef}
                                disabled={processing || !isOnline || !completionCorrectionReason.trim()}
                                onClick={correctCompletion}
                                className="min-h-11 rounded-md bg-amber-800 px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 disabled:opacity-50"
                            >
                                {labels.correctionConfirm}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}
