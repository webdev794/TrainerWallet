import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, CreditCard } from 'lucide-react';
import { type FormEvent } from 'react';

import { AppLogo } from '@/components/app-logo';
import { InputError } from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { formatDate, formatMoney } from '@/lib/format';
import * as publicInvoice from '@/routes/public-invoice';

type PublicInvoice = {
    token: string;
    number: string;
    business_name: string;
    status: string;
    status_label: string;
    currency: string;
    total: string;
    amount_paid: string;
    outstanding: number;
    due_date: string | null;
    is_payable: boolean;
    client_name: string;
    notes: string | null;
    items: {
        description: string;
        quantity: string;
        unit_amount: string;
        amount: string;
    }[];
    methods: string[];
    upi_intent: string | null;
    upi_qr: string | null;
    payments: {
        gateway_label: string;
        amount: string;
        status_label: string;
        paid_at: string | null;
    }[];
};

function PayButton({
    token,
    gateway,
    label,
}: {
    token: string;
    gateway: string;
    label: string;
}) {
    return (
        <form method="post" action={publicInvoice.pay([token, gateway]).url}>
            <input
                type="hidden"
                name="_token"
                value={
                    (
                        document.querySelector(
                            'meta[name=csrf-token]',
                        ) as HTMLMetaElement | null
                    )?.content ?? ''
                }
            />
            <Button type="submit" className="w-full">
                <CreditCard className="size-4" /> Pay with {label}
            </Button>
        </form>
    );
}

function UpiPay({ invoice }: { invoice: PublicInvoice }) {
    const form = useForm({ reference: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(publicInvoice.upi(invoice.token).url, {
            onSuccess: () => form.reset(),
        });
    };

    return (
        <div className="space-y-3 rounded-md border p-4">
            <p className="text-sm font-medium">Pay by UPI</p>
            {invoice.upi_qr && (
                <img
                    src={invoice.upi_qr}
                    alt="UPI QR code"
                    className="mx-auto size-44"
                />
            )}
            {invoice.upi_intent && (
                <a
                    href={invoice.upi_intent}
                    className="text-primary block text-center text-sm underline"
                >
                    Open in a UPI app
                </a>
            )}
            <form onSubmit={submit} className="space-y-2">
                <Input
                    placeholder="Enter your UPI reference / UTR"
                    value={form.data.reference}
                    onChange={(e) => form.setData('reference', e.target.value)}
                />
                <InputError message={form.errors.reference} />
                <Button
                    type="submit"
                    variant="outline"
                    className="w-full"
                    disabled={form.processing}
                >
                    I&rsquo;ve paid — submit reference
                </Button>
            </form>
        </div>
    );
}

export default function PublicInvoicePage({
    invoice,
}: {
    invoice: PublicInvoice;
    context: 'public' | 'portal';
}) {
    useFlashToast();
    const { flash } = usePage<{
        flash: { success?: string | null; error?: string | null };
    }>().props;

    return (
        <div className="bg-muted/30 min-h-screen py-10">
            <Head title={`Invoice ${invoice.number}`} />

            <div className="mx-auto w-full max-w-2xl space-y-6 px-4">
                <div className="flex items-center justify-between">
                    <AppLogo />
                    <Badge
                        variant={
                            invoice.status === 'paid' ? 'success' : 'secondary'
                        }
                    >
                        {invoice.status_label}
                    </Badge>
                </div>

                {flash?.success && (
                    <div className="bg-success/10 text-success flex items-center gap-2 rounded-md p-3 text-sm">
                        <CheckCircle2 className="size-4" /> {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="bg-destructive/10 text-destructive rounded-md p-3 text-sm">
                        {flash.error}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {invoice.business_name} · {invoice.number}
                        </CardTitle>
                        <p className="text-muted-foreground text-sm">
                            Billed to {invoice.client_name}
                            {invoice.due_date
                                ? ` · due ${formatDate(invoice.due_date)}`
                                : ''}
                        </p>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-1 text-sm">
                            {invoice.items.map((item, index) => (
                                <div
                                    key={index}
                                    className="flex justify-between"
                                >
                                    <span>
                                        {item.description}
                                        <span className="text-muted-foreground">
                                            {' '}
                                            × {item.quantity}
                                        </span>
                                    </span>
                                    <span>
                                        {formatMoney(
                                            item.amount,
                                            invoice.currency,
                                        )}
                                    </span>
                                </div>
                            ))}
                        </div>
                        <div className="flex justify-between border-t pt-3 text-lg font-semibold">
                            <span>Amount due</span>
                            <span>
                                {formatMoney(
                                    invoice.outstanding,
                                    invoice.currency,
                                )}
                            </span>
                        </div>
                        {parseFloat(invoice.amount_paid) > 0 && (
                            <p className="text-muted-foreground text-xs">
                                {formatMoney(
                                    invoice.amount_paid,
                                    invoice.currency,
                                )}{' '}
                                already paid of{' '}
                                {formatMoney(invoice.total, invoice.currency)}
                            </p>
                        )}
                        {invoice.notes && (
                            <p className="text-muted-foreground text-sm">
                                {invoice.notes}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {invoice.is_payable ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pay this invoice</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {invoice.methods.includes('stripe') && (
                                <PayButton
                                    token={invoice.token}
                                    gateway="stripe"
                                    label="card"
                                />
                            )}
                            {invoice.methods.includes('paypal') && (
                                <PayButton
                                    token={invoice.token}
                                    gateway="paypal"
                                    label="PayPal"
                                />
                            )}
                            {invoice.methods.includes('upi_manual') && (
                                <UpiPay invoice={invoice} />
                            )}
                            {invoice.methods.length === 0 && (
                                <p className="text-muted-foreground text-sm">
                                    No online payment method is set up yet.
                                    Please contact {invoice.business_name}.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="text-muted-foreground py-8 text-center text-sm">
                            {invoice.status === 'paid'
                                ? 'This invoice is fully paid. Thank you!'
                                : 'This invoice is not open for payment.'}
                        </CardContent>
                    </Card>
                )}

                {invoice.payments.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Payment history</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {invoice.payments.map((payment, index) => (
                                <div
                                    key={index}
                                    className="flex justify-between"
                                >
                                    <span className="text-muted-foreground">
                                        {payment.gateway_label} ·{' '}
                                        {payment.status_label}
                                    </span>
                                    <span>
                                        {formatMoney(
                                            payment.amount,
                                            invoice.currency,
                                        )}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>

            <Toaster />
        </div>
    );
}
