export default function Table({ children, className = '', containerClassName = '', ...props }) {
    return (
        <div className={`overflow-x-auto ${containerClassName}`}>
            <table className={`w-full text-left text-sm text-slate-600 ${className}`} {...props}>
                {children}
            </table>
        </div>
    );
}

Table.Header = function TableHeader({ children, className = '', ...props }) {
    return (
        <thead
            className={`border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider ${className}`}
            {...props}
        >
            {children}
        </thead>
    );
};

Table.Body = function TableBody({ children, className = '', ...props }) {
    return (
        <tbody className={`divide-y divide-slate-100 bg-white ${className}`} {...props}>
            {children}
        </tbody>
    );
};

Table.Row = function TableRow({ children, className = '', ...props }) {
    return (
        <tr className={`hover:bg-slate-50/60 transition-colors duration-150 ${className}`} {...props}>
            {children}
        </tr>
    );
};

Table.Head = function TableHead({ children, className = '', ...props }) {
    return (
        <th scope="col" className={`px-6 py-3.5 ${className}`} {...props}>
            {children}
        </th>
    );
};

Table.Cell = function TableCell({ children, className = '', ...props }) {
    return (
        <td className={`px-6 py-4 ${className}`} {...props}>
            {children}
        </td>
    );
};

Table.Empty = function TableEmpty({ colSpan = 5, message = 'No records found.', children, className = '' }) {
    return (
        <tr>
            <td colSpan={colSpan} className={`px-6 py-10 text-center text-slate-500 text-sm ${className}`}>
                {children || message}
            </td>
        </tr>
    );
};

