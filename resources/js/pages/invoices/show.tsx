import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Copy,
    Download,
    Pencil,
    Send,
    Trash2,
    Wallet,
    XCircle,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { InputError } from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import * as invoices from '@/routes/invoices';
import * as invoicePayments from '@/routes/invoices/payments';
import * as payments from '@/routes/payments';

type Payment = {
    id: number;
    gateway: string;
    gateway_label: string;
    amount: string;
    status: string;
    status_label: string;
    reference: string | null;
    paid_at: string | null;
    created_at: string | null;
};

type Invoice = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    editable: boolean;
    client: { id: number; name: string; email: string | null };
    currency: string;
    subtotal: string;
    discount_total: string;
    tax_rate: string;
    tax_total: string;
    total: string;
    amount_paid: string;
    outstanding: number;
    due_date: string | null;
    issued_at: string | null;
    paid_at: string | null;
    notes: string | null;
    allowed_methods: string[];
    items: {
        id: number;
        description: string;
        quantity: string;
        unit_amount: string;
        amount: string;
    }[];
    payments: Payment[];
};

const statusVariant: Record<
    string,
    'secondary' | 'success' | 'warning' | 'outline' | 'destructive'
> = {
    draft: 'outline',
    sent: 'secondary',
    viewed: 'secondary',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'destructive',
    void: 'outline',
};

const paymentVariant: Record<string, 'secondary' | 'success' | 'destructive'> =
    {
        pending: 'secondary',
        succeeded: 'success',
        failed: 'destructive',
        refunded: 'destructive',
    };

