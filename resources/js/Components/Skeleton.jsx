export default function Skeleton({ className = '', ...props }) {
    return (
        <div
            className={`animate-pulse rounded bg-slate-200/80 ${className}`}
            {...props}
        />
    );
}

Skeleton.Card = function SkeletonCard({ lines = 3, className = '' }) {
    return (
        <div className={`rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-4 ${className}`}>
            <div className="flex items-center justify-between">
                <Skeleton className="h-4 w-1/3" />
                <Skeleton className="h-6 w-16 rounded-full" />
            </div>
            <div className="space-y-2 pt-2">
                {Array.from({ length: lines }).map((_, i) => (
                    <Skeleton key={i} className={`h-3 ${i === lines - 1 ? 'w-2/3' : 'w-full'}`} />
                ))}
            </div>
        </div>
    );
};

Skeleton.Table = function SkeletonTable({ rows = 5, cols = 4, className = '' }) {
    return (
        <div className={`rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden ${className}`}>
            <div className="border-b border-slate-100 bg-slate-50 p-4">
                <Skeleton className="h-4 w-1/4" />
            </div>
            <div className="divide-y divide-slate-100 p-4 space-y-3">
                {Array.from({ length: rows }).map((_, r) => (
                    <div key={r} className="flex items-center space-x-4 pt-2">
                        {Array.from({ length: cols }).map((_, c) => (
                            <Skeleton key={c} className="h-3 flex-1" />
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
};

