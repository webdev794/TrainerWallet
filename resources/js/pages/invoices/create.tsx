import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEvent, useMemo } from 'react';

import { InputError } from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatMoney } from '@/lib/format';
import * as invoices from '@/routes/invoices';

type LineItem = {
    description: string;
    quantity: string;
    unit_amount: string;
    training_session_id: number | null;
};

type ClientOption = {
    id: number;
    name: string;
    email: string | null;
    default_rate: string | null;
};
type PackageOption = { id: number; name: string; amount: string; type: string };
type SessionOption = {
    id: number;
    client_id: number;
    client_name: string;
    scheduled_at: string;
    rate: string;
};
type MethodOption = { value: string; label: string };

type InvoiceData = {
    id: number;
    client_id: number;
    discount_total: string;
    tax_rate: string;
    due_date: string | null;
    notes: string | null;
    allowed_methods: string[];
    items: {
        description: string;
        quantity: string;
        unit_amount: string;
        training_session_id: number | null;
    }[];
};

type Props = {
    clients: ClientOption[];
    packages: PackageOption[];
    unbilledSessions: SessionOption[];
    currency: string;
    allowedMethodOptions: MethodOption[];
    invoice?: InvoiceData;
};

export default function InvoiceBuilder({
    clients,
    packages,
    unbilledSessions,
    currency,
    allowedMethodOptions,
    invoice,
}: Props) {
    const editing = Boolean(invoice);

    const form = useForm<{
        client_id: string;
        due_date: string;
        notes: string;
        discount_total: string;
        tax_rate: string;
        allowed_methods: string[];
        items: LineItem[];
    }>({
        client_id: invoice ? String(invoice.client_id) : '',
        due_date: invoice?.due_date ?? '',
        notes: invoice?.notes ?? '',
        discount_total: invoice?.discount_total ?? '0',
        tax_rate: invoice?.tax_rate ?? '0',
        allowed_methods: invoice?.allowed_methods ?? ['upi_manual'],
        items: invoice?.items?.length
            ? invoice.items.map((item) => ({
                  description: item.description,
                  quantity: String(item.quantity),
                  unit_amount: String(item.unit_amount),
                  training_session_id: item.training_session_id,
              }))
            : [
                  {
                      description: '',
                      quantity: '1',
                      unit_amount: '',
                      training_session_id: null,
                  },
              ],
    });

    const sessionsForClient = useMemo(
        () =>
            unbilledSessions.filter(
                (s) => String(s.client_id) === form.data.client_id,
            ),
        [unbilledSessions, form.data.client_id],
    );

    const setItem = (index: number, patch: Partial<LineItem>) => {
        form.setData(
            'items',
            form.data.items.map((item, i) =>
                i === index ? { ...item, ...patch } : item,
            ),
        );
    };

    const addItem = () =>
        form.setData('items', [
            ...form.data.items,
            {
                description: '',
                quantity: '1',
                unit_amount: '',
                training_session_id: null,
            },
        ]);

    const removeItem = (index: number) =>
        form.setData(
            'items',
            form.data.items.filter((_, i) => i !== index),
        );

    const addPackage = (id: string) => {
        const pkg = packages.find((p) => String(p.id) === id);
        if (!pkg) return;
        form.setData('items', [
            ...form.data.items.filter(
                (item) => item.description || item.unit_amount,
            ),
            {
                description: pkg.name,
                quantity: '1',
                unit_amount: pkg.amount,
                training_session_id: null,
            },
        ]);
    };

    const addSession = (session: SessionOption) => {
        if (
            form.data.items.some(
                (item) => item.training_session_id === session.id,
            )
        )
            return;
        form.setData('items', [
            ...form.data.items.filter(
                (item) => item.description || item.unit_amount,
            ),
            {
                description: `Session — ${formatDateTime(session.scheduled_at)}`,
                quantity: '1',
                unit_amount: session.rate,
                training_session_id: session.id,
            },
        ]);
    };

    const subtotal = form.data.items.reduce(
        (sum, item) =>
            sum +
            (parseFloat(item.quantity || '0') || 0) *
                (parseFloat(item.unit_amount || '0') || 0),
        0,
    );
    const discount = parseFloat(form.data.discount_total || '0') || 0;
    const taxable = Math.max(subtotal - discount, 0);
    const tax = (taxable * (parseFloat(form.data.tax_rate || '0') || 0)) / 100;
    const total = taxable + tax;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (editing && invoice) {
            form.put(invoices.update(invoice.id).url);
        } else {
            form.post(invoices.store().url);
        }
    };

    return (
        <AppLayout title={editing ? 'Edit invoice' : 'New invoice'}>
            <Head title={editing ? 'Edit invoice' : 'New invoice'} />

            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="client_id">Client</Label>
                                <Select
                                    id="client_id"
                                    value={form.data.client_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'client_id',
                                            e.target.value,
                                        )
                                    }
                                    disabled={editing}
                                >
                                    <option value="">Choose a client…</option>
                                    {clients.map((client) => (
                                        <option
                                            key={client.id}
                                            value={client.id}
                                        >
                                            {client.name}
                                        </option>
                                    ))}
                                </Select>
                                <InputError message={form.errors.client_id} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="due_date">Due date</Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    value={form.data.due_date}
                                    onChange={(e) =>
                                        form.setData('due_date', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.due_date} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between space-y-0">
                            <CardTitle>Line items</CardTitle>
                            <div className="flex gap-2">
                                {packages.length > 0 && (
                                    <Select
                                        className="h-9 w-40 text-xs"
                                        value=""
                                        onChange={(e) => {
                                            addPackage(e.target.value);
                                            e.target.value = '';
                                        }}
                                    >
                                        <option value="">Add package…</option>
                                        {packages.map((pkg) => (
                                            <option key={pkg.id} value={pkg.id}>
                                                {pkg.name} —{' '}
                                                {formatMoney(
                                                    pkg.amount,
                                                    currency,
                                                )}
                                            </option>
                                        ))}
                                    </Select>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={addItem}
                                >
                                    <Plus className="size-4" /> Row
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {form.data.items.map((item, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-12 items-end gap-2"
                                >
                                    <div className="col-span-6 space-y-1">
                                        {index === 0 && (
                                            <Label className="text-xs">
                                                Description
                                            </Label>
                                        )}
                                        <Input
                                            value={item.description}
                                            onChange={(e) =>
                                                setItem(index, {
                                                    description: e.target.value,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="col-span-2 space-y-1">
                                        {index === 0 && (
                                            <Label className="text-xs">
                                                Qty
                                            </Label>
                                        )}
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={item.quantity}
                                            onChange={(e) =>
                                                setItem(index, {
                                                    quantity: e.target.value,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="col-span-3 space-y-1">
                                        {index === 0 && (
                                            <Label className="text-xs">
                                                Unit
                                            </Label>
                                        )}
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={item.unit_amount}
                                            onChange={(e) =>
                                                setItem(index, {
                                                    unit_amount: e.target.value,
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="col-span-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => removeItem(index)}
                                            disabled={
                                                form.data.items.length === 1
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                            <InputError message={form.errors.items} />

                            {form.data.client_id &&
                                sessionsForClient.length > 0 && (
                                    <div className="border-t pt-3">
                                        <p className="text-muted-foreground mb-2 text-xs font-medium">
                                            Unbilled sessions for this client
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {sessionsForClient.map(
                                                (session) => (
                                                    <Button
                                                        key={session.id}
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            addSession(session)
                                                        }
                                                    >
                                                        <Plus className="size-3" />
                                                        {formatDateTime(
                                                            session.scheduled_at,
                                                        )}{' '}
                                                        ·{' '}
                                                        {formatMoney(
                                                            session.rate,
                                                            currency,
                                                        )}
                                                    </Button>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                                placeholder="Payment terms, thank-you note…"
                            />
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Subtotal
                                </span>
                                <span>{formatMoney(subtotal, currency)}</span>
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="discount_total">Discount</Label>
                                <Input
                                    id="discount_total"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.data.discount_total}
                                    onChange={(e) =>
                                        form.setData(
                                            'discount_total',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="tax_rate">Tax rate (%)</Label>
                                <Input
                                    id="tax_rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value={form.data.tax_rate}
                                    onChange={(e) =>
                                        form.setData('tax_rate', e.target.value)
                                    }
                                />
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Tax
                                </span>
                                <span>{formatMoney(tax, currency)}</span>
                            </div>
                            <div className="flex justify-between border-t pt-2 text-base font-semibold">
                                <span>Total</span>
                                <span>{formatMoney(total, currency)}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Payment methods</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {allowedMethodOptions.map((method) => (
                                <label
                                    key={method.value}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={form.data.allowed_methods.includes(
                                            method.value,
                                        )}
                                        onChange={(e) =>
                                            form.setData(
                                                'allowed_methods',
                                                e.target.checked
                                                    ? [
                                                          ...form.data
                                                              .allowed_methods,
                                                          method.value,
                                                      ]
                                                    : form.data.allowed_methods.filter(
                                                          (m) =>
                                                              m !==
                                                              method.value,
                                                      ),
                                            )
                                        }
                                    />
                                    {method.label}
                                </label>
                            ))}
                        </CardContent>
                    </Card>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={form.processing}
                    >
                        {editing ? 'Save invoice' : 'Create draft'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