function RecordPaymentDialog({
    invoice,
    open,
    onOpenChange,
}: {
    invoice: Invoice;
    open: boolean;
    onOpenChange: (v: boolean) => void;
}) {
    const form = useForm({
        method: 'cash',
        amount: String(invoice.outstanding),
        reference: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(invoicePayments.store(invoice.id).url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Record a payment</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="method">Method</Label>
                        <Select
                            id="method"
                            value={form.data.method}
                            onChange={(e) =>
                                form.setData('method', e.target.value)
                            }
                        >
                            <option value="cash">Cash</option>
                            <option value="upi_manual">UPI</option>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="amount">Amount</Label>
                        <Input
                            id="amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={form.data.amount}
                            onChange={(e) =>
                                form.setData('amount', e.target.value)
                            }
                        />
                        <InputError message={form.errors.amount} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="reference">Reference (optional)</Label>
                        <Input
                            id="reference"
                            value={form.data.reference}
                            onChange={(e) =>
                                form.setData('reference', e.target.value)
                            }
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Record payment
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function InvoiceShow({
    invoice,
    publicUrl,
}: {
    invoice: Invoice;
    publicUrl: string;
}) {
    const [payOpen, setPayOpen] = useState(false);
    const [copied, setCopied] = useState(false);

    const copyLink = () => {
        void navigator.clipboard?.writeText(publicUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
    };

    return (
        <AppLayout
            title={`Invoice ${invoice.number}`}
            actions={
                <div className="flex flex-wrap gap-2">
                    {invoice.editable && (
                        <Button asChild variant="outline" size="sm">
                            <Link href={invoices.edit(invoice.id).url}>
                                <Pencil className="size-4" /> Edit
                            </Link>
                        </Button>
                    )}
                    {invoice.status !== 'paid' && invoice.status !== 'void' && (
                        <Button
                            size="sm"
                            onClick={() =>
                                router.post(
                                    invoices.send(invoice.id).url,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <Send className="size-4" />{' '}
                            {invoice.status === 'draft' ? 'Send' : 'Resend'}
                        </Button>
                    )}
                    <Button asChild variant="outline" size="sm">
                        <a href={invoices.pdf(invoice.id).url} target="_blank">
                            <Download className="size-4" /> PDF
                        </a>
                    </Button>
                    {invoice.outstanding > 0 && invoice.status !== 'draft' && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setPayOpen(true)}
                        >
                            <Wallet className="size-4" /> Record payment
                        </Button>
                    )}
                </div>
            }
        >
            <Head title={`Invoice ${invoice.number}`} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader className="flex-row items-start justify-between space-y-0">
                            <div>
                                <CardTitle>{invoice.number}</CardTitle>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {invoice.client.name}
                                    {invoice.client.email
                                        ? ` · ${invoice.client.email}`
                                        : ''}
                                </p>
                            </div>
                            <Badge
                                variant={
                                    statusVariant[invoice.status] ?? 'secondary'
                                }
                            >
                                {invoice.status_label}
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Description</TableHead>
                                        <TableHead className="text-right">
                                            Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Unit
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoice.items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                {item.description}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {item.quantity}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatMoney(
                                                    item.unit_amount,
                                                    invoice.currency,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatMoney(
                                                    item.amount,
                                                    invoice.currency,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            <div className="mt-4 ml-auto w-full max-w-xs space-y-1 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <span>
                                        {formatMoney(
                                            invoice.subtotal,
                                            invoice.currency,
                                        )}
                                    </span>
                                </div>
                                {parseFloat(invoice.discount_total) > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">
                                            Discount
                                        </span>
                                        <span>
                                            -
                                            {formatMoney(
                                                invoice.discount_total,
                                                invoice.currency,
                                            )}
                                        </span>
                                    </div>
                                )}
                                {parseFloat(invoice.tax_total) > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">
                                            Tax ({invoice.tax_rate}%)
                                        </span>
                                        <span>
                                            {formatMoney(
                                                invoice.tax_total,
                                                invoice.currency,
                                            )}
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between border-t pt-1 font-semibold">
                                    <span>Total</span>
                                    <span>
                                        {formatMoney(
                                            invoice.total,
                                            invoice.currency,
                                        )}
                                    </span>
                                </div>
                                {parseFloat(invoice.amount_paid) > 0 && (
                                    <div className="text-success flex justify-between">
                                        <span>Paid</span>
                                        <span>
                                            -
                                            {formatMoney(
                                                invoice.amount_paid,
                                                invoice.currency,
                                            )}
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between border-t pt-1 font-semibold">
                                    <span>Balance</span>
                                    <span>
                                        {formatMoney(
                                            invoice.outstanding,
                                            invoice.currency,
                                        )}
                                    </span>
                                </div>
                            </div>

                            {invoice.notes && (
                                <p className="text-muted-foreground mt-4 text-sm">
                                    {invoice.notes}
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Payments</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {invoice.payments.length === 0 ? (
                                <p className="text-muted-foreground p-6 text-center text-sm">
                                    No payments recorded yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Method</TableHead>
                                            <TableHead>Amount</TableHead>
                                            <TableHead>When</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.payments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell>
                                                    {payment.gateway_label}
                                                    {payment.reference
                                                        ? ` · ${payment.reference}`
                                                        : ''}
                                                </TableCell>
                                                <TableCell>
                                                    {formatMoney(
                                                        payment.amount,
                                                        invoice.currency,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {payment.paid_at
                                                        ? formatDateTime(
                                                              payment.paid_at,
                                                          )
                                                        : payment.created_at
                                                          ? formatDateTime(
                                                                payment.created_at,
                                                            )
                                                          : '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            paymentVariant[
                                                                payment.status
                                                            ] ?? 'secondary'
                                                        }
                                                    >
                                                        {payment.status_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {payment.status ===
                                                        'pending' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.post(
                                                                    payments.confirm(
                                                                        payment.id,
                                                                    ).url,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            Confirm
                                                        </Button>
                                                    )}
                                                    {payment.status ===
                                                        'succeeded' && (
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                >
                                                                    Refund
                                                                </Button>
                                                            }
                                                            title="Refund this payment?"
                                                            description="The payer will be refunded through the original method where possible."
                                                            confirmLabel="Refund"
                                                            onConfirm={() =>
                                                                router.post(
                                                                    payments.refund(
                                                                        payment.id,
                                                                    ).url,
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        />
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Overview</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Issued
                                </span>
                                <span>
                                    {invoice.issued_at
                                        ? formatDate(invoice.issued_at)
                                        : 'Not sent'}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Due
                                </span>
                                <span>
                                    {invoice.due_date
                                        ? formatDate(invoice.due_date)
                                        : '—'}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Balance
                                </span>
                                <span className="font-semibold">
                                    {formatMoney(
                                        invoice.outstanding,
                                        invoice.currency,
                                    )}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Share</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex gap-2">
                                <Input readOnly value={publicUrl} />
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={copyLink}
                                >
                                    <Copy className="size-4" />
                                </Button>
                            </div>
                            {copied && (
                                <p className="text-success text-xs">
                                    Link copied
                                </p>
                            )}
                            <Button asChild variant="link" className="px-0">
                                <a href={publicUrl} target="_blank">
                                    Open payment page
                                </a>
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="flex gap-2">
                        {invoice.status !== 'paid' &&
                            invoice.status !== 'void' && (
                                <ConfirmDialog
                                    trigger={
                                        <Button
                                            variant="outline"
                                            className="flex-1"
                                        >
                                            <XCircle className="size-4" /> Void
                                        </Button>
                                    }
                                    title="Void this invoice?"
                                    description="It can no longer be paid, and any linked sessions are released."
                                    confirmLabel="Void"
                                    onConfirm={() =>
                                        router.post(
                                            invoices.voidMethod(invoice.id).url,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                />
                            )}
                        {invoice.editable && (
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="outline"
                                        className="flex-1"
                                    >
                                        <Trash2 className="size-4" /> Delete
                                    </Button>
                                }
                                title="Delete this draft?"
                                description="This cannot be undone."
                                confirmLabel="Delete"
                                onConfirm={() =>
                                    router.delete(
                                        invoices.destroy(invoice.id).url,
                                    )
                                }
                            />
                        )}
                    </div>
                </div>
            </div>

            <RecordPaymentDialog
                invoice={invoice}
                open={payOpen}
                onOpenChange={setPayOpen}
            />
        </AppLayout>
    );
}
