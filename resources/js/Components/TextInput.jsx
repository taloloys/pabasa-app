import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-xl border border-stone-300 bg-ivory-50/40 px-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 shadow-2xs transition duration-150 ease-in-out focus:border-gold-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-gold-400/30 disabled:bg-stone-100 disabled:text-stone-400 ' +
                className
            }
            ref={localRef}
        />
    );
});
