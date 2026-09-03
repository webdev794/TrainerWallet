export function formatMoney(
    amount: number | string | null | undefined,
    currency = 'INR',
): string {
    const value =
        typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
        }).format(Number.isFinite(value) ? value : 0);
    } catch {
        return `${currency} ${value}`;
    }
}

export function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export function formatDateTime(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
